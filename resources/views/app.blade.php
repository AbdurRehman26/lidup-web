<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f8f6ff">
    <meta name="description" content="Lidup your Mac. Keep builds, coding agents, and long-running tasks moving when the lid is closed.">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="LidUp">
    <meta property="og:locale" content="en_US">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Close the lid. Come back to done.">
    <meta property="og:description" content="LidUp keeps your Mac awake while builds, coding agents, and long-running tasks finish.">
    <meta property="og:image" content="{{ url('/marketing/social-share.jpg') }}">
    <meta property="og:image:secure_url" content="{{ url('/marketing/social-share.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="LidUp — Close the lid. Come back to done.">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Close the lid. Come back to done.">
    <meta name="twitter:description" content="LidUp keeps your Mac awake while builds, coding agents, and long-running tasks finish.">
    <meta name="twitter:image" content="{{ url('/marketing/social-share.jpg') }}">
    <meta name="twitter:image:alt" content="LidUp — Close the lid. Come back to done.">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <script>
        (() => {
            const saved = localStorage.getItem('lidup-theme');
            const theme = saved || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
            document.querySelector('meta[name="theme-color"]').content = theme === 'dark' ? '#100d17' : '#f8f6ff';
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @paddleJS
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
