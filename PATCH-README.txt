KNOWURLOCAL Android WebView safe-area fix

Based on the latest uploaded knowurlocal-main (5).zip.

Problem:
The native Activity was applying Android system-bar insets as WebView margins,
while the website CSS also applies env(safe-area-inset-top/bottom). This
double-applies the safe area and creates an empty strip above the map/navbar.
It can also reduce the usable viewport unnecessarily.

Patch:
Replace:
android-app/app/src/main/java/ph/knowurlocal/app/MainActivity.kt

with the file at the same path in this patch.

The WebView now extends edge-to-edge vertically. The website CSS remains
responsible for status/navigation safe areas. The WebView still resizes above
the software keyboard using the IME bottom inset, and still protects against
horizontal display cutouts.

No Laravel/public website files need changing for this particular fix.
The existing native install-banner hiding logic is preserved.

After replacing the file, from the android-app directory run:
  .\gradlew.bat clean :app:assembleDebug

Then install using USB debugging:
  .\gradlew.bat :app:installDebug

Or manually install:
  app\build\outputs\apk\debug\app-debug.apk

This is an update to the existing app as long as applicationId and signing
certificate are unchanged.

Validation note:
The patch was checked against the uploaded source and the exact inset-handling
block. A device build and physical-device verification have not been performed
here; please test the map page and a focused text field on the Infinix device.
