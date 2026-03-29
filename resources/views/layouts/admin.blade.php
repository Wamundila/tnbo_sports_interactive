<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'TNBO Interactive Admin')</title>
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    </head>
    <body>
        @auth('admin')
            <div class="admin-shell">
                <aside class="sidebar">
                    <div class="sidebar-brand">
                        <a class="brand" href="{{ route('admin.dashboard') }}">TNBO Interactive Admin</a>
                        <div class="subtitle">Local staff console for trivia and predictor operations</div>
                    </div>

                    <nav class="sidebar-nav">
                        <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

                        <details class="sidebar-group" @if (request()->routeIs('admin.quizzes.*') || request()->routeIs('admin.reports.*') || request()->routeIs('admin.help.*')) open @endif>
                            <summary>Daily Quiz</summary>
                            <div class="sidebar-group-links">
                                <a href="{{ route('admin.quizzes.index') }}" class="sidebar-link {{ request()->routeIs('admin.quizzes.*') ? 'active' : '' }}">Quizzes</a>
                                <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
                                <a href="{{ route('admin.help.howto') }}" class="sidebar-link {{ request()->routeIs('admin.help.*') ? 'active' : '' }}">How To</a>
                            </div>
                        </details>

                        <details class="sidebar-group" @if (request()->routeIs('admin.predictor.*')) open @endif>
                            <summary>Predictor League</summary>
                            <div class="sidebar-group-links">
                                <a href="{{ route('admin.predictor.index') }}" class="sidebar-link {{ request()->routeIs('admin.predictor.index') ? 'active' : '' }}">Campaigns</a>
                                <a href="{{ route('admin.predictor.campaigns.create') }}" class="sidebar-link {{ request()->routeIs('admin.predictor.campaigns.create') ? 'active' : '' }}">New Campaign</a>
                            </div>
                        </details>
                    </nav>
                </aside>

                <div class="content-area">
                    <header class="content-topbar">
                        <div>
                            <div class="eyebrow">Admin Console</div>
                            <strong>{{ auth('admin')->user()->name }}</strong>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="button button-light">Logout</button>
                        </form>
                    </header>

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
            </div>
        @else
            <div class="page-shell">
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
        @endauth
    </body>
</html>
