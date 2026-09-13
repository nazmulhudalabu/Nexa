<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Home' }} · Nexa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="site-shell">
        <header class="site-header">
            <a class="brand" href="{{ route('posts.index') }}" aria-label="Nexa home">
                <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="brand-name">Nexa</span>
            </a>
            <nav class="site-nav">
                @auth
                    <a class="nav-link active" href="{{ route('posts.index') }}">Home</a>
                    <a class="nav-link" href="{{ route('people.index') }}">People</a>
                    <a class="nav-link" href="{{ route('friends.index') }}">Friends</a>
                    <a class="nav-link" href="{{ route('stories.create') }}">Create Story</a>
                    <a class="nav-link notification-link" href="{{ route('notifications.index') }}">Notifications @if (auth()->user()->unreadNotifications->count())<span class="notification-badge">{{ auth()->user()->unreadNotifications->count() }}</span>@endif</a>
                    <a class="nav-link notification-link" href="{{ route('chat.index') }}">Messages @if (auth()->user()->unreadMessagesCount())<span class="message-badge">{{ auth()->user()->unreadMessagesCount() }}</span>@endif</a>
                    <a class="nav-link" href="{{ route('people.show', auth()->user()) }}">My profile</a>
                    <a class="nav-link" href="{{ route('account.security') }}">Security</a>
                    <a class="button button-accent nav-create" href="{{ route('posts.create') }}"><span aria-hidden="true">+</span> Create</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-button" type="submit" title="Log out">Log out</button></form>
                @else
                    <a class="nav-link" href="{{ route('login') }}">Log in</a>
                    <a class="nav-link" href="{{ route('people.index') }}">People</a>
                    <a class="button button-accent" href="{{ route('register') }}">Join Nexa</a>
                @endauth
            </nav>
        </header>

        <main class="page-content">
            @if (session('success'))
                <div class="flash flash-success" role="status">{{ session('success') }}</div>
            @endif

            @yield('content')
        </main>

        <footer class="site-footer">Nexa · Find your people. Share your world.</footer>
    </div>
</body>
</html>