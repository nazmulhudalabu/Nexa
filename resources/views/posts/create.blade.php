@extends('layouts.app', ['title' => 'New note'])

@section('content')
    <div class="form-wrap">
        <a class="back-link" href="{{ route('posts.index') }}">← Back to archive</a>
        <p class="eyebrow">New entry</p>
        <h1>Make a note of it.</h1>
        <p class="lede">Give a useful thought a name, then add enough context to find it again.</p>
        @include('posts.partials.form', ['action' => route('posts.store'), 'method' => 'POST', 'submitLabel' => 'Save note'])
    </div>
@endsection