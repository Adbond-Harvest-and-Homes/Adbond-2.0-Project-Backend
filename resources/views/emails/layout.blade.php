<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .header img {
            width: 30%;
            max-width: 150px;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px;
        }
        .order-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 0.8em;
        }
        .btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .code {
            margin-top: 20px;
            margin-bottom: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body style="font-family: 'Arial', sans-serif; line-height: 1.6; background-color: #f4f4f4; margin: 0; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="background-color: #007bff; color: white; text-align: center; padding: 20px;">
            <img src="{{ env('APP_URL') }}/images/newLogo.png" alt="Company Logo" style="width: 30%; max-width: 150px; margin-bottom: 10px;">
            <h1 style="text-align: center;">@yield('heading')</h1>
        </div>

        @yield('content')

        <div style="background-color: #343a40; color: white; text-align: center; padding: 15px; font-size: 0.8em;">
            <p>&copy; {{ now()->year }} {{ env('APP_NAME') }}. All Rights Reserved.</p>
            <p>Contact: {{ env('APP_EMAIL') }} | </p>
            <small>If you have any questions, please contact our support team.</small>
        </div>
    </div>
</body>
</html>

<!-- <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('heading')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
    <table width="100%" bgcolor="#f6f6f6" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="margin: 20px auto; border-radius: 5px; overflow: hidden;">
                    <!-- Logo --
                    <tr>
                        <td align="center" style="padding: 20px;">
                            <img src="{{ env('APP_URL') }}/images/logo.png" alt="Logo" width="120" style="display: block;">
                        </td>
                    </tr>

                    <!-- Body Content --
                    <tr>
                        <td style="padding: 20px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer --
                    <tr>
                        <td align="center" style="padding: 20px; font-size: 12px; color: #999999;">
                            &copy; {{ date('Y') }} Your Company. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html> -->
