<?php

/**
 * 用户积分模型
 *
 *
 */
class m_order extends CI_Model implements ObjInterface {

    const STATUS_PAY_SUCCESS = 1;
    const STATUS_PAY_PREPARE = 2;
    const STATUS_PAY_PROCESS = 3;
    CONST STATUS_SHIPPING_SUCCESS = 1;
    CONST STATUS_SHIPPING_PREPARE = 2;

    private $_memcache_goods_user_buy_prepare = 1000000;

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['user_id'] = $data['user_id'];
        $param['address_id'] = $data['address_id'];
        $param['type'] = strtolower(trim($data['type']));
        $param['create_time'] = time();
        $param['status'] = 'CREATE';
        $this->db->insert(TABLE_ORDER, $param);
        $oid = $this->db->insert_id();
        $order_goods = array();
        $total_price = 0;
        //TODO是否要清空ordergoods表中oid对应的其他goods信息
        foreach ($data['order_goods'] as $k => $v) {
            $order_goods[] = array(
                'oid' => $oid,
                'iid' => $v['iid'],
                'num' => $v['num'],
                'price' => $v['price'],
                'price_retail' => $v['price_retail'],
                'title' => $v['title'],
                'cid' => $v['cid'],
                'option' => $v['option'],
            );
            $total_price += $v['price'];
        }
        $this->db->insert_batch(TABLE_ORDER_GOODS, $order_goods);
        //更新订单号，订单价格
        $order_sn = $this->_create_order_sn($oid);
        $this->db->update(TABLE_ORDER, array('order_sn' => $order_sn, 'price' => $total_price), array('oid' => $oid));
        return $oid;
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $this->load->model('m_user');
        $this->load->model('m_address');
        $this->load->model('m_goods');
        $this->load->model('m_goods_category');
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        }
        $detail['user'] = $this->m_user->detail($detail['user_id']);
        $detail['address'] = $this->m_address->detail($detail['address_id']);
        $detail['status'] = new obj_item($this->get_order_statsu($detail['status']));
        $detail['pay_status'] = new obj_item($this->get_pay_statsu($detail['pay_status']));
        $detail['shipping_status'] = new obj_item($this->get_shipping_status($detail['shipping_status']));
        $detail['shipping'] = new obj_order_shipping(array(
            'time' => $detail['shipping_time'],
            'company' => $detail['shipping_company'],
            'company_title' => $this->get_shipping_company_title($detail['shipping_company']),
            'sn' => $detail['shipping_sn'],
        ));
        $detail['order_goods'] = $this->db->get_where(TABLE_ORDER_GOODS, array('oid' => $id))->result_array();
        foreach ($detail['order_goods'] as $k => $v) {
            $goods = $this->m_goods->detail($v['iid']);
            $v['pic'] = $goods->pic_small;
            $v['category'] = $this->m_goods_category->detail($v['cid']);
            $detail['order_goods'][$k] = new obj_order_goods($v);
        }
        return new obj_order($detail);
    }

    public function detail_by_sn($sn) {
        $this->load->model('m_user');
        $this->load->model('m_address');
        $detail = $this->_detail_by_sn($sn);
        if (empty($detail)) {
            return false;
        }
        return $this->detail($detail['oid']);
    }

    public function lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $this->db->select('oid');
        $rows = $this->db->get_where(TABLE_ORDER)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = $this->detail($v['oid']);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_ORDER)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['user_id']) {
            $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['pay_status']) {
            $this->db->where('pay_status', $condition['pay_status']);
        }
        if ($condition['shipping_status']) {
            $this->db->where('shipping_status', $condition['shipping_status']);
        }
        if ($condition['type']) {
            $this->db->where('type', $condition['type']);
        }
        if ($condition['q']) {
            $this->db->where('order_sn', trim($condition['q']));
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_ORDER, array('oid' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    private function _detail_by_sn($sn) {
        $detail = $this->db->get_where(TABLE_ORDER, array('order_sn' => $sn))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function count_goods_user_buy($user_id, $iid) {
        $this->load->library('cache_memcache');
        $key = 'GOODS_USER_BUY_' . $user_id . '_' . $iid;
        $times = $this->cache_memcache->get($key);
        //未发现记录，则同步数量到缓存
        if ($times === false) {
            $row = $this->db->get_where(TABLE_GOODS_USER_BUY, array('user_id' => $user_id, 'iid' => $iid))->row_array(0);
            if (empty($row)) {
                $this->db->insert(TABLE_GOODS_USER_BUY, array('user_id' => $user_id, 'iid' => $iid, 'num' => 0));
            }
            $times = 0;
            $this->_set_memcache_goods_user_buy($user_id, $iid, $row['num'], false); //使用add而不用set，避免并发时覆盖
        }
        return $times;
    }

    //利用memcache的原子操作减已购数量
    public function decrease_goods_user_buy($user_id, $iid, $num) {
        $this->load->library('cache_memcache');
        $key = 'GOODS_USER_BUY_' . $user_id . '_' . $iid;
        $times = $this->cache_memcache->get($key);
        //未发现记录，则同步数量到缓存
        if ($times === false) {
            $row = $this->db->get_where(TABLE_GOODS_USER_BUY, array('user_id' => $user_id, 'iid' => $iid))->row_array(0);
            if (empty($row)) {
                $this->db->insert(TABLE_GOODS_USER_BUY, array('user_id' => $user_id, 'iid' => $iid, 'num' => 0));
                return false;
            }
            $this->_set_memcache_goods_user_buy($user_id, $iid, $row['num'], false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->decrement($key, $num);
        if (!$r) {
            return false;
        }
        if ($r >= $this->_memcache_goods_user_buy_prepare) {//减掉用户购买量后数量不小于预备量，则成功减去
            $this->db->set('num', 'num - ' . intval($num), false);
            $this->db->update(TABLE_GOODS_USER_BUY, array(), array('user_id' => $user_id, 'iid' => $iid));
            return $r - $this->_memcache_goods_user_buy_prepare;
        } else {
            $this->cache_memcache->increment($key, $num); //将预减的数量加回来
            return false;
        }
    }

    //利用memcache的原子操作加已购数量
    public function increase_goods_user_buy($user_id, $iid, $num) {
        $this->load->library('cache_memcache');
        $key = 'GOODS_USER_BUY_' . $user_id . '_' . $iid;
        $times = $this->cache_memcache->get($key);
        //未发现记录，则同步数量到缓存
        if ($times === false) {
            $row = $this->db->get_where(TABLE_GOODS_USER_BUY, array('user_id' => $user_id, 'iid' => $iid))->row_array(0);
            if (empty($row)) {
                $this->db->insert(TABLE_GOODS_USER_BUY, array('user_id' => $user_id, 'iid' => $iid, 'num' => 0));
                $times = 0;
            } else {
                $times = $row['num'];
            }
            $this->_set_memcache_goods_user_buy($user_id, $iid, $times, false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->increment($key, $num);
        $this->db->set('num', 'num + ' . intval($num), false);
        $this->db->update(TABLE_GOODS_USER_BUY, array(), array('user_id' => $user_id, 'iid' => $iid));
        if (!$r) {//memcache异常
            return $times + $num;
        } else {
            return $r - $this->_memcache_goods_user_buy_prepare;
        }
    }

    private function _set_memcache_goods_user_buy($user_id, $iid, $value, $cover = true) {//$cover =true 默认使用set方法覆盖
        $this->load->library('cache_memcache');
        $key = 'GOODS_USER_BUY_' . $user_id . '_' . $iid;
        if ($cover) {
            return $this->cache_memcache->set($key, intval($value) + $this->_memcache_goods_user_buy_prepare, 0);
        } else {
            return $this->cache_memcache->add($key, intval($value) + $this->_memcache_goods_user_buy_prepare, 0);
        }
    }

    //增大并发数需要增加位数
    private function _create_order_sn($oid) {
        $order_sn_left = date('ymd') . (time() % 86400);
        if ($oid > 1000) {
            //方法1,每秒不超过100w的订单数,
            $order_sn_right = str_pad($oid % 1000000, 6, '0', STR_PAD_LEFT);
        } else {
            //方法2,每毫秒不超过1000个订单，修饰订单号
            $str = str_pad(microtime(1) * 1000 % 1000, 3, '0', STR_PAD_LEFT);
            $order_sn_right = $str . str_pad($oid % 1000, 3, '0', STR_PAD_LEFT);
        }
        return $order_sn_left . $order_sn_right;
    }

    public function pay_start($id) {
        $this->db->update(TABLE_ORDER, array('pay_status' => self::STATUS_PAY_PROCESS, 'pay_time' => time()), array('oid' => $id, 'pay_status' => self::STATUS_PAY_PREPARE));
        return true;
    }

    public function pay_finish($id) {
        $this->db->update(TABLE_ORDER, array('pay_status' => self::STATUS_PAY_SUCCESS, 'pay_time' => time(), 'status' => 'PROCESS'), array('oid' => $id, 'pay_status' => self::STATUS_PAY_PROCESS));
        return true;
    }

    public function pay_failed($id) {
        $this->db->update(TABLE_ORDER, array('pay_status' => self::STATUS_PAY_PREPARE, 'pay_time' => time()), array('oid' => $id, 'pay_status' => self::STATUS_PAY_PROCESS));
        return true;
    }

    public function shipping($id, $shipping_data) {
        $this->db->update(TABLE_ORDER, array(
            'shipping_status' => self::STATUS_SHIPPING_SUCCESS,
            'shipping_time' => time(),
            'shipping_company' => $shipping_data['shipping_company'],
            'shipping_sn' => $shipping_data['shipping_sn'],
            'status' => 'SHIPPING',
                ), array('oid' => $id, 'shipping_status' => self::STATUS_SHIPPING_PREPARE));
        return $this->db->affected_rows() > 0;
    }

    public function finish($id) {
        $this->db->update(TABLE_ORDER, array(
            'status' => 'FINISH',
            'finish_time' => time(),
                ), array('oid' => $id, 'status' => 'SHIPPING'));
        return $this->db->affected_rows() > 0;
    }

    public function close($id, $remark) {
        $update = array(
            'status' => 'CLOSE',
            'close_time' => time()
        );
        if (isset($remark['remark_sys'])) {
            $update['remark_sys'] = $remark['remark_sys'];
        }
        if (isset($remark['remark_to_user'])) {
            $update['remark_to_user'] = $remark['remark_to_user'];
        }
        $this->db->where_not_in('status', array('SUCCESS', 'CLOSE'));
        $this->db->update(TABLE_ORDER, $update, array('oid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function failed($id, $remark) {
        $update = array(
            'status' => 'FAILED',
            'close_time' => time()
        );
        if (isset($remark['remark_sys'])) {
            $update['remark_sys'] = $remark['remark_sys'];
        }
        if (isset($remark['remark_to_user'])) {
            $update['remark_to_user'] = $remark['remark_to_user'];
        }
        $this->db->where_not_in('status', array('SUCCESS', 'CLOSE'));
        $this->db->update(TABLE_ORDER, $update, array('oid' => $id));
        return $this->db->affected_rows() > 0;
    }
    
    public function get_user_cost($user_id) {
        $this->db->select('sum(price) as sum');
        $this->db->where_in('status',array('PROCESS','SHIPPING','FINISH'));
        return $this->db->get_where(TABLE_ORDER,array('user_id'=>intval($user_id)))->row(0)->sum;
    }

    public function get_order_statsu($key = false) {
        $data = array(
            'CREATE' => '已创建',
            'PROCESS' => '处理中',
            'SHIPPING' => '已发货',
            'FINISH' => '已完成',
            'CLOSE' => '已关闭',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'ORDER_ERROR', 'text' => '订单信息错误');
    }

    public function get_pay_statsu($key = false) {
        $data = array(
            1 => '已支付',
            2 => '未支付',
            3 => '支付中',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'ORDER_PAY_ERROR', 'text' => '订单支付信息错误');
    }

    public function get_shipping_status($key = false) {
        $data = array(
            1 => '已发货',
            2 => '未发货',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'ORDER_SHIPPING_ERROR', 'text' => '订单发货信息错误');
    }
    
    public function get_shipping_company_title($key) {
        $data = array(
            1 => '顺丰快递',
            2 => '申通快递',
            3 => '圆通快递',
            4 => '韵达快递',
            5 => '京东快递',
            6 => '苏宁快递',
            7 => '菜鸟联盟',
            8 => '中通快递',
            9 => '邮政EMS',
            10 => '天天快递',
            11 => '国美快递',
        );
        return isset($data[$key]) ? $data[$key] : '未知快递公司';
    }

}
