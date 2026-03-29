<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'TNBO Interactive Admin')</title>
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    </head>
    <body>
        <div class="page-shell">
            @auth('admin')
                <header class="topbar">
                    <div>
                        <a class="brand" href="{{ route('admin.dashboard') }}">TNBO Interactive Admin</a>
                        <div class="subtitle">Local staff console for trivia operations</div>
                    </div>
                    <nav class="topbar-nav">
                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                        <a href="{{ route('admin.quizzes.index') }}" class="{{ request()->routeIs('admin.quizzes.*') ? 'active' : '' }}">Quizzes</a>
                        <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
                        <a href="{{ route('admin.help.howto') }}" class="{{ request()->routeIs('admin.help.*') ? 'active' : '' }}">How To</a>
                    </nav>
                    <div class="topbar-actions">
                        <span class="admin-chip">{{ auth('admin')->user()->name }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="button button-light">Logout</button>
                        </form>
                    </div>
                </header>
            @endauth

            <main class="content-shell">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the following:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </body>
</html>
