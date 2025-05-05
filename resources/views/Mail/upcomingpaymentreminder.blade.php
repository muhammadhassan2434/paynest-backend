<!DOCTYPE html>
<html>
<head>
    <title>Upcoming Scheduled Payment</title>
</head>
<body>
    <p>Hello {{ $schedule->account->user->name ?? 'User' }},</p>

    <p>You have an upcoming scheduled payment of <strong>{{ number_format($schedule->amount, 2) }}</strong> due on {{ \Carbon\Carbon::parse($schedule->scheduled_for)->toFormattedDateString() }}.</p>

    <p>Please ensure your account has sufficient funds to complete this transaction.</p>

    <p>Thank you,<br>paynest</p>
</body>
</html>
