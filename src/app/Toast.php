<?php

namespace Evergreen\Generic\App;

use Illuminate\Support\Facades\Facade;

class Toast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-toastr';
    }
}
