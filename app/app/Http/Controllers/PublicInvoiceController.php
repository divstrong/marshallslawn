<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class PublicInvoiceController extends Controller
{
    public function show(string $token)
    {
        $invoice = Invoice::where('share_token', $token)
            ->with(['customer', 'credits.appliedBy'])
            ->firstOrFail();

        return view('invoices.public', compact('invoice'));
    }

    public function pay(string $token, Request $request)
    {
        $invoice = Invoice::where('share_token', $token)->firstOrFail();

        if (! in_array($invoice->status, ['sent', 'overdue'])) {
            return redirect()->back();
        }

        $method = $request->input('payment_method', 'card');
        $isPaymentPlan = $request->boolean('payment_plan');

        if ($isPaymentPlan) {
            // Enroll in 12-installment payment plan via credit card
            // 3.75% CC fee applied to each installment
            $installments = 12;
            $feeRate = 0.0375;
            $baseInstallment = round($invoice->total / $installments, 2);
            $installmentWithFee = round($baseInstallment * (1 + $feeRate), 2);

            $invoice->update([
                'is_payment_plan' => true,
                'payment_plan_installments' => $installments,
                'payment_plan_amount' => $installmentWithFee,
                'cc_fee_rate' => $feeRate,
                'payment_plan_started_at' => now(),
                'payment_plan_payments_made' => 1,
                'status' => 'payment_plan',
                'notes' => trim(
                    ($invoice->notes ?? '') .
                    "\n\n--- Payment plan enrolled on " . now()->format('M j, Y g:i A') .
                    "\n{$installments} payments of \${$installmentWithFee} every 30 days (includes 3.75% CC fee)" .
                    "\nFirst payment processed via credit card"
                ),
            ]);

            return redirect()->back()->with('success',
                "Payment plan enrolled! You will be charged \${$installmentWithFee} every 30 days for {$installments} payments."
            );
        }

        // One-time full payment
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'notes' => trim(
                ($invoice->notes ?? '') . "\n\n--- Payment received via {$method} on " . now()->format('M j, Y g:i A')
            ),
        ]);

        return redirect()->back()->with('success', 'Payment received — thank you!');
    }
}
