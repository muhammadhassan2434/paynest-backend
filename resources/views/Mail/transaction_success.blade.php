<!DOCTYPE html>
<html>
<head>
    <title>Transaction Successful</title>
</head>
<body>
    <h1>Transaction Successful</h1>
    <p>Dear {{ $sender_first_name }},</p>
    <p>Your transaction of <strong>Rs{{ $transaction_amount }}</strong> has been successfully sent to <strong>{{ $receiver_name }}</strong>.</p>
    <p>Thank you for using our service.</p>
</body>
</html>
