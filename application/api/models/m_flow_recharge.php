<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_flow_recharge
 *
 * @author win7
 */
class m_flow_recharge extends CI_Model implements ObjInterface {

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
        $param['mobiles'] = trim($data['mobiles']);
        $param['cmcc'] = trim($data['cmcc']);
        $param['cucc'] = trim($data['cucc']);
        $param['ctcc'] = trim($data['ctcc']);
        $param['etype'] = intval($data['etype']);
        $param['version'] = intval($data['version']);
        $param['status'] = self::STATUS_RECHARGE_INIT;
        $param['create_time'] = time();

        $this->db->insert(TABLE_QUEUE_FLOW_RECHARGE, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'status':
                    if (in_array(intval($data['status']), array(self::STATUS_RECHARGE_INIT,self::STATUS_RECHARGE_START,self::STATUS_RECHARGE_SUCCESS, self::STATUS_RECHARGE_FAILED, self::STATUS_RECHARGE_PROCESSING))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                case 'result_pay':
                    $param['result_pay'] = trim($data['result_pay']);
                    break;
                case 'order_sn':
                    $param['order_sn'] = trim($data['order_sn']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
    }

    public function lists($condition, $page = false, $size = false, $order = false) {
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_QUEUE_FLOW_RECHARGE)->result_array();

        return $rows;
    }

    public function count($condition) {
        $this->_condition($condition);
        return $this->db->count_all_results(TABLE_QUEUE_FLOW_RECHARGE);
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            $this->db->where('status', intval($condition['status']));
        }
        if ($condition['batchNo']) {
            $this->db->where('batchNo', intval($condition['batchNo']));
        }
    }

    public function recharge_start($id) {
        $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array('status' => self::STATUS_RECHARGE_START, 'modify_time' => time()), array('id' => $id, 'status' => self::STATUS_RECHARGE_INIT));
        return $this->db->affected_rows();
    }

    public function recharge_process($id, $result) {
        $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array(
            'status' => self::STATUS_RECHARGE_PROCESSING,
            'modify_time' => time(),
            'batchNo' => $result['batchNo'],
            'result_pay' => json_encode($result),
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_START));
        return $this->db->affected_rows();
    }

    public function recharge_finish($id, $result) {
        $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array(
            'status' => self::STATUS_RECHARGE_SUCCESS,
            'modify_time' => time(),
            'batchNo' => $result['batchNo'],
            'result_pay' => json_encode($result),
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_PROCESSING));
        return $this->db->affected_rows();
    }

    public function recharge_failed($id, $result) {
        $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array(
            'status' => self::STATUS_RECHARGE_FAILED,
            'modify_time' => time(),
            'batchNo' => $result['batchNo'],
            'result_pay' => json_encode($result),
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_PROCESSING));
        return $this->db->affected_rows();
    }

    /*
    //订单内充值卡是否已全部充值
    public function is_order_all_success($oid) {
        $this->db->select('count(1) as count');
        $this->db->where('oid', intval($oid));
        $this->db->where_not_in('status', array(self::STATUS_RECHARGE_SUCCESS));
        $count = $this->db->get_where(TABLE_QUEUE_FLOW_RECHARGE)->row(0)->count;
        return $count > 0 ? false : true;
    }

    //订单是否可以结束
    public function is_order_finish($oid) {
        $this->db->select('count(1) as count');
        $this->db->where('oid', intval($oid));
        $this->db->where_not_in('status', array(self::STATUS_RECHARGE_SUCCESS, self::STATUS_RECHARGE_FAILED));
        $count = $this->db->get_where(TABLE_QUEUE_FLOW_RECHARGE)->row(0)->count;
        return $count > 0 ? false : true;
    }

    //订单是否可以结束
    public function is_order_failed($oid) {
        $this->db->select('count(1) as count');
        $this->db->where('oid', intval($oid));
        $this->db->where_not_in('status', array(self::STATUS_RECHARGE_FAILED));
        $count = $this->db->get_where(TABLE_QUEUE_FLOW_RECHARGE)->row(0)->count;
        return $count > 0 ? false : true;
    }
     * 
     */
    public function order_status($oid) {
        $this->db->select('id,status');
        $this->db->where('oid', intval($oid));
        return $this->db->get_where(TABLE_QUEUE_FLOW_RECHARGE)->result_array();
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
        $this->load->config('myconfig');
        $config = $this->config->item('emay');
        $gwUrl = $config['url'];
        //TOKEN用于加密，客户登录流量平台-个人设置-个人信息 密钥，点击发送至邮箱可以得到TOKEN
        $sTOKEN = $config['token'];
        //APPID用于识别用户，客户使用登录号和密码登录流量平台-个人设置-个人信息 可以看到接口所需的APPID
        $appID = $config['appId'];
        $version = trim($data['version']); //加密版本必须传2
        
        //加密顺序，taskNo+CTCC+CUCC+CMCC
        $param['mobiles'] = trim($data['mobiles']);
        $param['taskNo'] = trim($data['order_sn']); //自定义唯一值，必须传唯一
        if(!empty($data['ctcc'])){   //移动套餐编号
            $param['ctcc'] = trim($data['ctcc']);
        }
        if(!empty($data['cucc'])){
            $param['cucc'] = trim($data['cucc']);
        }
        if(!empty($data['cmcc'])){
            $param['cmcc'] = trim($data['cmcc']);
        }
        
        $param['etype'] = trim($data['etype']); //生效类型，0:立即生效，　1:下月生效
        
        
        $query = http_build_query($param);
        
        $ID_key = str_replace("%2C",",",$query);
        
        $ID_value = str_replace("%2C",",",$query);
        
        //echo $ID_key.'------';
        
        $md5_key = md5($ID_key);      //MD5加密
        
        $base64_val = $this->encrypt($ID_value, $sTOKEN);  //AES,转16进制
        $base64_val =urlencode($base64_val);
        $postStr1 = 'key='.$md5_key.'&value='.$base64_val.'&appId='.$appID.'&version='.$version;   //POST的内容
        
        //echo $postStr1.'------';
        return json_decode(curl_upload($gwUrl, $postStr1),true);
        exit;
        //echo post($gwUrl, $postStr1);
        
        //暂使用现有接口
        /*$aes = new aes();
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
        return $result2;*/
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
        $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array(), array('id' => intval($id)));
        return $this->db->affected_rows();
    }
    
    public function encrypt($input, $key) {
	$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
	$input = $this->pkcs5_pad($input, $size);
	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$data = mcrypt_generic($td, $input);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	//$data = base64_encode($data);
	$data=bin2hex($data);
	return $data;
    }
    
    private function pkcs5_pad ($text, $blocksize) {
            $pad = $blocksize - (strlen($text) % $blocksize);
            return $text . str_repeat(chr($pad), $pad);
    }

    public function decrypt($sStr, $sKey) {
            $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB
    );

            $dec_s = strlen($decrypted);
            $padding = ord($decrypted[$dec_s-1]);
            $decrypted = substr($decrypted, 0, -$padding);
            return $decrypted;
    }
    
    public function callback() {
        $data_json = $this->api->in['data'];
        $data = json_decode($data_json,true);
        //var_dump($data);
        if($data['batchNo'] && $data['successCount'] > 0 && $data['failCount'] == 0){   //充值成功
            //$this->db->where('batchNo',$data['batchNo']);
            //$r = $this->db->get(TABLE_QUEUE_FLOW_RECHARGE)->result_array(0);
            
            $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array(
                'status' => self::STATUS_RECHARGE_SUCCESS,
                'modify_time' => time(),
                'result_query' => $data_json,
                    ), array('status' => self::STATUS_RECHARGE_PROCESSING, 'batchNo' => $data['batchNo']));
            if($this->db->affected_rows()){
                echo 'SUCCESS';
            }
            
            //return $this->db->affected_rows();
        }
        elseif($data['batchNo'] && $data['successCount'] == 0 && $data['failCount'] > 0){   //充值失败
            //$this->db->where('batchNo',$data['batchNo']);
            //$r = $this->db->get(TABLE_QUEUE_FLOW_RECHARGE)->result_array(0);
            
            $this->db->update(TABLE_QUEUE_FLOW_RECHARGE, array(
                'status' => self::STATUS_RECHARGE_FAILED,
                'modify_time' => time(),
                'result_query' => $data_json,
                    ), array('status' => self::STATUS_RECHARGE_PROCESSING, 'batchNo' => $data['batchNo']));
            
            if($this->db->affected_rows()){
                echo 'SUCCESS';
            }
            //return $this->db->affected_rows();
        }
        exit;
    }
    
    public function sendcallback() {
        $this->load->config('myconfig');
        $config = $this->config->item('emay');
        $url = 'http://apitest.ifcar99.com/api.php/flow_recharge/callback';
        //$url = 'http://www.baidu.com/';
        $param = array();
        $param['batchNo'] = '189798';  //批次号 
        $param['successCount'] = 1; //成功数量 
        $param['failCount'] = 0;    //失败数量 
        $param['errorlist'] = array(
            array('mobile' => '15658008231','code' => 'N0002','message' => '运营商异常')
            );
        $param_json = json_encode($param);
        //echo $param_json.'------';
        echo curl_upload($url, array('data'=>$param_json));
        exit;
        //echo post($gwUrl, $postStr1);
    }
    
    

}
