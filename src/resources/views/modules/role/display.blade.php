<div class="columns">
	<div class="column is-5 is-offset-1">
		<div class="field">
			{{ EGForm::text("name", [
				"label" => "Name",
				"value"  => $record['name'],
				"type" => $pageType
			]) }}
		</div>
	</div>

	<div class="column is-5">
		<div class="field">
			{{ EGForm::text("slug", [
				"label" => "Slug",
				"value"  => $record['slug'],
				"type" => $pageType
			]) }}
		</div>
	</div>
</div>
<div class="columns">
    <div class="column is-10 is-offset-1">
        <div class="field">
            <table id="permissions-table" class="table is-bordered is-striped is-hoverable">
                <thead>
                    <th>Area/Config</th>
                    @foreach ($notTickedCol as $key => $col)
                        <th class="center">
                            {{ str_replace('-', ' ', title_case(kebab_case($key))) }}
                            {{ EGForm::checkbox('column_tick_all_'.$key, [
                                "label" => "",
                                "type" => $pageType,
                                "value" => $col == 0 ? "1" : "",
                                "attributes" => [
                                    "data-type" => $key,
                                    "data-all" => "col"
                                ],  
                            ]) }}
                        </th>
                    @endforeach
                    <th class="center">Tick All</th>
                </thead>
                <tbody>
                    @foreach ($abilitiesList as $perm)
                        @php
                            $notTicked = 0;
                        @endphp
                        <tr>
                            <td>{{ $perm['title'] }}</td>
                            @foreach ($perm['permissions'] as $key => $p)
                                @if ($key != 'extras')
                                    @if ($p == 'EXCLUDE')
                                        <td></td>
                                    @else 
                                        @php
                                            if (!isset($record['permissions'][$key]) || $record['permissions'][$key] != "1") {
                                                $notTicked++;
                                            }
                                        @endphp
                                        <td class="center">
                                            {{ EGForm::checkbox("permissions[".$key."]", [
                                                "label" => "",
                                                "value" => isset($record['permissions'][$key]) ? $record['permissions'][$key] : "",
                                                "type" => $pageType,
                                                "attributes" => [
                                                    "data-identifier" => $perm['identifier'],
                                                    "data-perm" => true,
                                                    "data-type" => lcfirst(str_replace(' ', '', $p)),
                                                    "data-item" => true
                                                ]
                                            ])}}
                                        </td>
                                    @endif
                                @endif
                            @endforeach
                            <td>
                                @foreach ($perm['permissions']['extras'] as $key => $p)
                                    @php
                                        if (!isset($record['permissions'][$key]) || $record['permissions'][$key] != "1") {
                                            $notTicked++;
                                        }
                                    @endphp
                                    {{ EGForm::checkbox("permissions[".$key."]", [
                                        "label" => str_replace('-', ' ', title_case($p)),
                                        "value" => isset($record['permissions'][$key]) ? $record['permissions'][$key] : "",
                                        "type" => $pageType,
                                        "attributes" => [
                                            "data-extra" => "true",
                                            "data-identifier" => $perm['identifier'],
                                            "data-type" => "extras",
                                            "data-item" => true
                                        ]       
                                    ]) }}
                                @endforeach
                            </td>
                            <td class="center">
                                {{ EGForm::checkbox("row_tick_all_".$perm['identifier'], [
                                    "label" => "",
                                    "type" => $pageType,
                                    "value" => $notTicked == 0 ? "1" : "",
                                    "attributes" => [
                                        "data-identifier" => $perm['identifier'],
                                        "data-all" => "row"
                                    ],
                                ]) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push ('styles')
<style>
    .table .center p, .table .center {
        text-align: center;
    }

    .table .center .b-checkbox {
        width: 18px;
        height: 18px;
        margin: 0 auto;
    }
</style>
@endpush
@push ('scripts')
<script>
    $('[data-all="row"]').change(function () {
        var ticked = this.checked;
        $('[data-identifier="' + $(this).data('identifier') + '"]').each(function () {
            if (this.checked != ticked) {
                $(this).prop("checked", ticked);
		$('[name="' + this.id + '"]').val(ticked ? "1" : "");
            }
            checkAll("type", $(this).data('type'));
        });
    });

    $('[data-all="col"]').change(function () {
        var ticked = this.checked;
        $('[data-type="' + $(this).data('type') + '"]').each(function () { 
            if (this.checked != ticked) {
                $(this).prop("checked", ticked);
		$('[name="' + this.id + '"]').val(ticked ? "1" : "");
            }
            checkAll("identifier", $(this).data('identifier'));
        });
    });

    // Item tick/untick
    $('[data-item="true"]').change(function () {
        checkAll("type", $(this).data('type'));
        checkAll("identifier", $(this).data('identifier'));
    });

    function checkAll(type, selector) {
        let unticked = false;
        $('[data-'+type+'="' + selector + '"]').each(function () {
            if (!this.checked && $(this).data('all') == undefined) {
                unticked = true;
            }
        });

        var row = "row_tick_all_" + selector;
        if (type == "type") {
            var row = "column_tick_all_" + selector;
        }
        let rowAll = $('[data-'+type+'="' + selector + '"][data-all="row"]');

        if (unticked != rowAll.checked) {
            $('[name="' + row + '"]').val(!unticked ? "1" : "");
            $('[id="' + row + '"]').prop("checked", !unticked);
        }
    }
</script>
@endpush
