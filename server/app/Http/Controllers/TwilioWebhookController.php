<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Twilio\Security\RequestValidator;

/**
 * Twilio inbound + status webhooks. Handles A2P consent keywords (STOP / HELP /
 * START) in-app as a belt-and-suspenders alongside Twilio's Advanced Opt-Out,
 * and drops any other inbound text into the customer's office chat thread.
 */
class TwilioWebhookController extends Controller
{
    private const OPT_IN_KEYWORDS = ['YES', 'START', 'JOIN', 'UNSTOP', 'CONFIRM', 'AGREE'];

    private const OPT_OUT_KEYWORDS = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT', 'OPTOUT', 'REVOKE'];

    private const HELP_KEYWORDS = ['HELP', 'INFO'];

    public function inbound(Request $request): Response
    {
        if (! $this->isValidSignature($request)) {
            Log::warning('twilio.webhook.invalid_signature', ['kind' => 'inbound', 'ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        $from = (string) $request->input('From', '');
        $body = trim((string) $request->input('Body', ''));
        $messageSid = (string) $request->input('MessageSid', '');

        $customer = $this->findCustomerByPhone($from);

        if (! $customer) {
            Log::info('twilio.webhook.inbound.no_customer', ['sid' => $messageSid, 'from' => $from]);

            return $this->emptyTwiml();
        }

        // Consent keywords take priority over normal chat handling. Reaching here
        // for STOP/HELP means Twilio's Advanced Opt-Out didn't intercept, so we
        // record state and send the required reply ourselves.
        if ($reply = $this->handleConsentKeyword($customer, $body, $messageSid)) {
            return $reply;
        }

        // Anything else is an ordinary reply — log it into the office chat thread.
        if ($body !== '') {
            CustomerMessage::create([
                'customer_id' => $customer->id,
                'sender' => CustomerMessage::SENDER_CUSTOMER,
                'body' => $body,
            ]);
        }

        Log::info('twilio.webhook.inbound.received', ['sid' => $messageSid, 'customer_id' => $customer->id]);

        return $this->emptyTwiml();
    }

    public function status(Request $request): Response
    {
        if (! $this->isValidSignature($request)) {
            Log::warning('twilio.webhook.invalid_signature', ['kind' => 'status', 'ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        $status = (string) $request->input('MessageStatus', '');
        $level = in_array($status, ['failed', 'undelivered'], true) ? 'warning' : 'info';

        Log::log($level, 'twilio.webhook.status', [
            'sid' => (string) $request->input('MessageSid', ''),
            'status' => $status,
            'error_code' => $request->input('ErrorCode'),
            'to' => $request->input('To'),
        ]);

        return response('', 204);
    }

    /**
     * Handle an A2P consent keyword. Returns a TwiML reply when the message is a
     * recognized opt-in / opt-out / help keyword (updating consent state), or null
     * to let the message fall through to chat handling.
     */
    private function handleConsentKeyword(Customer $customer, string $body, string $messageSid): ?Response
    {
        $first = strtoupper(preg_split('/\s+/', trim($body))[0] ?? '');
        if ($first === '') {
            return null;
        }

        $company = Setting::get('company_name', "Marshall's Lawn & Landscape");

        if (in_array($first, self::OPT_OUT_KEYWORDS, true)) {
            $customer->markSmsOptedOut();
            Log::info('twilio.webhook.inbound.opt_out', ['sid' => $messageSid, 'customer_id' => $customer->id]);

            return $this->twimlMessage(
                "{$company}: You're unsubscribed and will receive no more messages. Reply START to resubscribe."
            );
        }

        if (in_array($first, self::HELP_KEYWORDS, true)) {
            Log::info('twilio.webhook.inbound.help', ['sid' => $messageSid, 'customer_id' => $customer->id]);

            return $this->twimlMessage(
                "{$company}: Lawn & landscaping service updates. Help: call (804) 733-3610 or visit "
                    . 'marshallslawninc.com. Reply STOP to unsubscribe. Msg & data rates may apply.'
            );
        }

        if (in_array($first, self::OPT_IN_KEYWORDS, true)) {
            $customer->markSmsConfirmed();
            Log::info('twilio.webhook.inbound.opt_in_confirmed', ['sid' => $messageSid, 'customer_id' => $customer->id]);

            return $this->twimlMessage(
                "{$company}: You're confirmed. You'll receive service updates. "
                    . 'Msg frequency varies. Msg & data rates may apply. Reply HELP for help, STOP to cancel.'
            );
        }

        return null;
    }

    private function twimlMessage(string $text): Response
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return response(
            '<?xml version="1.0" encoding="UTF-8"?><Response><Message>' . $escaped . '</Message></Response>',
            200
        )->header('Content-Type', 'text/xml');
    }

    private function isValidSignature(Request $request): bool
    {
        $token = config('twilio.auth_token');
        if (empty($token)) {
            return false;
        }

        $signature = $request->header('X-Twilio-Signature', '');
        if (! $signature) {
            return false;
        }

        return (new RequestValidator($token))
            ->validate($signature, $request->fullUrl(), $request->post());
    }

    private function findCustomerByPhone(string $from): ?Customer
    {
        $digits = substr(preg_replace('/\D/', '', $from), -10);
        if (strlen($digits) !== 10) {
            return null;
        }

        $lastFour = substr($digits, -4);

        return Customer::query()
            ->where('phone', 'like', '%' . $lastFour . '%')
            ->get()
            ->first(function (Customer $c) use ($digits) {
                $candidate = substr(preg_replace('/\D/', '', (string) $c->phone), -10);

                return $candidate === $digits;
            });
    }

    private function emptyTwiml(): Response
    {
        return response('<?xml version="1.0" encoding="UTF-8"?><Response/>', 200)
            ->header('Content-Type', 'text/xml');
    }
}
