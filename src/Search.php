<?php
namespace niaoyun\Search;

/**
 * @@var $builder
 * Class Search
 * @package niaoyun\Search
 */


class Search
{
    protected $fieldConfig      = array();
    protected $field            = array();
    protected $associated       = array();
    protected $fieldValue       = array();
    protected $selected         = array();
    protected $aliasData        = array();
    protected $param            = array();
    protected $timeSectionField = array();
    protected $markDomParam     = array();
    /**@var $builder Builder* */
    protected $builder;

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
    public function setAliasData($field, $alias)
    {
        $this->aliasData[$alias] = $field;
    }


    /**
     * @author xietaotao
     *
     * @param array $aliasData
     */
    public function setTimeSectionField($field)
    {
        $this->timeSectionField[] = $field;
    }

    /**
     * @param $name
     *
     * @return array
     * @author xietaotao
     */
    public function getFields()
    {
        return $this->field;

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

    public function setMarkDomParam($alias, $config = [])
    {

        $this->markDomParam[$alias] = $config;
    }

    public function getMarkDomParam($alias)
    {

        return $this->markDomParam[$alias];
    }

    public function getMarkDomParams()
    {

        return $this->markDomParam;
    }

    /**
     * @param $name
     *
     * @return array
     * @author xietaotao
     */
    public function setFieldConfig($field, $config = [])
    {
        $this->field[]             = $field;
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
        $ext = [
            'type' => 'text',
        ];
        if (isset($config['ext'])) {
            $ext = array_merge($config['ext'], $ext);
        }
        if (isset($config['alias'])) {
            $this->setMarkDomParam($config['alias'], $ext);
            $this->setAliasData($field, $config['alias']);
        } else {
            $this->setMarkDomParam($field, $ext);
        }
        $this->setFieldConfig($field, $config);
    }


    /**
     * 时间搜索模式
     *
     * @param $field
     * @param $config
     *
     * @author xietaotao
     */
    public function setTime($field, $config = [])
    {

        $config['op']    = 'between time';
        $config['alias'] = isset($config['alias']) ? $config['alias'] : ['start_time', 'end_time'];
        $ext             = isset($config['ext']) ? $config['ext'] : [];
        $defaultExt      = [
            'name_from' => 'start_time',
            'name_to'   => 'end_time',
            'id_from'   => 'startDate',
            'id_to'     => 'endDate',
            'format'    => '',
        ];
        $ext             = array_merge($defaultExt, $ext);
        $ext['type']     = 'nytime';
        if (isset($config['alias']) && is_array($config['alias'])) {
            foreach ($config['alias'] as $alias) {
                $this->setAliasData($field, $alias);
            }
        }
        $this->setMarkDomParam($field, $ext);
        $this->setTimeSectionField($field);
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
        $ext = [
            'type' => 'nyselect',
        ];
        if (isset($config['ext'])) {
            $ext = array_merge($config['ext'], $ext);
        }
        if (isset($config['alias'])) {
            $this->setMarkDomParam($config['alias'], $ext);
            $this->setAliasData($field, $config['alias']);
        } else {
            $this->setMarkDomParam($field, $ext);
        }
        $this->setFieldConfig($field, $config);
    }


    public function setAssocType($leftKey, $rightKey, $config = [])
    {
        $ext          = isset($config['ext']) ? $config['ext'] : [];
        $ext['type']  = 'nyselectunion';
        $ext['assoc'] = $rightKey;
        $value        = isset($ext['options']) ? $ext['options'] : [];
        $this->setAliasData($leftKey, $leftKey);
        $this->setAliasData($rightKey, $rightKey);
        $this->setfieldValue($leftKey, $value);
        $this->setFieldConfig($leftKey, $config);
        $this->setFieldConfig($rightKey, $config);
        $this->setAssociated($leftKey, $rightKey);
        $this->setMarkDomParam($leftKey, $ext);

        if (isset($config['alias']) && is_array($config['alias'])) {
            foreach ($config['alias'] as $field => $alias) {
                $this->setAliasData($field, $alias);
                $this->setFieldConfig($field, $config);
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
        return isset($this->associated[$name]) ? $this->associated[$name] : false;
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


    public function getWhere($param)
    {
        $whereMap    = [];
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
                    if (isset($param[$ass[$k]]) && $param[$ass[$k]] != '') {
                        $leftK         = $param[$k];
                        $rightV        = $param[$ass[$k]];
                        $param[$leftK] = $rightV;
                    }
                    unset($param[$k]);
                    unset($param[$ass[$k]]);
                } else {
                    unset($param[$k]);
                    unset($param[$ass[$k]]);
                }
            }
        }

        //转换别名为真实字段
        if ($this->aliasData) {
            foreach ($param as $key => $item) {
                if (isset($this->aliasData[$key])) {
                    if ($key != $this->aliasData[$key]) {

                        $newK = $this->aliasData[$key];
                        if (in_array($newK, $this->timeSectionField)) {
                            $param[$newK][] = $item;
                            unset($param[$key]);
                        } else {
                            $param[$newK] = $item;
                            unset($param[$key]);
                        }
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
                    break;
            }
            $maps[$key] = [$op, $item];
        }

        return $maps;

    }


    public function makeDom($action, $data = [], $method = 'get')
    {

        $builder      = new Builder($action, $method);
        $markDomParam = $this->getMarkDomParams();
        $builder->addFormItems($markDomParam);
        $builder->addNyButton('searchBtn', []);
        $builder->setFormData($data);

        return $builder->getVars();

    }


}

