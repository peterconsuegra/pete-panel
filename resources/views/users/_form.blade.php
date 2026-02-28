@php
  // Helper to mark invalid inputs
  $invalid = fn($name) => $errors->has($name) ? 'is-invalid' : '';
@endphp

<div class="row g-3">
  {{-- Admin --}}
  <div class="col-12 col-md-4">
    <label for="admin" class="form-label">Admin</label>
    <select name="admin" id="admin" class="form-select {{ $invalid('admin') }}">
      @php $adminVal = old('admin', (int) $user->admin); @endphp
      <option value="1" @selected($adminVal==1)>true</option>
      <option value="0" @selected($adminVal==0)>false</option>
    </select>
    @error('admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Username --}}
  <div class="col-12 col-md-4">
    <label for="name" class="form-label">Username</label>
    <input type="text"
           id="name"
           name="name"
           value="{{ old('name', $user->name) }}"
           class="form-control {{ $invalid('name') }}"
           autocomplete="off">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Letters, numbers, dashes and underscores only.</div>
  </div>

  {{-- Email --}}
  <div class="col-12 col-md-4">
    <label for="email" class="form-label">Email</label>
    <input type="email"
           id="email"
           name="email"
           value="{{ old('email', $user->email) }}"
           class="form-control {{ $invalid('email') }}">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Password (optional on edit) --}}
  <div class="col-12 col-md-6">
    <label for="password" class="form-label">Password</label>
    <input type="password"
           id="password"
           name="password"
           class="form-control {{ $invalid('password') }}"
           autocomplete="new-password">
    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">
      @if(($mode ?? null) === 'edit')
        Leave blank to keep the current password.
      @else
        Minimum 6 characters.
      @endif
    </div>
  </div>

  {{-- Confirm Password --}}
  <div class="col-12 col-md-6">
    <label for="password_confirmation" class="form-label">Confirm Password</label>
    <input type="password"
           id="password_confirmation"
           name="password_confirmation"
           class="form-control">
  </div>
</div>
