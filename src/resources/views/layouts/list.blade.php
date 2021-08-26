@extends('egl::layouts.app')

@section('heading')
    @includeIf("egl::partials.heading")
@endsection

@section('content')
    @includeIf("modules.$identifierPath.list-head")

    @if (!empty($datatable))
        @includeIf("egl::partials.datatables.datatable-html")
    @elseif (env('APP_ENV') == 'local')
        <h1>Please add the datatable information to your config</h1>
    @endif

    @includeIf("modules.$identifierPath.list-tail")
@endsection

@push('scripts')
    @if (!empty($datatable))
        @includeIf("egl::partials.datatables.datatable")
    @endif
@endpush
