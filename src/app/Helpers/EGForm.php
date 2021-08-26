<?php

namespace Evergreen\Generic\App\Helpers;

use EGFiles;
use Storage;
use Config;

class EGForm
{
    /**
    * Aliases for input elements
    * These will make use of the EGForm::Input() method
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function text($key, $aInp)
    {
        $aInp['inp_type'] = "text";
        EGForm::input($key, $aInp);
    }

    public static function email($key, $aInp)
    {
        $aInp['inp_type'] = "email";
        EGForm::input($key, $aInp);
    }

    public static function hidden($key, $aInp)
    {
        $aInp['inp_type'] = "hidden";
        EGForm::input($key, $aInp);
    }

    public static function currency($key, $aInp)
    {
        $aInp['inp_type'] = "number";
        if (!isset($aInp['prefix'])) {
            $aInp['prefix'] = [
                'prefix' => '&pound;',
                'icon' => false
            ];
        }
        if (!isset($aInp['attributes']['step'])) {
            if (isset($aInp['attributes'])) {
                $aInp['attributes']['step'] = 'any';
            } else {
                $aInp['attributes'] = [];
                $aInp['attributes']['step'] = 'any';
            }
        }
        EGForm::input($key, $aInp);
    }

    public static function number($key, $aInp)
    {
        $aInp['inp_type'] = "number";
        EGForm::input($key, $aInp);
    }

    public static function password($key, $aInp)
    {
        $aInp['inp_type'] = "password";
        EGForm::input($key, $aInp);
    }

    public static function date($key, $aInp)
    {
        $aInp['inp_type'] = "date";

        if (!isset($aInp['classes'])) {
            $aInp['classes'] = [];
        }
        $aInp['classes'][] = 'datepicker';

        if (!isset($aInp['attributes'])) {
            $aInp['attributes'] = [];
        }

        if (!isset($aInp['attributes']['dateFormat'])) {
            $aInp['attributes']['dateFormat'] = 'Y-m-d';
        }

        if (!isset($aInp['attributes']['altInput'])) {
            $aInp['attributes']['altInput'] = true;
        }

        if (!isset($aInp['attributes']['altFormat'])) {
            if (!is_null(Config::get("egc.dates.date"))) {
                $aInp['attributes']['altFormat'] = Config::get("egc.dates.date");
            } else {
                $aInp['attributes']['altFormat'] = 'd/m/Y';
            }
        }

        EGForm::input($key, $aInp);
    }

    public static function time($key, $aInp)
    {
        $aInp['inp_type'] = "time";

        if (!isset($aInp['classes'])) {
            $aInp['classes'] = [];
        }
        $aInp['classes'][] = 'datetimepicker';

        if (!isset($aInp['attributes'])) {
            $aInp['attributes'] = [];
        }

        if (!isset($aInp['attributes']['dateFormat'])) {
            if (!is_null(Config::get("egc.dates.time"))) {
                $aInp['attributes']['dateFormat'] = Config::get("egc.dates.time");
            } else {
                $aInp['attributes']['dateFormat'] = 'H:i';
            }
        }

        if (!isset($aInp['attributes']['enableTime'])) {
            $aInp['attributes']['enableTime'] = true;
        }

        if (!isset($aInp['attributes']['noCalendar'])) {
            $aInp['attributes']['noCalendar'] = true;
        }

        EGForm::input($key, $aInp);
    }

    public static function datetime($key, $aInp)
    {
        $aInp['inp_type'] = "datetime";

        if (!isset($aInp['classes'])) {
            $aInp['classes'] = [];
        }
        $aInp['classes'][] = 'datetimepicker';

        if (!isset($aInp['attributes'])) {
            $aInp['attributes'] = [];
        }

        if (!isset($aInp['attributes']['dateFormat'])) {
            $aInp['attributes']['dateFormat'] = 'Y-m-d H:i:s';
        }

        if (!isset($aInp['attributes']['altInput'])) {
            $aInp['attributes']['altInput'] = true;
        }

        if (!isset($aInp['attributes']['altFormat'])) {
            if (!is_null(Config::get("egc.dates.datetime"))) {
                $aInp['attributes']['altFormat'] = Config::get("egc.dates.datetime");
            } else {
                $aInp['attributes']['altFormat'] = 'd/m/Y H:i';
            }
        }

        if (!isset($aInp['attributes']['enableTime'])) {
            $aInp['attributes']['enableTime'] = true;
        }

        EGForm::input($key, $aInp);
    }

    public static function ckeditor($key, $aInp)
    {
        $GLOBALS['ckeditor'] = 1;
        if (!isset($aInp['classes'])) {
            $aInp['classes'] = [];
        }
        $aInp['classes'][] = 'ckeditor';

        EGForm::textArea($key, $aInp);
    }

    public static function file($key, $aInp)
    {
        $aInp['inp_type'] = "file";
        if (!isset($aInp['hidden_override'])) {
            $aInp['hidden_override'] = true;
        }
        if (!isset($aInp['hidden_override_name'])) {
            $aInp['hidden_override_name'] = 'file';
        }
        if (!isset($aInp['hidden_override_value'])) {
            $aInp['hidden_override_value'] = '1';
        }
        if (!isset($aInp['required_on_remove'])) {
            $aInp['required_on_remove'] = false;
        }
        EGForm::input($key, $aInp);
    }


    /**
    * Handles all "<input />" elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function input($key, $aInp)
    {
        $type = $aInp['type'];

        $bAllowInput = true;
        $bFileExists = false;
        $allowFileDelete = true;
        $allowFileReplace = true;

        if (isset($aInp['allowFileDelete'])) {
            $allowFileDelete = $aInp['allowFileDelete'];
        }

        if (isset($aInp['allowFileReplace'])) {
            $allowFileReplace = false;
            $bAllowInput = false;
        }

        $aInp = EGForm::setDefaults($key, $aInp, $type);
        $sInput = "";

        //handle file inputs
        if ($aInp["inp_type"] == "file") {
            //check if input is file and there is a stored value
            if (isset($aInp['value']) && !empty($aInp['value'])) {
                EGForm::hidden("current_file_id", [
                    'value' => $aInp['value'],
                    'type' => $aInp['type']
                ]);
                //get the file details
                if (is_integer($aInp['value'])) {
                    $file = EGFiles::find($aInp['value']);
                } else {
                    $file = json_decode($aInp['value']);
                    if (is_object($file)) {
                        $file = (array)$file;
                    }

                    if (is_array($file)) {
                        $original = $aInp;
                        if (isset($aInp['attributes']) && isset($aInp['attributes']['multiple'])) {
                            unset($aInp['attributes']['multiple']);
                        }

                        if (isset($aInp['label'])) {
                            echo '<label for="'.$key.'">'.$aInp['label'].':</label>';
                            unset($aInp['label']);
                        }

                        $aInp['allowFileReplace'] = false;

                        foreach ($file as $f) {
                            $aInp['value'] = $f;
                            EGForm::input(str_replace("[]", '', $key)."[".$f."]", $aInp);
                        }

                        unset($original['value']);
                        unset($original['label']);
                        if (isset($original['type']) && $original['type'] != "view") {
                            EGForm::input($key, $original);
                        }
                    }

                    return;
                }

                //check that a link is available and the file exists
                if (!is_null($file) && Storage::exists($file->location)) {
                    $bFileExists = true;
                }
            }

            if ($aInp['type'] == "view" && !$bFileExists) {
                $sInput .= '<div><i>No file attached</i></div>';
                if (!isset($aInp['always_allow']) || !$Ainp['always_allow']) {
                    $bAllowInput = false;
                }
            } else {
                if ($bFileExists) {
                    $sInput .= '<div><b>';
                    if (isset($aInp['show_image']) && $aInp['show_image']) {
                        $sInput .= '<a href="/download/'.$file['id'].'"><img src="/image/'.$file['id'].'" /></a><br />';
                    } else {
                        $sInput .= icon('attachment', '1.5rem', 0).'</span><a href="/download/'.$file['id'].'">'.$file['title'].'</a><br>';
                    }
                    $sInput .= '</b>';
                    if (!isset($aInp['disabled']) || !$aInp['disabled']) {
                        if ($aInp['required_on_remove']) {
                            $sInput .= "<br><label>Replace File:</label>";
                            echo '<script>
                                    window.onload = function(){document.getElementsByName("delete_'.$key.'")[0].onclick = function(){this.checked ? ifChecked() : ifNotChecked() }};
                                    function ifChecked(){
                                        document.getElementById("'.$aInp['hidden_override_name'].'").value = 0;
                                        var files = document.getElementsByName("'.$key.'");
                                        files[0].value = "";
                                        files[1].value = "";
                                    }
                                    function ifNotChecked(){
                                        document.getElementById("'.$aInp['hidden_override_name'].'").value = 1;
                                        var files = document.getElementsByName("'.$key.'");
                                        files[0].value = files[0].getAttribute("data-id");
                                        files[1].value = files[0].getAttribute("data-id");
                                    }
                                </script>';
                        } else {
                            if ($allowFileDelete) {
                                $sInput.= icon('trash', '1.5rem', 0).'<input type="checkbox" name="delete_'.$key.'"><br />';
                            }
                            if ($allowFileReplace) {
                                $sInput.= '<label>Replace File:</label>';
                            }
                        }
                    } else {
                        $bAllowInput = false;
                    }
                    $sInput .= '</div>';
                }
            }
        }

        if ($bAllowInput) {
            if (isset($aInp['currency']) && $aInp['currency']) {
                $sInput .= '<div class="currency">
                <span class="currency">&pound;</span>';
                $aInp['classes'][] = 'currency';
            }

            $aInp['classes'][] = 'input';

            $sAttr =  EGForm::buildInputAttr($key, $aInp, array(
                "type",
                "name",
                "class",
                "id",
                "value",
                "attributes",
                "required",
                "disabled",
            ));
            $divClasses = ['control'];
            if (isset($aInp['prefix']) && !isset($aInp['other'])) {
                $divClasses[] = "has-icons-left";
            }
            if (isset($aInp['suffix']) && !isset($aInp['other'])) {
                $divClasses[] = "has-icons-right";
            }

            $sInput .= '<div class="'.implode(" ", $divClasses).'"><input '.$sAttr.' />';
            if (isset($aInp['prefix']) && !isset($aInp['other'])) {
                $sInput.= EGForm::addIcons($aInp, 'prefix', 'left');
            }
            if (isset($aInp['suffix']) && !isset($aInp['other'])) {
                $sInput.= EGForm::addIcons($aInp, 'suffix', 'right');
            }
            $sInput .='</div>';
        }

        if (isset($aInp['hidden_override']) && isset($aInp['hidden_override_name']) && $aInp['hidden_override']) {
            echo "<input type='hidden' name='file' value='' />";
            echo "<input type='hidden' name='".$aInp['hidden_override_name']."' id='".
            $aInp['hidden_override_name']."' value='".$aInp['hidden_override_value']."' />";
        }

        EGForm::drawInputHTML($key, $aInp, $sInput);
    }

    public static function addIcons($input, $type, $direction)
    {
        $string = '';
        $size = "1rem";
        $prefix = $input[$type];
        $icon = true;
        if (is_array($input[$type])) {
            if (isset($input[$type][$type])) {
                $prefix = $input[$type][$type];
            } else {
                $prefix = '';
            }

            if (isset($input[$type]['size'])) {
                $size = $input[$type]['size'];
            }

            if (isset($input[$type]['icon']) && $input[$type]['icon'] == false) {
                $icon = false;
            }
        }

        $string .= '<span class="icon is-small is-'.$direction.'">';
        if ($icon) {
            $string .= icon($prefix, $size, false);
        } else {
            $string .= $prefix;
        }
        $string .='</span>';

        return $string;
    }


    /**
    * Text area elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function textArea($key, $aInp)
    {
        global $type;

        $aInp['inp_type'] = "textarea";

        $aInp['classes'][] = 'textarea';

        $aInp = EGForm::setDefaults($key, $aInp, $type);

        $sAttr = EGForm::buildInputAttr($key, $aInp, array(
            "class",
            "id",
            "name",
            "attributes",
            "required",
            "disabled",
            "rows",
        ));

        $sInput = '<textarea '.$sAttr.' >'.htmlentities($aInp['value']).'</textarea>';

        EGForm::drawInputHTML($key, $aInp, $sInput);
    }


    /**
    * Checkbox elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function checkbox($key, $aInp)
    {
        global $type;

        $aInp['inp_type'] = "checkbox";
        $aInp = EGForm::setDefaults($key, $aInp, $type);

        $sInput = '<div class="checkbox">';

        $sInput .= EGForm::buildCheckboxInput($key, $aInp);
        echo EGForm::displayErrors($key);

        $sInput .= '</div>';
        echo $sInput;
    }


    /**
    * Multiple checkbox elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function multiCheckbox($key, $aInp)
    {
        global $type;

        $aInp['inp_type'] = "checkbox";
        $aInp = EGForm::setDefaults($key, $aInp, $type);
        $originalLabel = $aInp['label'];
        $sInput = '';

        //Loop around list options
        if (!empty($aInp['list'])) {
            foreach ($aInp['list'] as $list_key => $list_label) {
                #Check to see if we have a match
                if (is_array($aInp['values']) && array_key_exists($list_key, $aInp['values'])) {
                    $aInp['value'] = $aInp['values'][$list_key];
                } else {
                    $aInp['value'] = "0";
                }

                /**
                * OPTIONS:
                * multi-block
                */
                // if (isset($aInp['list_style'])) {
                //     $aInp['list_style'] = "multi-block";
                // }

                $aInp['id'] = $key."[".$list_key."]";
                $aInp['key'] = $key."[".$list_key."]";
                $aInp['label'] = $list_label;
                $sInput.= '<div class="field '.(isset($aInp['list_style']) ? $aInp['list_style'] : '').'"><div class="checkbox">';

                $sInput .= EGForm::buildCheckboxInput($aInp['key'], $aInp);
                $sInput.="</div></div>";
            }
        }

        $sInput .= "";
        $aInp['label'] = $originalLabel;

        EGForm::drawInputHTML($key, $aInp, $sInput);
    }


    /**
    * Builds the checkbox HTML to be used by the input
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function buildCheckboxInput($key, $aInp)
    {
        $sAttr = EGForm::buildInputAttr($key, $aInp, array(
            "type",
            "id",
            "attributes",
            "required",
            "disabled",
            "checked"
        ));

        $checkboxTypes = '';
        if (isset($aInp['checkboxTypes'])) {
            if (is_array($aInp['checkboxTypes'])) {
                $checkboxTypes = implode(" ", $aInp['checkboxTypes']);
            } else {
                $checkboxTypes = $aInp['checkboxTypes'];
            }
        }

        $label = '';
        if ($aInp['inp_type'] != "hidden" && isset($aInp['label'])) {
            $label ='<label for="'.$key.'">'.$aInp['label'].'</label>';
        }

        // If the value is not '1', the checkbox is not checked
        if ($aInp['value'] != '1') {
            $aInp['value'] = '';
        }

        $sInput = <<<EOT
<div class="control">
    <div class="b-checkbox is-primary $checkboxTypes">
        <input type="hidden" class="checkboxValue" name="$key" value="{$aInp['value']}">
        <input class="styled" $sAttr>
        $label
    </div>
</div>
EOT;

        return $sInput;
    }

    public static function checkSelectOthers($aData)
    {
        $others = array();
        foreach ($aData as $key => $value) {
            if ($value == "OTHER" && isset($aData[$key.'_other'])) {
                $aData[$key] = $aData[$key.'_other'];
                unset($aData[$key.'_other']);
            }
        }

        return $aData;
    }


    /**
    * Select elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function select($key, $aInp)
    {
        global $type;

        $aInp['inp_type'] = "select";
        $aInp = EGForm::setDefaults($key, $aInp, $type);
        //get the value from the array if it exists
        if (array_search($aInp['value'], (array) $aInp['list'])) {
            $aInp['value'] = array_search($aInp['value'], (array) $aInp['list']);
        }

        if ($aInp['type'] == 'view' || !empty($aInp['disabled'])) {
            if (isset($aInp['list'][$aInp['value']])) {
                $aInp['value'] = $aInp['list'][$aInp['value']];
            } elseif (isset($aInp['other']) && $aInp['other'] == true) {
                $aInp['value'] = $aInp['value'];
            } else {
                $aInp['value'] = '';
            }

            //display as text field when viewing
            EGForm::text($key, $aInp);
        } else {
            //otherwise display as select drop down
            if (!empty($aInp['list'])) {
                $sAttr = EGForm::buildInputAttr($key, $aInp, array(
                    "name",
                    "class",
                    "id",
                    "disabled",
                ));
                $divClasses = ['control'];
                if (isset($aInp['prefix'])) {
                    $divClasses[] = "has-icons-left";
                }
                if (isset($aInp['suffix'])) {
                    $divClasses[] = "has-icons-right";
                }
                $sInput = '<div class="'.implode(' ', $divClasses).'">';
                $sInput .= '<div class="select">';
                $sInput .= '<select '.$sAttr;
                if (isset($aInp['other']) && $aInp['other'] == true) {
                    $sInput.= " onchange='".$key."changed();'";
                }
                $sInput.=' />';

                if (isset($aInp['selector']) && $aInp['selector']) {
                    if (isset($aInp['selector_message']) && !empty($aInp['selector_message'])) {
                        $message = $aInp['selector_message'];
                    } else {
                        $message = "Please choose...";
                    }
                    $sInput .= '<option value="">'.$message.'</option>';
                }

                $aInp['otherValue'] = '';
                if (isset($aInp['other']) && $aInp['other'] == true && !isset($aInp['list'][$aInp['value']]) && isset($aInp['value']) && $aInp['value'] != '') {
                    $aInp['otherValue'] = $aInp['value'];
                    $aInp['value'] = "OTHER";
                }

                foreach ($aInp['list'] as $keys => $value) {
                    $selected = "";
                    if ($aInp['value'] == $keys) {
                        $selected = "selected";
                    }
                    $sInput .= '<option value="'.$keys.'" '.$selected.'>'.htmlentities($value).'</option>';
                }

                $sInput .= '</select>';
                $sInput .= '</div>';
                if (isset($aInp['prefix'])) {
                    $sInput.= EGForm::addIcons($aInp, 'prefix', 'left');
                }
                if (isset($aInp['suffix'])) {
                    $sInput.= EGForm::addIcons($aInp, 'suffix', 'right');
                }
                $sInput .= '</div>';

                EGForm::drawInputHTML($key, $aInp, $sInput);

                if (isset($aInp['other']) && $aInp['other'] == true) {
                    $otherAInp = $aInp;
                    $otherAInp['label'] = null;
                    if (isset($aInp['list'][$aInp['value']]) || $aInp['value'] == '') {
                        $otherAInp['attributes']['style'] = "display:none";
                    }
                    if (isset($otherAInp['other_placeholder'])) {
                        $otherAInp['attributes']['placeholder'] = $otherAInp['other_placeholder'];
                    } else {
                        $otherAInp['attributes']['placeholder'] = "Please specify";
                    }

                    $otherAInp['value'] = $aInp['otherValue'];

                    if (isset($otherAInp['other_type'])) {
                        switch ($otherAInp['other_type']) {
                            case 'textArea':
                                EGForm::textArea($key."_other", $otherAInp);
                                break;
                            case 'number':
                                EGForm::number($key."_other", $otherAInp);
                                break;
                            case 'date':
                                EGForm::date($key."_other", $otherAInp);
                                break;
                            case 'time':
                                EGForm::time($key."_other", $otherAInp);
                                break;
                            case 'ckeditor':
                                EGForm::ckeditor($key."_other", $otherAInp);
                                break;
                            case 'file':
                                EGForm::file($key."_other", $otherAInp);
                                break;
                            case 'text':
                            default:
                                EGForm::text($key."_other", $otherAInp);
                                break;
                        }
                    } else {
                        EGForm::text($key."_other", $otherAInp);
                    }
                    echo "
                        <script>
                            $(document).ready(function(){
                                ".$key."changed()
                            });
                            function ".$key."changed(){
                                if ($(\"#".$key."\").val() == \"OTHER\"){
                                    $(\"#".$key."_other\").show();
                                } else {
                                    $(\"#".$key."_other\").hide();
                                }
                            }
                        </script>
                    ";
                }
            }
        }
    }

    /**
    * Radio elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function radio($key, $aInp)
    {
        global $type;

        $aInp['inp_type'] = "radio";
        $aInp = EGForm::setDefaults($key, $aInp, $type);

        if ($aInp['type'] == 'view' || !empty($aInp['disabled'])) {
            //display as text field when viewing
            if (isset($aInp['list']) && isset($aInp['list'][$aInp['value']])) {
                $aInp['value'] = $aInp['list'][$aInp['value']];
            }
            EGForm::text($key, $aInp);
        } else {
            /**
            * OPTIONS:
            * multi-block
            */
            if (!isset($aInp['list_style'])) {
                $aInp['list_style'] = "radio-inline";
            }

            //otherwise display as list of radio buttons
            if (!empty($aInp['list'])) {
                $classes = "";
                if (isset($aInp['classes'])) {
                    $classes = 'class="';
                    $classes .= implode(" ", $aInp['classes']);
                    $classes .= '"';
                }

                $sInput = '<div class="radio-buttons">';
                // $sInput = '<div class="radio-buttons">';
                foreach ($aInp['list'] as $radio_key => $value) {
                    if (isset($aInp['keyValue'])) {
                        $checked = ($aInp['keyValue'] == $radio_key ? "checked" : "");
                    } else {
                        $checked = ($aInp['value'] == $radio_key ? "checked" : "");
                    }
                    $sInput .= '<label class="'.(isset($aInp['list_style']) ? $aInp['list_style'] : '').'"><input type="radio" '.$classes.' name="'.$key.'" value="'.$radio_key.'" '.$checked.'>'.$value.'</label>';
                }
                $sInput .= '</div>';
                EGForm::drawInputHTML($key, $aInp, $sInput);
            }
        }
    }

    /**
    * Generic return AutoComplete elements
    * @param  string $data  collection of data
    */
    public static function returnAutoComplete($data)
    {
        $return = [];
        foreach ($data as $row) {
            $return[] = [
                'id' => $row->id,
                'label' => $row->autocomplete_label
            ];
        }

        return $return;
    }

    /**
    * Generic autocomplete elements
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    */
    public static function autoComplete($key, $aInp)
    {
        global $type;
        $aInp = EGForm::setDefaults($key, $aInp, $type);

        if ($aInp['type'] == 'view' && !empty($aInp['disabled'])) {
            //display disabled text box
            $aInp['value'] = isset($aInp['display_value']) ? $aInp['display_value'] : '';
            EGForm::text($key, $aInp);
        } else {
            //display full autocomplete
            $aInp['classes'] = [$aInp['inp_type'], "selectize"];
            if (isset($aInp['context'])) {
                $aInp['attributes'] = ["data-url" => $aInp['context']];
            }

            $sAttr = EGForm::buildInputAttr($key, $aInp, array(
                "name",
                "class",
                "required",
                "attributes",
            ));

            $sOptions = '';
            if (isset($aInp['selector']) && $aInp['selector']) {
                if (isset($aInp['selector_message']) && !empty($aInp['selector_message'])) {
                    $message = $aInp['selector_message'];
                } else {
                    $message = "Please choose...";
                }
                $sOptions .= '<option value="">'.$message.'</option>';
            }

            if (!empty($aInp['list'])) {
                foreach ($aInp['list'] as $list_key => $list_value) {
                    $selected = $aInp['value'] == $list_key ? "selected" : "";
                    $sOptions .= <<<EOT


            <option $selected value="$list_key">$list_value</option>

EOT;
                }
            } else {
                if (!isset($aInp['display_value'])) {
                    $aInp['display_value'] = '';
                }
                $sOptions .= <<<EOT

            <option value="{$aInp['value']}">{$aInp['display_value']}</option>

EOT;
            }

            $sInput = <<<EOT

            <div class="field">
                <div class="control">
                    <select $sAttr>
                        $sOptions
                    </select>
                </div>
            </div>

EOT;
            EGForm::drawInputHTML($key, $aInp, $sInput);
        }
    }

    /**
    * Build entire input element and output to screen
    * @param  string $key   database key
    * @param  array  $aInp  settings for input field
    * @param  string $input HTML that will be used for the input
    */
    public static function drawInputHTML($key, $aInp, $input = "")
    {
        if ($aInp['inp_type'] != "hidden" && isset($aInp['label'])) {
            $requiredHTML = isset($aInp['required']) && $aInp['required'] ? '<span class="required">*</span> ' : '';
            echo '<label for="'.$key.'">'.$requiredHTML.$aInp['label'].':</label>';
        }

        echo $input;
        echo EGForm::displayErrors($key);
    }

    /**
    * Display errors and output to screen
    * @param string $key database key
    * @return string
    */
    public static function displayErrors($key)
    {
        $errors = \Session::get('errors', new \Illuminate\Support\MessageBag);
        if (isset($errors)) {
            $error = $errors->get($key);
            if (isset($error) && count($error) > 0) {
                return '<p class="help is-danger">'.EGForm::errorMessage($error).'</p>';
            }
        }
        return '';
    }

    /**
    * Builds up a string from an error array
    * @param array $errors field errors
    * @return string
    */
    protected static function errorMessage($errors)
    {
        $msg = '';
        if (is_array($errors)) {
            foreach ($errors as $key => $value) {
                if ($msg != '') {
                    $msg.=" // ";
                }
                $msg .= $value;
            }
        }

        return $msg;
    }


    /**
    * Build a string of attributes to be included within an input element
    * @param  string $key        database key
    * @param  array  $aInp       settings for input field
    * @param  array  $attributes array of attributes to be used in resulting string
    * @return string
    */
    public static function buildInputAttr($key, $aInp, $attributes = array())
    {
        if (!empty($attributes)) {
            $sAttr = "";

            //Loop around the attributes and build the attribute string for the form field
            foreach ($attributes as $attr) {
                switch ($attr) {
                    case "type":
                        $sAttr .= 'type="'.$aInp['inp_type'].'" ';
                        break;
                    case "name":
                        $sAttr .= 'name="'.$key.'" ';
                        break;
                    case "class":
                        $sAttr .= 'class="form-control '.(\EGForm::curHasError($key) ? 'is-danger' : '').' '.(!empty($aInp['classes']) ? EGForm::arrayList($aInp['classes']) : '').'" ';
                        break;
                    case "id":
                    // if an ID is present use, otherwise use the key
                        $sAttr .= 'id="'.(isset($aInp['id']) ? $aInp['id'] : $key).'" ';
                        break;
                    case "value":
                        $sAttr .= 'value="'.htmlentities($aInp['value']).'" ';
                        break;
                    case "attributes":
                        $sAttr .= (!isset($aInp['attributes']) ? '' : EGForm::assocArrayList($aInp['attributes'])).' ';
                        break;
                    case "disabled":
                        $sAttr .= (isset($aInp['disabled']) && $aInp['disabled'] != false ? "disabled" : '').' ';
                        break;
                    case "required":
                        $sAttr .= (isset($aInp['required']) && $aInp['required'] != false ? "required" : '').' ';
                        break;
                    case "disabled":
                        $sAttr .= (isset($aInp['disabled']) && $aInp['disabled'] != false ? "disabled" : '').' ';
                        break;
                    case "checked":
                        $sAttr .= $aInp['value'] == 'YES' || $aInp['value'] == 1 ? 'checked ' : ' ';
                        break;
                    case "rows":
                        $sAttr .= 'rows="'.$aInp['rows'].'" ';
                        break;
                }
            }

            return $sAttr;
        }
    }

    public static function curHasError($key)
    {
        $errors = \Session::get('errors', new \Illuminate\Support\MessageBag);
        if (isset($errors)) {
            $error = $errors->get($key);
            if (isset($error) && count($error) > 0) {
                return true;
            }
        }

        return false;
    }


    /**
    * Set any defaults for the aInp array
    * @param  string $key  database key
    * @param  array  $aInp settings for input field
    * @param  string $type the page type
    * @return array        returns the changed aInp array
    */
    public static function setDefaults($key, $aInp, $type)
    {
        $record = \Session::get('record');
        //set the default input type
        if (!isset($aInp['inp_type'])) {
            $aInp['inp_type'] = "text";
        }


        //set the value if not set
        if (!isset($aInp['value'])) {
            $aInp['value'] = $record[$key];
        }
        //if there has been no value set an empty value
        if (!isset($aInp['value'])) {
            $aInp['value'] = "";
        }

        if (isset($aInp['default']) && $aInp['value'] == "") {
            $aInp['value'] = $aInp['default'];
        }

        //if there has been an old value set the form value to use it
        if (old($key) !== null) {
            $aInp['value'] = old($key);
        }

        if (old($key) !== null && in_array($aInp['inp_type'], ["autocomplete-list", "autocomplete-ajax"])) {
            $aInp['display_value'] = old($key."_display_value");
        }
        //set the type
        if (isset($type) && !isset($aInp['type'])) {
            $aInp['type'] = $type;
        }

        //default the type to view if it hasn't already been set
        if (!isset($aInp['type'])) {
            $aInp['type'] = "view";
        }

        //set fields to disabled when in view mode
        if (($aInp['type'] == "view") && !isset($aInp['disabled'])) {
            $aInp['disabled'] = true;
        }

        switch ($aInp['inp_type']) {
            case "textarea":
                //default the number of rows
                if (!isset($aInp['rows'])) {
                    $aInp['rows'] = 3;
                }
                break;
            case "checkbox":
                //disable checkboxes when in view mode
                if (($aInp['type'] == "view") && !isset($aInp['disabled'])) {
                    $aInp['disabled'] = true;
                }
                //if there are old values set the form to use them
                if (old($key) !== null) {
                    $aInp['values'] = old($key);
                }
                break;
        }

        return $aInp;
    }


    /**
    * Lists an array as a string
    * @param  array  $array array of anything that needs to be converted to string
    * @return string        string of array elements
    */
    protected static function arrayList($array = [])
    {
        $list = "";
        foreach ($array as $value) {
            $list .= $value." ";
        }

        return $list;
    }


    /**
    * Lists an array into a key="value" string
    * @param  array  $array array of anything that needs to be converted to string
    * @return string        string of array elements
    */
    protected static function assocArrayList($array = [])
    {
        $list = "";
        foreach ($array as $key => $value) {
            if ($value === true) {
                $list .= $key."=true ";
            } elseif ($value === false) {
                $list .= $key."=false ";
            } else {
                $list .= $key."='".$value."' ";
            }
        }

        return $list;
    }
}
