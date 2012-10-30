<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Twig;

class Extension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'filterSelect' => new \Twig_Function_Method($this, 'filterSelect', array('is_safe' => array('html'))),
        );
    }

    public function filterSelect($params, $extraParams = array())
    {
        $options = $params['options'];
        unset($params['options']);
        $defaultParams = array(
            'onchange' => 'this.form.submit();');
        $params = array_merge($defaultParams, $params, $extraParams);
        $selected = null;
        if (isset($params['value'])) {
            $selected = $params['value'];
            unset($params['value']);
        }
        $select = '<select';
        foreach ($params as $name => $value) {
            $select .= " $name=\"$value\"";
        }
        $select .= '>';
        foreach ($options as $value => $text) {
            if ($selected == $value) {
                $select .= "<option selected=\"selected\" value=\"$value\">$text</option>";
            } else {
                $select .= "<option value=\"$value\">$text</option>";
            }
        }
        $select .= "</select>";
        return $select;
    }

    public function getName()
    {
        return 'BnvTwigExtension';
    }
}