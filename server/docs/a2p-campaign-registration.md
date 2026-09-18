# A2P 10DLC Campaign Registration — Marshall's Lawn & Landscape

Single source of truth for the Twilio campaign registration fields. **The registered
messages below MUST match what the app actually sends** (see references), or a reviewer
auditing a live exchange can reject for inconsistency.

App message sources:
- Opt-in request: `App\Models\Customer::sendSmsOptInRequest()`
- Opt-in confirmation / opt-out / HELP replies: `App\Http\Controllers\TwilioWebhookController::handleConsentKeyword()`
- Public opt-in form: `App\Http\Controllers\SmsOptInController` + `resources/views/sms-opt-in.blade.php` → https://app.marshallslawninc.com/sms-opt-in
- Transactional notifications: `App\Services\CustomerSmsNotifier` (bodies editable in Settings → Notifications; seeded in the `sms_templates` table)

---

## Brand / business
Marshall's Lawn & Landscape — a residential and commercial lawn care and landscaping
company in Virginia. Legal business name, EIN, address, and website are supplied on the
Twilio Brand registration.

## Use case type
**Customer Care** (transactional service notifications to existing customers). Only choose
**Mixed** if you ever send promotional/marketing content — currently you do not.

## Campaign description
> **NOTE:** deliberately avoids the keyword cluster `consent` + `condition` + `purchase/service`.
> TCR's automated pre-vet strips the negation from "consent is NOT a condition of purchase" and
> reads it as consent-gating — a common driver of 30896 / MESSAGE_FLOW rejections. "Optional" is
> conveyed with "whether or not they opt in," which carries no trigger words.
>
> Marshall's Lawn & Landscape is a lawn care and landscaping company in Virginia. This campaign
> sends transactional service notifications to our existing customers about their own service
> only: appointment/scheduling reminders, job status updates (scheduled, completed, rescheduled
> or canceled), and invoice notifications. No marketing or promotional content is ever sent. Each
> customer opts in for themselves on our public web form at
> https://app.marshallslawninc.com/sms-opt-in by entering their own mobile number and ticking an
> optional, un-pre-checked consent box, then replying YES to a confirmation text before any
> message is sent. The consent box is optional and is never a condition of service — customers
> receive full service (estimates, scheduling, and lawn care) by phone or email whether or not
> they opt in. Message frequency varies. Reply STOP to unsubscribe, HELP for help.

## Sample messages
> Rendered from the seeded `sms_templates` bodies (see the `create_sms_templates_table`
> migration) — NOT hand-written. Square brackets mark variable content, per Twilio's
> instruction on the Campaign Registration form. If the office edits a body in
> Settings → Notifications, update these samples to match.

1. > Hi [Jane], this is Marshall's Lawn & Landscape. Your [Weekly Mow] is scheduled for [Mon, Jul 13]. Reply STOP to opt out, HELP for help.
2. > Hi [Jane], Marshall's Lawn & Landscape has completed your [Spring Cleanup]. Thank you for your business! Reply STOP to opt out.
3. > Hi [Jane], Marshall's Lawn & Landscape has issued invoice [INV-00042] for [$250.00]. View it here: https://app.marshallslawninc.com/invoice/[abc123]. Reply STOP to opt out.
4. > Hi [Jane], this is Marshall's Lawn & Landscape. Your [Weekly Mow] scheduled for [Mon, Jul 13] has been [rescheduled]. We'll be in touch. Reply STOP to opt out.

## Message contents
- Embedded links: **Yes** (invoice links may be included)
- Phone numbers: **Yes**
- Direct lending or loan arrangement: **No**
- Age-gated content: **No**
- Privacy policy: https://marshallslawninc.com/privacy-policy/
- Terms of service: https://marshallslawninc.com/privacy-policy/ (same page — it must carry
  both the SMS privacy clause and the "Text Messaging (SMS) Terms" section; there is no
  separate terms page on the site)

## How do end-users consent to receive messages?
Opt-in method: **Web Form** only. The YES reply confirms a web sign-up; it is not a keyword
campaign. Staff never opt anyone in — adding a customer in the admin sends no SMS (see
`CreateCustomer`).

> End users opt in on our public web form at https://app.marshallslawninc.com/sms-opt-in. The
> customer enters their first name, last name and mobile number. Below the fields is an optional
> consent checkbox that is not pre-checked. It reads: "(Optional) I agree to receive text
> notifications about my own lawn & landscaping service — appointment reminders, job updates, and
> invoice notifications — from Marshall's Lawn & Landscape at the mobile number provided. Consent
> is not a condition of any purchase or service. Message frequency varies. Message and data rates
> may apply. Reply STOP to unsubscribe, HELP for help. See our Privacy Policy & SMS Terms." The
> label links to https://marshallslawninc.com/privacy-policy/, which contains our privacy policy
> and SMS terms. If the box is left unchecked, no number is enrolled and no messages are sent. When
> the box is checked, we send one confirmation text: "Marshall's Lawn & Landscape: Hi {first name},
> reply YES to receive text updates about your lawn & landscaping service. Msg frequency varies.
> Msg & data rates may apply. Reply HELP for help, STOP to cancel." No other messages are sent
> until the customer replies YES. Customers enter their own numbers; staff never opt anyone in.

## Opt-in message (double opt-in request)
Sent by `Customer::sendSmsOptInRequest()`. Must match the registered text:
> Marshall's Lawn & Landscape: Hi {first}, reply YES to receive text updates about your lawn &
> landscaping service. Msg frequency varies. Msg & data rates may apply. Reply HELP for help,
> STOP to cancel.

## Opt-in confirmation (reply to YES)
Sent by `TwilioWebhookController::handleConsentKeyword()`:
> Marshall's Lawn & Landscape: You're confirmed. You'll receive service updates. Msg frequency
> varies. Msg & data rates may apply. Reply HELP for help, STOP to cancel.

## Opt-out reply (reply to STOP)
> Marshall's Lawn & Landscape: You're unsubscribed and will receive no more messages. Reply START
> to resubscribe.

## HELP reply
> Marshall's Lawn & Landscape: Lawn & landscaping service updates. Help: call (804) 733-3610 or
> visit marshallslawninc.com. Reply STOP to unsubscribe. Msg & data rates may apply.

> Keep these four strings byte-identical between this doc, the registered campaign, and the code
> (`TwilioWebhookController` + `Customer::sendSmsOptInRequest`). A reviewer comparing a live
> exchange to the registration will reject on any mismatch.

## Opt-out & help handling
Twilio's Advanced Opt-Out is the primary handler. `TwilioWebhookController::handleConsentKeyword()`
is a belt-and-suspenders fallback that also records the consent state on the customer record
(STOP → `opted_out`, YES/START → `confirmed`) and replies with the CTIA-required copy above. The
recognized keywords:
- Opt-out: STOP, STOPALL, UNSUBSCRIBE, CANCEL, END, QUIT, OPTOUT, REVOKE
- Opt-in: YES, START, JOIN, UNSTOP, CONFIRM, AGREE
- Help: HELP, INFO

## Pre-submission checklist
- [ ] Twilio Brand registered (legal name, EIN, address, website).
- [ ] Privacy Policy page live at the URL above, and it states SMS opt-in data is **not shared
      with third parties for marketing** (a common rejection cause).
- [ ] Public opt-in form reachable at https://app.marshallslawninc.com/sms-opt-in and renders the
      exact checkbox copy above.
- [ ] The four consent strings match between this doc, the code, and the campaign fields.
- [ ] `TWILIO_*` env vars set in production; `TWILIO_NOTIFICATIONS_ENABLED=true` and
      `TWILIO_DOUBLE_OPT_IN=true`.
- [ ] Inbound + status webhooks pointed at `/webhooks/twilio/inbound` and `/webhooks/twilio/status`.
- [ ] Update the HELP-line phone number above if (804) 733-3610 is not correct.
