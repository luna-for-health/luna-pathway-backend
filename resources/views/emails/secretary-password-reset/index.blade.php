<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Helvetica, Arial, sans-serif;
            color: #43455F;
        }

        .wrapper {
            width: 100%;
            background: #ffffff;
            padding: 30px 0;
        }

        .content {
            width: 100%;
            max-width: 580px;
            margin: auto;
            background: #ffffff;
        }

        .header {
            font-size: 24px;
            font-weight: 600;
            margin: 25px 0 15px;
            color: #1A1B2F;
            text-align: center;
        }

        .body-text {
            font-size: 15px;
            line-height: 1.6;
            color: #43455F;
            width: 90%;
            margin: 0 auto 12px;
            text-align: center;
        }

        .code-box {
            background: #F4F6FF;
            border-radius: 10px;
            padding: 20px;
            margin: 30px auto;
            width: 200px;
            text-align: center;
        }

        .code {
            font-size: 32px;
            font-weight: bold;
            color: #3C5BFF;
            letter-spacing: 4px;
        }

        .footer-text {
            font-size: 12px;
            color: #82879C;
            text-align: center;
            margin-top: 35px;
            margin-bottom: 10px;
        }

        @media only screen and (max-width: 600px) {
            .content {
                width: 100% !important;
            }

            .body-text {
                width: 100% !important;
            }
        }
    </style>
</head>

<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="content" cellpadding="0" cellspacing="0">

                    <tr>
                        <td>
                            <div class="header">{{ __('auth.password_reset_mail_title') }}</div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <p class="body-text">
                                {{ __('auth.password_reset_mail_description') }}
                            </p>
                            <p class="body-text">
                                {{ __('auth.password_reset_mail_quote') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center">
                            <div class="code-box">
                                <div class="code">{{ $code }}</div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <p class="footer-text">
                                &copy; {{ date('Y') }} {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
