@extends('layouts.app', ['title' => 'Forgot password'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">Account recovery</p><h1>Reset your password.</h1>
@if (session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
<form class="post-form" method="POST" action="{{ route('password.email') }}">@csrf
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
    <button class="button button-dark" type="submit">Email reset link</button>
</form><p class="auth-note"><a class="clear-link" href="{{ route('login') }}">Back to log in</a></p></div>
@endsection
