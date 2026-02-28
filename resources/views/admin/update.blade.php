@extends('layout')

@section('content')
<div class="container-fluid">

    {{-- header --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:160px">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <h1 class="h3 mb-1">Core Updates</h1>
            <p class="text-muted mb-0">Keep WordPress Pete running fast, secure, and shiny ✨</p>
        </div>
    </div>

    {{-- update card --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Update status</span>
                    <span class="small text-muted">{{ $checkedAt ?? '' }}</span>
                </div>

                <div class="card-body">

                    {{-- status --}}
                    @if (is_null($latest))
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Could not check for updates right now.
                        </div>
                    @elseif ($hasUpdate)
                        <div class="alert alert-info mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle mt-1"></i>
                            <div>
                                <div class="fw-semibold">Update available</div>
                                <div class="small text-muted">A newer version is ready to install.</div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle mt-1"></i>
                            <div>
                                <div class="fw-semibold">You’re up to date</div>
                                <div class="small text-muted">No updates available at this time.</div>
                            </div>
                        </div>
                    @endif

                    {{-- meta info --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Installed version</div>
                                <div class="fw-semibold">{{ $version ?: '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Latest available</div>
                                <div class="fw-semibold">{{ $latest ?: '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Environment</div>
                                <div class="fw-semibold">{{ $environment ?: '—' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- release notes --}}
                    @if(!empty($notes))
                        <div class="border rounded p-3 mb-3">
                            <div class="fw-semibold mb-2">What’s new</div>
                            <div class="small">{!! $notes !!}</div>
                        </div>
                    @endif

                    {{-- actions --}}
                    <div class="d-grid d-sm-flex gap-2">
                        @if ($hasUpdate)
                            <form action="{{ route('admin.update.core') }}" method="POST" class="d-inline">
                                @csrf
                                {{-- Use a hidden textarea to avoid attribute-escaping issues for long scripts --}}
                                 <input type="hidden" id="latest" name="latest" value="{{ $latest }}">

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-arrow-down me-1"></i>
                                    Update to {{ $latest }}
                                </button>
                            </form>
                        @endif

                        {{-- Optional: show a disabled button when nothing to do --}}
                        @if (!$hasUpdate)
                            <button type="button" class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-check2 me-1"></i> No action required
                            </button>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
