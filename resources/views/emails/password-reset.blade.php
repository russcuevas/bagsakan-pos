<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bagsakan POS</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #F8FAFC;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #F8FAFC;
            padding: 40px 0;
        }

        .main-table {
            background-color: #FFFFFF;
            margin: 0 auto;
            width: 100%;
            max-width: 580px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .header {
            background: linear-gradient(135deg, #102A4E 0%, #075998 100%);
            padding: 32px 30px;
            text-align: center;
        }

        .header-title {
            color: #FFFFFF;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .header-subtitle {
            color: #93C5FD;
            font-size: 13px;
            margin-top: 4px;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .content {
            padding: 36px 32px 28px 32px;
        }

        .greeting {
            font-size: 17px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 12px;
        }

        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }

        .btn-container {
            text-align: center;
            margin: 32px 0;
        }

        .btn {
            background-color: #075998;
            color: #FFFFFF !important;
            display: inline-block;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(7, 89, 152, 0.28);
        }

        .info-box {
            background-color: #F0F9FF;
            border-left: 4px solid #0284C7;
            padding: 14px 16px;
            border-radius: 6px;
            font-size: 13px;
            color: #0369A1;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .break-link-text {
            font-size: 12px;
            color: #94A3B8;
            word-break: break-all;
            line-height: 1.4;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #F1F5F9;
        }

        .break-link-text a {
            color: #075998;
            text-decoration: underline;
        }

        .footer {
            background-color: #F8FAFC;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #E2E8F0;
            font-size: 12px;
            color: #94A3B8;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <table class="main-table" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="header">
                    <div class="header-title">Worthy Acosta Trading</div>
                    <div class="header-subtitle">Bagsakan POS & Inventory Management</div>
                </td>
            </tr>
            <tr>
                <td class="content">
                    <div class="greeting">Hello, {{ $user->name }}!</div>
                    <div class="text">
                        We received a request to reset the password associated with your account on <strong>Bagsakan
                            POS</strong>. Click the button below to choose a new password:
                    </div>

                    <div class="btn-container">
                        <a href="{{ $resetUrl }}" class="btn" target="_blank">Reset My Password</a>
                    </div>

                    <div class="info-box">
                        <strong>Important Security Note:</strong><br>
                        This password reset link will automatically expire in <strong>{{ $count }}
                            minutes</strong>. If you did not request a password reset, you can safely ignore this email
                        and your account password will remain unchanged.
                    </div>

                    <div class="break-link-text">
                        If you're having trouble clicking the "Reset My Password" button, copy and paste the URL below
                        into your web browser:<br>
                        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    &copy; {{ date('Y') }} Bagsakan Kalidad Food Products Trading. All rights reserved.<br>
                    This is an automated system notification.
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
