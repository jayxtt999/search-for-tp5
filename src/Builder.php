<?php
/**
 * Created by PhpStorm.
 * User: xietaotao
 * Date: 2018/1/18
 * Time: 15:59
 */

namespace niaoyun\Search;

class Builder
{

    private $_vars = [];


    public function __construct($post_url, $method)
    {
        $this->_vars['post_url'] = trim($post_url);
        $this->_vars['_method']  = $method;
    }


    public function setFormData($form_data = [])
    {
        foreach ($this->_vars['form_items'] as &$item) {

            if (isset($form_data[$item['name']])) {
                $item['value'] = $form_data[$item['name']];
            } else {
                $item['value'] = isset($item['value']) ? $item['value'] : '';
            }

            if (isset($item['assoc'])) {
                $item['assoc_value'] = isset($form_data[$item['assoc']]) ? $form_data[$item['assoc']] : '';
            }
            // 针对日期范围特殊处理
            switch ($item['type']) {
                case 'ny_time':
                    if ($item['name_from'] == $item['name_to']) {
                        list($item['value_from'], $item['value_to']) = $form_data[$item['id']];
                    } else {
                        $item['value_from'] = isset($form_data[$item['name_from']]) ? $form_data[$item['name_from']] : '';
                        $item['value_to']   = isset($form_data[$item['name_to']]) ? $form_data[$item['name_to']] : '';
                    }
                    break;
            }
        }

        return $this;
    }

    public function addNySelectUnion($name = '', $item)
    {

        return $this->addSelect($name, $item, 'ny_select_union');
    }

    public function addNySelect($name = '', $item)
    {

        return $this->addSelect($name, $item, 'ny_select');
    }

    public function addSelect($name = '', $item, $type = 'select')
    {


        $title       = isset($item['title']) ? $item['title'] : '';
        $value       = isset($item['value']) ? $item['value'] : '';
        $options     = isset($item['options']) ? $item['options'] : '';
        $extra_class = isset($item['extra_class']) ? $item['extra_class'] : '';
        $extra_attr  = isset($item['extra_attr']) ? $item['extra_attr'] : '';
        $placeholder = isset($item['placeholder']) ? $item['placeholder'] : '请输入' . $title;
        $itemConfig  = [
            'type'        => $type,
            'name'        => $name,
            'title'       => $title,
            'options'     => $options,
            'value'       => $value,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => $placeholder,
        ];
        if (isset($item['assoc'])) {
            $itemConfig['assoc'] = $item['assoc'];
        }
        $this->_vars['form_items'][] = $itemConfig;

        return $this;

    }


    public function addNyTime($name = '', $item)
    {

        $title       = isset($item['title']) ? $item['title'] : '';
        $value       = isset($item['value']) ? $item['value'] : '';
        $extra_class = isset($item['extra_class']) ? $item['extra_class'] : '';
        $extra_attr  = isset($item['extra_attr']) ? $item['extra_attr'] : '';
        $placeholder = isset($item['placeholder']) ? $item['placeholder'] : '请输入' . $title;
        $name_from   = isset($item['name_from']) ? $item['name_from'] : '';
        $name_to     = isset($item['name_to']) ? $item['name_to'] : '';
        $id_from     = isset($item['id_from']) ? $item['id_from'] : '';
        $id_to       = isset($item['id_to']) ? $item['id_to'] : '';

        $itemConfig = [
            'type'        => 'ny_time',
            'name'        => $name,
            'name_from'   => $name_from,
            'name_to'     => $name_to,
            'id_from'     => $id_from,
            'id_to'       => $id_to,
            'title'       => $title,
            'value'       => $value,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => $placeholder,
        ];

        $this->_vars['form_items'][] = $itemConfig;

        return $this;


    }

    public function addTime($name = '', $item)
    {


        return $this->addText($name, $item, 'time');

    }


    /**
     * 添加按钮
     *
     * @param string $name     表单项名，也是按钮id
     * @param array  $attr     按钮属性
     * @param string $ele_type 按钮类型，默认为button，也可以为a标签
     *
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this|array
     */
    public function addNyButton($name = '', $item)
    {

        $id                          = isset($item['id']) ? $item['id'] : '';
        $itemConfig                  = [
            'type' => 'ny_button',
            'name' => $name,
            'id'   => $id,
        ];
        $this->_vars['form_items'][] = $itemConfig;

        return $this;
    }


    public function addText($name = '', $item, $type = 'text')
    {
        $title       = isset($item['title']) ? $item['title'] : '';
        $value       = isset($item['value']) ? $item['value'] : '';
        $extra_class = isset($item['extra_class']) ? $item['extra_class'] : '';
        $extra_attr  = isset($item['extra_attr']) ? $item['extra_attr'] : '';
        $placeholder = isset($item['placeholder']) ? $item['placeholder'] : '请输入' . $title;
        $itemConfig  = [
            'type'        => $type,
            'name'        => $name,
            'title'       => $title,
            'value'       => $value,
            'extra_class' => $extra_class,
            'extra_attr'  => $extra_attr,
            'placeholder' => $placeholder,
        ];

        $this->_vars['form_items'][] = $itemConfig;

        return $this;
    }


    public function addFormItem($key, $itemConfig)
    {
        if ($key != '') {
            $type   = $itemConfig['type'];
            $method = 'add' . ucfirst($type);
            call_user_func([$this, $method], $key, $itemConfig);
        }

        return $this;
    }


    public function addFormItems($items = [])
    {

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                call_user_func([$this, 'addFormItem'], $key, $item);
            }
        }

        return $this;
    }


    public function getVars()
    {
        return $this->_vars;
    }


}