{{ EGForm::hidden("id", [
    "value" => $record['id'],
    "type" => $pageType
])}}
<div class="columns">
    <div class="column is-4">
        <div class="field">
            {{ EGForm::email("email", [
                "label" => "Email",
                "value"  => $record['email'],
                "type" => $pageType
            ]) }}
        </div>
    </div>

    <div class="column is-4">
        <div class="field">
            {{ EGForm::text("name", [
                "label" => "Name",
                "value"  => $record['name'],
                "type" => $pageType
            ]) }}
        </div>
    </div>
</div>
<div class="columns">
    <div class="column is-4">
        <div class="field">
            {{ EGForm::password("password", [
                "label" => "Password",
                "attributes" =>[
                    "placeholder" => "Password Set"
                ],
                "type" => $pageType
            ]) }}
        </div>
    </div>
    @if($pageType != 'view')
        <div class="column is-4">
            <div class="field">
            {{ EGForm::password("password_confirmation", [
                "label" => "Verify Password",
                "attributes" =>[
                    "placeholder" => "Password Set"
                ],
                "type" => $pageType
            ]) }}
            </div>
        </div>
    @endif
</div>
@can('edit-role')
    <div class="columns">
        <div class="column">
            <div class="field">
            {{ EGForm::multiCheckbox("roles", [
                "type"  => $pageType,
                "label" => "Roles",
                "list"  => $roles,
                "values"  => $currentRoles,
                "classes" => ['roles'],
                "list-style" => "multi-block"
            ]) }}
        </div>
    </div>
@endcan