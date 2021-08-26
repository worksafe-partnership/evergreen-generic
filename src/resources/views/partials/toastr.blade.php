<script>
    toastr.options.closeHtml = ' <button class="delete"></button>';
    toastr.options.timeout = 10000;
    toastr.options.positionClass = 'toast-bottom-right';
    toastr.options.newestOnTop = false;
</script>

<?php $toast = Session::pull('toast');?>
<script type="text/javascript">
    @if (!is_null($toast))
        toastr.{{ $toast['type'] }}(
            "{{ $toast['message']}}",
            "{{ $toast['title']}}");
    @endif
    @if($errors->count() > 0)
            @foreach($errors->all() as $error)
                toastr.error("{!! $error !!}", null, {closeButton: true, preventDuplicates: true, timeOut: 0});
            @endforeach
    @endif
</script>