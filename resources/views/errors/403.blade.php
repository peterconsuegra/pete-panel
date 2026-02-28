@extends('layout')

@section('content')
<div class="container py-5 text-center">
    <h1 class="display-4 text-danger">
        <i class="bi bi-shield-lock"></i> Access Denied
    </h1>
    <p class="lead mt-3">
        Sorry, you don’t have permission to access this page.
    </p>
    <a href="{{ url('/') }}" class="btn btn-pete mt-4">
        <i class="bi bi-house-door"></i> Go back to Dashboard
    </a>
</div>
@endsection
