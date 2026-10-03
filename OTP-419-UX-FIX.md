# OTP 419 UX Fix

## Problem

The email verification page relied on the CSRF token embedded when `/otp` was rendered. Laravel stores that token in the browser session. If the anonymous session became idle long enough to expire while the user was checking their email, the OTP submission could fail with a raw `419 Page Expired` response even though the OTP record itself was still the source of truth.

## Fix

The OTP page now has a small, OTP-specific session keep-alive flow:

- `GET /otp/session` refreshes the current browser session and returns its current CSRF token.
- The page refreshes that token every 60 seconds while the OTP page is open.
- The token is refreshed again immediately before OTP verification.
- The resend request also refreshes the token immediately before posting.
- The session lifetime is not changed globally.
- The OTP remains independently time-limited by the `EmailVerification` database record.
- The keep-alive response is marked `Cache-Control: no-store, private`.
- The keep-alive endpoint is rate-limited to 60 requests per minute per limiter key.

This keeps normal email-checking delays from becoming a confusing 419 while preserving Laravel's CSRF protection on the actual POST requests.
