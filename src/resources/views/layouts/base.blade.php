{{ header("X-FRAME-OPTIONS: DENY") }}
{{ header("Content-Security-Policy: frame-ancestors 'none'") }}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ asset('js/login.js') }}"></script>

    @stack('styles')
    <style id="antiClickjack">body{display:none !important;}</style>
    <script type="text/javascript">
        if (self === top) { 
            var antiClickjack = document.getElementById("antiClickjack");
            antiClickjack.parentNode.removeChild(antiClickjack); 
        } else { 
            top.location = self.location; 
        }
    </script>
</head>
<body class="base-egl">
    
    @yield('content')
    @stack('scripts')
    @include("egl::partials.toastr")
</body>
</html>
