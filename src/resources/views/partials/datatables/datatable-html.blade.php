<table class="table table-bordered datatable-table display" id="{{ $datatable['name'] }}">
    <thead>
        <tr>
            @foreach($datatable['columns'] as $key => $value)
                <th class="{{$key}}">{{ isset($value['label']) ? $value['label'] : "" }}</th>
            @endforeach
        </tr>
    </thead>
</table>