@extends('layout')

@section('header')
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Edit User</h1>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
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
      <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')

        @include('users._form', ['user' => $user, 'mode' => 'edit'])

        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-pete">
            <i class="bi bi-check2-circle"></i> Update
          </button>
          <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
@endsection
