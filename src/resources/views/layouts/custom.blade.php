@extends('egl::layouts.app')

@section('heading')
	@includeIf("egl::partials.heading")
@endsection

@section('content')
	@includeIf($view)
@endsection