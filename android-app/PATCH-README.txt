KNOWURLOCAL Android WebView responsive-layout patch

Files are relative to the android-app folder.

1. app/src/main/java/ph/knowurlocal/app/MainActivity.kt
   - Keeps the existing WebView, authentication cookies, trusted-host navigation,
     SSL rejection, file picker, downloads, and location permission behavior.
   - Places the WebView inside a FrameLayout and applies system-bar/display-cutout
     insets as layout margins.
   - Includes IME (keyboard) bottom insets so the WebView viewport shrinks above
     the keyboard instead of leaving the input area underneath it.
   - Explicitly uses the device-width viewport and a 100% text zoom.

2. app/src/main/res/drawable-nodpi/ic_launcher_foreground.png
   - Adds transparent breathing room to the adaptive launcher foreground so
     Android launcher masking does not make the logo appear oversized/cropped.
   - Keeps the existing logo artwork and white adaptive-icon background.

Apply by extracting this patch into android-app and replacing the matching files.
Do not replace the rest of the Android project. Then run:
  .\\gradlew.bat clean :app:assembleDebug

This patch has not been built on a physical Infinix device. Verify keyboard,
status-bar, map, and launcher-icon behavior on the phone after installation.
