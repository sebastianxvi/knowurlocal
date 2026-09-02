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

    <title>KNOWURLOCAL Admin Account Approved</title>
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
            style="padding: 40px 16px;"
        >

            <!-- MAIN EMAIL CARD -->
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

                <!-- BRAND HEADER -->
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
                            ADMINISTRATION PORTAL
                        </div>
                    </td>
                </tr>

                <!-- CONTENT -->
                <tr>
                    <td
                        style="
                            padding: 38px 42px 34px;
                        "
                    >

                        <!-- EYEBROW -->
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
                            Administrator Account
                        </div>

                        <!-- TITLE -->
                        <h1
                            style="
                                margin: 8px 0 14px;
                                font-size: 27px;
                                line-height: 35px;
                                font-weight: 700;
                                color: #111827;
                            "
                        >
                            Your admin account has been approved
                        </h1>

                        <!-- INTRODUCTION -->
                        <p
                            style="
                                margin: 0 0 18px;
                                font-size: 14px;
                                line-height: 23px;
                                color: #4b5563;
                            "
                        >
                            Hello {{ $firstName }},
                        </p>

                        <p
                            style="
                                margin: 0 0 18px;
                                font-size: 14px;
                                line-height: 23px;
                                color: #4b5563;
                            "
                        >
                            Your administrator account for the
                            KNOWURLOCAL administration portal has been
                            reviewed and approved by a Super Admin.
                        </p>

                        <!-- ACCOUNT DETAILS -->
                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            role="presentation"
                            style="
                                width: 100%;
                                margin: 22px 0;
                                background-color: #f7f9fc;
                                border: 1px solid #e6ebf2;
                                border-radius: 12px;
                            "
                        >
                            <tr>
                                <td style="padding: 20px;">

                                    <div
                                        style="
                                            margin-bottom: 12px;
                                            font-size: 13px;
                                            font-weight: 700;
                                            color: #1f2937;
                                        "
                                    >
                                        Account details
                                    </div>

                                    <div
                                        style="
                                            margin-bottom: 8px;
                                            font-size: 13px;
                                            line-height: 20px;
                                            color: #596579;
                                        "
                                    >
                                        <strong>Email:</strong>
                                        {{ $email }}
                                    </div>

                                    <div
                                        style="
                                            font-size: 13px;
                                            line-height: 20px;
                                            color: #596579;
                                        "
                                    >
                                        <strong>Status:</strong>
                                        Active
                                    </div>

                                </td>
                            </tr>
                        </table>

                        <!-- NEXT STEP -->
                        <p
                            style="
                                margin: 0 0 24px;
                                font-size: 14px;
                                line-height: 23px;
                                color: #4b5563;
                            "
                        >
                            You can now sign in to the KNOWURLOCAL
                            Administration Portal using the credentials
                            you established during registration.
                        </p>

                        <!-- CTA -->
                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            role="presentation"
                        >
                            <tr>
                                <td align="center">

                                    <a
                                        href="{{ $loginLink }}"
                                        style="
                                            display: inline-block;
                                            min-width: 230px;
                                            padding: 14px 28px;
                                            background-color: #3568c8;
                                            color: #ffffff;
                                            text-decoration: none;
                                            text-align: center;
                                            font-size: 14px;
                                            line-height: 20px;
                                            font-weight: 700;
                                            border-radius: 8px;
                                        "
                                    >
                                        Go to Administration Portal
                                    </a>

                                </td>
                            </tr>
                        </table>

                        <!-- SECURITY NOTICE -->
                        <p
                            style="
                                margin: 24px 0 0;
                                font-size: 12px;
                                line-height: 19px;
                                color: #6b7280;
                            "
                        >
                            If you did not expect this approval,
                            please contact the KNOWURLOCAL system
                            administrator.
                        </p>

                    </td>
                </tr>

                <!-- FOOTER -->
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