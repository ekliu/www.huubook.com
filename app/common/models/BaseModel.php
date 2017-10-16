<?php

namespace Huubook\Models;

use Phalcon\Mvc\Model;

class BaseModel extends Model
{
    //查询条件
    protected $conditons = '';
    protected $parameters = array();
    //find参数
    protected $find = array();
    protected $table;

    public function initialize()
    {
        //设置主从服务器
        $this->setReadConnectionService('dbs');
        $this->setWriteConnectionService("dbm");
        //关闭检测字段是否不能为空
        $this->setup(['notNullValidations' => false]);

    }

    /**
     * 选择连接的数据库
     */
    public function connect($db)
    {
        //设置链接的数据库，等级高于配置文件，不设置别默认连接配置文件中的数据库
        $this->setSchema($db);
        return $this;
    }

    /**
     * 使用主库
     * 例1  $this->useMaster()->table('article')->wyWhere(['id'=>12])->wyUpdate(['author'=>'张三']);
     */
    public function useMaster()
    {
        $this->setReadConnectionService('dbm');
        return $this;
    }

    /**
     * 设置链接的表名，返回当前类
     * 例1  $this->table('article')->wyWhere(['id'=>12])->wyUpdate(['author'=>'张三']);
     */
    public function table($table)
    {
        $this->setSource($table);
        $this->table = $table;
        return $this;
    }

    /**
     * Add a basic where clause to the query.
     * $conditons = 'username = :username: and password = :password:';
     * $parameters = [
     * 'username' => $username,
     * 'password' => $password,
     * ];
     * 例1 ：$this->table('article')->wyWhere(['id'=>12])->wyUpdate(['author'=>'张三']); //where 为  id=12
     * 例2,可传二维数组 ：$this->table('article')->wyWhere(['id'=>12,['created_at',">=",'2017147']])->wyUpdate(['author'=>'张三']); //where 为  id=12 and created_at>=2017147
     * 例3 ：$this->table('article')->wyWhere(['id'=>12,['id',"<",'5','or']])->wyUpdate(['author'=>'张三']);   //where 为  (id=12 or id<=5)
     * 例4：$this->table('article')->wyWhere(['id'=>12,['id',"<",'5','or']])->wyWhere([['name','like','%杨%']],'or')->wyUpdate(['author'=>'张三']);   //where 为  (id=12 or id<=5)  or (name  like '%杨%')
     */
    public function wyWhere(array $para, $boolean = 'and')
    {
        if (is_array($para) && !empty($para)) {
            if (!empty($this->conditons)) {
                //多个where 连接时,彼此之间用 $boolean  连接
                $this->conditons .= ' ' . $boolean . ' ';
            }
            $this->conditons .= "(";
            $i = 0;
            $count = count($this->parameters);
            foreach ($para as $key => $one) {
                //连接符，默认为and
                $mark = 'and';
                if (is_array($one)) {
                    if (count($one) == 2) {
                        $str = $one[0] . " = ?" . ($count + 1);
                        $this->parameters[$count + 1] = strval($one[1]);
                    } else {
                        $str = $one[0] . " " . $one[1] . " ?" . ($count + 1);
                        $this->parameters[$count + 1] = strval($one[2]);
                    }
                    //操作中可以设置连接符
                    if (isset($one[3])) {
                        $mark = $one[3];
                    }
                } else {
                    $str = $key . " =  ?" . ($count + 1);
                    $this->parameters[$count + 1] = strval($one);
                }
                if (isset($str)) {
                    if ($this->conditons == "(") {
                        //最开始的一个条件时，不加and 或者 加or
                        if (strcasecmp('or', $mark) == 0) {
                            $this->conditons .= '1 ' . $mark . " " . $str;
                        } else {
                            $this->conditons .= $str;
                        }
                    } else {
                        if ($i == 0) {
                            //多个where 连接时，每个where 条件开头不加 and 与 or
                            $mark = '';
                        }
                        $this->conditons .= ' ' . $mark . ' ' . $str;
                    }
                }
                $i++;
                $count++;
            }
            $this->conditons .= ")";
        }
        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     * @param array $para
     * @return BaseModel
     * @author 有法（北京）网络科技有限公司  2017-03-21
     */
    public function wyOrWhere(array $para)
    {
        return $this->wyWhere($para, 'or');
    }

    /**
     * 分页,与wyWhere配合使用，一般wyWhere 使用二维数组
     * @param $page  第几页
     * @param $per_page 每页个数
     * 例1 ：$this->table('article')->wyWhere([['id','>=','15']])->wyPage(1,15)->wyColumns(['author'=>'张三']);
     */
    public function wyPage($page, $per_page)
    {
        $this->find['limit'] = $per_page;
        $this->find['offset'] = ($page - 1) * $per_page;
        return $this;
    }

    /**
     * 排序
     * @param $order 传字符串（可以多个字段排序，逗号隔开；如 name DESC, status ASC）
     * 当传输组时,请按如此传参： 【'name','desc'】
     * 例1，字符串 ：$this->table('article')->wyWhere([['id','>=','15']])->wyOrder('article_id  desc')->wyPage(1,15)->wyColumns(['author'=>'张三']);
     * 例2,数组 ：$this->table('article')->wyWhere([['id','>=','15']])->wyOrder(['article_id','desc'])->wyPage(1,15)->wyColumns(['author'=>'张三']);
     */
    public function wyOrder($order)
    {
        if (is_string($order)) {
            $this->find['order'] = $order;
        }
        if (is_array($order)) {
            $this->find['order'] = $order[0] . " " . $order[1];
        }
        return $this;
    }

    /**
     * @param string $group 分组条件（多个请用逗号隔开），如：name, status
     * 例1 $this->table('article')->wyWhere([['id','>=','15']])->wyGroup('ac_id')->wyOrder(['article_id','desc'])->wyPage(1,15)->wyColumns(['author'=>'张三']);
     * 例2,根据多个字段分组 $this->table('article')->wyWhere([['id','>=','15']])->wyGroup('ac_id,author')->wyOrder(['article_id','desc'])->wyPage(1,15)->wyColumns(['author'=>'张三']);
     */
    public function wyGroup($group)
    {
        $this->find['group'] = $group;
        return $this;
    }

    /**
     * 获取满足条件的总数量
     * @author 有法（北京）网络科技有限公司  2017-03-21
     */
    public function wyCount()
    {
        if (!empty($this->conditons)) {
            $this->find[] = $this->conditons;
            $this->find['bind'] = $this->parameters;
        }
        $return = parent::count($this->find);
        $this->reStart();
        return $return;
    }

    /**
     * gen更新数据 ,必须先使用wyWhere()
     * @param $para
     * 例1 ：$this->table('article')->wyWhere(['id'=>12])->wyUpdate(['author'=>'张三']);
     */
    public function wyUpdate(array $para)
    {
        if (empty($para)) {
            return 0;
        }
        //必须存在更新条件  where
        if (!empty($this->conditons)) {
            $this->find[] = $this->conditons;
            $this->find['bind'] = $this->parameters;
        } else {
            return 0;
        }
        $return = $this->sendSql('update', $para);
        $this->reStart();
        return $return;
    }

    /**
     * 删除,必须先使用wyWhere()
     * @return int
     * @author 有法（北京）网络科技有限公司  2017-03-21
     * 例1 ：$this->table('article')->wyWhere(['id'=>12])->wyDelete();
     */
    public function wyDelete()
    {
        //必须存在更新条件  where
        if (!empty($this->conditons)) {
            $this->find[] = $this->conditons;
            $this->find['bind'] = $this->parameters;
        } else {
            return 0;
        }
        $return = $this->sendSql('delete');
        $this->reStart();
        return $return;
    }

    /**
     * 外部不要调用，只为类文件中wyUpdate,wyDelete提供服务
     * 拼接sql语句执行;现有问题    无论是否成功都会返回true,如记录不存在时
     * @param $type
     * @param array $arr
     * @return mixed
     * @author 有法（北京）网络科技有限公司  2017-03-21
     */
    protected final function sendSql($type, $arr = array())
    {
        if (!empty($arr) && $type = 'update') {
            //重新拼接 key 值
            $set = '';
            $i = 1;
            foreach ($arr as $key => $one) {
                $set .= "`" . $key . "` ='" . $one . "'";
                if ($i != count($arr)) {
                    $set .= ",";
                }
                $i++;
            }
            $sql = "update `" . $this->table . "` set " . $set . "  where " . $this->conditons;
        }
        if ($type == 'delete') {
            $sql = "delete from `" . $this->table . "` where " . $this->conditons;
        }
        $return = $this->getWriteConnection()->execute(preg_replace("/\\?\\d*/", "?", $sql), array_values($this->parameters));
        if ($return) {
            return $this->getWriteConnection()->affectedRows();
        }
        return $return;
    }

    /**
     * 插入数据
     * @param array $para 要插入色数据
     * @param string $id 插入表中地自增长字段，用于返回该字段新添加的值，默认'id'
     * 例1 ：$this->table('article')->wyInsert(['author'=>'张三','title'=>'标题','ac_id'=>12],'article_id');
     */
    public function wyInsert(array $para, $id = 'id')
    {
        if (empty($para)) {
            return 0;
        }
        $return = parent::create($para);
        if ($return) {
            $id = $this->$id;
            $this->reStart();
            if ($id) {
                return $id;
            } else {
                return $return;
            }
        }
        $this->reStart();
        return $return;
    }

    /**
     * 取出数据
     * @param $field 获取指定字段（可为数组，也可为字符串）
     * @param $limit
     * 例1 ：$this->table('article')->wyWhere(['id'=>12])->wyColumns(['author','title']);
     * 例2 ：$this->table('article')->wyWhere(['id'=>12])->wyColumns('author,title');
     */
    public function wyColumns($field = ['*'], $limit = 0)
    {
        if (!empty($this->conditons)) {
            $this->find[] = $this->conditons;
            $this->find['bind'] = $this->parameters;
        }
        if (is_array($field)) {
            $this->find['columns'] = implode(",", $field);
        } elseif (is_string($field)) {
            $this->find['columns'] = $field;
        }
        if (!empty($limit)) {
            $this->find['limit'] = $limit;
        }
        // var_dump($this->conditons, $this->parameters);die;
        $res = parent::find($this->find);
        $this->reStart();
        return empty($res) ? [] : $res->toArray();
    }

    /**
     * 取出数据，并分页
     * @param $field 获取指定字段（可为数组，也可为字符串）
     * @param $page 获取第几页数据
     * @param $perPage 每页中获取地记录条数
     * 例1 ：$this->table('article')->wyWhere(['id'=>12])->wyPaginate(['author','title'],1,10);
     */
    public function wyPaginate($field = ['*'], $page, $perPage = 15)
    {
        if (!empty($this->conditons)) {
            $this->find[] = $this->conditons;
            $this->find['bind'] = $this->parameters;
        }
        if (is_array($field)) {
            $this->find['columns'] = implode(",", $field);
        } elseif (is_string($field)) {
            $this->find['columns'] = $field;
        }
        //➊ 获取总数量  ➍➎➏➐➑➒➓
        $count = parent::count($this->find);
        //➋ 设置分页参数
        $this->find['limit'] = $perPage;
        $this->find['offset'] = ($page - 1) * $perPage;
        //➌ 查询结果
        $res = parent::find($this->find);
        $this->reStart();
        $res = empty($res) ? [] : $res->toArray();
        return [
            'count' => $count,
            'last_page' => ceil($count / $perPage),
            'page' => $page,
            'page_num' => $perPage,
            'data' => $res,
        ];
    }

    /**
     * 获取一条数据
     * @param array $field，获取的字段
     * 例1 ：$this->table('article')->wyWhere(['id'=>12])->wyFirst(['author','title']);
     */
    public function wyFirst($field = ['*'])
    {
        if (!empty($this->conditons)) {
            $this->find[] = $this->conditons;
            $this->find['bind'] = $this->parameters;
            $this->find['columns'] = implode(",", $field);
        }
        $res = parent::findFirst($this->find);
        $this->reStart();
        return empty($res) ? [] : $res->toArray();
        // return parent::findFirst($this->find)->toArray();
    }

    /**
     * 开启事务，并将读库设置为主库
     * @author 有法（北京）网络科技有限公司  2017-03-21
     * 例：
     * $this->wyBegin();
     *
     * $this->table('article')->wyWhere(['id'=>12])->wyUpdate(['author'=>'张三']);
     *
     * $this->wyRollback(); //或者 $this->wyCommit();
     */
    public function wyBegin()
    {
        $this->useMaster();
        $this->getWriteConnection()->begin();
    }

    /**
     * 事务回滚
     * @author 有法（北京）网络科技有限公司  2017-03-21
     */
    public function wyRollback()
    {
        $this->getWriteConnection()->rollback();
    }

    /**
     * 事务提交
     * @author 有法（北京）网络科技有限公司  2017-03-21
     */
    public function wyCommit()
    {
        $this->getWriteConnection()->commit();
    }

    /**
     * 使用原生语句
     * $this->getWriteConnection()->execute('update yf_project_member set created_at=1111 where id=2');
     */

    /**
     * 重新开始
     * @author 有法（北京）网络科技有限公司  2017-05-05
     */
    protected function reStart()
    {
        $this->conditons = '';
        $this->parameters = array();
        $this->find = array();
        $this->table = '';
    }

    /**
     * 过滤是null的数据
     * @param array $input
     * @return array
     */
    public function filterNull(array $input)
    {
        if (!empty($input)) {
            foreach ($input as $key => $item) {
                if ($item === null) {
                    unset($input[$key]);
                }
            }
        }
        return $input;
    }


    /**
     * 批量插入
     * @param $table
     * @param array $data
     * @return int
     * @author 有法（北京）网络科技有限公司  2017-08-31  liug
     * 使用方法：
     *      $model->wyInsertBatch('表名', 二维数据组);
     */
    public function wyInsertBatch($table = '', array $data)
    {
        $res = 0;
        if (!empty($table)) {
            $sql = $this->makeInsertSql($table, $data);
            $this->getWriteConnection()->execute($sql);
            $res = $this->getWriteConnection()->lastInsertId();
        }
        return $res;
    }

    /**
     * 生成批量添加sql语句
     * @param string $table
     * @param array $data
     * @return string
     */
    public function makeInsertSql($table, array $data)
    {
        $sql = '';
        if (!empty($table)) {
            $keys = array_keys(reset($data));
            $keys = array_map(function ($key) {
                return "`{$key}`";
            }, $keys);
            $keys = implode(',', $keys);
            $sql = "INSERT INTO " . $table . " ({$keys}) VALUES ";
            foreach ($data as $v) {
                $v = array_map(function ($value) {
                    return "'{$value}'";
                }, $v);
                $values = implode(',', array_values($v));
                $sql .= " ({$values}), ";
            }
            $sql = rtrim(trim($sql), ',');
        }
        return $sql;
    }


}