<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: HTML
 */
namespace Bossanova\Html;

class Html
{
    /**
     * This function is creating a <select> combo box
     * @Param array $options - all options contained in the new combo box
     * @Param string $value - selected option
     * @Param array $attr - attributes from the <select> tag
     * @Return string $html - return the HTML <select> combo syntax
     */
    public function select($options, $value, $attr)
    {
        $html = "<select";

        if (count($attr)) {
            foreach ($attr as $k => $v) {
                $html .= " $k=\"$v\"";
            }
        }

        $html .= ">";

        if (is_array($options) && count($options)) {
            foreach ($options as $k => $v) {
                $html .= "<option value='$k'";

                if ($k === $value) {
                    $html .= " selected='selected'";
                }

                $html .= ">$v</option>";
            }
        } else {
            $html .= "<option value=''></option>";

            $num = explode(',', $options);

            if ($num[0] > 0 && $num[1] > 0) {
                $len = strlen($num[0]);

                if ($num[0] < $num[1]) {
                    for ($i = $num[0]; $i <= $num[1]; $i ++) {
                        $i = sprintf("%0{$len}d", $i);

                        $html .= "<option value='$i'";

                        if ($i === $value) {
                            $html .= " selected='selected'";
                        }

                        $html .= ">$i</option>";
                    }
                } else {
                    for ($i = $num[0]; $i >= $num[1]; $i --) {
                        $i = sprintf("%0{$len}d", $i);

                        $html .= "<option value='$i'";

                        if ($i === $value) {
                            $html .= " selected='selected'";
                        }

                        $html .= ">$i</option>";
                    }
                }
            }
        }

        $html .= "</select>";

        return $html;
    }

    public function checkbox()
    {
    }

    public function radiobox()
    {
    }

    public function textarea()
    {
    }
}
