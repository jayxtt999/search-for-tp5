<?php
namespace niaoyun\Search;

/**
 * @@var $builder
 * Class Search
 * @package niaoyun\Search
 */


class Search
{
    protected $fieldConfig = array();
    protected $associated  = array();
    protected $fieldValue  = array();
    protected $selected    = array();
    protected $aliasData   = array();
    protected $param       = array();
    /**@var $builder Builder* */
    protected $builder;
    const ONLY_KEY   = 'OnlyKey';
    const ONLY_TYPE  = 'OnlyType';
    const ASSOC_TYPE = 'AssocType';

    //验证表达式，copy think\db\Builder exp ,避免sql级别错误
    protected $exp = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'not like' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'exp' => 'EXP', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN', 'exists' => 'EXISTS', 'notexists' => 'NOT EXISTS', 'not exists' => 'NOT EXISTS', 'null' => 'NULL', 'notnull' => 'NOT NULL', 'not null' => 'NOT NULL', '> time' => '> TIME', '< time' => '< TIME', '>= time' => '>= TIME', '<= time' => '<= TIME', 'between time' => 'BETWEEN TIME', 'not between time' => 'NOT BETWEEN TIME', 'notbetween time' => 'NOT BETWEEN TIME'];

    /**
     * @author xietaotao
     * @return array
     */
    public function getAliasData($field)
    {
        return isset($this->aliasData[$field]) ? $this->aliasData[$field] : false;
    }

    /**
     * @author xietaotao
     *
     * @param array $aliasData
     */
    public function setAliasData($field, $aliasData)
    {
        $this->aliasData[$field] = $aliasData;
    }


    /**
     * @param $name
     *
     * @return array
     * @author xietaotao
     */
    public function getFieldConfig($field)
    {
        return isset($this->fieldConfig[$field]) ? $this->fieldConfig[$field] : false;

    }


    /**
     * @param $name
     *
     * @return array
     * @author xietaotao
     */
    public function setFieldConfig($field, $config = [])
    {
        $this->fieldConfig[$field] = $config;
    }


    /**
     * 单一关键字模式
     *
     * @param $field
     * @param $config
     *
     * @author xietaotao
     */
    public function setOnlyKey($field, $config = [])
    {

        if (!isset($config['op'])) {
            $config['op'] = 'eq';
        }
        $config['__type__'] = self::ONLY_KEY;
        isset($config['alias']) ? $this->setAliasData($config['alias'], $field) : '';
        $this->setFieldConfig($field, $config);
    }


    /**
     * 单一类型模式
     *
     * @param $field
     * @param $config
     *
     * @author xietaotao
     */
    public function setOnlyType($field, $config = [])
    {

        if (!isset($config['op'])) {
            $config['op'] = 'eq';
        }
        $config['__type__'] = self::ONLY_TYPE;
        isset($config['alias']) ? $this->setAliasData($config['alias'], $field) : '';
        $this->setFieldConfig($field, $config);
    }


    public function setAssocType($leftKey, $rightKey, $config = [])
    {

        $value = isset($config['ext']['options']) ? $config['ext']['options'] : [];
        $this->setAliasData($leftKey, $leftKey);
        $this->setAliasData($rightKey, $rightKey);
        $this->setfieldValue($leftKey, $value);
        $config['__type__'] = self::ASSOC_TYPE;
        $this->setFieldConfig($leftKey, $config);
        $this->setFieldConfig($rightKey, $config);
        $this->setAssociated($leftKey, $rightKey);
        if (is_array($value) && $value) {
            foreach ($value as $key => $item) {
                isset($item['alias']) ? $this->setAliasData($item['alias'], $key) : '';
                //将配置复制到所以子字段
                $this->setFieldConfig($key, $config);
            }
        }
    }


    /**
     * @param $name
     *
     * @return mixed
     * @author xietaotao
     */
    public function getAssociated($name)
    {
        return isset($this->associated[$name])?$this->associated[$name]:false;
    }

    /**
     * @param $name
     * @param $value
     *
     * @author xietaotao
     */
    public function setAssociated($name, $value)
    {
        $this->associated[$name] = [$name => $value];
    }

    /**
     * @param $data
     *
     * @author xietaotao
     */
    public function setFieldValue($field, $value)
    {

        $this->fieldValue[$field] = $value;

    }

    public function getFieldValue($field)
    {

        return $this->fieldValue[$field];
    }


    public function getWhere($param = '')
    {
        $whereMap    = [];
        $param       = $param ? $param : input('param.', '');
        $this->param = $param;
        if (!$param) {
            return $whereMap;
        }
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
        //优先处理存在关联的key
        if ($this->associated) {
            foreach ($this->associated as $k => $ass) {
                if (isset($param[$k])) {

                    if( isset($param[$ass[$k]]) && $param[$ass[$k]]!=''){
                        $leftK         = $param[$k];
                        $rightV        = $param[$ass[$k]];
                        $param[$leftK] = $rightV;
                    }
                    unset($param[$k]);
                    unset($param[$ass[$k]]);
                }
            }
        }

        //转换别名为真实字段
        if ($this->aliasData) {
            foreach ($param as $key => $item) {
                if (array_key_exists($key, $this->aliasData)) {
                    if ($key != $this->aliasData[$key]) {
                        $param[$this->aliasData[$key]] = $item;
                        unset($param[$key]);
                    }
                } else {
                    unset($param[$key]);
                }
            }
        }
        //处理配置部分并生成whereMap，暂时只有op[操作符]
        $maps = [];
        foreach ($param as $key => $item) {
            $config = $this->getFieldConfig($key);
            $op     = strtolower($config['op']);
            if (!isset($this->exp[$op])) {
                continue;
            }
            switch ($op) {
                case 'like':
                    $item = "%$item%";
                    break;
                case 'between time':
                case 'not between time':
                    $item = explode(' - ', $item);
            }
            $maps[$key] = [$op, $item];
        }
        return $maps;

    }

    private function makeDomOnlyType($field,$fieldConfig, $value)
    {
        $ext          = isset($fieldConfig['ext']) ? $fieldConfig['ext'] : [];
        $ext['name']  = $fieldConfig['alias'];
        $label       = isset($ext['label']) ? $ext['label'] : $fieldConfig['add_label'] = false;
        $ext['type'] = 'select_new';
        $ext['class'] = 'ny-oa-select';
        $ext['selected'] = true;
        isset($ext['options'])?'':$ext['options']=[];
        $this->builder->add_input($label, $ext,$fieldConfig['alias']);

    }


    private function makeDomOnlyKey($field,$fieldConfig, $value)
    {

        $ext          = isset($fieldConfig['ext']) ? $fieldConfig['ext'] : [];
        $ext['name']  = $fieldConfig['alias'];
        $ext['value'] = $value;
        $ext['class'] = 'ny-input';
        $label        = isset($ext['label']) ? $ext['label']  : $fieldConfig['add_label'] = false;
        $this->builder->add_input($label, $ext,$fieldConfig['alias']);

    }


    private function makeDomAssocType($field,$fieldConfig, $value){

        $associatedV = $this->getAssociated($field);
        if(!$associatedV){
            return;
        }
        $ext          = isset($fieldConfig['ext']) ? $fieldConfig['ext'] : [];
        $label       = isset($ext['label']) ? $ext['label'] : '';
        $options = isset($ext['options'])?$ext['options']:[];
        if($options){
            $newOptions = [];
            foreach($options as $k=>$v){
                $newOptions[$v['alias']] = $v['name'];
            }
            $ext['options'] = $newOptions;
        }
        $ext['class']  = 'ny-serach-box margin-left-30 pull-left clearfix';
        $ext['name']  = $field;
        $ext['type'] = 'select_new';
        $ext['selected'] = true;
        $ext['add_label'] = false;
        $this->builder->add_input($label, $ext,$field);

        $extWord['name'] = $associatedV[$field];
        $extWord['type'] = 'text';
        $extWord['class'] = 'ny-input';
        $extWord['placeholder'] = '搜索...';
        $extWord['add_label'] = false;
        $this->builder->add_input($label, $extWord,$associatedV[$field]);

    }


    public function makeDom($action)
    {

        //强制转换为get提交
        $args          = [
            'method' => 'get',
            'add_honeypot'=>false,
            'add_submit'=>false,
        ];
        $this->builder = new Builder($action, $args);
        foreach ($this->param as $key => $value) {
            //根据别名获取真实字段
            $field = $this->getAliasData($key);
            if (!$field) {
                continue;
            }
            //获取字段配置
            $fieldConfig = $this->getFieldConfig($field);
            //根据类型生成DOM
            $type = $fieldConfig['__type__'];
            $func = 'makeDom' . $type;
            call_user_func(array($this, $func), $field,$fieldConfig, $value);
        }

        $submitBtn = [
            'type'=>'button',
            'class'=>'ny-searchbox-btn',
            'id'=>'searchBtn',
        ];
        $this->builder->add_input('',$submitBtn);

        $html = $this->builder->build_form(false);
        return $html;
    }


}

