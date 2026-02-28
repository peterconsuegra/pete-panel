<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register • WordPress Pete</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Favicons --}}
  <link rel="icon" type="image/png" href="/favicon/favicon.ico">
  <link rel="manifest" href="/favicon/manifest.json">

  {{-- Fonts & Icons --}}
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  {{-- Bootstrap 5 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

  <style>
    :root { --pete-blue:#0D274D; --pete-green:#003B24; }
    body { font-family:'Montserrat',system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif; background:#f8f9fa; }

    .brandbar{
      background-image:linear-gradient(90deg,var(--pete-blue) 0%,var(--pete-green) 100%);
      color:#fff;
    }

    .btn-pete{
      background-image:linear-gradient(135deg,var(--pete-blue) 0%,var(--pete-green) 100%);
      color:#fff; font-weight:600; border:none; padding:.65rem 1.25rem; border-radius:.5rem;
      box-shadow:0 4px 10px rgba(0,0,0,.25); transition:transform .15s ease, box-shadow .15s ease;
    }
    .btn-pete:hover,.btn-pete:focus{ color:#fff; transform:translateY(-2px); box-shadow:0 6px 14px rgba(0,0,0,.35) }
    .btn-pete:active{ transform:translateY(0); box-shadow:0 3px 8px rgba(0,0,0,.2) }

    .panel{ border:1px solid rgba(0,0,0,.08); border-radius:.75rem; background:#fff; }
    .panel-heading{ padding:.75rem 1rem; border-bottom:1px solid rgba(0,0,0,.06); }
  </style>
</head>
<body>

  {{-- Slim brand bar, no admin links --}}
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
        <h2 class="mb-2">Create admin account</h2>
        <p class="text-muted mb-0">One account to manage your WordPress sites, backups, and integrations.</p>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-8 col-lg-7 col-xl-6">
        <div class="panel shadow-sm">
          <div class="panel-heading d-flex align-items-center justify-content-between">
            <h3 class="mb-0 fs-5">Register</h3>
            <small class="text-muted">It’s quick and secure</small>
          </div>

          <div class="p-3 p-md-4">
            {{-- Flash / validation --}}
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

            @php
              // ISO 3166-1 English short names (common display names)
              $countries = [
                'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan',
                'Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia (Plurinational State of)',
                'Bosnia and Herzegovina','Botswana','Brazil','Brunei Darussalam','Bulgaria','Burkina Faso','Burundi','Cabo Verde','Cambodia','Cameroon',
                'Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo','Congo (Democratic Republic of the)','Costa Rica',
                'Côte d’Ivoire','Croatia','Cuba','Cyprus','Czechia','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt',
                'El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini','Ethiopia','Fiji','Finland','France','Gabon','Gambia','Georgia',
                'Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Holy See','Honduras','Hungary','Iceland',
                'India','Indonesia','Iran (Islamic Republic of)','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya',
                'Kiribati','Korea (Democratic People’s Republic of)','Korea (Republic of)','Kuwait','Kyrgyzstan','Lao People’s Democratic Republic',
                'Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Madagascar','Malawi','Malaysia','Maldives',
                'Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia (Federated States of)','Moldova (Republic of)','Monaco',
                'Mongolia','Montenegro','Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger',
                'Nigeria','North Macedonia','Norway','Oman','Pakistan','Palau','Palestine, State of','Panama','Papua New Guinea','Paraguay','Peru',
                'Philippines','Poland','Portugal','Qatar','Romania','Russian Federation','Rwanda','Saint Kitts and Nevis','Saint Lucia',
                'Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles',
                'Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Sudan','Spain','Sri Lanka','Sudan',
                'Suriname','Sweden','Switzerland','Syrian Arab Republic','Tajikistan','Tanzania (United Republic of)','Thailand','Timor-Leste','Togo',
                'Tonga','Trinidad and Tobago','Tunisia','Türkiye','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom',
                'United States of America','Uruguay','Uzbekistan','Vanuatu','Venezuela (Bolivarian Republic of)','Viet Nam','Yemen','Zambia','Zimbabwe',
              ];
            @endphp

            <form method="POST" action="/register" novalidate autocomplete="off">
              @csrf

              {{-- Name --}}
              <div class="mb-3">
                <label for="user_name" class="form-label">Full name</label>
                <input type="text"
                       id="user_name"
                       name="user_name"
                       value="{{ old('user_name') }}"
                       class="form-control @error('user_name') is-invalid @enderror"
                       required>
                @error('user_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Username --}}
              <div class="mb-3">
                <label for="name" class="form-label">Username</label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Email --}}
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">We’ll use this for account recovery and notifications.</div>
                @enderror
              </div>

              {{-- Password --}}
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="form-text">At least 8 characters recommended.</div>
                @enderror
              </div>

              {{-- Confirm --}}
              <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="form-control"
                       required>
              </div>

              {{-- Country --}}
              <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <select id="country"
                        name="country"
                        class="form-select @error('country') is-invalid @enderror">
                  <option value="">Select your country</option>
                  @foreach($countries as $c)
                    <option value="{{ $c }}" {{ old('country')===$c ? 'selected' : '' }}>{{ $c }}</option>
                  @endforeach
                </select>
                @error('country')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Marketing opt-in --}}
              <div class="mb-4 form-check">
                <input class="form-check-input"
                       type="checkbox"
                       id="contact_me_again"
                       name="contact_me_again"
                       value="1"
                       {{ old('contact_me_again', 1) ? 'checked' : '' }}>
                <label class="form-check-label" for="contact_me_again">
                  Please let me know about Pete’s latest updates and tutorials via email
                </label>
              </div>

              <div class="d-grid">
                <button type="submit" id="register_action" class="btn btn-pete btn-lg">
                  <i class="bi bi-person-plus me-1"></i>Create account
                </button>
              </div>
            </form>
          </div>
        </div>

        {{-- Optional demo scripts if you need them --}}
        @if(isset($pete_demo) && $pete_demo === 'active')
          <script>
            // demo-specific behavior can live here
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>
