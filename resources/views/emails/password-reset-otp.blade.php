<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge"
    >

    <title>KNOWURLOCAL | Password Reset</title>

</head>


<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f3f6fa;
        font-family: Arial, Helvetica, sans-serif;
        color: #1f2937;
    "
>


<!-- =========================================================
     EMAIL WRAPPER
     =========================================================

     Tables are used intentionally because email clients such
     as Gmail and Outlook have inconsistent support for modern
     CSS layouts.
     ========================================================= -->

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    role="presentation"
    style="
        width: 100%;
        background-color: #f3f6fa;
        margin: 0;
        padding: 0;
    "
>

    <tr>

        <td
            align="center"
            style="
                padding: 40px 16px;
            "
        >


            <!-- =================================================
                 MAIN EMAIL CARD
                 ================================================= -->

            <table
                width="600"
                cellpadding="0"
                cellspacing="0"
                border="0"
                role="presentation"
                style="
                    width: 100%;
                    max-width: 600px;
                    background-color: #ffffff;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid #e5e7eb;
                "
            >


                <!-- =================================================
                     BRAND HEADER
                     ================================================= -->

                <tr>

                    <td
                        align="center"
                        style="
                            background-color: #3568c8;
                            padding: 30px 24px;
                        "
                    >

                        <div
                            style="
                                font-size: 13px;
                                line-height: 18px;
                                letter-spacing: 1.8px;
                                font-weight: 700;
                                color: #ffffff;
                            "
                        >
                            KNOWURLOCAL
                        </div>


                        <div
                            style="
                                margin-top: 7px;
                                font-size: 12px;
                                line-height: 18px;
                                color: rgba(255,255,255,0.82);
                            "
                        >
                            PASSWORD RESET
                        </div>

                    </td>

                </tr>


                <!-- =================================================
                     EMAIL CONTENT
                     ================================================= -->

                <tr>

                    <td
                        style="
                            padding: 38px 42px 34px;
                        "
                    >


                        <!-- =================================================
                             CONTEXT LABEL
                             ================================================= -->

                        <div
                            style="
                                font-size: 11px;
                                line-height: 16px;
                                letter-spacing: 1.4px;
                                font-weight: 700;
                                color: #3568c8;
                                text-transform: uppercase;
                            "
                        >
                            Password Reset Request
                        </div>


                        <!-- =================================================
                             MAIN HEADING
                             ================================================= -->

                        <h1
                            style="
                                margin: 8px 0 14px;
                                font-size: 27px;
                                line-height: 35px;
                                font-weight: 700;
                                color: #111827;
                            "
                        >
                            Reset your password
                        </h1>


                        <!-- =================================================
                             INTRODUCTION
                             ================================================= -->

                        <p
                            style="
                                margin: 0 0 22px;
                                font-size: 14px;
                                line-height: 23px;
                                color: #4b5563;
                            "
                        >
                            We received a request to reset the password
                            for your KNOWURLOCAL account. Use the code
                            below to continue.
                        </p>


                        <!-- =================================================
                             OTP CODE LABEL
                             ================================================= -->

                        <div
                            style="
                                margin-bottom: 9px;
                                text-align: center;
                                font-size: 11px;
                                line-height: 16px;
                                letter-spacing: 1.2px;
                                font-weight: 700;
                                color: #7b8798;
                                text-transform: uppercase;
                            "
                        >
                            Your password reset code
                        </div>


                        <!-- =================================================
                             OTP CODE BOX
                             ================================================= -->

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            role="presentation"
                        >

                            <tr>

                                <td align="center">

                                    <div
                                        style="
                                            display: inline-block;
                                            min-width: 230px;
                                            padding: 18px 28px;
                                            background-color: #f4f7fc;
                                            border: 1px solid #dce5f2;
                                            border-radius: 12px;
                                            text-align: center;
                                            font-size: 30px;
                                            line-height: 38px;
                                            letter-spacing: 7px;
                                            font-weight: 700;
                                            color: #244d91;
                                        "
                                    >
                                        {{ $otp }}
                                    </div>

                                </td>

                            </tr>

                        </table>


                        <!-- =================================================
                             EXPIRATION NOTICE
                             ================================================= -->

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            role="presentation"
                            style="
                                margin-top: 24px;
                            "
                        >

                            <tr>

                                <td
                                    style="
                                        padding: 14px 16px;
                                        background-color: #fff8e8;
                                        border: 1px solid #f5dfad;
                                        border-radius: 8px;
                                    "
                                >

                                    <p
                                        style="
                                            margin: 0;
                                            font-size: 12px;
                                            line-height: 19px;
                                            color: #765d21;
                                        "
                                    >

                                        <strong>
                                            This code expires in 10 minutes.
                                        </strong>

                                        For your security, request a new
                                        password reset code if this one
                                        expires.

                                    </p>

                                </td>

                            </tr>

                        </table>


                        <!-- =================================================
                             SECURITY NOTICE
                             ================================================= -->

                        <p
                            style="
                                margin: 24px 0 0;
                                font-size: 12px;
                                line-height: 19px;
                                color: #6b7280;
                            "
                        >
                            Never share this verification code with anyone.
                            KNOWURLOCAL will never ask you to provide your
                            verification code through email, messages, or
                            other communication channels.
                        </p>


                        <!-- =================================================
                             UNEXPECTED REQUEST
                             ================================================= -->

                        <p
                            style="
                                margin: 12px 0 0;
                                font-size: 12px;
                                line-height: 19px;
                                color: #6b7280;
                            "
                        >
                            If you did not request a password reset,
                            you can safely ignore this email.
                        </p>

                    </td>

                </tr>


                <!-- =================================================
                     FOOTER
                     ================================================= -->

                <tr>

                    <td
                        align="center"
                        style="
                            padding: 22px 24px;
                            background-color: #f8fafc;
                            border-top: 1px solid #edf0f4;
                        "
                    >

                        <div
                            style="
                                font-size: 11px;
                                line-height: 17px;
                                color: #8a94a6;
                            "
                        >
                            KNOWURLOCAL
                        </div>


                        <div
                            style="
                                margin-top: 3px;
                                font-size: 10px;
                                line-height: 16px;
                                color: #9aa3b2;
                            "
                        >
                            San Jose, Occidental Mindoro
                        </div>


                        <div
                            style="
                                margin-top: 8px;
                                font-size: 10px;
                                line-height: 15px;
                                color: #a1a9b5;
                            "
                        >
                            This is an automated message. Please do not reply.
                        </div>

                    </td>

                </tr>


            </table>

        </td>

    </tr>

</table>


</body>

</html>