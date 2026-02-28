{{-- resources/views/admin/userCreate.blade.php --}}
@extends('layout')

@section('header')
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Create User</h1>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back to Users
    </a>
  </div>
@endsection

@section('content')
  {{-- Flash messages --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Validation errors --}}
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1">Please fix the following:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <p class="text-muted small mb-3">
        Users created by the administrator can only view and manage the sites they create themselves.
      </p>

      <form method="POST" action="{{ route('users.store') }}" novalidate>
        @csrf

        {{-- Reuse the same form partial; on create, password is required --}}
        @include('users._form', ['user' => $user ?? new \App\Models\User(), 'mode' => 'create'])

        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-pete">
            <i class="bi bi-person-plus"></i> Create
          </button>
          <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
@endsection
