package ph.knowurlocal.app

import android.Manifest
import android.app.DownloadManager
import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.os.Build
import android.os.Environment
import android.webkit.CookieManager
import android.webkit.GeolocationPermissions
import android.webkit.PermissionRequest
import android.webkit.SslErrorHandler
import android.webkit.URLUtil
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.net.http.SslError
import android.webkit.ValueCallback
import android.widget.Toast
import android.widget.FrameLayout
import android.view.ViewGroup
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.ComponentActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat

class MainActivity : ComponentActivity() {
    private lateinit var webView: WebView
    private var fileChooserCallback: ValueCallback<Array<Uri>>? = null
    private var pendingGeoOrigin: String? = null
    private var pendingGeoCallback: GeolocationPermissions.Callback? = null
    private var pendingDownload: (() -> Unit)? = null

    private val filePicker = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
        val callback = fileChooserCallback ?: return@registerForActivityResult
        val data = result.data
        val uris = if (result.resultCode == RESULT_OK) {
            when {
                data?.clipData != null -> {
                    val clip = data.clipData!!
                    Array(clip.itemCount) { index -> clip.getItemAt(index).uri }
                }
                data?.data != null -> arrayOf(data.data!!)
                else -> null
            }
        } else null
        callback.onReceiveValue(uris)
        fileChooserCallback = null
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Draw the native window edge-to-edge, but keep the WebView's actual
        // layout viewport inside system bars and above the on-screen keyboard.
        WindowCompat.setDecorFitsSystemWindows(window, false)
        window.statusBarColor = android.graphics.Color.TRANSPARENT
        window.navigationBarColor = android.graphics.Color.TRANSPARENT

        val root = FrameLayout(this).apply {
            setBackgroundColor(android.graphics.Color.rgb(246, 247, 250))
        }

        webView = WebView(this).apply {
            setBackgroundColor(android.graphics.Color.rgb(246, 247, 250))
        }
        root.addView(
            webView,
            FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.MATCH_PARENT
            )
        )

        WindowCompat.getInsetsController(window, root).apply {
            isAppearanceLightStatusBars = true
            isAppearanceLightNavigationBars = true
        }

        ViewCompat.setOnApplyWindowInsetsListener(root) { _, windowInsets ->
            val safeInsets = windowInsets.getInsets(
                WindowInsetsCompat.Type.systemBars() or WindowInsetsCompat.Type.displayCutout()
            )
            val imeInsets = windowInsets.getInsets(WindowInsetsCompat.Type.ime())
            val bottomInset = maxOf(safeInsets.bottom, imeInsets.bottom)

            val params = webView.layoutParams as FrameLayout.LayoutParams
            if (
                params.leftMargin != safeInsets.left ||
                params.topMargin != safeInsets.top ||
                params.rightMargin != safeInsets.right ||
                params.bottomMargin != bottomInset
            ) {
                params.setMargins(
                    safeInsets.left,
                    safeInsets.top,
                    safeInsets.right,
                    bottomInset
                )
                webView.layoutParams = params
            }
            windowInsets
        }

        setContentView(root)
        ViewCompat.requestApplyInsets(root)
        configureWebView()

        if (savedInstanceState == null) {
            webView.loadUrl(BuildConfig.START_URL)
        } else {
            webView.restoreState(savedInstanceState)
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (this@MainActivity::webView.isInitialized && webView.canGoBack()) {
                    webView.goBack()
                } else {
                    finish()
                }
            }
        })
    }

    @Suppress("SetJavaScriptEnabled")
    private fun configureWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            loadsImagesAutomatically = true
            javaScriptCanOpenWindowsAutomatically = false
            setSupportMultipleWindows(false)
            allowFileAccess = false
            allowContentAccess = true
            mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
            cacheMode = WebSettings.LOAD_DEFAULT
            mediaPlaybackRequiresUserGesture = true
            // Respect each page's device-width viewport instead of letting
            // WebView use a desktop-style layout viewport.
            useWideViewPort = true
            loadWithOverviewMode = false
            textZoom = 100
            setSupportZoom(false)
            builtInZoomControls = false
            displayZoomControls = false
            userAgentString = userAgentString + " KNOWURLOCAL-AndroidApp/1.0"
        }
        CookieManager.getInstance().apply {
            setAcceptCookie(true)
            setAcceptThirdPartyCookies(webView, false)
        }
        webView.isVerticalScrollBarEnabled = false
        webView.isHorizontalScrollBarEnabled = false
        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                val uri = request.url
                if (isTrustedWebUrl(uri)) return false
                openExternal(uri)
                return true
            }

            override fun onReceivedError(
                view: WebView,
                request: WebResourceRequest,
                error: WebResourceError
            ) {
                if (request.isForMainFrame) showConnectionError()
            }

            override fun onReceivedSslError(view: WebView, handler: SslErrorHandler, error: SslError) {
                // Never bypass certificate validation for the production application.
                handler.cancel()
                showConnectionError()
            }

            override fun onPageFinished(view: WebView, url: String) {
                super.onPageFinished(view, url)
                // Hide the website's browser/PWA install banner in the native app.
                // This is a native-side safeguard in addition to the User-Agent check
                // in app-install-banner.js, so a stale or delayed page script cannot
                // expose an install prompt inside the already-installed Android app.
                view.evaluateJavascript(
                    """
                    (() => {
                        if (!/KNOWURLOCAL-AndroidApp/i.test(navigator.userAgent || '')) return;
                        document.documentElement.classList.add('kyl-native-android-app');
                        let style = document.getElementById('kyl-native-app-install-guard');
                        if (!style) {
                            style = document.createElement('style');
                            style.id = 'kyl-native-app-install-guard';
                            style.textContent = '.kyl-app-install-banner, [data-app-install-banner] { display: none !important; }';
                            (document.head || document.documentElement).appendChild(style);
                        }
                        const banner = document.querySelector('[data-app-install-banner]');
                        if (banner) {
                            banner.hidden = true;
                            banner.setAttribute('aria-hidden', 'true');
                        }
                        document.documentElement.style.setProperty('--kyl-install-banner-height', '0px');
                    })();
                    """.trimIndent(),
                    null
                )
            }
        }
        webView.webChromeClient = object : WebChromeClient() {
            override fun onShowFileChooser(
                webView: WebView,
                filePathCallback: ValueCallback<Array<Uri>>,
                fileChooserParams: FileChooserParams
            ): Boolean {
                fileChooserCallback?.onReceiveValue(null)
                fileChooserCallback = filePathCallback
                val intent = try {
                    fileChooserParams.createIntent().apply {
                        addCategory(Intent.CATEGORY_OPENABLE)
                        val acceptedTypes = fileChooserParams.acceptTypes.filter { it.isNotBlank() }.toTypedArray()
                        type = if (acceptedTypes.isEmpty()) "*/*" else "*/*"
                        if (acceptedTypes.isNotEmpty()) putExtra(Intent.EXTRA_MIME_TYPES, acceptedTypes)
                        putExtra(Intent.EXTRA_ALLOW_MULTIPLE, fileChooserParams.mode == FileChooserParams.MODE_OPEN_MULTIPLE)
                    }
                } catch (_: Exception) {
                    Intent(Intent.ACTION_GET_CONTENT).apply {
                        addCategory(Intent.CATEGORY_OPENABLE)
                        type = "*/*"
                        putExtra(Intent.EXTRA_ALLOW_MULTIPLE, fileChooserParams.mode == FileChooserParams.MODE_OPEN_MULTIPLE)
                    }
                }
                return try {
                    filePicker.launch(Intent.createChooser(intent, "Choose a file"))
                    true
                } catch (_: ActivityNotFoundException) {
                    fileChooserCallback = null
                    filePathCallback.onReceiveValue(null)
                    Toast.makeText(this@MainActivity, "No file picker is available on this device.", Toast.LENGTH_LONG).show()
                    false
                }
            }

            override fun onGeolocationPermissionsShowPrompt(origin: String, callback: GeolocationPermissions.Callback) {
                val host = Uri.parse(origin).host
                if (host != TRUSTED_HOST) {
                    callback.invoke(origin, false, false)
                    return
                }
                val fineGranted = ContextCompat.checkSelfPermission(
                    this@MainActivity, Manifest.permission.ACCESS_FINE_LOCATION
                ) == PackageManager.PERMISSION_GRANTED
                val coarseGranted = ContextCompat.checkSelfPermission(
                    this@MainActivity, Manifest.permission.ACCESS_COARSE_LOCATION
                ) == PackageManager.PERMISSION_GRANTED
                if (fineGranted || coarseGranted) {
                    callback.invoke(origin, true, false)
                } else {
                    pendingGeoOrigin = origin
                    pendingGeoCallback = callback
                    requestPermissions(
                        arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION),
                        LOCATION_PERMISSION_REQUEST
                    )
                }
            }

            override fun onPermissionRequest(request: PermissionRequest) {
                // WebRTC/camera/microphone access is not granted implicitly.
                request.deny()
            }
        }
        webView.setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
            enqueueDownload(url, userAgent, contentDisposition, mimeType)
        }
    }

    private fun isTrustedWebUrl(uri: Uri): Boolean {
        return uri.scheme == "https" && (uri.host == TRUSTED_HOST || uri.host == "www.$TRUSTED_HOST")
    }

    private fun openExternal(uri: Uri) {
        val scheme = uri.scheme?.lowercase()
        val intent = when (scheme) {
            "tel" -> Intent(Intent.ACTION_DIAL, uri)
            "mailto" -> Intent(Intent.ACTION_SENDTO, uri)
            "geo" -> Intent(Intent.ACTION_VIEW, uri)
            "https", "http" -> Intent(Intent.ACTION_VIEW, uri)
            else -> null
        }
        if (intent == null) {
            Toast.makeText(this, "This link cannot be opened in the app.", Toast.LENGTH_SHORT).show()
            return
        }
        try {
            startActivity(intent)
        } catch (_: ActivityNotFoundException) {
            Toast.makeText(this, "No app is available to open this link.", Toast.LENGTH_LONG).show()
        }
    }

    private fun enqueueDownload(url: String, userAgent: String, contentDisposition: String, mimeType: String) {
        try {
            val request = DownloadManager.Request(Uri.parse(url)).apply {
                setMimeType(mimeType)
                addRequestHeader("User-Agent", userAgent)
                CookieManager.getInstance().getCookie(url)?.let { addRequestHeader("Cookie", it) }
                setTitle(URLUtil.guessFileName(url, contentDisposition, mimeType))
                setDescription("Downloading from KNOWURLOCAL")
                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                setDestinationInExternalPublicDir(
                    Environment.DIRECTORY_DOWNLOADS,
                    URLUtil.guessFileName(url, contentDisposition, mimeType)
                )
                setAllowedOverMetered(true)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) setAllowedOverRoaming(false)
            }
            val startDownload = {
                val manager = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
                manager.enqueue(request)
                Toast.makeText(this, "Download started. Check your Downloads folder.", Toast.LENGTH_LONG).show()
            }
            if (Build.VERSION.SDK_INT <= Build.VERSION_CODES.P &&
                ContextCompat.checkSelfPermission(this, Manifest.permission.WRITE_EXTERNAL_STORAGE) != PackageManager.PERMISSION_GRANTED
            ) {
                pendingDownload = startDownload
                requestPermissions(arrayOf(Manifest.permission.WRITE_EXTERNAL_STORAGE), DOWNLOAD_PERMISSION_REQUEST)
            } else {
                startDownload()
            }
        } catch (_: Exception) {
            openExternal(Uri.parse(url))
        }
    }

    private fun showConnectionError() {
        val html = """
            <!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">
            <style>body{font-family: sans-serif;background:#f6f7fa;color:#172033;display:grid;place-items:center;min-height:90vh;margin:0;padding:24px;text-align:center}button{background:#2563eb;color:white;border:0;border-radius:12px;padding:13px 22px;font-size:16px}</style>
            </head><body><main><h2>Unable to connect</h2><p>Check your internet connection, then try again.</p><button onclick="location.href='${BuildConfig.START_URL}'">Try again</button></main></body></html>
        """.trimIndent()
        webView.loadDataWithBaseURL(BuildConfig.START_URL, html, "text/html", "UTF-8", null)
    }

    @Deprecated("Deprecated in Android API; retained for runtime permission callback compatibility")
    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        when (requestCode) {
            LOCATION_PERMISSION_REQUEST -> {
                val granted = grantResults.any { it == PackageManager.PERMISSION_GRANTED }
                pendingGeoCallback?.invoke(pendingGeoOrigin, granted, false)
                pendingGeoCallback = null
                pendingGeoOrigin = null
            }
            DOWNLOAD_PERMISSION_REQUEST -> {
                val granted = grantResults.any { it == PackageManager.PERMISSION_GRANTED }
                val action = pendingDownload
                pendingDownload = null
                if (granted) action?.invoke()
                else Toast.makeText(this, "Storage permission is needed to save this download.", Toast.LENGTH_LONG).show()
            }
        }
    }

    override fun onPause() {
        CookieManager.getInstance().flush()
        super.onPause()
    }

    override fun onSaveInstanceState(outState: Bundle) {
        webView.saveState(outState)
        super.onSaveInstanceState(outState)
    }

    override fun onDestroy() {
        fileChooserCallback?.onReceiveValue(null)
        fileChooserCallback = null
        webView.stopLoading()
        webView.webChromeClient = null
        webView.webViewClient = WebViewClient()
        webView.destroy()
        super.onDestroy()
    }

    companion object {
        private const val TRUSTED_HOST = "knowurlocal.site"
        private const val LOCATION_PERMISSION_REQUEST = 4102
        private const val DOWNLOAD_PERMISSION_REQUEST = 4103
    }
}
