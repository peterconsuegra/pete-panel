@extends('layout')

@section('content')
<div class="container-fluid" id="serverSSHPanel">

    {{-- SSH Public Key --------------------------------------------------------- --}}
  <div class="row mb-4">
    <div class="col-lg-9 col-xl-8">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-5">Server SSH Public Key</h3>
        </div>
        <div class="p-3 p-md-4">
          @if(!empty($sshKeyError))
            <div class="alert alert-warning small mb-0">
              <strong>Couldn’t read the public key.</strong><br>
              <span class="text-muted">{{ $sshKeyError }}</span>
            </div>
          @else
            <label for="sshPubKey" class="form-label">This is the SSH public key for the Pete PHP container. Add it to your Git provider to push or pull WordPress plugins and Laravel projects.</label>
            <div class="input-group">
              <textarea id="sshPubKey"
                        class="form-control"
                        rows="3"
                        readonly
                        spellcheck="false">{{ $sshPubKey }}</textarea>
              <button class="btn btn-outline-secondary"
                      type="button"
                      id="btnCopyKey"
                      title="Copy to clipboard">
                <i class="bi bi-clipboard" aria-hidden="true"></i>
                <span class="visually-hidden">Copy</span>
              </button>
            </div>
            <div class="form-text">
              This is the <em>public</em> key (<code>/var/www/.ssh/id_ed25519.pub</code>); it’s safe to share with Git providers.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
    

    
</div>
@endsection


