# KNOWURLOCAL Android application

This is a native Android application shell for the existing KNOWURLOCAL Laravel web system. It loads the production HTTPS application at `https://knowurlocal.site/`; authentication, agency data, evaluations, inquiries, and server-side business logic remain in Laravel.

## Project identity

- App name: `KNOWURLOCAL`
- Application ID: `ph.knowurlocal.app`
- Start URL: `https://knowurlocal.site/`
- Minimum Android: API 23 (Android 6.0)
- Compile / target SDK: API 36
- JDK: 17
- Version: `1` / `1.0.0`

Do not change the application ID after publishing. Increment `versionCode` for every published Android release.

## Open and build

1. Install Android Studio and the Android SDK Platform 36 / Build Tools supported by the installed Android Gradle Plugin.
2. Install JDK 17 (Android Studio's bundled JBR is suitable if it is JDK 17).
3. Open this `android-app` directory in Android Studio and allow Gradle sync to download the declared plugins and dependencies.
4. Connect a physical Android device with USB debugging enabled, or create an emulator.
5. Run the `app` configuration for a debug build.

From a terminal with Gradle 8.13 available and `ANDROID_HOME` configured, generate the standard Gradle launcher once if your checkout does not contain the wrapper JAR/scripts, then build:

```bash
gradle wrapper --gradle-version 8.13
gradle assembleDebug
```

Debug APK output:

```text
app/build/outputs/apk/debug/app-debug.apk
```

The debug build uses the package suffix `.debug` so it can coexist with a release installation.

## Release APK and Google Play bundle

The release APK and Play App Bundle must be signed. Create a protected release keystore outside the repository and provide these values through your local `~/.gradle/gradle.properties` or environment variables:

```properties
KYL_RELEASE_STORE_FILE=/absolute/path/to/knowurlocal-release.jks
KYL_RELEASE_STORE_PASSWORD=your-keystore-password
KYL_RELEASE_KEY_ALIAS=knowurlocal
KYL_RELEASE_KEY_PASSWORD=your-key-password
```

Never commit the keystore, passwords, or signing files. Keep encrypted offline backups. Do not send the keystore or passwords in chat or upload them with the source archive.

Build the website-distribution APK:

```bash
gradle assembleRelease
```

Output:

```text
app/build/outputs/apk/release/app-release.apk
```

Build the Google Play App Bundle:

```bash
gradle bundleRelease
```

Output:

```text
app/build/outputs/bundle/release/app-release.aab
```

**Signing decision before first public release:** the direct-download APK and the Play-delivered app must have compatible signing identity if users are expected to update seamlessly or move between distribution channels. Configure Google Play App Signing to use the same app-signing certificate as the directly distributed APK (rather than accepting an unrelated Play-generated app-signing key). Verify the Play Console's key enrollment flow before publishing. If using a separate Play upload key, keep it distinct from the app-signing key and configure the AAB upload process accordingly. Never lose the app-signing key.

## Website APK distribution integration

The Laravel website now exposes:

- `GET /app/android/download/status` — reports whether the APK artifact is present.
- `GET /app/android/download` — downloads the APK only when the artifact exists.

Place the **signed release APK** at:

```text
public/downloads/knowurlocal.apk
```

The existing site-wide Install App banner checks this endpoint on Android. When the APK is present, the button becomes **Download App** and downloads the APK. When it is absent, the button falls back to the existing browser/PWA installation behavior and instructions. This prevents a broken download link before the first release is ready.

After copying a verified APK into that location, deploy the file and the accompanying Laravel/Blade/JavaScript changes together. Verify the status endpoint returns `available: true`, the download response has `Content-Type: application/vnd.android.package-archive`, and the downloaded APK installs on supported physical devices.

## Platform-aware website installation flow

The shared website install banner uses platform-specific distribution priorities:

- **Android browser:** checks `GET /app/android/download/status` without caching. If the signed APK exists, the primary action downloads `public/downloads/knowurlocal.apk`. If the artifact is missing or the status check fails, the website falls back to the browser's PWA install prompt; if no prompt is available, it displays Android-specific Home Screen / install instructions.
- **iPhone and iPad:** does not offer the Android APK or attempt a one-tap PWA prompt. It displays instructions for adding the website to the Home Screen through Safari's Share menu. This is a website shortcut / supported web-app experience, not a native iOS application.
- **Desktop and laptop:** uses the browser's PWA install prompt when available, with browser-specific manual installation guidance as a fallback. Safari on macOS receives Add to Dock instructions when the prompt is unavailable.

The signed release APK is retained at `android-app/app/release/app-release.apk` and copied to `public/downloads/knowurlocal.apk` for website distribution. The Laravel download-status route checks the latter path. When updating the app, build a new signed release with the same signing identity, increment `versionCode`, and replace both release artifacts. Do not commit signing keys or credentials. If the APK is deployed outside GitHub, upload the file to the production server at `public/downloads/knowurlocal.apk`.

## Implemented Android behavior

- Loads only the trusted KNOWURLOCAL HTTPS origin inside the WebView.
- Keeps Laravel's normal browser session cookies in the app WebView.
- Enables JavaScript and DOM storage required by the existing web UI.
- Uses system browser/apps for external links, phone links, email links, and map links.
- Supports file selection for web forms and attachment uploads.
- Requests Android location permission only when the trusted site requests geolocation.
- Uses Android DownloadManager for files downloaded by the website.
- Handles Android Back navigation through WebView history.
- Cancels invalid TLS certificates; it never bypasses SSL errors.
- Blocks cleartext HTTP and mixed content.
- Shows a retry page when the main site cannot be reached.

## Not yet included / required testing

- This project is source code, not a prebuilt or physically verified APK.
- Native push notifications require a Firebase Cloud Messaging integration and server-side delivery; the existing in-site notifications and active-page realtime behavior are not a substitute for background push.
- Camera capture from `<input capture>` needs device testing and may need a dedicated camera intent flow. The file picker is implemented for existing upload controls.
- Test login, logout, session expiry, OTP, profile upload, inquiry attachments, geolocation, Leaflet maps, Reverb/Ably realtime, downloads, keyboard resizing, Android Back behavior, and offline/reconnect behavior on physical Android devices.
- Test current Android versions and at least one lower supported version before release.
- Verify production hosting serves the APK download response correctly and that the release signing identity remains unchanged across updates.
