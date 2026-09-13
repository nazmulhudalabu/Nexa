@extends('layouts.app')

@section('content')
    <script>window.location = @json(route('posts.index'));</script>
@endsection