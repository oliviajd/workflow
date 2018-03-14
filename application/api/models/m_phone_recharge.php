<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_phone_recharge
 *
 * @author win7
 */
class m_phone_recharge extends CI_Model implements ObjInterface {

    const LIMIT_USER_MONTH = 300;
    const STATUS_RECHARGE_INIT = 5;
    const STATUS_RECHARGE_START = 6;
    const STATUS_RECHARGE_SUCCESS = 1;
    const STATUS_RECHARGE_FAILED = 2;
    const STATUS_RECHARGE_PROCESSING = 3;

    private $_memcache_store_prepare = 1000000;

    public function add($data) {
        $param['user_id'] = intval($data['user_id']);
        $param['oid'] = intval($data['oid']);
        $param['iid'] = intval($data['iid']);
        $param['order_sn'] = trim($data['order_sn']);
        $param['mobile'] = trim($data['mobile']);
        $param['face_price'] = intval($data['face_price']);
        $param['sale_price'] = intval($data['sale_price']);
        $param['pay_money'] = intval($data['pay_money']);
        $param['pay_credit'] = intval($data['pay_credit']);
        $param['status'] = self::STATUS_RECHARGE_INIT;
        $param['create_time'] = time();

        $this->db->insert(TABLE_QUEUE_PHONE_RECHARGE, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page = false, $size = false, $order = false) {
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_QUEUE_PHONE_RECHARGE)->result_array();

        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            $this->db->where('status', intval($condition['status']));
        }
    }

    public function recharge_start($id) {
        $this->db->update(TABLE_QUEUE_PHONE_RECHARGE, array('status' => self::STATUS_RECHARGE_START, 'modify_time' => time()), array('id' => $id, 'status' => self::STATUS_RECHARGE_INIT));
        return $this->db->affected_rows();
    }

    public function recharge_process($id, $result) {
        $this->db->update(TABLE_QUEUE_PHONE_RECHARGE, array(
            'status' => self::STATUS_RECHARGE_PROCESSING,
            'modify_time' => time(),
            'result_pay' => json_encode($result),
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_START));
        return $this->db->affected_rows();
    }

    public function recharge_finish($id, $result) {
        $this->db->update(TABLE_QUEUE_PHONE_RECHARGE, array(
            'status' => self::STATUS_RECHARGE_SUCCESS,
            'modify_time' => time(),
            'result_query' => json_encode($result),
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_PROCESSING));
        return $this->db->affected_rows();
    }

    public function recharge_failed($id, $result) {
        $this->db->update(TABLE_QUEUE_PHONE_RECHARGE, array(
            'status' => self::STATUS_RECHARGE_FAILED,
            'modify_time' => time(),
            'result_query' => json_encode($result),
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_PROCESSING));
        return $this->db->affected_rows();
    }

    /*
    //订单内充值卡是否已全部充值
    public function is_order_all_success($oid) {
        $this->db->select('count(1) as count');
        $this->db->where('oid', intval($oid));
        $this->db->where_not_in('status', array(self::STATUS_RECHARGE_SUCCESS));
        $count = $this->db->get_where(TABLE_QUEUE_PHONE_RECHARGE)->row(0)->count;
        return $count > 0 ? false : true;
    }

    //订单是否可以结束
    public function is_order_finish($oid) {
        $this->db->select('count(1) as count');
        $this->db->where('oid', intval($oid));
        $this->db->where_not_in('status', array(self::STATUS_RECHARGE_SUCCESS, self::STATUS_RECHARGE_FAILED));
        $count = $this->db->get_where(TABLE_QUEUE_PHONE_RECHARGE)->row(0)->count;
        return $count > 0 ? false : true;
    }

    //订单是否可以结束
    public function is_order_failed($oid) {
        $this->db->select('count(1) as count');
        $this->db->where('oid', intval($oid));
        $this->db->where_not_in('status', array(self::STATUS_RECHARGE_FAILED));
        $count = $this->db->get_where(TABLE_QUEUE_PHONE_RECHARGE)->row(0)->count;
        return $count > 0 ? false : true;
    }
     * 
     */
    public function order_status($oid) {
        $this->db->select('id,status');
        $this->db->where('oid', intval($oid));
        return $this->db->get_where(TABLE_QUEUE_PHONE_RECHARGE)->result_array();
    }

    //当月用户的充值额度
    public function get_user_month_limit($user_id) {
        $month_start = strtotime(date('Y-m-01 00:00:00'));
        $this->db->select('sum(face_price) as total');
        $this->db->where('create_at >= ' . $month_start, false, false);
        $this->db->where('create_at < ' . time(), false, false);
        return self::LIMIT_USER_MONTH - $this->db->get_where(TABLE_PHONE_RECHARGE_LOG, array('user_id' => intval($user_id), 'status' => 1))->row(0)->sum;
    }

    //增加用户的单月限额
    public function user_month_limit_increase($user_id, $num) {
        $this->load->library('cache_memcache');
        $key = 'USER_MONTH_LIMIT_' . $user_id;
        $store = $this->cache_memcache->get($key);
        //未发现记录，则同步库存到缓存
        if ($store === false) {
            $limit = $this->get_user_month_limit($user_id);
            if (intval($limit) <= 0) {
                return false;
            }
            $this->_set_memcache_user_month_limit($user_id, $limit, false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->increment($key, $num);
        if (!$r) {
            return false;
        }
        //todo 写入数据库
        return true;
    }

    //减少用户的当月限额
    public function user_month_limit_decrease($user_id, $num) {
        $this->load->library('cache_memcache');
        $key = 'USER_MONTH_LIMIT_' . $user_id;
        $store = $this->cache_memcache->get($key);
        //未发现记录，则同步库存到缓存
        if ($store === false) {
            $limit = $this->get_user_month_limit($user_id);
            if (intval($limit) <= 0) {
                return false;
            }
            $this->_set_memcache_user_month_limit($user_id, $limit, false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->decrement($key, $num);
        if (!$r) {
            return false;
        }
        if ($r >= $this->_memcache_store_prepare) {//减掉用户购买量后库存不小于预备量，则成功减库存
            //todo 写入数据库
            return true;
        } else {
            $this->cache_memcache->increment($key, $num); //将预减的库存数加回来
            return false;
        }
    }

    private function _set_memcache_user_month_limit($id, $value, $cover = true) {//$cover =true 默认使用set方法覆盖
        $this->load->library('cache_memcache');
        $key = 'USER_MONTH_LIMIT_' . $id;
        if ($cover) {
            return $this->cache_memcache->set($key, intval($value) + $this->_memcache_store_prepare, 0);
        } else {
            return $this->cache_memcache->add($key, intval($value) + $this->_memcache_store_prepare, 0);
        }
    }

    //array('user_id','order_sn', ...)
    public function recharge($data) {
        //暂使用现有接口
        if ($_SERVER['SERVER_NAME'] == 'api.car.com' || $_SERVER['SERVER_NAME'] == 'api_admin.car.com') {
            $host = 'www.car.com';
        } else if ($_SERVER['SERVER_NAME'] == 'api.ifcar99.com') {
            $host = 'https://www.ifcar99.com';
        } else if ($_SERVER['SERVER_NAME'] == 'apitest.ifcar99.com') {
            $host = 'http://test.ifcar99.com';
        } else {
            $host = '';
        }
        $aes = new aes();
        $aes->setKey('asdf(*U91@!#LMAS-a0sdfzsadf!L}{ppsak'); //和远程API中的保持一致
        $str = $aes->encode(json_encode($data));
        $api_url1 = $host . '/api.php?module=account_admin&action=Cost_llcz';
        $r = curl_upload($api_url1, array('str' => base64_encode($str)));
        $result = json_decode($r, true);
        if (!$result) {
            return array('status' => -3, 'msg' => $r);
        } else if ($result['status'] != 1) {
            return $result;
        }
        //todo 使用连连支付进行充值
        $api_url2 = $host . '/api/authllcz/llcz_charge_api3.php';
        $param['order_no'] = trim($data['order_sn']);
        $param['account_no'] = trim($data['mobile']);
        $param['user_id'] = intval($data['user_id']);
        $param['face_price'] = intval($data['face_price']);
        $param['sale_price'] = floatval($data['sale_price'] / 100);
        $param['time'] = time() * 1000;
        $r2 = curl_upload($api_url2, http_build_query(array('request' => $param)));
        $result2 = json_decode($r2, true);
        if (!$result2) {
            return array('status' => -3, 'msg' => $r2);
        } else {
            $result2['status'] = $result2['result'];
        }
        return $result2;
    }

    public function query($data) {
        //暂使用现有接口
        if ($_SERVER['SERVER_NAME'] == 'api.car.com' || $_SERVER['SERVER_NAME'] == 'api_admin.car.com') {
            $host = 'www.car.com';
        } else if ($_SERVER['SERVER_NAME'] == 'api.ifcar99.com') {
            $host = 'https://www.ifcar99.com';
        } else if ($_SERVER['SERVER_NAME'] == 'apitest.ifcar99.com') {
            $host = 'http://test.ifcar99.com';
        } else {
            $host = '';
        }
        $api_url2 = $host . '/api/authllcz/llcz_query_api3.php';
        $param['order_no'] = trim($data['order_sn']);
        $param['user_id'] = intval($data['user_id']);
        $r2 = curl_upload($api_url2, $param);
        $result2 = json_decode($r2, true);
        if (!$result2) {
            return array('status' => -3, 'msg' => $r2);
        } else {
            return $result2;
        }
    }

    public function join_recharge_queue($oid) {
        $this->load->model('m_order');
        $this->load->model('m_goods');
        $order = $this->m_order->detail($oid);
        if ($order->status->id != 'PROCESS') {
            return false;
        }
        $goods_num = 0;
        $card = array();
        foreach ($order->order_goods as $k => $v) {
            if ($v->category->id == 5) {//充值卡
                $goods_num += $v->num;
                $card[] = $v;
            }
        }
        if ($goods_num > 1) {
            $n = 1;
            foreach ($card as $k => $v) {
                $option = json_decode($v->option);
                for ($i = 0; $i < $v->num; $i++) {
                    $this->add(array(
                        'user_id' => $order->user->user_id,
                        'oid' => $order->oid,
                        'order_sn' => $order->order_sn . '-' . $n,
                        'iid' => $v->iid,
                        'mobile' => $order->user->loginname,
                        'face_price' => $option->face_price,
                        'sale_price' => 0,
                        'pay_money' => 0,
                        'pay_credit' => $v->price,
                    ));
                    $n++;
                }
            }
        } else {
            foreach ($card as $k => $v) {
                $option = json_decode($v->option);
                $this->add(array(
                    'user_id' => $order->user->user_id,
                    'oid' => $order->oid,
                    'order_sn' => $order->order_sn,
                    'iid' => $v->iid,
                    'mobile' => $order->user->loginname,
                    'face_price' => $option->face_price,
                    'sale_price' => 0,
                    'pay_money' => 0,
                    'pay_credit' => $v->price,
                ));
            }
        }
    }

    public function increase_query_times($id) {
        $this->db->set('total_query_times', 'total_query_times + 1', false);
        $this->db->update(TABLE_QUEUE_PHONE_RECHARGE, array(), array('id' => intval($id)));
        return $this->db->affected_rows();
    }

}
