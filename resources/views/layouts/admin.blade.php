<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('css/admin.css')}}">
    @stack('styles')
</head>
<body>

{{-- Sidebar Overlay (mobile) --}}
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

{{-- ── Sidebar ── --}}
@include('partials.admin_sidebar')
@include('partials.admin_header')


{{-- ── Page Content ── --}}
<main id="main-content">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script  src="{{asset('js/admin.js')}}"></script>

@stack('scripts')
</body>
</html>
