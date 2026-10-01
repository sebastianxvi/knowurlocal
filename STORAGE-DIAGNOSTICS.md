# Temporary private-storage diagnostics

This patch adds structured, credential-safe logging around the two private-bucket upload paths:

- Admin support-response attachments (`support_response_attachment`)
- FAQ response attachments (`faq_response_attachment`)

The logs report only whether the resolved private-disk access key and secret are present. They do **not** log credential values. They also report the configured driver, region, bucket, endpoint hostname, path-style setting, app environment, upload size/MIME type, and (on failure) exception class/message.

## How to use

1. Review the changes and commit/deploy them to the Production branch.
2. In Vercel, open the current Production deployment's runtime logs.
3. Attempt one small upload in the admin support-response flow, then one FAQ attachment upload.
4. Search the logs for `Private storage upload starting.` and `Private storage upload failed.`.
5. Check `access_key_present` and `secret_key_present` first. If either is false, investigate Production environment-variable propagation/configuration. If both are true, use the exception class/message and endpoint/region/bucket context to investigate S3 authentication or request configuration.

Do not copy or share environment-variable values. Once the issue is resolved, these temporary logs can be removed or reduced to failure-only logging.
