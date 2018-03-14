<?php

/**
 * Redis 操作，支持 Master/Slave 的负载集群
 *
 */
class cache
{
    
    // 是否使用 M/S 的读写集群方案
    private $_isUseCluster = false;
    
    // Slave 句柄标记
    private $_sn = 0;
    
    // 是否连接成功
    public $is_connect = false;
    
    // 服务器连接句柄
    private $_linkHandle = array(
        'master' => null, // 只支持一台 Master
        'slave' => array()
    );
    // 可以有多台 Slave
    
    /**
     * 构造函数
     *
     * @param boolean $isUseCluster
     *            是否采用 M/S 方案
     */
    public function __construct($isUseCluster = false)
    {
        $this->_isUseCluster = $isUseCluster;
        $this->is_connect = $this->connect(array(
            'host' => REDIS_CACHE_HOST,
            'port' => REDIS_CACHE_PORT
        ));
    }

    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     *
     * @param array $config
     *            Redis服务器配置
     * @param boolean $isMaster
     *            当前添加的服务器是否为 Master 服务器
     * @return boolean
     */
    public function connect($config = array('host'=>'127.0.0.1','port'=>6379), $isMaster = true)
    {
        // default port
        if (! isset($config['port'])) {
            $config['port'] = 6379;
        }
        // 设置 Master 连接
        if ($isMaster) {
            $this->_linkHandle['master'] = new Redis();
            // dump($this->_linkHandle['master']);exit;
            $ret = $this->_linkHandle['master']->pconnect($config['host'], $config['port']);
        } else {
            // 多个 Slave 连接
            $this->_linkHandle['slave'][$this->_sn] = new Redis();
            $ret = $this->_linkHandle['slave'][$this->_sn]->pconnect($config['host'], $config['port']);
            ++ $this->_sn;
        }
        return $ret;
    }

    /**
     * 关闭连接
     *
     * @param int $flag
     *            关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag = 2)
    {
        switch ($flag) {
            // 关闭 Master
            case 0:
                $this->getRedis()->close();
                break;
            // 关闭 Slave
            case 1:
                for ($i = 0; $i < $this->_sn; ++ $i) {
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
            // 关闭所有
            case 1:
                $this->getRedis()->close();
                for ($i = 0; $i < $this->_sn; ++ $i) {
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
        }
        return true;
    }

    /**
     * 得到 Redis 原始对象可以有更多的操作
     *
     * @param boolean $isMaster
     *            返回服务器的类型 true:返回Master false:返回Slave
     * @param boolean $slaveOne
     *            返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($isMaster = true, $slaveOne = true)
    {
        // 只返回 Master
        if ($isMaster) {
            return $this->_linkHandle['master'];
        } else {
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
    }

    /**
     * 写缓存
     *
     * @param string $key
     *            组存KEY
     * @param string $value
     *            缓存值
     * @param int $expire
     *            过期时间， 0:表示无过期时间
     */
    public function set($key, $value, $flag = false, $expire = 0)
    {
        if ($this->is_connect === false)
            return false;
            // 永不超时
        if ($expire == 0) {
            // dump($this->getRedis());exit;
            $ret = $this->getRedis()->set($key, $value);
        } else {
            $ret = $this->getRedis()->setex($key, $expire, $value);
        }
        return $ret;
    }

    /**
     * 读缓存
     *
     * @param string $key
     *            缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean 失败返回 false, 成功返回字符串
     */
    public function get($key)
    {
        if ($this->is_connect === false)
            return false;
            // 是否一次取多个值
        $func = is_array($key) ? 'mGet' : 'get';
        // 没有使用M/S
        if (! $this->_isUseCluster) {
            
            return $this->getRedis()->{$func}($key);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->{$func}($key);
    }

    /*
     * // magic function
     * public function __call($name,$arguments){
     * return call_user_func($name,$arguments);
     * }
     */
    /**
     * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
     *
     * @param string $key
     *            缓存KEY
     * @param string $value
     *            缓存值
     * @return boolean
     */
    public function setnx($key, $value)
    {
        return $this->getRedis()->setnx($key, $value);
    }

    /**
     * 删除缓存
     *
     * @param
     *            string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function delete($key)
    {
        // $key => "key1" || array('key1','key2')
        if ($this->is_connect === false)
            return false;
        return $this->getRedis()->delete($key);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key
     *            缓存KEY
     * @param int $default
     *            操作时的默认值
     * @return int
     */
    public function incr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->incr($key);
        } else {
            return $this->getRedis()->incrBy($key, $default);
        }
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key
     *            缓存KEY
     * @param int $default
     *            操作时的默认值
     * @return int
     */
    public function decr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->decr($key);
        } else {
            return $this->getRedis()->decrBy($key, $default);
        }
    }

    /**
     * 添空当前数据库
     *
     * @return boolean
     */
    public function clear()
    {
        return $this->getRedis()->flushDB();
    }

    /* =================== 以下私有方法 =================== */
    
    /**
     * 随机 HASH 得到 Redis Slave 服务器句柄
     *
     * @return redis object
     */
    private function _getSlaveRedis()
    {
        // 就一台 Slave 机直接返回
        if ($this->_sn <= 1) {
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = $this->_hashId(mt_rand(), $this->_sn);
        return $this->_linkHandle['slave'][$hash];
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     *
     * @param string $id            
     * @param int $m            
     * @return int
     */
    private function _hashId($id, $m = 10)
    {
        // 把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for ($i = 0; $i < $l; $i ++) {
            // 相加模式HASH
            $h += substr($b, $i * 2, 2);
        }
        $hash = ($h * 1) % $m;
        return $hash;
    }

    /**
     * lpush
     */
    public function lpush($key, $value)
    {
        return $this->getRedis()->lpush($key, $value);
    }

    /**
     * rpush
     */
    public function rpush($key, $value)
    {
        return $this->getRedis()->rpush($key, $value);
    }

    /**
     * add lpop
     */
    public function lpop($key)
    {
        return $this->getRedis()->lpop($key);
    }

    /**
     * lrange
     */
    public function lrange($key, $start, $end)
    {
        return $this->getRedis()->lrange($key, $start, $end);
    }

    /**
     * set hash opeation
     */
    public function hset($name, $key, $value)
    {
        if (is_array($value)) {
            return $this->getRedis()->hset($name, $key, serialize($value));
        }
        return $this->getRedis()->hset($name, $key, $value);
    }

    /**
     * get hash opeation
     */
    public function hget($name, $key = null, $serialize = true)
    {
        if ($key) {
            $row = $this->getRedis()->hget($name, $key);
            if ($row && $serialize) {
                unserialize($row);
            }
            return $row;
        }
        return $this->getRedis()->hgetAll($name);
    }

    /**
     * delete hash opeation
     */
    public function hdel($name, $key = null)
    {
        if ($key) {
            return $this->getRedis()->hdel($name, $key);
        }
        return $this->getRedis()->hdel($name);
    }

    /**
     * Transaction start
     */
    public function multi()
    {
        return $this->getRedis()->multi();
    }

    /**
     * Transaction send
     */
    public function exec()
    {
        return $this->getRedis()->exec();
    }

    /**
     * 验证指定的键是否存在
     */
    public function exists($key)
    {
        return $this->getRedis()->exists($key);
    }

    /**
     * 取得所有指定键的值。如果一个或多个键不存在，该数组中该键的值为假
     * 描述：取得所有指定键的值。如果一个或多个键不存在，该数组中该键的值为假
     * 参数：其中包含键值的列表数组
     * 返回值：返回包含所有键的值的数组
     */
    public function getMultiple($arr)
    {
        return $this->getRedis()->getMultiple($arr);
    }

    /**
     * 返回的列表的长度。如果列表不存在或为空，该命令返回0。如果该键不是列表，该命令返回FALSE。
     */
    public function lsize($lists)
    {
        return $this->getRedis()->lsize($lists);
    }

    /**
     * 返回的列表的长度。如果列表不存在或为空，该命令返回0。如果该键不是列表，该命令返回FALSE。
     */
    public function llen($lists)
    {
        return $this->getRedis()->llen($lists);
    }

    /**
     * 描述：返回指定键存储在列表中指定的元素。 0第一个元素，1第二个… -1最后一个元素，-2的倒数第二…错误的索引或键不指向列表则返回FALSE。
     * 参数：key index
     * 返回值：成功返回指定元素的值，失败false
     */
    public function lget($key, $index)
    {
        return $this->getRedis()->lget($key, $index);
    }

    /**
     * 描述：为列表指定的索引赋新的值,若不存在该索引返回false.
     * 参数：key index value
     * 返回值：成功返回true,失败false
     */
    public function lset($key, $index, $value)
    {
        return $this->getRedis()->lset($key, $index, $value);
    }

    /**
     * 返回在该区域中的指定键列表中开始到结束存储的指定元素，lGetRange(key, start, end)。0第一个元素，1第二个元素… -1最后一个元素，-2的倒数第二…
     * 参数：key start end
     * 返回值：成功返回查找的值，失败false
     */
    public function lgetrange($key, $start, $end)
    {
        return $this->getRedis()->lgetrange($key, $start, $end);
    }

    /**
     * 从列表中从头部开始移除count个匹配的值。如果count为零，所有匹配的元素都被删除。如果count是负数，内容从尾部开始删除。
     * 参数：key count value
     * 返回值：成功返回删除的个数，失败false
     */
    public function lremove($key, $count, $value)
    {
        return $this->getRedis()->lremove($key, $count, $value);
    }

    /**
     * 为一个Key添加一个值。如果这个值已经在这个Key中，则返回FALSE。
     * 参数：key value
     * 返回值：成功返回true,失败false
     */
    public function sadd($key, $value)
    {
        return $this->getRedis()->sadd($key, $value);
    }

    /**
     * 删除Key中指定的value值
     * 参数：key member
     * 返回值：true or false
     */
    public function sremove($key, $count, $value)
    {
        return $this->getRedis()->sremove($key, $member, $value);
    }

    /**
     * 将Key1中的value移动到Key2中
     * 参数：srcKey dstKey member
     * 返回值：true or false
     */
    public function smove($srcKey, $dstKey, $member)
    {
        return $this->getRedis()->smove($srcKey, $dstKey, $member);
    }

    /**
     * 检查集合中是否存在指定的值。
     * 参数：key value
     * 返回值：true or false
     */
    public function scontains($key, $value)
    {
        return $this->getRedis()->scontains($key, $value);
    }

    /**
     * 返回集合中存储值的数量
     * 参数：key
     * 返回值：成功返回数组个数，失败0
     */
    public function ssize($key)
    {
        return $this->getRedis()->ssize($key);
    }

    /**
     * 描述：随机移除并返回key中的一个值
     * 参数：key
     * 返回值：成功返回删除的值，失败false
     */
    public function spop($key)
    {
        return $this->getRedis()->spop($key);
    }

    /**
     * 描述：返回一个所有指定键的交集。如果只指定一个键，那么这个命令生成这个集合的成员。如果不存在某个键，则返回FALSE。
     * 参数：key1, key2, keyN
     * 返回值：成功返回数组交集，失败false
     */
    public function sinter($key1, $key2 = false, $key3 = false, $key4 = false, $key5 = false)
    {
        return $this->getRedis()->sinter($key);
    }

    /**
     * 描述：执行sInter命令并把结果储存到新建的变量中。
     * 参数：
     * Key: dstkey, the key to store the diff into.
     * Keys: key1, key2… keyN. key1..keyN are intersected as in sInter.
     * 返回值：成功返回数组交集，失败false
     */
    public function sinterstore($new, $key1, $key2)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->sinterstore($new, $key1, $key2);
    }

    /**
     * 描述：返回一个所有指定键的并集
     * 参数：Keys: key1, key2, … , keyN
     * 返回值：成功返回合并后的集，失败false
     */
    public function sunion($key1, $key2)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->sunion($key1, $key2);
    }

    /**
     * 描述：执行sunion命令并把结果储存到新建的变量中。
     * 参数：
     * Key: dstkey, the key to store the diff into.
     * Keys: key1, key2… keyN. key1..keyN are intersected as in sInter.
     * 返回值：成功返回合并后的集，失败false
     */
    public function sunionstore($new, $key1, $key2)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->sunionstore($new, $key1, $key2);
    }

    /**
     * 描述：返回第一个集合中存在并在其他所有集合中不存在的结果
     * 参数：Keys: key1, key2, … , keyN: Any number of keys corresponding to sets in redis.
     * 返回值：成功返回数组，失败false
     */
    public function sdiff($key1, $key2)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->sdiff($key1, $key2);
    }

    /**
     * 描述：执行sdiff命令并把结果储存到新建的变量中。
     * 参数：
     * Key: dstkey, the key to store the diff into.
     * Keys: key1, key2, … , keyN: Any number of keys corresponding to sets in redis
     * 返回值：成功返回数组，失败false
     */
    public function sdiffstore($new, $key1, $key2)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->sdiffstore($new, $key1, $key2);
    }

    /**
     * 描述：返回集合的内容
     * 参数：Key: key
     * 返回值：An array of elements, the contents of the set.
     */
    public function smembers($new)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->smembers($new);
    }

    /**
     * 描述：返回集合的内容
     * 参数：Key: key
     * 返回值：An array of elements, the contents of the set.
     */
    public function sgetmembers($new)
    {
        // TODO: 需要做不定长参数传参
        return $this->getRedis()->sgetmembers($new);
    }
}// End Class
