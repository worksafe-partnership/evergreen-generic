<?php

namespace Evergreen\Generic\App\Http\Controllers;

use Evergreen\Generic\App\Page;

class PageController extends Controller
{
    public function __construct(Page $mod)
    {
        $this->middleware('auth');
        $this->model = $mod;
    }
}
