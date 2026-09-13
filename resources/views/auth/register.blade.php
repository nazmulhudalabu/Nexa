@extends('layouts.app', ['title' => 'Create account'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">Start your archive</p><h1>Make room for good ideas.</h1>
<form class="post-form" method="POST" action="{{ route('register.store') }}">@csrf
    <div class="field"><label for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" required autofocus>@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="phone">Phone (optional)</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}">@error('phone')<span class="field-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
    <button class="button button-dark" type="submit">Create account</button>
</form><p class="auth-note">Already have an account? <a class="clear-link" href="{{ route('login') }}">Log in</a></p></div>
@endsection