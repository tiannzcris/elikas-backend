<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 0; background: #F9FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    <div style="max-width: 480px; margin: 0 auto; padding: 32px 24px;">
        <p style="color: #2F5496; font-weight: 600; font-size: 18px; margin: 0 0 24px;">E-LIKAS</p>

        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #E5E7EB;">
            <h1 style="font-size: 18px; margin: 0 0 8px;">Hi {{ $name }}</h1>
            <p style="color: #6B7280; font-size: 14px; margin: 0 0 20px;">
                An administrator changed the password on your E-LIKAS account. Your new password is below.
            </p>

            <div style="background: #F9FAFB; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <p style="font-size: 13px; color: #6B7280; margin: 0 0 4px;">New password</p>
                <p style="font-size: 14px; margin: 0; font-family: monospace;">{{ $password }}</p>
            </div>

            <p style="font-size: 13px; color: #6B7280; margin: 0 0 20px;">
                If you did not expect this change, contact your system administrator
                immediately -- accounts are managed centrally rather than through a
                self-service reset.
            </p>

            <a href="{{ url('/login') }}" style="display: inline-block; background: #2F5496; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                Log in to E-LIKAS
            </a>
        </div>

        <p style="color: #9CA3AF; font-size: 12px; margin-top: 20px;">
            CSWDO Ligao City · Electronic Ligao Kaligtasan Sistema
        </p>
    </div>
</body>
</html>
