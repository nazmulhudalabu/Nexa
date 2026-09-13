@extends('layouts.app', ['title' => 'Verify email'])
@section('content')
<div class="auth-wrap"><p class="eyebrow">One last step</p><h1>Verify your email.</h1><p class="lede">We sent a verification link to {{ auth()->user()->email }}. Verify it to activate your account.</p>
@if (session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('verification.send') }}">@csrf<button class="button button-dark" type="submit">Send another link</button></form>
</div>
@endsection
