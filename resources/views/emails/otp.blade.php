<x-mail::message>
    # Introduction

    The body of your message.

    <x-mail::button :url="''">
        Button Text
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eeeeee;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #333333;
            font-size: 24px;
            margin: 0;
        }

        .content {
            color: #555555;
            line-height: 1.6;
            font-size: 16px;
        }

        .otp-code {
            display: block;
            width: fit-content;
            margin: 25px auto;
            padding: 15px 30px;
            background-color: #f0f8ff;
            /* Light blue background */
            border: 2px dashed #a8d9ff;
            /* Dashed blue border */
            border-radius: 8px;
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            /* Blue text color */
            text-align: center;
            letter-spacing: 3px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            font-size: 14px;
            color: #888888;
        }

        .footer p {
            margin: 5px 0;
        }

        .note {
            font-size: 14px;
            color: #777777;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Email Verification Required</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Thank you for registering with us. To complete your email verification, please use the One-Time Password
                (OTP) below:</p>

            <span class="otp-code">{{ $otp }}</span>

            <p>This OTP is valid for a limited time. Please do not share this code with anyone.</p>
            <p>If you did not request this, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Your Application Name. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
