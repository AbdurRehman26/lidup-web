<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#dce7e6">
    <meta name="description" content="LidUp keeps long-running work moving when you step away from your Mac.">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @paddleJS
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
