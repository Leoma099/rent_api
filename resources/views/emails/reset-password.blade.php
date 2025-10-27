<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Password Reset Request</h2>
    <p>Hello,</p>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <p>
        Click the link below to reset your password:
        <br>
        <a href="{{ $resetUrl }}">Reset Password</a>
    </p>
    <p>If you did not request a password reset, no further action is required.</p>
</body>
</html>
