@extends('egl::layouts.base')

@section('content')
<section class="hero is-primary is-fullheight">
  <div class="hero-body">
    <div class="container">
        <div class="card egl-base">
            <header class="card-header">
                <p class="card-header-title">
                    Login
                </p>
                <a class="card-header-icon">
                    <span class="site-logo" style="background-image:url('/logo.png');">
                    </span>
                </a>
            </header>
            <div class="card-content">
                <div class="content">
                    <form class="form" role="form" method="POST" action="{{ url('/password/reset') }}">
                        {!! csrf_field() !!}

                        <input type="hidden" name="token" value="{{$token}}" />

                        <div class="field">
                            <label class="label">E-Mail Address</label>
                            <p class="control">
                                <input type="email" class="input" name="email" value="{{ old('email') }}">
                            </p>
                        </div>
                        <div class="field">
                            <label class="label">Password</label>
                            <p class="control">
                                <input type="password" class="input" name="password">
                            </p>
                        </div>
                        <div class="field">
                            <label class="label">Confirm Password</label>
                            <p class="control">
                                <input type="password" class="input" name="password_confirmation">
                            </p>
                        </div>
                        <div class="field is-grouped">
                            <p class="control">
                                <button type="submit" class="button is-primary">Reset Password</button>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
</section>



@endsection
