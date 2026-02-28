@extends('layout')

@section('header')
    <h1 class="h4"><i class="bi bi-person-circle me-2"></i> My Account</h1>
@endsection

@section('content')
<div class="card panel">
    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('users.myaccount.update') }}" method="POST">
            @csrf

            <input type="hidden" id="currentUserId" name="currentUserId" value="{{$currentUser->id}}">

            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-pete">Update Password</button>
        </form>
    </div>
</div>
@endsection
