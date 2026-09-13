@extends('layouts.app', ['title' => 'Edit note'])

@section('content')
    <div class="form-wrap">
        <a class="back-link" href="{{ route('posts.show', $post) }}">← Back to note</a>
        <p class="eyebrow">Edit entry</p>
        <h1>Refine the idea.</h1>
        <p class="lede">Keep the useful parts. Let the rest evolve.</p>
        @include('posts.partials.form', ['action' => route('posts.update', $post), 'method' => 'PUT', 'submitLabel' => 'Update note', 'post' => $post])
    </div>
@endsection