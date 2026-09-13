@extends('layouts.app', ['title' => 'Reset password'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">Account recovery</p><h1>Choose a new password.</h1>
<form class="post-form" method="POST" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}">
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" required></div>
    <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" required autocomplete="new-password">@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
    <button class="button button-dark" type="submit">Reset password</button>
</form></div>
@endsection
