<select id="eg-search" name="search" placeholder="Search..."></select>

@push('scripts')
	<script type="text/javascript">
		$("#eg-search").selectize({
            valueField: 'url',
            labelField: 'value',
            searchField: 'value',
            create: false,
            onItemAdd: function(value, item) {
                window.location = value;
            },
            load: function(query, callback) {
                if (!query.length) return callback();
                $.ajax({
                    url: "{{config("egc.search.url")}}",
                    data: {
                    	_token: $('input[name="_token"]').val(),
                    	search: query
                    },
                    type: 'POST',
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        callback(res);
                    }
                });
            }
        });
	</script>
@endpush
