<?php

namespace App\Support;

class HtmlContent
{
    /**
     * @return array{images: list<string>, swatches: list<array{name: string, src: string}>, body: string, lead: string}
     */
    public static function splitMedia(string $html): array
    {
        $swatches = [];
        if (preg_match_all(
            '/<p[^>]*>\s*([^<]{1,40}?)\s*<\/p>\s*<p[^>]*>\s*<img[^>]+src=["\']([^"\']+)["\'][^>]*>\s*<\/p>/iu',
            $html,
            $pairs,
            PREG_SET_ORDER
        )) {
            foreach ($pairs as $pair) {
                $name = trim(html_entity_decode(strip_tags($pair[1])));
                $src = html_entity_decode(trim($pair[2]));
                if ($name !== '' && $src !== '') {
                    $swatches[] = ['name' => $name, 'src' => $src];
                }
            }
        }

        $images = [];
        foreach ($swatches as $swatch) {
            if (! in_array($swatch['src'], $images, true)) {
                $images[] = $swatch['src'];
            }
        }

        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $src) {
                $src = html_entity_decode(trim($src));
                if ($src !== '' && ! in_array($src, $images, true)) {
                    $images[] = $src;
                }
            }
        }

        $body = preg_replace(
            '/<p[^>]*>\s*[^<]{1,40}?\s*<\/p>\s*<p[^>]*>\s*<img[^>]*>\s*<\/p>/iu',
            '',
            $html
        ) ?? $html;
        $body = preg_replace('/<p[^>]*>\s*<img[^>]*>\s*<\/p>/iu', '', $body) ?? $body;
        $body = preg_replace('/<img[^>]*>/iu', '', $body) ?? $body;
        $body = preg_replace('/<p[^>]*>\s*&nbsp;\s*<\/p>/iu', '', $body) ?? $body;
        $body = preg_replace('/<p[^>]*>\s*<\/p>/iu', '', $body) ?? $body;
        $body = trim($body);

        $lead = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $body, $m)) {
            $lead = trim(html_entity_decode(strip_tags($m[1])));
        } elseif ($body !== '') {
            $lead = trim(html_entity_decode(strip_tags($body)));
        }
        if (mb_strlen($lead) > 220) {
            $lead = rtrim(mb_substr($lead, 0, 217)).'…';
        }

        return [
            'images' => $images,
            'swatches' => $swatches,
            'body' => $body,
            'lead' => $lead,
        ];
    }
}
