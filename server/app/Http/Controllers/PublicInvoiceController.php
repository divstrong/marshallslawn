<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AcceptBluePaymentService;
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

    public function pay(string $token, Request $request, AcceptBluePaymentService $gateway)
    {
        $invoice = Invoice::where('share_token', $token)->firstOrFail();

        if (! in_array($invoice->status, ['sent', 'overdue'])) {
            return redirect()->back();
        }

        $validated = $request->validate([
            'source_token' => ['required', 'string'],
            'payment_type' => ['nullable', 'in:card,ach'],
            'payment_plan' => ['nullable', 'boolean'],
        ]);

        $type = $validated['payment_type'] ?? 'card';
        $method = $type === 'ach' ? 'ach' : 'card';
        // Honour the office's decision even if the request says otherwise: the toggle
        // is hidden when plans aren't allowed, and hidden isn't the same as enforced.
        $isPaymentPlan = (bool) ($validated['payment_plan'] ?? false)
            && $invoice->paymentPlanAvailable();

        if ($isPaymentPlan) {
            // First installment of a 12-payment plan; 3.75% CC fee per installment.
            $installments = 12;
            $feeRate = 0.0375;
            $baseInstallment = round($invoice->total / $installments, 2);
            $installmentWithFee = round($baseInstallment * (1 + $feeRate), 2);

            $result = $gateway->charge($invoice, $validated['source_token'], $installmentWithFee, $type);
            if (! $result['success']) {
                return redirect()->back()->with('error', $this->declineMessage($result['error']));
            }

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $installmentWithFee,
                'method' => $method,
                'reference' => $result['transaction_id'],
                'notes' => 'Payment plan installment 1 of ' . $installments . ' (Accept Blue)',
            ]);

            $invoice->update([
                'is_payment_plan' => true,
                'payment_plan_installments' => $installments,
                'payment_plan_amount' => $installmentWithFee,
                'cc_fee_rate' => $feeRate,
                'payment_plan_started_at' => now(),
                'payment_plan_payments_made' => 1,
                'status' => 'payment_plan',
            ]);

            return redirect()->back()->with('success',
                "First payment of \${$installmentWithFee} received. You'll be charged \${$installmentWithFee} every 30 days for the remaining " . ($installments - 1) . ' payments.'
            );
        }

        // One-time payment of the outstanding balance.
        $amount = round($invoice->balanceDue(), 2);
        if ($amount <= 0) {
            return redirect()->back()->with('success', 'This invoice is already paid in full.');
        }

        $result = $gateway->charge($invoice, $validated['source_token'], $amount, $type);
        if (! $result['success']) {
            return redirect()->back()->with('error', $this->declineMessage($result['error']));
        }

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $result['transaction_id'],
            'notes' => 'Paid online (Accept Blue)',
        ]);

        // Recompute against payments; mark paid once the balance is cleared.
        if ($invoice->fresh()->balanceDue() <= 0) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        }

        return redirect()->back()->with('success', 'Payment received — thank you!');
    }

    private function declineMessage(?string $error): string
    {
        return $error
            ? 'Payment could not be completed: ' . $error
            : 'Payment could not be completed. Please try again or use a different card.';
    }
}
