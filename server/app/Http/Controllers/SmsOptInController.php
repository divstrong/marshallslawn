<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated SMS opt-in page.
 *
 * Exists primarily so the A2P / 10DLC campaign reviewer has a publicly reachable
 * Call-to-Action URL that shows the exact consent checkbox and disclosure
 * language. A real submission records a PENDING consent; the customer must still
 * reply YES to the double opt-in confirmation before any SMS is sent.
 */
class SmsOptInController extends Controller
{
    public function show()
    {
        return view('sms-opt-in');
    }

    public function store(Request $request)
    {
        // Honeypot: bots fill hidden fields. Pretend success, store nothing.
        if (filled($request->input('website'))) {
            return redirect()->route('sms-opt-in')->with('opted_in', true);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            // Consent is OPTIONAL and never a condition of service (A2P 10DLC). The
            // box is un-pre-checked; leaving it unchecked simply enrolls no one.
            'consent' => ['nullable'],
        ]);

        if (! $request->boolean('consent')) {
            return redirect()->route('sms-opt-in')->with('not_opted_in', true);
        }

        $digits = preg_replace('/\D+/', '', $data['phone']);

        $customer = Customer::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone,'(',''),')',''),'-',''),' ','') LIKE ?", ['%' . $digits])
            ->first();

        if (! $customer) {
            $customer = new Customer([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'status' => 'lead',
            ]);
        }

        // Record as PENDING, then fire the one-time double opt-in request. The
        // customer must reply YES to be promoted to CONFIRMED before any messages
        // are sent. sendSmsOptInRequest() is a no-op unless double opt-in is enabled
        // and the record is pending, so this is safe pre-approval.
        $customer->sms_consent_status = Customer::SMS_PENDING;
        $customer->save();
        $customer->sendSmsOptInRequest();

        return redirect()->route('sms-opt-in')->with('opted_in', true);
    }
}
