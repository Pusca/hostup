<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OwnerLead;
use Illuminate\Http\Request;

class OwnerLeadController extends Controller
{
    public function index()
    {
        $leads = OwnerLead::orderByRaw("status = 'new' desc")
            ->latest()
            ->paginate(25);

        return view('admin.leads.index', compact('leads'));
    }

    public function update(Request $request, OwnerLead $lead)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $lead->update($data);

        return back()->with('status', 'Richiesta aggiornata.');
    }

    public function destroy(OwnerLead $lead)
    {
        $lead->delete();

        return back()->with('status', 'Richiesta eliminata.');
    }
}
