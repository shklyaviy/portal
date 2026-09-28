<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        Lead::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'comment' => $data['comment'] ?? null,
            'source' => $data['source'] ?? $request->headers->get('referer'),
        ]);

        return back()->with('success', 'Заявка отправлена. Мы свяжемся с вами.');
    }
}
