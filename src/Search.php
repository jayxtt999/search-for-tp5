<?php
namespace niaoyun\Search;

class Search
{

    protected  $fieldConfig = array();
    protected  $associated = array();
    protected  $fieldValue = array();
    protected  $selected = array();
    protected  $aliasData = array();
    //验证表达式，copy think\db\Builder exp ,避免sql级别错误
    protected $exp = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'not like' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'exp' => 'EXP', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN', 'exists' => 'EXISTS', 'notexists' => 'NOT EXISTS', 'not exists' => 'NOT EXISTS', 'null' => 'NULL', 'notnull' => 'NOT NULL', 'not null' => 'NOT NULL', '> time' => '> TIME', '< time' => '< TIME', '>= time' => '>= TIME', '<= time' => '<= TIME', 'between time' => 'BETWEEN TIME', 'not between time' => 'NOT BETWEEN TIME', 'notbetween time' => 'NOT BETWEEN TIME'];

    /**
     * @author xietaotao
     * @return array
     */
    public function getAliasData($field)
    {
        return $this->aliasData[$field];
    }

    /**
     * @author xietaotao
     *
     * @param array $aliasData
     */
    public function setAliasData($field,$aliasData)
    {
        $this->aliasData[$field] = $aliasData;
    }


    private static $_instance = null;

    //私有构造函数，防止外界实例化对象
    /*private function __construct() {
    }*/
    //私有克隆函数，防止外办克隆对象
    private function __clone() {
    }
    //静态方法，单例统一访问入口
    static public function getInstance() {
        if (is_null ( self::$_instance ) || !isset ( self::$_instance )) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }

    /**
     * @param $name
     * @return array
     * @author xietaotao
     */
    public function getFieldConfig($field)
    {
        return isset($this->fieldConfig[$field])?$this->fieldConfig[$field]:false;

    }


    /**
     * @param $name
     * @return array
     * @author xietaotao
     */
    public function setFieldConfig($field,$config=[])
    {
        $this->fieldConfig[$field] = $config;
    }


    /**
     * 单一关键字模式
     * @param $field
     * @param $config
     *
     * @author xietaotao
     */
    public function setOnlyKey($field,$config=[]){

        if(!isset($config['op'])){
            $config['op'] = 'eq';
        }
        isset($config['alias'])?$this->setAliasData($config['alias'],$field):'';
        $this->setFieldConfig($field,$config);
    }


    /**
     * 单一类型模式
     * @param $field
     * @param $config
     *
     * @author xietaotao
     */
    public function setOnlyType($field,$value=[],$config=[]){
        if(!is_array($value)){
            return false;
        }
        if(!isset($config['op'])){
            $config['op'] = 'eq';
        }
        isset($config['alias'])?$this->setAliasData($config['alias'],$field):'';
        $this->setFieldConfig($field,$config);
        $this->setfieldValue($field,$value);
    }



    public function setAssocType($leftKey,$rightKey,$config=[],$value=[]){

        $this->setAliasData($leftKey,$leftKey);
        $this->setAliasData($rightKey,$rightKey);
        $this->setAssociated($leftKey,$rightKey);
        if(is_array($value) && $value){
            foreach($value as $key=>$item){
                isset($item['alias'])?$this->setAliasData($item['alias'],$key):'';
                //将配置复制到所以子字段
                $this->setFieldConfig($key,$config);
            }
        }
    }





    /**
     * @param $name
     * @return mixed
     * @author xietaotao
     */
    public function getAssociated($name)
    {
        return $this->associated[$name];
    }

    /**
     * @param $name
     * @param $value
     * @author xietaotao
     */
    public function setAssociated($name, $value)
    {
        $this->associated[$name] = [$name=>$value];
    }

    /**
     * @param $data
     * @author xietaotao
     */
    public function setFieldValue($field,$value){

        $this->fieldValue[$field] = $value;

    }

    public function getFieldValue($field){

        return $this->fieldValue[$field];
    }


    public function getWhere($param=''){


        $whereMap = [];
        $param = $param ? $param : input('param.', '');
        if (!$param) {
            return $whereMap;
        }
        foreach($param as $k=>$v){
            //过滤空值
            if($v==""){
                unset($param[$k]);
            }
            //转换$k，目的是过滤可能存在的不合法$k
            $k2 = htmlspecialchars($k,ENT_QUOTES);
            unset($param[$k]);
            $param[$k2] = htmlspecialchars(trim($v),ENT_QUOTES);
        }
        //优先处理存在关联的key
        if($this->associated){
            foreach($this->associated as $k=>$ass){
                if(isset($param[$k])){
                    $leftK = $param[$k];
                    $rightV = $param[$ass[$k]];
                    $param[$leftK] = $rightV;
                    unset($param[$k]);
                    unset($param[$ass[$k]]);
                }
            }
        }
        //转换别名为真实字段
        if($this->aliasData){
            foreach($param as $key=>$item){
                if(array_key_exists($key,$this->aliasData)){
                    if($key != $this->aliasData[$key]){
                        $param[$this->aliasData[$key]] = $item;
                        unset($param[$key]);
                    }
                }else{
                    unset($param[$key]);
                }
            }
        }

        //处理配置部分并生成whereMap，暂时只有op[操作符]
        $maps = [];
        foreach($param as $key=>$item){
            $config = $this->getFieldConfig($key);
            $op = strtolower($config['op']);
            if(!isset($this->exp[$op])){
                continue;
            }
            switch($op){
                case 'like':
                    $item = "%$item%";
                    break;
                case 'between time':
                case 'not between time':
                    $item = explode(' - ', $item);
            }
            $maps[$key] = [$op, $item];

        }
        var_dump($maps);
        return $maps;

    }



}

