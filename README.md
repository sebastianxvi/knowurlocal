<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Vercel deployment notes (KnowUrLocal)

### Persistent file storage

The Vercel runtime filesystem is ephemeral. Configure an S3-compatible object store before production traffic is enabled. Separate public and private buckets are supported and recommended when using Supabase Storage:

- `PUBLIC_STORAGE_DRIVER=s3` and `PRIVATE_STORAGE_DRIVER=s3`
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, and `AWS_DEFAULT_REGION`
- `AWS_PUBLIC_BUCKET` set to the public bucket name and `AWS_PRIVATE_BUCKET` set to the private bucket name
- `AWS_PUBLIC_URL` set to the public base URL for the public bucket, such as the bucket's public URL or a CDN URL
- `AWS_ENDPOINT` and `AWS_USE_PATH_STYLE_ENDPOINT` when required by your S3-compatible provider
- `AWS_PUBLIC_PREFIX` and `AWS_PRIVATE_PREFIX` are optional; with separate buckets, they default to the bucket root. If using a legacy single bucket instead, set `AWS_BUCKET`; the disks retain the `public` and `private` prefixes by default.

The bucket/prefix used for private attachments must not permit public reads. Those files continue to be streamed through Laravel routes, where the existing authorization checks run. Do not enable S3 storage until the bucket policy and public URL have been tested. Existing files stored on a local disk are not copied automatically; migrate them separately before switching production traffic.

For local development, keep both driver variables set to `local`.


### Realtime deployment

Keep `BROADCAST_CONNECTION=reverb` and `VITE_REALTIME_DRIVER=reverb` for local development. For a Vercel production build, configure `BROADCAST_CONNECTION=ably` and `ABLY_KEY` (the server-side Ably API key) in the Vercel project environment. The frontend reads the Laravel broadcasting driver from a server-rendered meta tag, so it does not depend on a Vite build-time environment variable. Never expose `ABLY_KEY` in a `VITE_*` variable.

The Ably Echo adapter uses Laravel's existing `/broadcasting/auth` endpoint for private-channel authorization. Keep `routes/channels.php` authorization callbacks intact. The existing server-side Ably broadcaster publishes events; browser clients must not receive the Ably API secret. Confirm the Ably account/key has the required publish and subscribe capabilities, and test user, admin, and collaboration channels with separate accounts before production traffic.
