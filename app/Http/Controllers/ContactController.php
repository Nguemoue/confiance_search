<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Display the Contact page.
     */
    public function index(Request $request): View
    {
        return view('pages.contact');
    }

    /**
     * Store a newly submitted contact message.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        ContactMessage::create($validated);

        return back()->with('status', 'Votre message a été envoyé avec succès ! Notre équipe vous répondra dans les plus brefs délais.');
    }
}
