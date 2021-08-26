@push('scripts')
<script>
$('.selectize').selectize({
    valueField: 'id',
    labelField: 'label',
    searchField: 'label',
    create: false,
    render: {
        option: function(item, escape) {
            return '<div>' +
                        '<span>' +
                            item.label +
                        '</span>' +
                    '</div>';
        }
    },
    load: function(query, callback) {
        if (!query.length) return callback();
        var url = this.$input[0].getAttribute('data-url');
        if (url != null) {
            $.ajax({
                url: '/autocomplete/'+url+'/'+query,
                type: 'GET',
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res.results);
                }
            });
        }
    }
});
</script>
@endpush
