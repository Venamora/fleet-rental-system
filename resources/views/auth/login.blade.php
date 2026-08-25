@extends('layouts.guest')
@section('content')
<form method="POST" action="{{ url('/login') }}" class="login-form" novalidate>
    @csrf
    <div class="field-group"><label for="username">Nama pengguna</label><input id="username" name="username" autocomplete="username" value="{{ old('username') }}" required autofocus placeholder="Masukkan nama pengguna">@error('username')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div class="field-group"><div class="label-row"><label for="password">Kata sandi</label></div><input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
    @error('login')<div class="inline-error" role="alert">{{ $message }}</div>@enderror
    <button class="button button-primary login-submit" type="submit">Masuk ke Fleetdesk <span aria-hidden="true">&rarr;</span></button>
</form>
@endsection
