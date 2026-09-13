@extends('layouts.app', ['title' => 'Log in'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">Welcome back</p><h1>Return to your ideas.</h1>
<form class="post-form" method="POST" action="{{ route('login.store') }}">@csrf
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
    <p><a class="clear-link" href="{{ route('password.request') }}">Forgot your password?</a></p>
    <label class="check"><input type="checkbox" name="remember"> Remember me</label>
    <button class="button button-dark" type="submit">Log in</button>
</form><p class="auth-note">New here? <a class="clear-link" href="{{ route('register') }}">Create an account</a></p><p class="auth-note"><a class="clear-link" href="{{ route('account.reactivate.form') }}">Reactivate a deactivated account</a></p></div>
@endsection