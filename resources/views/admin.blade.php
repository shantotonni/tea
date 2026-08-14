<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cha Kunjo — Admin</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('build/admin.css') }}">

    <script>
        // vue-router base — works whether the app is served by `artisan serve`
        // or from a sub-directory under the XAMPP document root.
        window.__ADMIN_BASE__ = @json(rtrim(parse_url(url('/'), PHP_URL_PATH) ?? '', '/') . '/');
    </script>
</head>
<body>
    <div id="admin-app"></div>
    <script type="module" src="{{ asset('build/admin.js') }}"></script>
</body>
</html>
