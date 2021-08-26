<?php

namespace Evergreen\Generic\App\Helpers;

use Carbon;

class EGUtil
{
    public static function nextNo($key)
    {
        $aNextNumber = \App\NextNumber::firstOrNew(["key" => $key]);

        if (!$aNextNumber->exists) {
            $aNextNumber->next_number = 1;
            $aNextNumber->save();
        }

        $next_no = $aNextNumber->next_number;

        $aNextNumber->update(["next_number" => $aNextNumber->next_number+1]); // increase the number

        return $next_no;
    }

    /**
     * Multi Checkbox Functions
     */

    /**
     * Converts an array for multi checkboxes into a delimited string
     * @param  array $array      multicheckbox array
     * @param  string $delimiter the delimiter used
     * @return string
     */
    public static function convertMCArrayToString($array, $delimiter = ":")
    {
        $aChecked=array();
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if ($value == 1) {
                    $aChecked[] = $key;
                }
            }
            if (!empty($aChecked)) {
                return ":".implode(":", $aChecked).":";
            }
        }
    }

    /**
    * Converts a delimited multi checkbox string into an array
    * @param  string $string delimited string of multi checkbox values
    * @return array
    */
    public static function convertMCStringToArray($string)
    {
        $aValues=array();
        if (strlen($string)) {
            $aValues=array_flip(array_filter(explode(":", $string)));
            if (!empty($aValues)) {
                foreach ($aValues as $key => $value) {
                    $aValues[$key] = true;
                }
            }
        }
        return $aValues;
    }

    /** Date/Time Functions */

    public static function formatHumanDate($value, $fromFormat = "d/m/Y")
    {
        return EGUtil::formatDate($value, $fromFormat, "Y-m-d");
    }

    public static function formatDBDate($value, $toFormat = "d/m/Y")
    {
        return EGUtil::formatDate($value, "Y-m-d", $toFormat);
    }

    public static function formatDate($value, $fromFormat, $toFormat)
    {
        $value = trim(str_replace("00:00:00", "", $value));
        if (!is_null($value) && $value != "0000-00-00" && !empty($value)) {
            return strlen($value) ? Carbon::createFromFormat($fromFormat, $value)->format($toFormat) : null;
        }
        return null;
    }

    public static function formatHumanDateTime($value, $fromFormat = "d/m/Y H:i:s")
    {
        return EGUtil::formatDateTime($value, $fromFormat, "Y-m-d H:i:s");
    }

    public static function formatDBDateTime($value, $toFormat = "d/m/Y H:i")
    {
        return EGUtil::formatDateTime($value, "Y-m-d H:i:s", $toFormat);
    }

    public static function formatDateTime($value, $fromFormat, $toFormat)
    {
        if (!is_null($value) && $value != "0000-00-00 00:00:00" && !empty($value)) {
            return strlen($value) ? Carbon::createFromFormat($fromFormat, $value)->format($toFormat) : null;
        }
        return null;
    }
}
