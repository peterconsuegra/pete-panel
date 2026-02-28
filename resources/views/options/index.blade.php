@extends('layout')

@php use Illuminate\Support\Str; @endphp

@if(session('error_message'))
  <div class="alert alert-danger">
    <strong>Error:</strong> {{ session('error_message') }}
  </div>
@endif

@section('content')
<div class="container-fluid">

    {{-- page header ----------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-3">
        <div class="col-md-8">
            <h1 class="h3 mb-1">System Options</h1>
            <p class="text-muted mb-0">Global variables that fine-tune WordPress Pete’s behaviour.</p>
        </div>
        <div class="col-md-4 d-flex justify-content-md-end">
            <a href="{{ route('options.create') }}" class="btn btn-pete btn-lg w-100">
                <i class="bi bi-plus-lg me-1"></i>Create System Variable
            </a>
        </div>
    </div>

    {{-- options table --------------------------------------------------- --}}
    <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fs-5">Variables</h3>
            @if($options->count())
                <small class="text-muted">{{ $options->count() }} total</small>
            @endif
        </div>

        <div class="table-responsive">
            @if($options->count())
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="70">ID</th>
                            <th>Key</th>
                           
                             <th>Value</th>
                            <th class="text-end" width="240">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($options as $option)
                            <tr>
                                <td class="text-muted">{{ $option->id }}</td>
                                <td class="fw-semibold">{{ $option->option_name }}</td>
                                <td class="text-break">{{ Str::limit($option->option_value, 80) }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('options.edit', $option->id) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('options.destroy', $option->id) }}"
                                              method="POST"
                                              class="d-inline-block"
                                              onsubmit="return confirm('Delete this variable? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Delete">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-5 text-center">
                    <p class="lead mb-0">No variables yet — click “Create System Variable” to add one.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
