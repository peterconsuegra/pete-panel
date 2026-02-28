<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login • WordPress Pete</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Favicons (reuse your existing paths) --}}
  <link rel="icon" type="image/png" href="/favicon/favicon.ico">
  <link rel="manifest" href="/favicon/manifest.json">

  {{-- Fonts & Icons --}}
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  {{-- Bootstrap 5 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

  <style>
    :root { --pete-blue:#0D274D; --pete-green:#003B24; }
    body { font-family: 'Montserrat', system-ui,-apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif; background:#f8f9fa; }

    /* Brand button */
    .btn-pete{
      background-image: linear-gradient(135deg, var(--pete-blue) 0%, var(--pete-green) 100%);
      color:#fff; font-weight:600; border:none; padding:.65rem 1.25rem; border-radius:.5rem;
      box-shadow:0 4px 10px rgba(0,0,0,.25); transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-pete:hover,.btn-pete:focus{ color:#fff; transform:translateY(-2px); box-shadow:0 6px 14px rgba(0,0,0,.35) }
    .btn-pete:active{ transform:translateY(0); box-shadow:0 3px 8px rgba(0,0,0,.2) }

    /* Panel helpers (same as app layout) */
    .panel{ border:1px solid rgba(0,0,0,.08); border-radius:.75rem; background:#fff; }
    .panel-heading{ padding:.75rem 1rem; border-bottom:1px solid rgba(0,0,0,.06); }
    .panel-footer{ padding:.75rem 1rem; border-top:1px solid rgba(0,0,0,.06); }

    /* Minimal top brand bar (no admin links) */
    .brandbar{
      background-image: linear-gradient(90deg, var(--pete-blue) 0%, var(--pete-green) 100%);
      color:#fff;
    }
  </style>
</head>
<body>

  {{-- Slim top brand bar without menu --}}
  <div class="brandbar py-2">
    <div class="container d-flex align-items-center gap-2" style="padding: 10px">
      <img src="/PetePanelLogo.png" alt="Pete" style="height:25px;">
    </div>
  </div>

  <main class="container py-5">
    {{-- hero --}}
    <div class="row align-items-center mb-5 g-4">
      <div class="col-md-6 text-center text-md-start">
        <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
      </div>
      <div class="col-md-6 d-flex flex-column justify-content-center">
        <h2 class="mb-2">Welcome back</h2>
        <p class="text-muted mb-0">Sign in to manage your WordPress sites, backups, and integrations.</p>
      </div>
    </div>

    {{-- login card --}}
    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="panel shadow-sm">
          <div class="panel-heading d-flex align-items-center justify-content-between">
            <h3 class="mb-0 fs-5">Log in</h3>
            <small class="text-muted">Secure access</small>
          </div>

          <div class="p-3 p-md-4">
            {{-- status / errors --}}
            @if (session('status'))
              <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger">
                <strong>Whoops!</strong> Please fix the following:
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate autocomplete="off">
              @csrf

              <div class="visually-hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                <input type="hidden" name="form_start_ts" value="{{ time() }}">
              </div>

              {{-- Email --}}
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input  type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        required autofocus>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">Use the email associated with your Pete account.</div>
                @enderror
              </div>

              {{-- Password --}}
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input  type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Remember + forgot --}}
              <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label" for="remember">Remember me</label>
                </div>
                @if (Route::has('password.request'))
                  <a class="small" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-pete btn-lg" id="btnlogin">
                  <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </button>
              </div>
            </form>
          </div>

          
        </div>

        {{-- Demo auto‑redirect (preserves your previous behavior) --}}
        @if(isset($pete_demo) && $pete_demo === 'active')
          <script>
            (function(){
              @if(!isset($admin_login) || $admin_login !== 'active')
              window.location.replace('/auth/register');
              @endif
            })();
          </script>
        @endif
      </div>
    </div>
  </main>

  <footer class="py-4 bg-white border-top small">
    <div class="container d-flex justify-content-between">
      <div>&nbsp;</div>
      <div class="text-end">Build Anywhere. Deploy Everywhere. <a href="https://deploypete.com">deploypete.com</a></div>
    </div>
  </footer>

  {{-- Bootstrap 5 bundle --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>
