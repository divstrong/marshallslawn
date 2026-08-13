<?php

namespace App\Http\Controllers;

use App\Livewire\SettingsTerms;
use App\Models\Estimate;
use Illuminate\Http\Request;

class PublicEstimateController extends Controller
{
    public function show(string $token)
    {
        $estimate = Estimate::where('share_token', $token)
            ->with(['customer', 'property', 'lineItems.service'])
            ->firstOrFail();

        return view('estimates.public', compact('estimate'));
    }

    public function accept(string $token, Request $request)
    {
        $estimate = Estimate::where('share_token', $token)->firstOrFail();

        // The terms box gates the button in the browser; validate it here too, so an
        // acceptance can't arrive without agreement when scripting is off or bypassed.
        $request->validate([
            'terms_accepted' => ['accepted'],
            'accepted_items' => ['array'],
        ], [
            'terms_accepted.accepted' => 'Please agree to the Terms & Conditions before accepting.',
        ]);

        if (in_array($estimate->status, ['draft', 'sent'])) {
            $acceptedIds = $request->input('accepted_items', []);

            $estimate->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'terms_accepted_at' => now(),
                // Snapshot: Settings → Terms can change, the agreed wording can't.
                'accepted_terms' => SettingsTerms::estimateTerms(),
                'notes' => trim(
                    ($estimate->notes ?? '') . "\n\n--- Customer accepted line items: " .
                    implode(', ', $acceptedIds) . ' on ' . now()->format('M j, Y g:i A')
                ),
            ]);
        }

        return redirect()->back()->with('success', 'Estimate accepted! We will be in touch shortly.');
    }

    public function decline(string $token, Request $request)
    {
        $estimate = Estimate::where('share_token', $token)->firstOrFail();

        if (in_array($estimate->status, ['draft', 'sent'])) {
            $estimate->update(['status' => 'declined']);
        }

        return redirect()->back()->with('success', 'Estimate declined.');
    }
}
