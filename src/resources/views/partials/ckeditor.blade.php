<!-- global parameter set within the EGForm::ckeditor method -->
@if (isset($GLOBALS['ckeditor']) && $GLOBALS['ckeditor'])
	@push('scripts')
		<script src="/vendor/unisharp/laravel-ckeditor/ckeditor.js"></script>
	@endpush
@endif