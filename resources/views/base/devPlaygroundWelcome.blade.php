@extends('layout')

@section('content')


<div class="container-fluid">


         {{-- ENJOY OUR DEV PLAYGROUND — hero (same style as sites/index) --}}
        <div class="row align-items-center mb-5 g-4">
            <div class="col-md-6 text-center text-md-start">
                <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
            </div>
            <div class="col-md-6 d-flex flex-column justify-content-center text-center text-md-start">
                <h1 class="fw-bold mb-3">
                    ENJOY OUR DEV PLAYGROUND.<br>
                    <span class="text-success">FOREVER FREE</span>
                </h1>
                
               <div class="d-grid d-md-flex gap-2">
                    <a href="{{ route('sites.create') }}" class="btn btn-pete btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Create WordPress Site
                    </a>
                    <a href="{{ url('/sites') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-window me-1"></i>View My Sites
                    </a>
                </div>
                
            </div>
        </div>


        <div class="row">
            <div class="col-12">
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="mb-0 fs-5">What’s included in the Playground</h3>
                    </div>
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-infinity me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-semibold mb-1">Create unlimited sites</div>
                                            <div class="text-muted small">Spin up as many WordPress projects as you need.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-files me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-semibold mb-1">Clone unlimited sites</div>
                                            <div class="text-muted small">Duplicate any site to new subdomains in seconds.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-box-arrow-up me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-semibold mb-1">Export unlimited sites</div>
                                            <div class="text-muted small">Package projects with Pete format for reliable moves.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-plug me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-semibold mb-1">Access to Pete premium plugins</div>
                                            <div class="text-muted small">First‑party tools optimized for WordPress Pete.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-box-arrow-in-down me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-semibold mb-1">Import unlimited sites</div>
                                            <div class="text-muted small">Bring in backups or Pete bundles with one flow.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-arrow-left-right me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-semibold mb-1">One‑click WordPress ↔ Laravel integration</div>
                                            <div class="text-muted small">Bridge your WP app with Laravel in a single step.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="small text-muted mt-4 mb-0">
                            Want more? Unlock extra features and updates any time with a commercial license.
                            <a href="https://wordpresspete.com/pricing/" target="_blank" rel="noopener">See pricing</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>


</div>
@endsection
