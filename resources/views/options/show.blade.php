@extends('layout')

@section('content')
<div class="container-fluid">

    {{-- header ----------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-3">
        <div class="col-md-8">
            <h1 class="h3 mb-1">System Variable</h1>
            <p class="text-muted mb-0">Read-only details for this configuration entry.</p>
        </div>
        <div class="col-md-4 d-flex justify-content-md-end gap-2">
            <a class="btn btn-outline-primary btn-lg w-100"
               href="{{ route('options.edit', $option->id) }}">
                <i class="bi bi-pencil-square"></i> Edit
            </a>

            <form action="{{ route('options.destroy', $option->id) }}"
                  method="POST"
                  onsubmit="return confirm('Delete this variable? This action cannot be undone.');"
                  class="w-100">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-lg w-100">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

    {{-- details panel ---------------------------------------------------- --}}
    <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fs-5">Details</h3>
            <small class="text-muted">#{{ $option->id }}</small>
        </div>

        <div class="p-3 p-md-4">
            <dl class="row mb-0">
                <dt class="col-sm-3">Key (option_name)</dt>
                <dd class="col-sm-9">
                    <code>{{ $option->option_name }}</code>
                </dd>

                <dt class="col-sm-3">Value (option_value)</dt>
                <dd class="col-sm-9">
                    <span class="text-break">{{ $option->option_value }}</span>
                </dd>

                @if(!is_null($option->category))
                    <dt class="col-sm-3">Category</dt>
                    <dd class="col-sm-9">{{ $option->category }}</dd>
                @endif

                @if(!is_null($option->order))
                    <dt class="col-sm-3">Order</dt>
                    <dd class="col-sm-9">{{ $option->order }}</dd>
                @endif

                @if(!empty($option->title))
                    <dt class="col-sm-3">Title</dt>
                    <dd class="col-sm-9">{{ $option->title }}</dd>
                @endif

                @if(!empty($option->version))
                    <dt class="col-sm-3">Version</dt>
                    <dd class="col-sm-9">{{ $option->version }}</dd>
                @endif

                @isset($option->visible)
                    <dt class="col-sm-3">Visible</dt>
                    <dd class="col-sm-9">
                        <span class="badge {{ $option->visible ? 'bg-success' : 'bg-secondary' }}">
                            {{ $option->visible ? 'Yes' : 'No' }}
                        </span>
                    </dd>
                @endisset

                @if(isset($option->created_at) || isset($option->updated_at))
                    <dt class="col-sm-3">Timestamps</dt>
                    <dd class="col-sm-9">
                        @isset($option->created_at)
                            <div>Created: {{ $option->created_at }}</div>
                        @endisset
                        @isset($option->updated_at)
                            <div>Updated: {{ $option->updated_at }}</div>
                        @endisset
                    </dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('options.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Variables
        </a>
    </div>

</div>
@endsection
