@extends('layouts.app', ['title' => 'Reactivate account'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">Welcome back</p><h1>Reactivate your account.</h1>
<form class="post-form" method="POST" action="{{ route('account.reactivate') }}">@csrf
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autofocus></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
<button class="button button-dark" type="submit">Reactivate account</button>
</form></div>
@endsection