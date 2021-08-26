{{ header("X-FRAME-OPTIONS: DENY") }}
{{ header("Content-Security-Policy: frame-ancestors 'none'") }}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}?<?php echo filemtime(public_path().'/css/app.css'); ?>" rel="stylesheet">
    <script src="{{ asset('js/app.js') }}?<?php echo filemtime(public_path().'/js/app.js'); ?>"></script>
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
<body>
    @php
/*        if(isset($_COOKIE['appMenuState'])) {
            $menuState = $_COOKIE['appMenuState'];
        } else {
            $menuState = 1;
        }
        if(isset($_COOKIE['pinnedState'])) {
            $pinnedState = $_COOKIE['pinnedState'];
        } else {
            $pinnedState = 0;
        }
        if(isset($_COOKIE['menuButtonState'])) {
            $menuButtonState = $_COOKIE['menuButtonState'];
        } else {
            $menuButtonState = 0;
        } */

        $menuState = 1;
        $pinnedState = 0;
        $menuButtonState = 0;
    @endphp
    <aside class="menu @if ($menuState)
        is-active pinned
    @endif" id="app-menu">
        <div class="nano">
            <div class="nano-content">
                <div class="menu-top"></div>
                <div class="site-logo" style="background-image:url('/logo.png');"
                @if (config('egc.sidebar_logo_href.on'))
                    @if (is_null(config('egc.sidebar_logo_href.on')))
                     onclick="document.location = '/';"
                    @else
                     onclick="document.location = '{{config('egc.sidebar_logo_href.url')}}';"
                    @endif
                @endif
                ></div>
                @if (config("egc.search.on"))
                    @includeIf("egl::partials.search")
                @endif
                <ul class="menu-list">
                    @includeIf("egl::partials.sidebar.links")
                </ul>
            </div>
        </div>
    </aside>
    <div class="bottom-menu">

<div class="profile">
                <div class="links">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="button is-primary is-outlined is-inverted">
            Logout
        </a>
        <form   id="logout-form"
                action="{{ route('logout') }}"
                method="POST"
                style="display: none;">
            {{ csrf_field() }}
        </form>
                <a href="https://evergreencomputing.com/case_study?site={{env('EGC_PROJECT_NAME')}}" class="egc-advert" target="_blank" title="Helping streamline business processes with bespoke application software development, customised CRM systems and online database systems."></a>
                </div>
            </div>
    </div>
    <main id="app-main">
        <div class="nano">
            <div class="nano-content">
                <div class="toolbar">
                    <section class="breadcrumb-bar section">
                        <div class="container is-fluid">
                            <div class="top-menu">
                                <div class="level">
                                    <span id="pin-toggle" title="Pin/Unpin main menu" class="button is-medium is-unselectable">
                                        <span>{{icon('menu', '1.5rem')}}</span>
                                    </span>
                                    <div class="breadcrumbs level-left">
                                        @includeIf("egl::partials.toolbar.breadcrumbs")
                                    </div>
                                    <div class="pills level-right">
                                        @includeIf("egl::partials.toolbar.pills")
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="button-bar section">
                        <div class="container is-fluid">
                            <div class="top-menu">
                                <div class="heading">
                                    @yield("heading")
                                </div>
                                <div class="action-buttons">
                                    @includeIf("egl::partials.toolbar.action-buttons")
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                @if (!empty($tabLinks))
                <div class="tabmenu">
                    @includeIf("egl::partials.toolbar.tabs")
                </div>
                @endif
                <div class="main">
                    <div class="trow">
                    @if (!empty($menuLinks))
                        <div class="tcol" style="width: 220px">
                            @includeIf("egl::partials.toolbar.menu")
                        </div>
                    @endif
                        <div class="tcol">
                            <section class="section">
                                <div class="container is-fluid">
                                    @yield("content")
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @if(isset($pageType) && ($pageType == "edit" || $pageType == "create"))
            <div class="bottom-bar">
                <div class="field is-grouped is-grouped-centered ">
                    @foreach($formButtons as $button)
                        <p class="control">
                            @if(isset($button['href']))
                                <a href="{{$button['href']}}"
                            @else
                                <button
                            @endif

                            @if(isset($button['class'])   && is_array($button['class'])) class="{{implode(" ", $button['class'])}}" @endif
                            @if(isset($button['onclick']) && !empty($button['onclick'])) onclick="{{$button['onclick']}}"           @endif
                            @if(isset($button['name'])    && !empty($button['name'])) name="{{$button['name']}}"                    @endif
                            @if(isset($button['value'])   && !empty($button['value'])) value="{{$button['value']}}"                 @endif
                            @if(isset($button['id'])      && !empty($button['id']))      id="{{$button['id']}}"                     @endif

                            >

                            @if(isset($button['icon_before']) && !empty($button['icon_before']))
                                {{icon($button['icon_before'])}}&nbsp;
                            @endif

                            {{$button['label']}}

                            @if(isset($button['icon_after']) && !empty($button['icon_after']))
                                &nbsp;{{icon($button['icon_after'])}}
                            @endif


                            @if(isset($button['href']))
                                </a>
                            @else
                                </button>
                            @endif
                        </p>
                    @endforeach
                </div>
            </div>
        </form>
    @endif
    @stack('scripts')
    <script>
        $(".nano").nanoScroller({ iOSNativeScrolling: true });
    </script>
    @include("egl::partials.toastr")



    @yield('body-content')
    <script>
    // polyfill for svg icons ie8+
    !function(a,b){"function"==typeof define&&define.amd?define([],function(){return a.svg4everybody=b()}):"object"==typeof module&&module.exports?module.exports=b():a.svg4everybody=b()}(this,function(){function a(a,b,c){if(c){var d=document.createDocumentFragment(),e=!b.hasAttribute("viewBox")&&c.getAttribute("viewBox");e&&b.setAttribute("viewBox",e);for(var f=c.cloneNode(!0);f.childNodes.length;)d.appendChild(f.firstChild);a.appendChild(d)}}function b(b){b.onreadystatechange=function(){if(4===b.readyState){var c=b._cachedDocument;c||(c=b._cachedDocument=document.implementation.createHTMLDocument(""),c.body.innerHTML=b.responseText,b._cachedTarget={}),b._embeds.splice(0).map(function(d){var e=b._cachedTarget[d.id];e||(e=b._cachedTarget[d.id]=c.getElementById(d.id)),a(d.parent,d.svg,e)})}},b.onreadystatechange()}function c(c){function e(){for(var c=0;c<o.length;){var h=o[c],i=h.parentNode,j=d(i),k=h.getAttribute("xlink:href")||h.getAttribute("href");if(!k&&g.attributeName&&(k=h.getAttribute(g.attributeName)),j&&k){if(f)if(!g.validate||g.validate(k,j,h)){i.removeChild(h);var l=k.split("#"),q=l.shift(),r=l.join("#");if(q.length){var s=m[q];s||(s=m[q]=new XMLHttpRequest,s.open("GET",q),s.send(),s._embeds=[]),s._embeds.push({parent:i,svg:j,id:r}),b(s)}else a(i,j,document.getElementById(r))}else++c,++p}else++c}(!o.length||o.length-p>0)&&n(e,67)}var f,g=Object(c),h=/\bTrident\/[567]\b|\bMSIE (?:9|10)\.0\b/,i=/\bAppleWebKit\/(\d+)\b/,j=/\bEdge\/12\.(\d+)\b/,k=/\bEdge\/.(\d+)\b/,l=window.top!==window.self;f="polyfill"in g?g.polyfill:h.test(navigator.userAgent)||(navigator.userAgent.match(j)||[])[1]<10547||(navigator.userAgent.match(i)||[])[1]<537||k.test(navigator.userAgent)&&l;var m={},n=window.requestAnimationFrame||setTimeout,o=document.getElementsByTagName("use"),p=0;f&&e()}function d(a){for(var b=a;"svg"!==b.nodeName.toLowerCase()&&(b=b.parentNode););return b}return c});
    svg4everybody();

    $('.egl-form').submit(function(){
        $('.submitbutton').addClass('is-loading');
        setTimeout( function(){
            $('.submitbutton').removeClass('is-loading');
        }, 2000);
    });
    </script>
</body>
</html>
