<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe</title>
</head>
<body style="margin:0;background:#f6f7f9;font-family:Arial,sans-serif;color:#111827;">
    <main style="max-width:560px;margin:72px auto;padding:0 20px;">
        <section style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;box-shadow:0 10px 30px rgba(0,0,0,.05);">
            <h1 style="font-size:24px;margin:0 0 12px;">Unsubscribe from marketing emails?</h1>
            <p style="font-size:15px;line-height:1.6;color:#4b5563;margin:0 0 24px;">
                Confirm that you no longer want to receive marketing emails at <strong>{{ $email }}</strong>.
            </p>
            <form method="POST" action="{{ $postUrl }}">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" style="border:0;border-radius:10px;padding:11px 18px;background:#111827;color:#fff;font-weight:700;cursor:pointer;">
                    Unsubscribe
                </button>
            </form>
        </section>
    </main>
</body>
</html>
