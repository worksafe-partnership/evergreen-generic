<script>

var options = {
    processing: true,
    serverSide: {{$datatable['serverSide']}},
    stateSave: true,
    responsive: true,
    ajax: '{{$datatable["data"]}}',
    fnRowCallback: function( nRow, data, iDisplayIndex ) {
        if(typeof(data.deleted_at) != "undefined" && data.deleted_at != null){
            nRow.className+= " deleted";
        }
        return nRow;
    },
    buttons: [
        { extend: 'copy', className: 'button is-light' },
        { extend: 'excel', className: 'button is-light' },
        { extend: 'pdf', className: 'button is-light' },
        { extend: 'csv', className: 'button is-light' }
    ]
};

options.columnDefs = [
    @if (!empty($datatable['columns']))
        @foreach ($datatable['columns'] as $col => $value) {
            targets: ["{{$col}}"],
            @foreach ($value as $key => $val)
                {{$key}}: "{!!$val!!}",
            @endforeach
            @if (!isset($value['render']) && (!isset($value['raw']) || (!$value['raw'])))
                render: $.fn.dataTable.render.text(),
            @endif
        },
        @endforeach
    @endif
];

options.columns = [
    @if(!empty($datatable['columns']))
        @foreach($datatable['columns'] as $col => $value)
            {
                data: '{{ $col }}',
                @if (isset($value["col_type"]) && $value['col_type'] == "date")
                    render: function ( data, type, row ) {
                        if ( (type === 'display' || type === 'filter') && typeof(data) != "undefined") {
                            if (data != '' && data != null) {
                                var dateFormat = "{{isset($value['date_format']) ? $value['date_format'] : 'DD/MM/YYYY'}}";
                                return (moment(data * 1000).format(dateFormat));
                            } else {
                                return ''; // return blank to hide zero timestamp
                            }
                        }
                        return data;
                    },
                @endif
                name: '{{ $col }}'
            },
        @endforeach
    @endif
];

options.order = [
    @if(!empty($datatable['order']))
        @foreach ($datatable['order'] as $col => $order)
            $('th.{{$col}}').index(), "{{$order}}"
        @endforeach
    @else
        0, "DESC"
    @endif
];

options.dom =  'Bfrtip';
options.oLanguage = {
    "sLengthMenu": "Show _MENU_ records",
    "sSearch": "Search:",
};

var table = $('#{{$datatable["name"]}}').DataTable(options);

table.on("draw.dt", function (e, settings, json, xhr) {
    $(".nano").nanoScroller();
});

@if(isset($datatable["href"]))
    $('#{{$datatable["name"]}} tbody').on( 'click', 'tr', function (e) {
        var row = table.row(this);
        if (!row.responsive.hasHidden()) {
            if (e.currentTarget.classList.contains("multiselect")) {
                var checkbox = $(e.currentTarget).find(".b-checkbox input[type=checkbox]");
                if (checkbox.is(":checked")) {
                    checkbox.removeAttr("checked");
                } else {
                    checkbox.attr("checked", "checked");
                }
            } else {
                e.preventDefault();
                var d = table.row( this ).data();
                var url = '{{$datatable["href"]}}';
                document.location.href = url.replace("%ID%", d.id);
            }
        }
    } );
    table.on('responsive-display', function (e, datatable, row, showHide, update){
        if (showHide) {
            var url = '{{$datatable["href"]}}';
            $("ul[data-dtr-index=" + row.index() + "] li").last().after("<li><a href='" + url.replace("%ID%", row.data().id) + "' class='button is-success'>View {{$config['singular']}}</a></li>")
        }
    });
@else
    $('#{{$datatable["name"]}}').addClass("dt-no-link")
@endif


</script>
