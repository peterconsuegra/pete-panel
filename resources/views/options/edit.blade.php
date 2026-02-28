@extends('layout')

@section('content')
<div class="container-fluid">

    {{-- header ----------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-3">
        <div class="col-md-8">
            <h1 class="h3 mb-1">Edit System Variable</h1>
            <p class="text-muted mb-0">Update the key/value and related metadata.</p>
        </div>
        <div class="col-md-4 d-flex justify-content-md-end">
            <a href="{{ route('options.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                <i class="bi bi-arrow-left"></i> Back to Variables
            </a>
        </div>
    </div>

    {{-- form panel ------------------------------------------------------- --}}
    <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fs-5">Variable #{{ $option->id }}</h3>
            @if(!empty($option->title) || !empty($option->option_name))
                <small class="text-muted">
                    {{ $option->title ?: $option->option_name }}
                </small>
            @endif
        </div>

        <div class="p-3 p-md-4">

            {{-- validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-2">Please fix the following:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('options.update', $option->id) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                @include('options._form')

                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('options.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" id="update_button" class="btn btn-pete">
                        <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
