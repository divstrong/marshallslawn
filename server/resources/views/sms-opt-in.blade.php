<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Text Message Opt-In — Marshall's Lawn &amp; Landscape</title>
    <style>
        :root { --primary:#c9092f; --primary-dark:#a80828; }
        * { box-sizing: border-box; }
        body { margin:0; background:#f3f4f6; color:#111827; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height:1.5; }
        .wrap { max-width:640px; margin:0 auto; padding:32px 16px; }
        .card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); padding:28px; }
        h1 { font-size:24px; font-weight:700; margin:0 0 8px; }
        p { color:#4b5563; margin:8px 0; }
        .fine { font-size:13px; color:#6b7280; }
        label.field { display:block; font-size:14px; font-weight:600; color:#374151; margin-bottom:4px; }
        input[type=text], input[type=tel] { width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; }
        .row { display:flex; gap:16px; flex-wrap:wrap; }
        .row > div { flex:1; min-width:180px; }
        .field-group { margin-top:18px; }
        .consent { display:flex; gap:10px; align-items:flex-start; margin-top:18px; font-size:13px; color:#4b5563; }
        .consent input { margin-top:3px; }
        .btn { margin-top:22px; background:var(--primary); color:#fff; border:0; border-radius:8px; padding:11px 22px; font-size:15px; font-weight:600; cursor:pointer; }
        .btn:hover { background:var(--primary-dark); }
        .alert { margin-top:20px; padding:14px 16px; border-radius:8px; }
        .alert.ok { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
        .alert.info { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
        .err { color:#dc2626; font-size:13px; margin-top:4px; }
        a { color:var(--primary); }
        .hidden { position:absolute; left:-9999px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Sign up for text updates</h1>
            <p>
                Get text notifications about your own lawn &amp; landscaping service from Marshall's Lawn &amp;
                Landscape: appointment reminders, job updates, and invoice notifications.
            </p>
            <p class="fine">
                Signing up for texts is completely optional. You will receive full service — estimates, scheduling,
                and lawn care — and can be reached by phone or email whether or not you opt in. Consent to texts is
                never required to do business with us.
            </p>

            @if (session('opted_in'))
                <div class="alert ok">
                    <strong>Thanks for opting in to receive SMS notifications from Marshall's Lawn &amp; Landscape!</strong>
                    <div class="fine" style="margin-top:6px; color:inherit;">
                        We'll send a one-time confirmation text to the number you provided. Reply <strong>YES</strong>
                        to confirm. You can reply <strong>STOP</strong> at any time to unsubscribe, or <strong>HELP</strong> for help.
                    </div>
                </div>
            @endif

            @if (session('not_opted_in'))
                <div class="alert info">
                    <strong>You're all set — no text messages will be sent.</strong>
                    <div class="fine" style="margin-top:6px; color:inherit;">
                        You didn't check the consent box, and that's completely fine. Text consent is optional and never
                        required for service — we'll continue to reach you by phone and email. You can come back and opt
                        in for texts any time.
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('sms-opt-in.store') }}">
                @csrf

                {{-- Honeypot: hidden from humans, tempting to bots. --}}
                <div class="hidden" aria-hidden="true">
                    <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="row field-group">
                    <div>
                        <label class="field" for="first_name">First name</label>
                        <input type="text" name="first_name" id="first_name" required value="{{ old('first_name') }}">
                        @error('first_name') <div class="err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field" for="last_name">Last name</label>
                        <input type="text" name="last_name" id="last_name" required value="{{ old('last_name') }}">
                        @error('last_name') <div class="err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field-group">
                    <label class="field" for="phone">Mobile number</label>
                    <input type="tel" name="phone" id="phone" required value="{{ old('phone') }}" placeholder="(555) 555-5555">
                    @error('phone') <div class="err">{{ $message }}</div> @enderror
                </div>

                <div class="consent">
                    {{-- Un-prechecked AND optional: consent is never required to submit or to receive service. --}}
                    <input type="checkbox" name="consent" id="consent" value="1">
                    <label for="consent">
                        <span style="font-weight:600; color:#6b7280;">(Optional)</span>
                        I agree to receive text notifications about my own lawn &amp; landscaping service — appointment
                        reminders, job updates, and invoice notifications — from Marshall's Lawn &amp; Landscape at the
                        mobile number provided. Consent is not a condition of any purchase or service. Message frequency
                        varies. Message and data rates may apply. Reply STOP to unsubscribe, HELP for help. See our
                        <a href="https://marshallslawninc.com/privacy-policy" target="_blank" rel="noopener">Privacy Policy</a>
                        and
                        <a href="https://marshallslawninc.com/terms" target="_blank" rel="noopener">Terms &amp; Conditions</a>.
                    </label>
                </div>
                @error('consent') <div class="err">{{ $message }}</div> @enderror

                <button type="submit" class="btn">Sign up for texts</button>
            </form>
        </div>
    </div>
</body>
</html>
