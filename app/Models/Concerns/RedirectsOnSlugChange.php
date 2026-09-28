<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use Throwable;

/**
 * When an editor changes the slug of a public entity, keep the old URL alive
 * with a 301 so SEO is not lost (requirement #4 from the brief).
 *
 * The model must implement publicPathForSlug(string $slug): string.
 */
trait RedirectsOnSlugChange
{
    public static function bootRedirectsOnSlugChange(): void
    {
        static::updated(function ($model): void {
            if (! $model->wasChanged('slug')) {
                return;
            }

            $oldSlug = (string) $model->getOriginal('slug');
            $newSlug = (string) $model->slug;
            if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
                return;
            }

            $from = $model->publicPathForSlug($oldSlug);
            $to = $model->publicPathForSlug($newSlug);
            if ($from === $to) {
                return;
            }

            try {
                // Avoid loops: if the new URL previously redirected to the old one, drop it.
                Redirect::query()->where('from_path', $to)->delete();

                Redirect::query()->updateOrCreate(
                    ['from_path' => $from],
                    ['to_path' => $to, 'status_code' => 301, 'is_active' => true]
                );

                // Re-point any redirects that led to the old URL straight to the new one.
                Redirect::query()->where('to_path', $from)->update(['to_path' => $to]);
            } catch (Throwable) {
                // Redirect bookkeeping must never break content editing.
            }
        });
    }
}
