@extends('layouts.admin')

@section('title', 'Admin Login')

@section('content')
    <div class="auth-wrap">
        <section class="hero-card">
            <div class="eyebrow">TNBO Sports Interactive</div>
            <h1>Admin Login</h1>
            <p>
                This dashboard is local to the Interactive service. Staff accounts here are separate from AuthBox user accounts and are used only for quiz operations, reporting, and audit review.
            </p>
        </section>

        <section class="auth-card">
            <h2>Sign in</h2>
            <form method="POST" action="{{ route('admin.login.store') }}" class="stack-md">
                @csrf
                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button type="submit" class="button">Enter dashboard</button>
            </form>
        </section>
    </div>
@endsection
