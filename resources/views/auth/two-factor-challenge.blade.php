@extends('layouts.app', ['title' => 'Security check'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">Security check</p><h1>Enter your security code.</h1><form class="post-form" method="POST" action="{{ route('two-factor.verify') }}">@csrf
<div class="field"><label for="code">Authentication code or recovery code</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>@error('code')<span class="field-error">{{ $message }}</span>@enderror</div><button class="button button-dark" type="submit">Continue</button></form></div>
@endsection
