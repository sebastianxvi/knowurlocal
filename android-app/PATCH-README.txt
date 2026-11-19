KNOWURLOCAL native Android install-banner fix

Files in this patch:
- app/src/main/java/ph/knowurlocal/app/MainActivity.kt
- ../public/jsfiles/public_user/app-install-banner.js (Laravel public asset)

Apply the Kotlin file inside android-app as usual. The JavaScript file belongs to
the Laravel project at public/jsfiles/public_user/app-install-banner.js, not inside
the android-app directory. Deploy that asset with the Laravel application if you
want the browser-side guard as well.

The native Kotlin safeguard hides the install banner after each WebView page load
when the native User-Agent marker is present. The JavaScript safeguard hides it
immediately when that same marker is detected. The normal browser/PWA install
flow remains unchanged because it does not contain the native marker.

Build from android-app:
  .\gradlew.bat clean :app:assembleDebug
