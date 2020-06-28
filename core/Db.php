<?php
//数据库操作类
namespace core;

class Db
{
    public static $connection;
    protected static $PDOStatement; //PDOStatement对象
    protected static $table; //数据表名称
    protected static $lastInsertId = null;//保存上一步插入操作产生AUTO_INCREMENT
    protected static $numRows = null;//返回受上一个 SQL 语句影响的行数
    protected $alias;
    protected $join;
    protected $where;
    protected $field;
    protected $order;
    protected $group;
    protected $having;
    protected $limit;
    protected static $queryStr; //sql语句
    protected $selectSql = 'SELECT%FIELD%FROM%TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';//查询语句
    protected $insertSql = 'INSERT INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMONT%';//插入语句
    protected $updateSql = 'UPDATE %TABLE% SET %SET% %WHERE% %COMMONT%';//更新语句
    protected $deleteSql = 'DELETE FROM %TABLE% %WHERE%';//删除语句

    //构造函数
    public function __construct()
    {
        $this->connect();
    }

    protected static function connect()
    {
        $dbConfig = Config::get('database');
        $dsn = $dbConfig['type'] . ':host=' . $dbConfig['hostname'] . ';dbname=' . $dbConfig['database'];
        try {
            if (!is_object(self::$connection)) {
                self::$connection = new \PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            }
        } catch (\Exception $e) {
            self::throwException($e->getMessage());
        }
    }

    //表名
    //name('sys_sample')
    public static function name($table)
    {
        if (!empty($table)) {
            self::$table = $table;
        }
        return new self;
    }

    //字段
    //field('user_id,user_name') 或 field(['u.user_id','u.user_name'])
    public function field($field)
    {
        is_string($field) && $field = explode(',', $field);
        foreach ($field as $key => $val) {
            $this->field[] = $val;
        }
        return $this;
    }

    //别名
    //alias('a')
    public function alias($alias)
    {
        if (is_array($alias)) {
            //todo
        } else if (is_string($alias)) {
            $this->alias = $alias;
        }
        return $this;
    }

    //联表
    //join('sys_sample s') 或 join([['think_work w','a.id=w.artist_id','left']])
    public function join($join, $condition = null, $type = 'INNER')
    {
        if (is_string($join)) {
            if (!is_null($condition)) {
                $joinType = strtoupper($type) . ' JOIN';//转化join类型
                $this->join[] = [
                    $joinType,
                    $join,
                    $condition,
                ];
            } else {
                self::throwException('请传入condition参数');
            }
        } else if (is_array($join)) {
            foreach ($join as $key => $val) {
                !isset($val[0]) && self::throwException('请传入join参数');
                !isset($val[1]) && self::throwException('请传入condition参数');
                $joinType = isset($val[2]) ? strtoupper($val[2]) . ' JOIN' : strtoupper($type) . ' JOIN';//转化join类型
                $this->join[] = [
                    $joinType,
                    $val[0],
                    $val[1]
                ];
            }
        }
        return $this;
    }

    //条件
    //where('user_id','1') 或 where(['user_id','1']) 或 where(['user_id'=>['in',[1,2,3]]])
    public function where($field, $value = null)
    {
        if (is_string($field)) {
            $this->where[$field] = $value;
        } else if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->where[$key] = $val;
            }
        }
        return $this;
    }

    //限制条数
    //limit(5) 或 limit(1,2)
    public function limit($offset, $rows = null)
    {
        if (is_string($offset)) {
            $this->limit = $offset;
        } else if (is_numeric($offset)) {
            if (is_null($rows)) {
                $this->limit = (string)$offset;
            } else if (is_numeric($rows)) {
                $this->limit = (string)$offset . ',' . $rows;
            }
        }
        return $this;
    }

    //排序
    //order('id desc,status') 或者 order(['id'=>'desc','create_time'=>'desc'])
    public function order($field, $order=null)
    {
        if (is_string($field)) {
            if (!is_null($order)) {
                $this->order[$field] = $order;
            } else {
                $field = explode(',', $field);
                foreach ($field as $val) {
                    $args = explode(' ', $val);
                    if (isset($args[1])) {
                        $this->order[$args[0]] = $args[1];
                    } else {
                        $this->order[$args[0]] = 'asc';
                    }
                }
            }
        } else if (is_array($field)) {
            if (is_null($order)) {
                foreach ($field as $key => $val) {
                    if (is_numeric($key)) {
                        $this->order[$val] = 'asc';
                    } else {
                        $this->order[$key] = $val;
                    }
                }
            }
        }
        return $this;
    }

    //分组
    //group('user_id') 或 group('user_id,order_id')
    public function group($group)
    {
        if (is_string($group)) {
            $this->group = $group;
        }
        return $this;
    }

    //筛选分组后的数据
    //having(count(id)>1)
    public function having($having)
    {
        is_string($having) && $this->having = $having;
        return $this;
    }

    /**
     * 查询单条数据
     * @return mixed
     */
    public function find()
    {
        $sql = str_replace(
            ['%FIELD%', '%TABLE%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'],
            [$this->parseField(), $this->parseTable(), $this->parseJoin(), $this->parseWhere(), $this->parseGroup(), $this->parseHaving(), $this->parseOrder(), $this->parseLimit()],
            $this->selectSql
        );
        self::query($sql);
        $result = self::$PDOStatement->fetch(constant('PDO::FETCH_ASSOC'));//以关联数组形式返回结果集
        return $result;
    }

    /**
     * 查询数据集
     * @return mixed
     */
    public function select()
    {
        $sql = str_replace(
            ['%FIELD%', '%TABLE%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'],
            [$this->parseField(), $this->parseTable(), $this->parseJoin(), $this->parseWhere(), $this->parseGroup(), $this->parseHaving(), $this->parseOrder(), $this->parseLimit()],
            $this->selectSql
        );
        self::query($sql);
        $result = self::$PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));//以关联数组形式返回结果集
        return $result;
    }

    /**
     * 插入数据 insert(['user_id'=>'1','user_name'=>'admin'])
     * @param array $data
     * @return bool true/false 返回成功或失败
     */
    public function insert(array $data)
    {
        if (empty($data)) {
            return 0;
        }
        foreach ($data as $key => $val) {
            $data["`{$key}`"] = "'{$val}'";
            unset($data[$key]);
        }
        $fields = array_keys($data);
        $datas = array_values($data);
        $sql = str_replace(
            ['%TABLE%', '%FIELD%', '%DATA%', '%COMMONT%'],
            [$this->parseTable(), implode(',', $fields), implode(',', $datas), $this->parseComment()],
            $this->insertSql
        );
        $result = self::query($sql);
        self::$lastInsertId = self::$connection->lastInsertId();//插入数据表的id
        return $result;//返回上次执行的结果
    }

    /**
     * 更新数据 update(['user_id'=>'1','user_name'=>'admin'])
     * @param array $data
     * @return int 返回受影响的条数
     */
    public function update(array $data)
    {
        if (empty($data)) {
            return 0;
        }
        $setFiled = [];
        foreach ($data as $key => $val) {
            $setFiled[] = "`{$key}` = '{$val}'";
        }
        $sql = str_replace(
            ['%TABLE%', '%SET%', '%WHERE%', '%COMMONT%'],
            [$this->parseTable(), implode(',', $setFiled), $this->parseWhere(), $this->parseComment()],
            $this->updateSql
        );
        self::query($sql);
        self::$numRows = self::$PDOStatement->rowCount();
        return self::$numRows;
    }

    /**
     * 删除数据 delete()
     * @return int 返回受影响的条数
     */
    public function delete()
    {
        $sql = str_replace(
            ['%TABLE%', '%WHERE%'],
            [$this->parseTable(), $this->parseWhere()],
            $this->deleteSql
        );
        self::query($sql);
        self::$numRows = self::$PDOStatement->rowCount();
        return self::$numRows;
    }

    /**
     * 查询某一列的值 column('user_name')
     * @param string $field 查询的列
     * @return mixed
     */
    public function column($field)
    {
        $this->field($field);
        $result = $this->select();
        return $result;
    }

    /**
     * 原生sql查询(仅用于查询)
     * @param string $sql
     * @return mixed
     */
    public static function query($sql = '')
    {
        if (!self::$connection) self::connect();
        if (!empty(self::$PDOStatement)) self::free();
        self::$queryStr = $sql;
        self::$PDOStatement = self::$connection->prepare($sql);//预处理（只在服务端编辑一次SQL语句，提高效率，可以防止sql注入）
        $result = self::$PDOStatement->execute();//执行一条预处理语句
        return $result;
    }

    //PDO::exec() 返回受修改或删除 SQL 语句影响的行数。如果没有受影响的行，则 PDO::exec() 返回 0。
    //用于增删改
    public function execute()
    {

    }

    /**
     * 组装sql语句
     * @return mixed
     */
    /*protected function buildSql()
    {
        $sql = str_replace(
            ['%FIELD%', '%TABLE%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'],
            [$this->parseField(), $this->parseTable(), $this->parseJoin(), $this->parseWhere(), $this->parseGroup(), $this->parseHaving(), $this->parseOrder(), $this->parseLimit()],
            $this->selectSql
        );
        return $sql;
    }*/

    /**
     * 解析field
     * @return string
     */
    protected function parseField()
    {
        $field = ' * ';
        if (!empty($this->field)) {
            $fieldArr = [];
            foreach ($this->field as $key => $val) {
                if (false !== strpos($val, '.')) {
                    //如果有联表，则u.user_id,转化成`u`.`user_id`
                    $val = explode('.', trim($val));
                    $fieldArr[] = "`{$val[0]}`.`{$val[1]}`";
                } else {//如果没有联表，则默认保存原值
                    $fieldArr[] = "`{$val}`";
                }
            }
            $field = ' ' . implode($fieldArr, ',') . ' ';
        }
        return $field;
    }

    /**
     * 解析table
     * @return string
     */
    protected function parseTable()
    {
        $table = '';
        if (!empty(self::$table)) {
            $table = ' `' . self::$table . '` ';
        }
        return $table;
    }

    /**
     * 解析join
     * @return string
     */
    protected function parseJoin()
    {
        $join = '';
        if (!empty($this->join)) {
            !empty($this->alias) && $join = "`{$this->alias}`";
            foreach ($this->join as $key => $val) {
                list($type, $table, $condition) = $val;
                $table = explode(' ', $table);
                $table = "`{$table[0]}` `{$table[1]}`";//转化join表
                $condition = explode('=', $condition);
                foreach ($condition as $k => $v) {
                    $v = (explode('.', trim($v)));
                    $condition[$k] = "`{$v[0]}`.`{$v[1]}`";
                }
                $condition = implode('=', $condition);//转化条件
                $join .= " {$type} {$table} ON {$condition}";
            }
            $join .= ' ';
        }
        return $join;
    }

    /**
     * 解析where
     * @return string
     */
    protected function parseWhere()
    {
        $where = '';
        if (!empty($this->where)) {
            $whereArr = [];
            foreach ($this->where as $key => $val) {
                if (strpos($key, '.')) {//处理如u.user_id的字段
                    $keyArr = explode('.' ,$key);
                    $key = "`{$keyArr[0]}`.`{$keyArr[1]}`";
                } else {//处理user_id
                    $key = "`{$key}`";
                }
                if (is_array($val)) {
                    if (count($val) > 1) {
                        switch (strtoupper($val[0])) {
                            case 'EQ' :
                            case '=' :
                                $whereArr[] = "{$key} = '{$val[1]}'";
                                break;
                            case 'NEQ' :
                            case '<>' :
                                $whereArr[] = "{$key} <> '{$val[1]}'";
                                break;
                            case 'GT' :
                            case '>' :
                                $whereArr[] = "{$key} > '{$val[1]}'";
                                break;
                            case 'LT' :
                            case '<' :
                                $whereArr[] = "{$key} < '{$val[1]}'";
                                break;
                            case 'EGT' :
                            case '>=' :
                                $whereArr[] = "{$key} >= '{$val[1]}'";
                                break;
                            case 'ELT' :
                            case '<=' :
                                $whereArr[] = "{$key} <= '{$val[1]}'";
                                break;
                            case 'IN' :
                                $val[1] = is_array($val[1]) ? implode(',', $val[1]) : $val[1];
                                $whereArr[] = "{$key} IN ({$val[1]})";
                                break;
                            case 'NOT IN' :
                                $val[1] = is_array($val[1]) ? implode(',', $val[1]) : $val[1];
                                $whereArr[] = "{$key} NOT IN ({$val[1]})";
                                break;
                            case 'BETWEEN' :
                                $range = is_array($val[1]) ? $val[1] : explode(',', $val[1]);
                                $whereArr[] = "{$key} BETWEEN {$range[0]} AND {$range[1]}";
                                break;
                            case 'NOT BETWEEN' :
                                $range = is_array($val[1]) ? $val[1] : explode(',', $val[1]);
                                $whereArr[] = "{$key} NOT BETWEEN {$range[0]} AND {$range[1]}";
                                break;
                            case 'LIKE' :
                                $whereArr[] = "{$key} LIKE '{$val[1]}'";
                                break;
                        }
                    } else {
                        if (is_string($val[0])) {
                            $whereArr[] = "{$key} = '{$val[0]}'";
                        }
                    }
                } else {
                    $whereArr[] = "{$key} = '{$val}'";
                }
            }
            $where = 'WHERE ' . implode(' AND ', $whereArr) . ' ';
        }

        return $where;
    }

    /**
     * 解析group
     * @return string
     */
    protected function parseGroup()
    {
        $group = '';
        if (!empty($this->group)) {
            $group = 'GROUP BY ' . $this->group;
        }
        return $group;
    }

    /**
     * 解析having
     * @return string
     */
    protected function parseHaving()
    {
        $having = '';
        if (!empty($this->having) && is_string($this->having)) {
            $having = ' HAVING '.$this->having;
        }
        return $having;
    }

    /**
     * 解析order
     * @return string
     */
    protected function parseOrder()
    {
        $order = '';
        if (!empty($this->order)) {
            $orderArr = [];
            foreach ($this->order as $key=>$val) {
                $orderArr[] = $key . ' ' . $val;
            }
            $order = 'ORDER BY ' . implode(',', $orderArr);
        }
        return $order;
    }

    /**
     * 解析limit
     * @return string
     */
    protected function parseLimit()
    {
        $limit = '';
        if (!empty($this->limit)) {
            if (is_string($this->limit)) {
                $limit = ' LIMIT ' . $this->limit;
            }
        }
        return $limit;
    }

    /**
     * 解析comment
     */
    protected function parseComment()
    {
        $comment = '';

        return $comment;
    }

    /**
     * 释放PDOStatement资源
     */
    protected static function free()
    {
        self::$PDOStatement = null;
    }

    public function getLastSql()
    {
        return self::$queryStr;
    }


    /**
     * 自定义错误处理
     * @param $errMsg
     */
    public static function throwException($errMsg)
    {
        echo '<div style="width:80%;background-color:#ABCDEF;color:black;font-size:20px;padding:20px 0px;">' . $errMsg . '</div>';
    }
}