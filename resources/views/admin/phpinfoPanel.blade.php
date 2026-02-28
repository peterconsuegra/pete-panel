@extends('layout')

@section('content')
<div class="container-fluid">

    

    {{-- PHPINFO PANEL -------------------------------------------------------- --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="panel">
                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fs-5">
                        <i class="bi bi-card-list me-2"></i> phpinfo()
                    </h3>
                </div>
                <div class="p-0">
                    <iframe
                        src="{{route('admin.phpinfo.view')}}"
                        width="100%"
                        style="border:0; min-height: 1200px;"
                        title="phpinfo output"></iframe>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


