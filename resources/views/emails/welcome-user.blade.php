<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 0; background: #F9FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    <div style="max-width: 480px; margin: 0 auto; padding: 32px 24px;">
        <p style="color: #2F5496; font-weight: 600; font-size: 18px; margin: 0 0 24px;">E-LIKAS</p>

        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #E5E7EB;">
            <h1 style="font-size: 18px; margin: 0 0 8px;">Welcome, {{ $name }}</h1>
            <p style="color: #6B7280; font-size: 14px; margin: 0 0 20px;">
                An E-LIKAS account has been created for you as <strong>{{ $roleDisplayName }}</strong>.
            </p>

            <div style="background: #F9FAFB; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <p style="font-size: 13px; color: #6B7280; margin: 0 0 4px;">Email</p>
                <p style="font-size: 14px; margin: 0 0 12px;">{{ $email }}</p>
                <p style="font-size: 13px; color: #6B7280; margin: 0 0 4px;">Password</p>
                <p style="font-size: 14px; margin: 0; font-family: monospace;">{{ $password }}</p>
            </div>

            <p style="font-size: 13px; color: #6B7280; margin: 0 0 20px;">
                Please keep these details private. If you ever need your password changed,
                contact your system administrator directly -- accounts are managed centrally
                rather than through a self-service reset.
            </p>

            <a href="{{ url('/login') }}" style="display: inline-block; background: #2F5496; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                Log in to E-LIKAS
            </a>

            @if($showDesktopDownload)
                <div style="border-top: 1px solid #E5E7EB; margin-top: 24px; padding-top: 20px;">
                    <p style="font-size: 14px; font-weight: 600; margin: 0 0 6px;">Offline Companion App</p>
                    <p style="font-size: 13px; color: #6B7280; margin: 0 0 12px;">
                        You can also register families while offline
                        using our desktop companion app. Install it once on the computer at
                        your barangay hall -- it works even with no internet connection, and
                        syncs automatically once you're back online.
                    </p>
                    <a href="{{ $downloadUrl }}" style="display: inline-block; background: white; color: #2F5496; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: 1px solid #2F5496;">
                        Download for Windows
                    </a>
                    <p style="font-size: 12px; color: #9CA3AF; margin: 10px 0 0;">
                        Windows may show a security warning during install since this app
                        isn't yet digitally signed -- click "More info" then "Run anyway" to
                        continue. This is expected, not a sign of a problem.
                    </p>
                </div>
            @endif
        </div>

        <p style="color: #9CA3AF; font-size: 12px; margin-top: 20px;">
            CSWDO Ligao City · Electronic Ligao Kaligtasan Sistema
        </p>
    </div>
</body>
</html>
