<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Helvetica, Arial, sans-serif; color: #43455F;">
    <p>This is a test email sent by {{ config('app.name') }} through Brevo.</p>
    <p>Sent at {{ now()->toDateTimeString() }}.</p>
</body>
</html>
