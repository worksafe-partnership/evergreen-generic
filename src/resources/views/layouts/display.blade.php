@extends('egl::layouts.app')

@section('heading')
    @includeIf("egl::partials.heading")
@endsection

@section("content")
    @if($pageType == "edit" || $pageType == "create")
        <form class="egl-form" action="{{ $formPath }}" method="POST" enctype='multipart/form-data'>
            {{ csrf_field() }}
    @endif

    @if(is_null($customBlade))
        @if (env('APP_ENV') == 'local')
            @include("modules.$identifierPath.display")
        @else
            @includeIf("modules.$identifierPath.display")
        @endif
    @else
        @includeIf($customBlade)
    @endif

@endsection

@include("egl::partials.autocomplete")
@include("egl::partials.ckeditor")
