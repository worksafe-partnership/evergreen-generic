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
                    <form class="form" role="form" method="POST" action="{{ url('/login') }}">
                        {!! csrf_field() !!}

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
                            <p class="control">
                                <label class="checkbox">
                                  <input type="checkbox" name="remember">
                                  Remember Me</a>
                              </label>
                            </p>
                        </div>
                        <div class="field is-grouped">
                            <p class="control">
                                <button type="submit" class="button is-primary">Login</button>
                            </p>
                            <p class="control">
                                <a href="{{ url('/password/reset') }}" class="button is-link">Forgot Password?</a>
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
