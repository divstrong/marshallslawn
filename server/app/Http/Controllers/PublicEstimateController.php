<?php

namespace App\Http\Controllers;

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

        if (in_array($estimate->status, ['draft', 'sent'])) {
            $acceptedIds = $request->input('accepted_items', []);

            $estimate->update([
                'status' => 'accepted',
                'accepted_at' => now(),
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
