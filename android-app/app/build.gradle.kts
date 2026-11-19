plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

// Signing values are read from local Gradle properties or environment variables.
// Never commit a keystore or its passwords to this repository.
val releaseStoreFile = providers.gradleProperty("KYL_RELEASE_STORE_FILE").orNull ?: System.getenv("KYL_RELEASE_STORE_FILE")
val releaseStorePassword = providers.gradleProperty("KYL_RELEASE_STORE_PASSWORD").orNull ?: System.getenv("KYL_RELEASE_STORE_PASSWORD")
val releaseKeyAlias = providers.gradleProperty("KYL_RELEASE_KEY_ALIAS").orNull ?: System.getenv("KYL_RELEASE_KEY_ALIAS")
val releaseKeyPassword = providers.gradleProperty("KYL_RELEASE_KEY_PASSWORD").orNull ?: System.getenv("KYL_RELEASE_KEY_PASSWORD")
val hasReleaseSigning = listOf(releaseStoreFile, releaseStorePassword, releaseKeyAlias, releaseKeyPassword).all { !it.isNullOrBlank() }

android {
    namespace = "ph.knowurlocal.app"
    compileSdk = 36

    defaultConfig {
        applicationId = "ph.knowurlocal.app"
        minSdk = 23
        targetSdk = 36
        versionCode = 1
        versionName = "1.0.0"

        buildConfigField("String", "START_URL", "\"https://knowurlocal.site/\"")
    }

    buildFeatures {
        buildConfig = true
    }

    signingConfigs {
        if (hasReleaseSigning) {
            create("release") {
                storeFile = file(releaseStoreFile!!)
                storePassword = releaseStorePassword
                keyAlias = releaseKeyAlias
                keyPassword = releaseKeyPassword
            }
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions {
        jvmTarget = "17"
    }

    buildTypes {
        debug {
            applicationIdSuffix = ".debug"
            versionNameSuffix = "-debug"
        }
        release {
            if (hasReleaseSigning) signingConfig = signingConfigs.getByName("release")
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            // Signing is intentionally configured outside source control.
            // Add signingConfig only when a private release keystore is created.
        }
    }
}

dependencies {
    implementation("androidx.activity:activity-ktx:1.10.1")
    implementation("androidx.core:core-ktx:1.16.0")
    // Keep the AndroidX SplashScreen resource attributes on the current stable library.
    implementation("androidx.core:core-splashscreen:1.2.0")
}
