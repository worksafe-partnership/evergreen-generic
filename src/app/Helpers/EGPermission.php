<?php
if (! function_exists('can')) {
    function can($perm, $c)
    {
        if (is_string($c)) {
            $c = config("structure.".$c.".config");
        }

        if (!isset($c['permissions']) || $c['permissions'] === false) {
            return true;
        }
        if ($c['permissions'] === true ||
            (is_array($c['permissions']) && isset($c['permissions']['on']) && $c['permissions']['on'] == true) ||
            (is_array($c['permissions']) && isset($c['permissions']['exclude']) && in_array($perm, $c['permissions']['exclude']))) {
            if (Gate::allows($perm.'-'.$c['identifier_path'])) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('usingSoftDeletes')) {
    function usingSoftDeletes($model, $record = null)
    {
        if (is_null($model)) {
            return false;
        }
        if (in_array("Illuminate\Database\Eloquent\SoftDeletes", class_uses($model))) {
            if (is_null($record)) {
                return true;
            } else if (isset($record->deleted_at) && !is_null($record->deleted_at)) {
                return true;
            }
        }
        return false;
    }
}
