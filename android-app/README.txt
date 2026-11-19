KNOWURLOCAL Android source fix

Based on the uploaded android-app.zip.

1. Replace:
   app/src/main/java/ph/knowurlocal/app/MainActivity.kt
   with the MainActivity.kt in this patch.

2. In the project, rename the duplicate Java activity so Gradle no longer compiles it:
   app/src/main/java/ph/knowurlocal/app/MainActivity.java
   -> app/src/main/java/ph/knowurlocal/app/MainActivity.java.disabled

   This preserves the old Java source as a backup. Do not leave both
   MainActivity.java and MainActivity.kt active in the same package.

3. From the android-app project root, run:
   .\gradlew.bat clean :app:assembleDebug

Changes in MainActivity.kt:
- Imports SslError from android.net.http.SslError.
- Uses Array<String> for Activity.onRequestPermissionsResult.

No other WebView behavior was intentionally changed.
