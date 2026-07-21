<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\OwnerLeadReceived;
use App\Models\OwnerLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OwnerLeadController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: bots that fill the hidden "website" field get a silent OK.
        if ($request->filled('website')) {
            return redirect()->route('home')->with('status', 'Richiesta inviata. Ti ricontatteremo al più presto.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'name' => 'nome',
            'email' => 'email',
            'phone' => 'telefono',
            'city' => 'città',
            'message' => 'messaggio',
        ]);

        $lead = OwnerLead::create($data);

        // La notifica email non deve mai far fallire il form: il lead è già in DB.
        try {
            Mail::to(config('services.hostup.owner_lead_email'))
                ->send(new OwnerLeadReceived($lead));
        } catch (\Throwable $e) {
            Log::warning('Notifica lead non inviata: ' . $e->getMessage());
        }

        return redirect()->to(route('home') . '#contatti')
            ->with('status', 'Richiesta inviata! Ti ricontatteremo entro 24 ore.');
    }
}
