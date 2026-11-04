{{-- Shared KNOWURLOCAL legal text. Keep this source in sync for registration modal and standalone legal routes. --}}
<section class="legal-document" id="legal-terms-document" data-legal-document="terms" aria-labelledby="legal-terms-title" @if(($legalStandaloneDocument ?? null) === 'privacy') hidden @endif>
    <div class="legal-document-intro">
        <span class="legal-eyebrow">KNOWURLOCAL · PLATFORM GUIDELINES</span>
        <h2 id="legal-terms-title">Terms of Service</h2>
        <p>Effective November 5, 2026 · Applies to public users and invited administrators</p>
        <p>These terms explain the conditions for using KNOWURLOCAL, a web-based mapping and information system for discovering local government agencies and services, submitting agency evaluations, and contacting agencies through support inquiries.</p>
    </div>

    <h3>1. Agreement and eligibility</h3>
    <p>By creating or using an account, you agree to these Terms and the Privacy Policy. If you do not agree, do not create or use an account. Administrative accounts are available only through a valid invitation and are limited to the role and agency assigned to that account.</p>

    <h3>2. Account information and security</h3>
    <p>You must provide accurate account information, keep your sign-in credentials confidential, and promptly report suspected unauthorized access through the system administrator. You are responsible for activity performed through your account, except where applicable law provides otherwise. Do not share, transfer, or use another person's account.</p>

    <h3>3. Public evaluations and community content</h3>
    <p>When submitting an agency evaluation, rating, comment, report, or helpful vote, you must provide honest, relevant, and lawful content based on your experience. Do not post threats, harassment, discriminatory content, personal or confidential information about others, spam, fabricated claims, or content that infringes another person's rights.</p>
    <p>Public evaluations may be displayed with a masked reviewer name, rating, comment, and date. The platform does not display the evaluator's account profile picture alongside public evaluations. Masking a name is a privacy measure, not a guarantee that a person cannot be inferred from the details they choose to include.</p>
    <p>KNOWURLOCAL may review reports and moderate, hide, or remove content that violates these Terms or applicable law. An agency response does not necessarily represent the views of the platform or other agencies.</p>

    <h3>4. Agency inquiries and attachments</h3>
    <p>Use support inquiries to ask relevant questions of the selected agency. Keep messages respectful and avoid including passwords, financial account credentials, sensitive personal information, or information about other people unless it is necessary and appropriate for your request.</p>
    <p>Inquiry content and attachments are made available to authorized administrators responsible for handling the request, including the selected agency and authorized system administrators. Do not upload unlawful, malicious, or unrelated files. Do not assume that an inquiry or attachment is private from the agency you contact.</p>

    <h3>5. Administrative access and responsibilities</h3>
    <p>Administrators must use system data only for authorized KNOWURLOCAL duties. They must protect account and inquiry information, avoid unnecessary access or disclosure, and not copy, publish, or use personal data for unrelated purposes. Access may be limited, suspended, or revoked when an invitation or authorization ends, or where misuse is suspected.</p>

    <h3>6. Acceptable use</h3>
    <p>You may not attempt to bypass access controls, interfere with the service, introduce malicious code, scrape or harvest personal information, impersonate another person or agency, probe systems without authorization, or use the platform for unlawful activity. Do not attempt to access records outside your permissions.</p>

    <h3>7. Platform information and availability</h3>
    <p>Agency details, service descriptions, map locations, office hours, and other information may be provided or maintained by agencies and other sources. Information can change or contain errors; verify important requirements directly with the relevant agency. KNOWURLOCAL is an information and communication platform and does not guarantee an agency's service, decision, or response.</p>
    <p>The platform may be updated, temporarily unavailable, or have features changed for maintenance, security, or operational reasons. We will take reasonable steps to maintain the service but cannot promise uninterrupted availability.</p>

    <h3>8. Content and intellectual property</h3>
    <p>You retain rights you hold in content you submit. You grant the platform permission to store, display, and process that content as needed to operate the features you use, moderate submissions, and provide the service. Platform branding, software, and design remain subject to their applicable rights. Do not use them without authorization.</p>

    <h3>9. Suspension and termination</h3>
    <p>Accounts or access may be suspended or terminated for violations, security concerns, misuse, or when access is no longer authorized. Where appropriate, users may contact the system administrator to raise a concern about an account or moderation action.</p>

    <h3>10. Changes and governing requirements</h3>
    <p>These Terms may be revised as the system or its requirements change. The current version and effective date will be made available through the platform. Use of the platform after a revision takes effect constitutes acceptance where permitted by law. These Terms are subject to applicable laws of the Philippines.</p>

    <h3>11. Contact and concerns</h3>
    <p>For questions about these Terms, account access, or platform conduct, contact the KNOWURLOCAL project or system administrator through the official contact channel provided by the project. Do not send passwords or OTP codes in a support message.</p>
</section>

<section class="legal-document" id="legal-privacy-document" data-legal-document="privacy" aria-labelledby="legal-privacy-title" @if(($legalStandaloneDocument ?? null) !== 'privacy') hidden @endif>
    <div class="legal-document-intro">
        <span class="legal-eyebrow">KNOWURLOCAL · DATA AND PRIVACY</span>
        <h2 id="legal-privacy-title">Privacy Policy</h2>
        <p>Effective November 5, 2026 · How information is handled</p>
        <p>This policy describes the information KNOWURLOCAL processes to provide its web-based mapping, agency information, evaluation, and support-ticket features. It is intended to reflect the system's current functions and should be reviewed by the project’s responsible privacy/legal officer before formal adoption.</p>
    </div>

    <h3>1. Information processed</h3>
    <ul>
        <li><strong>Account information:</strong> first and last name, email address, account role, assigned agency where applicable, account status, and email-verification state.</li>
        <li><strong>Authentication and security:</strong> password hashes (not plain-text passwords), OTP verification records, session and sign-in information, and security-related records.</li>
        <li><strong>Profile information:</strong> profile picture uploaded by a public user, when provided.</li>
        <li><strong>Evaluations:</strong> agency ratings, comments, dates, helpful votes, reports, and related moderation or response records.</li>
        <li><strong>Support inquiries:</strong> questions, agency selection, follow-up messages, responses, status and read markers, and files or images attached to an inquiry or response.</li>
        <li><strong>System usage:</strong> chatbot questions and responses, FAQ feedback, notifications, and activity or audit records used to support the system and its administration.</li>
        <li><strong>Technical information:</strong> IP address, device/browser details, and session information where captured by system logs or security functions.</li>
    </ul>

    <h3>2. How information is used</h3>
    <ul>
        <li>Create, authenticate, maintain, and secure accounts, including email verification and password changes.</li>
        <li>Display agency information, map results, evaluations, and public feedback.</li>
        <li>Route support inquiries to the selected agency and allow authorized staff to respond, follow up, and manage tickets.</li>
        <li>Provide chatbot/FAQ assistance, notifications, and user-requested account features.</li>
        <li>Detect misuse, investigate reports, maintain audit trails, troubleshoot errors, and improve system reliability.</li>
        <li>Meet applicable legal, regulatory, and legitimate public-service administration requirements.</li>
    </ul>

    <h3>3. Public display and agency access</h3>
    <p>Public agency evaluations are displayed with a masked reviewer name, rating, comment, and relevant date. The evaluator's profile picture and full account name are not intentionally displayed beside public evaluations. Because a comment may contain identifying details, users should avoid including information that could reveal their identity.</p>
    <p>When a user submits a support inquiry to an agency, the selected agency's authorized staff and authorized system administrators may access the inquiry, the user's identifying account information, profile picture, messages, and attached files as needed to handle the request. Administrators may also access information within their assigned system permissions. Access is role-based and intended for service delivery, moderation, and system administration.</p>

    <h3>4. Sharing and service providers</h3>
    <p>KNOWURLOCAL does not sell personal information. Information may be disclosed to the agency a user contacts, authorized system administrators, and service providers needed to operate the platform (such as hosting, email delivery, or storage), subject to their role and applicable safeguards. Information may also be disclosed when required by law or necessary to address security, fraud, or legal claims.</p>

    <h3>5. Email, OTPs, passwords, and sessions</h3>
    <p>Email addresses are used for account identity and verification and for relevant account messages, including OTPs for registration, password recovery, or password changes. OTPs are time-limited and must not be shared. Passwords are stored using password hashing. Session cookies are used to maintain sign-in state and protect system functions; browser settings may affect their operation.</p>

    <h3>6. Retention</h3>
    <p>Information is retained for as long as needed to provide the service, maintain account and inquiry history, support security and audit requirements, resolve disputes, and comply with applicable obligations. Retention periods may differ by record type. When information is no longer needed, it may be deleted, anonymized, or securely disposed of where appropriate and technically supported. A deletion request may be subject to lawful or operational retention requirements.</p>

    <h3>7. Security</h3>
    <p>The project uses technical and organizational safeguards appropriate to its functions, which may include password hashing, verification codes, access controls, session protections, and activity records. No internet-based service can guarantee absolute security. Users and administrators should protect their credentials and report suspected compromise promptly.</p>

    <h3>8. Your privacy rights and requests</h3>
    <p>Subject to applicable law, you may request access to or correction of personal information, ask about its processing, object to certain processing, or request deletion or other available remedies. You may submit a request through the KNOWURLOCAL project or system administrator using the project's official contact channel. We may need to verify your identity and explain if a request cannot be fully fulfilled because of a legal, security, or operational requirement.</p>
    <p>This policy is intended to be interpreted consistently with the Philippine Data Privacy Act of 2012 (Republic Act No. 10173) and its implementing rules, where applicable. The responsible project office should confirm the appropriate lawful basis, retention schedule, and privacy contact before publication.</p>

    <h3>9. Children and sensitive information</h3>
    <p>Do not submit sensitive personal information or personal information about another person unless it is necessary, lawful, and appropriate for the feature. If a user is not legally able to consent independently, use of the service should be guided by the applicable requirements and responsible guardian or authority.</p>

    <h3>10. Updates and contact</h3>
    <p>This policy may be updated when the system or its data practices change. The effective date will be revised when material changes are made. For privacy questions or requests, contact the KNOWURLOCAL project or system administrator through the official contact channel provided by the project.</p>
</section>
