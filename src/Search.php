<?php
namespace niaoyun\Search;


class Search
{
    private $param             = [];//参数集合
    private $url               = [];//提交url
    private $method            = [];//提交方式
    private $searchConfig      = [];//搜索配置
    private $searchAssocConfig = [];//关联搜索配置
    private $searchAssocKeyV   = [];//关联搜索对应关系
    private $fieldAssoc        = [];//字段与别名对应关系
    const TEXT  = 'text';
    const TYPE  = 'type';
    const ASSOC = 'assoc';
    const TIMEBETWEEN = 'timebetween';

    /**
     * Search2 constructor.
     *
     * @param        $url 提交搜索的url，一般为自身
     * @param        $param 参数
     * @param string $method 提交方式
     */
    public function __construct($url, $param, $method = 'get')
    {
        $this->url    = $url;
        $this->method = $method;
        foreach ($param as $k => $v) {
            //过滤空值
            if ("" == trim($v)) {
                unset($param[$k]);
                continue;
            }
            //转换$k，目的是过滤可能存在的不合法$k
            $k2 = htmlspecialchars($k, ENT_QUOTES);
            unset($param[$k]);
            $param[$k2] = htmlspecialchars($v, ENT_QUOTES);
        }
        $this->param = $param;

    }

    /**
     * 文本类型
     * @param        $field 字段名
     * @param string $assoc 别名
     * @param string $op  操作符
     * @param array  $textExt 拓展
     *
     * @author xietaotao
     */
    public function addTextItem($field, $assoc = '', $op = 'eq', $textExt = [])
    {
        $this->searchConfig[$field] = [
            'item_type' => self::TEXT,
            'field'     => $field,
            'assoc'     => $assoc ? strtolower($assoc) : strtolower($field),
            'op'        => strtolower($op),
            'elm_ext'   => $textExt,
        ];
        $this->fieldAssoc[$field]   = $assoc;
    }

    /**
     * 关联类型
     * @param $left 左选项别名
     * @param $right 右选项别名
     * @param $option 配置
     *
     * @author xietaotao
     */
    public function addAssoc($left, $right, $option)
    {

        $newKey                           = $left . '_' . $right;
        $this->searchAssocConfig[$newKey] = $option;
        $this->searchAssocKeyV[$newKey]   = [$left => $right];
        foreach ($option as $opk => $opv) {
            $opAlias                = isset($opv['alias']) ? $opv['alias'] : $opk;
            $this->fieldAssoc[$opk] = $opAlias;

        }
    }

    /**
     * 下拉类型
     * @param        $field 字段名
     * @param string $assoc 别名
     * @param string $op 操作符
     * @param array  $options 下拉选项数据，key=>val
     * @param array  $textExt 拓展,一般是需要渲染到dom的属性
     *
     * @author xietaotao
     */
    public function addType($field, $assoc = '', $op = 'eq', $options = [], $textExt = [])
    {

        $this->searchConfig[$field] = [
            'item_type' => self::TYPE,
            'field'     => $field,
            'assoc'     => $assoc ? strtolower($assoc) : strtolower($field),
            'op'        => strtolower($op),
            'options'   => $options,
            'elm_ext'   => $textExt,
        ];
        $this->fieldAssoc[$field]   = $assoc;

    }

    /**
     * 时间区间类型
     * @param        $field 字段名
     * @param string $assocStartTime 开始时间别名
     * @param string $assocEndTime  结束时间别名
     * @param string $op 操作符
     * @param array  $textExt 拓展，一般是需要渲染到dom的属性
     *
     * @author xietaotao
     */
    public function addTimeBetween($field, $assocStartTime = 'start_time', $assocEndTime = 'end_time',$op='between time',$textExt = [])
    {

        $this->searchConfig[$field] = [
            'item_type' => self::TIMEBETWEEN,
            'field'     => $field,
            'assoc'     => [$assocStartTime,$assocEndTime],
            'name_from'     => strtolower($assocStartTime),
            'name_to'     => strtolower($assocEndTime),
            'op'        => strtolower($op),
            'elm_ext'   => $textExt,
        ];
        $this->fieldAssoc[$field]   = $field;
    }


    /**
     * 时间类型
     * @param        $field 字段名
     * @param string $assoc 别名
     * @param string $op 操作符
     * @param array  $textExt 扩展，一般是需要渲染到dom的属性
     *
     * @author xietaotao
     */
    public function addTime($field, $assoc = '', $op = 'eq', $textExt = [])
    {

        $textExt['id']          = 'startDate';
        $textExt['placeholder'] = '请选择时间';
        $this->addTextItem($field, $assoc, $op, $textExt);
    }

    /**
     * 根据别名获取字段
     * @param $alias
     *
     * @return bool|int|string
     * @author xietaotao
     */
    private function getFieldForAlias($alias)
    {

        foreach ($this->fieldAssoc as $_field => $_alias) {
            if ($alias == $_alias) {
                return $_field;
            }
        }

        return false;

    }

    /**
     * 一些特定的op 进行特殊的处理，暂时只有like
     * @param $op
     * @param $value
     *
     * @return array
     * @author xietaotao
     */
    private function reOpVal($op, $value)
    {
        switch ($op) {
            case "like":
                $value = "%" . $value . "%";
                break;
        }
        return [$op, $value];
    }

    private function isDate($date){

        if(!strtotime($date) || !preg_match("/^\d{4}\-\d{2}\-\d{2}$/",$date)){
            return false;
        }else{
            return true;
        }

    }


    /**
     * 获取where语句
     * @param array $additionWhere 附加的where,应用于比如存在status的搜索但默认不能为-1这种情况
     *
     * @return array
     * @author xietaotao
     */
    public function getWhere($additionWhere=[])
    {

        if (!$this->param) {
            return $additionWhere;
        }
        $where = [];
        if ($this->searchConfig) {
            foreach ($this->searchConfig as $config) {

                $itemType = $config['item_type'];
                $assoc    = $config['assoc'];
                if(is_array($assoc)){
                    foreach($assoc as $_assoc){
                        if (!(isset($this->param[$_assoc]))) {
                            continue;
                        }
                    }
                }else{
                    if (!(isset($this->param[$assoc]))) {
                        continue;
                    }
                }
                switch ($itemType) {
                    case self::TEXT:
                        $where[$config['field']] = $this->reOpVal($config['op'], $this->param[$assoc]);
                        break;
                    case self::TYPE:
                        $value   = $this->param[$assoc];
                        $options = $config['options'];
                        if (isset($options[$value])) {
                            $where[$config['field']] = $this->reOpVal($config['op'], $value);
                        }
                        break;
                    case self::TIMEBETWEEN:
                        if(isset($this->param[$assoc[0]]) && ('' != $this->param[$assoc[0]])  && (isset($this->param[$assoc[1]])) && ('' != $this->param[$assoc[1]])){
                            $value1   = $this->param[$assoc[0]];
                            $value2   = $this->param[$assoc[1]];
                            if($this->isDate($value1) && $this->isDate($value2)){
                                $where[$config['field']] = $this->reOpVal($config['op'], [$value1,$value2]);
                            }
                        }elseif(isset($this->param[$assoc[0]]) && ('' != $this->param[$assoc[0]])){
                            $value   = $this->param[$assoc[0]];
                            if($this->isDate($value)){
                                $where[$config['field']] = $this->reOpVal('egt', $value);
                            }
                        }elseif(isset($this->param[$assoc[1]]) && ('' != $this->param[$assoc[1]])){
                            if($this->isDate($value)){
                                $where[$config['field']] = $this->reOpVal('elt', $value);
                            }
                        }
                        break;
                }
            }
        }

        if ($this->searchAssocKeyV) {
            foreach ($this->searchAssocKeyV as $ocKey => $searchAssoc) {
                $k = key($searchAssoc);
                $v = $searchAssoc[$k];
                if (!isset($this->param[$k]) || '' == $this->param[$k] || !isset($this->param[$v]) || '' == $this->param[$v]) {
                    continue;
                }
                $assocConfig = $this->searchAssocConfig[$ocKey];
                $field       = $this->getFieldForAlias($this->param[$k]);
                if (!$field || !isset($assocConfig[$field])) {
                    continue;
                }
                $fieldConfig   = $assocConfig[$field];
                $where[$field] = $this->reOpVal($fieldConfig['op'], $this->param[$v]);
            }
        }
        $where = array_merge($additionWhere,$where);
        return $where;
    }

    /**
     * 将别名转换为选项
     * @param $assocConfig
     *
     * @return array
     * @author xietaotao
     */
    private function convertAssocToOptions($assocConfig)
    {
        $options = [];
        foreach ($assocConfig as $assoc) {
            $options[$assoc['alias']] = $assoc['name'];
        }

        return $options;
    }


    /**
     * 获取搜索数据，给搜索组件模板使用
     * @return array
     * @author xietaotao
     */
    public function getSearchData()
    {
        $formItems = [
            'post_url' => $this->url,
            '_method'  => $this->method,
        ];

        foreach ($this->searchAssocKeyV as $ocKey => $searchAssoc) {

            $assocConfig = $this->searchAssocConfig[$ocKey];
            foreach ($searchAssoc as $_k => $_c) {
                $left = [
                    'item_type' => self::ASSOC,
                    'assoc1'    => $_k,
                    'options'   => $this->convertAssocToOptions($assocConfig),
                    'assoc2'    => $_c,
                ];
                array_unshift($this->searchConfig, $left);
            }
        }


        foreach ($this->searchConfig as $config) {
            $itemType = $config['item_type'];
            switch ($itemType) {
                case self::TEXT:
                    $item = isset($config['elm_ext']) ? $config['elm_ext'] : [];
                    //name 为强制重命名项目，必须等于别名
                    $item['type'] = $itemType;
                    $item['name'] = $config['assoc'];
                    //没有设置id 自动加一个
                    $item['id'] = isset($item['id']) ? $item['id'] : "_" . $item['name'];
                    //强制设置一下相关属性，这样模板页就不需要去判断key是否存在了
                    $item['class']             = isset($item['class']) ? $item['class'] : '';
                    $item['title']             = isset($item['title']) ? $item['title'] : '';
                    $item['extra_attr']        = isset($item['extra_attr']) ? $item['extra_attr'] : '';
                    $item['placeholder']       = isset($item['placeholder']) ? $item['placeholder'] : '请输入' . ($item['title'] ? $item['title'] : '关键字');
                    $item['value']             = isset($this->param[$config['assoc']]) ? $this->param[$config['assoc']] : '';
                    $formItems['form_items'][] = $item;
                    break;
                case self::TYPE:
                    $item = isset($config['elm_ext']) ? $config['elm_ext'] : [];
                    //name 为强制重命名项目，必须等于别名
                    $item['type'] = $itemType;
                    $item['name'] = $config['assoc'];
                    //没有设置id 自动加一个
                    $item['id'] = isset($item['id']) ? $item['id'] : "_" . $item['name'];
                    //强制设置一下相关属性，这样模板页就不需要去判断key是否存在了
                    $item['class']             = isset($item['class']) ? $item['class'] : '';
                    $item['title']             = isset($item['title']) ? $item['title'] : '';
                    $item['extra_attr']        = isset($item['extra_attr']) ? $item['extra_attr'] : '';
                    $item['value']             = isset($this->param[$config['assoc']]) ? $this->param[$config['assoc']] : '';
                    $item['options']           = $config['options'];
                    $formItems['form_items'][] = $item;
                    break;
                case self::ASSOC:
                    $item         = isset($config['elm_ext']) ? $config['elm_ext'] : [];
                    $item['type'] = $itemType;
                    $item['name'] = $config['assoc1'];
                    $item['id']   = isset($item['id']) ? $item['id'] : "_" . $item['name'];
                    //强制设置一下相关属性，这样模板页就不需要去判断key是否存在了
                    $item['class']             = isset($item['class']) ? $item['class'] : '';
                    $item['title']             = isset($item['title']) ? $item['title'] : '';
                    $item['extra_attr']        = isset($item['extra_attr']) ? $item['extra_attr'] : '';
                    //assoc 比较特殊，大佬说了，没有选项默认给第一个选项
                    $item['value']             = isset($this->param[$config['assoc1']]) ? $this->param[$config['assoc1']] : array_keys($config['options'])[0];
                    $item['options']           = $config['options'];
                    $item['assoc']             = $config['assoc2'];
                    $item['assoc_value']       = isset($this->param[$config['assoc2']]) ? $this->param[$config['assoc2']] : '';
                    $formItems['form_items'][] = $item;
                    break;
                case self::TIMEBETWEEN:
                    $item = isset($config['elm_ext']) ? $config['elm_ext'] : [];
                    //name 为强制重命名项目，必须等于别名
                    $item['type'] = $itemType;
                    $item['name_from'] = $config['assoc'][0];
                    $item['name_to'] = $config['assoc'][1];
                    //强制设置一下相关属性，这样模板页就不需要去判断key是否存在了
                    $item['class']             = isset($item['class']) ? $item['class'] : '';
                    $item['title']             = isset($item['title']) ? $item['title'] : '';
                    $item['extra_attr']        = isset($item['extra_attr']) ? $item['extra_attr'] : '';
                    $item['placeholder']       = isset($item['placeholder']) ? $item['placeholder'] : '请输入' . ($item['title'] ? $item['title'] : '关键字');
                    $item['value_from']             = isset($this->param[$config['assoc'][0]]) ? $this->param[$config['assoc'][0]] : '';
                    $item['value_to']             = isset($this->param[$config['assoc'][1]]) ? $this->param[$config['assoc'][1]] : '';
                    $formItems['form_items'][] = $item;
                    break;

            }
        }

        $button                    = [
            'type' => 'button',
        ];
        $formItems['form_items'][] = $button;

        return $formItems;


    }

}

