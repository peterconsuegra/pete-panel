@extends('layout')

@section('header')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">
            <i class="bi bi-people me-2"></i> Users
        </h1>

        <a class="btn btn-pete" href="{{ route('users.create') }}">
            <i class="bi bi-person-plus me-1"></i> Create user
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endsection

@section('content')
<div class="card panel">
    <div class="card-body">

        {{-- Table heading with per-page selector + total count --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">All users</h2>

            <div class="d-flex align-items-center gap-3">
                <form method="GET" action="{{ route('users.index') }}" class="d-inline-block">
                    {{-- Preserve any existing query params except per_page --}}
                    @foreach(request()->except('per_page', 'page') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <label for="per_page" class="form-label me-2 mb-0 small text-muted">Rows per page:</label>
                    <select name="per_page" id="per_page"
                            class="form-select form-select-sm d-inline-block w-auto"
                            onchange="this.form.submit()">
                        @foreach([5,10,20,50] as $size)
                            <option value="{{ $size }}" {{ (int)request('per_page', 10) === $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </form>

                @if($users->total() > 0)
                    <small class="text-muted">{{ number_format($users->total()) }} total</small>
                @endif
            </div>
        </div>

        @if($users->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-nowrap">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col" class="text-nowrap">Admin</th>
                            <th scope="col" class="text-nowrap">Created</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="text-muted">{{ $user->id }}</td>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td>
                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                        <i class="bi bi-envelope"></i> {{ $user->email }}
                                    </a>
                                </td>
                                <td>
                                    @if((bool) $user->admin)
                                        <span class="badge text-bg-primary">
                                            <i class="bi bi-shield-check me-1"></i> Admin
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">User</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ optional($user->created_at)->format('Y-m-d') ?? '—' }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group" aria-label="User actions">
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('users.edit', $user) }}">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>

                                        <form action="{{ route('users.destroy', $user) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination footer mirrors Sites page --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}
                </div>
                {{ $users->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-2">
                    <i class="bi bi-people fs-1 text-muted"></i>
                </div>
                <p class="text-muted mb-3">No users found yet.</p>
                {{-- Fix: use the correct route name --}}
                <a class="btn btn-pete" href="{{ route('users.create') }}">
                    <i class="bi bi-person-plus me-1"></i> Create your first user
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
