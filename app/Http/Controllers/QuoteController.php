<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: real visitors never fill this hidden field.
        if ($request->filled('website')) {
            return redirect()->route('quote');
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'company' => 'required|string|max:160',
            'email' => 'required|email|max:160',
            'phone' => 'required|string|max:40',
            'product' => 'required|string|max:120',
            'quantity' => 'nullable|string|max:120',
            'message' => 'nullable|string|max:3000',
        ]);

        $to = config('site.contact.sales_inbox') ?: config('site.contact.email');
        $body = collect($data)->map(fn ($v, $k) => ucfirst($k).': '.($v ?: '-'))->implode("\n");

        Mail::raw("New quote request from the website\n\n".$body, function ($m) use ($to, $data) {
            $m->to($to)->replyTo($data['email'], $data['name'])->subject('Quote request: '.$data['product'].' ('.$data['company'].')');
        });

        return redirect()->route('quote')->with('sent', true);
    }
}
