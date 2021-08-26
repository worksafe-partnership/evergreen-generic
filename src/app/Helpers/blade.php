<?php

/**
* Creates an icon (svg) using eg-icons.svg
* @param  string $icon  icon key
* @param  string $size CSS unit size default 1rem
* @param  bool $isEcho whether to return or echo (return for building strings in php and echo for blade echo)
*/
if (! function_exists('icon')) {
    function icon($icon, $size = null, $isEcho = true)
    {
        $size_inc = '';
        if ($size !== null) {
            $size_inc = 'style="width:'. $size .';height:'. $size .'"';
        }

        $result = '<svg class="eg-'.$icon.'" '.$size_inc.' ><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="/eg-icons.svg#eg-'.$icon.'"></use></svg>';

        if ($isEcho) {
            echo $result;
        } else {
            return $result;
        }
    }
}
