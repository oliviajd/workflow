<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_borrow
 *
 * @author win7
 */
class m_borrow extends CI_Model implements ObjInterface {

    const STATUS_VERIFY_INIT = 6;
    const STATUS_VERIFY_ONLINE = 1;
    const STATUS_VERIFY_FULL = 3;
    const STATUS_VERIFY_FAILED = 4;
    //标的存管处理流程状态
    const STATUS_P_BA_INIT = 'BA_INIT';
    const STATUS_P_BA_PAY_START = 'BA_PAY_START';
    const STATUS_P_BA_PAY_SUBMIT = 'BA_PAY_SUBMIT';
    const STATUS_P_BA_PAY_PASS = 'BA_PAY_PASS';
    const STATUS_P_BA_PAY_SUCCESS = 'BA_PAY_SUCCESS';
    const STATUS_P_BA_PAY_FAILED = 'BA_PAY_FAILED';
    const STATUS_P_BA_REPAY_FREEZE_START = 'BA_REPAY_FREEZE_START';
    const STATUS_P_BA_REPAY_FREEZE_SUCCESS = 'BA_REPAY_FREEZE_SUCCESS';
    const STATUS_P_BA_REPAY_FREEZE_FAILED = 'BA_REPAY_FREEZE_FAILED';
    const STATUS_P_BA_REPAY_START = 'BA_REPAY_START';
    const STATUS_P_BA_REPAY_SUBMIT = 'BA_REPAY_SUBMIT';
    const STATUS_P_BA_REPAY_PASS = 'BA_REPAY_PASS';
    const STATUS_P_BA_REPAY_SUCCESS = 'BA_REPAY_SUCCESS';
    const STATUS_P_BA_REPAY_FAILED = 'BA_REPAY_FAILED';
    const STATUS_P_BA_END_START = 'BA_END_START';
    const STATUS_P_BA_END_SUBMIT = 'BA_END_SUBMIT';
    const STATUS_P_BA_END_PASS = 'BA_END_PASS';
    const STATUS_P_BA_END_SUCCESS = 'BA_END_SUCCESS';
    const STATUS_P_BA_END_FAILED = 'BA_END_FAILED';
    //标的平台处理流程状态
    const STATUS_P_FINISH = 'FINISH';
    const STATUS_P_INIT = 'INIT';
    const STATUS_P_PAY_START = 'PAY_START';
    const STATUS_P_PAY_SUCCESS = 'PAY_SUCCESS';
    const STATUS_P_PAY_FAILED = 'PAY_FAILED';
    const STATUS_P_REPAY_START = 'REPAY_START';
    const STATUS_P_REPAY_SUCCESS = 'REPAY_SUCCESS';
    const STATUS_P_REPAY_FAILED = 'REPAY_FAILED';

    private $_memcache_account_prepare = 10000000; //用memcache做项目金额数增减时的预备量，memcache 超减时会返回0，无法判断剩余可投金额是否足够

    public function add($data) {
        $id = $this->create_no(); //TODO ID 需要加锁处理
        $param['borrow_nid'] = $id;
        $param['status'] = self::STATUS_VERIFY_INIT;
        $param['verify_time'] = time();
        $param['addtime'] = time();

        $param['name'] = trim($data['title']);
        $param['account'] = floor(intval($data['money_total']) / 100) * 100; //100的倍数
        $param['borrow_apr'] = $data['rate'];
        $param['borrow_period'] = round($data['days'] / 30, 2);
        $param['borrow_style'] = intval($data['repay_way']);
        $param['pinggu'] = intval($data['guarantee']);
        $param['danbao'] = intval($data['assessment']);
        $param['xszx'] = intval($data['is_for_new_comer']) == 1 ? 1 : 0;
        $param['dan'] = intval($data['is_for_single']) == 1 ? 1 : 0;
        $param['borrow_valid_time'] = intval($data['expire']) / 3600 / 24;
        $param['borrow_type'] = $data['category'];
        $param['limit_on_time'] = intval($data['limit_on_time']);
        $param['borrow_end_time'] = intval($data['expire']) + max($param['limit_on_time'], time());

        $param['maincode'] = $data['cards'];
        $param['borrow_contents'] = $data['desc'];
        $param['userinfo'] = $data['user_desc'];
        $param['fengxian'] = $data['pay_from'];

        $param['pics'] = array(); //使用现有PICS字段，记录图片地址（？是否带路径）
        $pics = explode(',', $data['pic']);
        foreach ($pics as $k2 => $v2) {
            $param['pics'][] = trim(parse_url($v2, PHP_URL_PATH));
        }
        $param['pics'] = implode(',', $param['pics']);

        //其他参数
        $param['borrow_account_wait'] = $param['account'];
        //未知作用参数
        $param['open_account'] = 1;
        $param['open_borrow'] = 1;
        $param['open_credit'] = 1;
        $param['vouch_account'] = intval($data['repay_way']);
        $param['vouch_account_wait'] = intval($data['repay_way']);
        $param['isDXB'] = 0;
        //临时默认是这个id
        $param['ba_account_id'] = '6212461960000000317';
        $this->db->insert(TABLE_BORROW, $param);
        $auto_id = $this->db->insert_id();
        //上标时插入队列
        $this->db->insert(TABLE_QUEUE_BORROW, array('borrow_auto_id' => $auto_id, 'borrow_id' => $id, 'status' => self::STATUS_P_INIT, 'ba_status' => self::STATUS_P_BA_INIT));
        return $id;
    }

    public function update($id, $data) {
        if ($data['money_total']) {
            $this->_set_memcache_account($id, 0);
        }
        //标的金额不允许修改
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'title':
                    $param['name'] = trim($data['title']);
                    break;
                case 'rate':
                    $param['borrow_apr'] = trim($data['rate']);
                    break;
                case 'money_total':
                    $param['account'] = floor(intval($data['money_total']) / 100) * 100; //100的倍数
                    //需要更新wait字段
                    break;
                case 'days':
                    $param['borrow_period'] = round($data['days'] / 30, 2);
                    break;
//                case 'repay_way'://暂只有一种，不需要修改
//                    $param['borrow_style'] = intval($data['repay_way']);
//                    break;
//                case 'guarantee'://暂只有一种，不需要修改
//                    $param['pinggu'] = intval($data['guarantee']);
//                    break;
//                case 'assessment'://暂只有一种，不需要修改
//                    $param['danbao'] = intval($data['assessment']);
//                    break;
                case 'is_for_new_comer':
                    $param['xszx'] = intval($data['is_for_new_comer']);
                    break;
                case 'is_for_single':
                    $param['dan'] = intval($data['is_for_single']);
                    break;
                case 'expire':
                    $param['borrow_valid_time'] = intval($data['expire']) / 3600 / 24;
                    break;
                case 'category':
                    $param['borrow_type'] = intval($data['category']);
                    break;
                case 'pic':
                    $param['pics'] = array();
                    $pics = explode(',', $data['pic']);
                    foreach ($pics as $k2 => $v2) {
                        $param['pics'][] = trim(parse_url($v2, PHP_URL_PATH));
                    }
                    $param['pics'] = implode(',', $param['pics']);
                    break;
                case 'cards':
                    $param['maincode'] = trim($data['cards']);
                    break;
                case 'desc':
                    $param['borrow_contents'] = trim($data['desc']);
                    break;
                case 'user_desc':
                    $param['userinfo'] = trim($data['user_desc']);
                    break;
                case 'pay_from':
                    $param['fengxian'] = trim($data['pay_from']);
                    break;
                case 'limit_on_time':
                    $param['limit_on_time'] = intval($data['limit_on_time']);
                    break;
//                case 'status':
//                    if (in_array(intval($data['status']), array(self::STATUS_GOODS_INIT, self::STATUS_GOODS_OFF, self::STATUS_GOODS_ON))) {
//                        $param['status'] = intval($data['status']);
//                    }
//                    break;
                default:
                    break;
            }
        }
        $this->db->update(TABLE_BORROW, $param, array('borrow_nid' => $id));
        $r = $this->db->affected_rows() > 0;
        if ($data['money_total']) {
            $this->db->set('borrow_account_wait', 'account - borrow_account_yes', false); //修改标的金额的时候更新wait字段
            $this->db->update(TABLE_BORROW, array(), array('borrow_nid' => $id));
            //将可投设为正常值，开放投资
            $detail = $this->detail($id);
            $this->_set_memcache_account($id, $detail->money_unget);
        }
        return $r;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_borrow_status($detail['status']);
            $detail['category'] = $this->get_borrow_category($detail['borrow_type']);
            $detail['days'] = round($detail['borrow_period'] * 30);
            $detail['pic'] = explode(',', $detail['pics']);
            $detail['cards'] = explode(',', $detail['maincode']);
            
            $detail['list_type'] = 0;
            if($detail['queue_status'] == self::STATUS_P_INIT){
                $detail['list_type'] = 1;
            }
            if(in_array($detail['queue_ba_status'],array(self::STATUS_P_BA_PAY_START,self::STATUS_P_BA_PAY_SUBMIT,self::STATUS_P_BA_PAY_PASS))){
                $detail['list_type'] = 2;
            }
            if($detail['queue_status'] == self::STATUS_P_PAY_SUCCESS){
                $detail['list_type'] = 3;
            }
            if($detail['queue_status'] == self::STATUS_P_REPAY_SUCCESS){
                $detail['list_type'] = 4;
            }
            
            if ($detail['recordNo']){
                $this->load->config('myconfig');
                $ancun_nonce = time();
                $ancun = $this->config->item('51cunzheng');
                $signature = md5($ancun['key'].$ancun_nonce.$detail['recordNo']);
                
                $rows[$k]['ancun_url'] = $ancun['url']."/investment-detail?partnerKey=".$ancun['key']."&recordNo={$detail['recordNo']}&nonce={$ancun_nonce}&signature={$signature}";
            }else{
                $rows[$k]['ancun_url'] = "";
            }
            
            return new obj_borrow($detail);
        }
    }
    
    public function detail_admin($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_borrow_status($detail['status']);
            $detail['category'] = $this->get_borrow_category($detail['borrow_type']);
            $detail['days'] = round($detail['borrow_period'] * 30);
            $detail['pic'] = explode(',', $detail['pics']);
            $detail['cards'] = explode(',', $detail['maincode']);
            return new obj_borrow($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $this->db->select(TABLE_BORROW. ' .*,'.TABLE_QUEUE_BORROW.' .status as queue_status,'.TABLE_QUEUE_BORROW.' .ba_status as queue_ba_status');
        $this->db->join(TABLE_QUEUE_BORROW,TABLE_QUEUE_BORROW.'.borrow_id = '.TABLE_BORROW.'.borrow_nid');
        //$this->db->join(TABLE_BORROW_REPAY,TABLE_BORROW_REPAY.'.borrow_nid = '.TABLE_BORROW.'.borrow_nid');
        $rows = $this->db->get_where(TABLE_BORROW)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_borrow_status($v['status']);
            $rows[$k]['category'] = $this->get_borrow_category($v['borrow_type']);
            $rows[$k]['days'] = round($v['borrow_period'] * 30);
            $rows[$k]['pic'] = explode(',', $v['pics']);
            $rows[$k]['cards'] = explode(',', $v['maincode']);
            
            $rows[$k]['list_type'] = 0;
            if($v['queue_status'] == self::STATUS_P_INIT){
                $rows[$k]['list_type'] = 1;
            }
            if(in_array($v['queue_ba_status'],array(self::STATUS_P_BA_PAY_START,self::STATUS_P_BA_PAY_SUBMIT,self::STATUS_P_BA_PAY_PASS))){
                $rows[$k]['list_type'] = 2;
            }
            if($v['queue_status'] == self::STATUS_P_PAY_SUCCESS){
                $rows[$k]['list_type'] = 3;
            }
            if($v['queue_status'] == self::STATUS_P_REPAY_SUCCESS){
                $rows[$k]['list_type'] = 4;
            }
            
            if ($v['recordNo']){
                $this->load->config('myconfig');
                $ancun_nonce = time();
                $ancun = $this->config->item('51cunzheng');
                $signature = md5($ancun['key'].$ancun_nonce.$v['recordNo']);
                
                $rows[$k]['ancun_url'] = $ancun['url']."/investment-detail?partnerKey=".$ancun['key']."&recordNo={$v['recordNo']}&nonce={$ancun_nonce}&signature={$signature}";
            }else{
                $rows[$k]['ancun_url'] = "";
            }
        
            $rows[$k] = new obj_borrow($rows[$k]);
        }
        return $rows;
    }
    
    public function lists_admin($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BORROW)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_borrow_status($v['status']);
            $rows[$k]['category'] = $this->get_borrow_category($v['borrow_type']);
            $rows[$k]['days'] = round($v['borrow_period'] * 30);
            $rows[$k]['pic'] = explode(',', $v['pics']);
            $rows[$k]['cards'] = explode(',', $v['maincode']);
            $rows[$k] = new obj_borrow($rows[$k]);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        $this->db->join(TABLE_QUEUE_BORROW,TABLE_QUEUE_BORROW.'.borrow_id = '.TABLE_BORROW.'.borrow_nid');
        return $this->db->get_where(TABLE_BORROW)->row(0)->count;
    }
    
    public function count_admin($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BORROW)->row(0)->count;
    }

    public function sum_account($condition) {
        $this->db->select('sum(account) as sum');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BORROW)->row(0)->sum;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if (isset($condition['start_time'])) {
            $this->db->where(TABLE_BORROW.'.addtime >=' . intval($condition['start_time']), false, false);
        }
        if (isset($condition['end_time'])) {
            $this->db->where(TABLE_BORROW.'.addtime <=' . intval($condition['end_time']), false, false);
        }
        if (isset($condition['success_start_time'])) {
            $this->db->where(TABLE_BORROW.'.reverify_time >=' . intval($condition['success_start_time']), false, false);
        }
        if (isset($condition['success_end_time'])) {
            $this->db->where(TABLE_BORROW.'.reverify_time <=' . intval($condition['success_end_time']), false, false);
        }
        if (isset($condition['start_id'])) {
            $this->db->where(TABLE_BORROW.'.borrow_nid >=' . intval($condition['start_id']), false, false);
        }
        if (isset($condition['end_id'])) {
            $this->db->where(TABLE_BORROW.'.borrow_nid <=' . intval($condition['end_id']), false, false);
        }
        if (isset($condition['is_top'])) {
            $this->db->where(TABLE_BORROW.'.is_ding', $condition['is_top']);
        }
        if (isset($condition['borrow_type'])) {
            $this->db->where(TABLE_BORROW.'.borrow_type', $condition['borrow_type']);
        }
        if ($condition['q']) {
            $this->db->where('( '.TABLE_BORROW.'.id = \'' . intval($condition['q']) . '\' or '.TABLE_BORROW.'.name like \'%' . $this->db->escape_str($condition['q'], true) . '%\')', false, false);
        }
        if($condition['list_type']){
           //募资中
            if($condition['list_type'] == 1){    
                $this->db->where(TABLE_QUEUE_BORROW.'.status',self::STATUS_P_INIT);
            }
            //银行处理中
            if($condition['list_type'] == 2){
                $this->db->where_in(TABLE_QUEUE_BORROW.'.ba_status',array(self::STATUS_P_BA_PAY_START,self::STATUS_P_BA_PAY_SUBMIT,self::STATUS_P_BA_PAY_PASS));
            }
            //还款中
            if($condition['list_type'] == 3){
                $this->db->where(TABLE_QUEUE_BORROW.'.status',self::STATUS_P_PAY_SUCCESS);
            }
            //已结清
            if($condition['list_type'] == 4){
                $this->db->where(TABLE_QUEUE_BORROW.'.status',self::STATUS_P_REPAY_SUCCESS);
            } 
        }
        if (isset($condition['status'])) {
            //$this->db->where('status', $condition['status']);
            is_array($condition['status']) ? $this->db->where_in(TABLE_BORROW.'.status', $condition['status']) : $this->db->where(TABLE_BORROW.'.status', $condition['status']);
        }
        
    }

    public function _detail($id) {
        $this->db->select(TABLE_BORROW. ' .*,'.TABLE_QUEUE_BORROW.' .status as queue_status,'.TABLE_QUEUE_BORROW.' .ba_status as queue_ba_status');
        $this->db->join(TABLE_QUEUE_BORROW,TABLE_QUEUE_BORROW.'.borrow_id = '.TABLE_BORROW.'.borrow_nid');
        $detail = $this->db->get_where(TABLE_BORROW, array('borrow_nid' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    private function _detail_by_auto_id($auto_id) {
        $detail = $this->db->get_where(TABLE_BORROW, array('id' => $auto_id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function detail_by_auto_id($auto_id) {
        $detail = $this->_detail_by_auto_id($auto_id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_borrow_status($detail['status']);
            $detail['category'] = $this->get_borrow_category($detail['borrow_type']);
            $detail['days'] = round($detail['borrow_period'] * 30);
            $detail['pic'] = explode(',', $detail['pics']);
            $detail['cards'] = explode(',', $detail['maincode']);
            return new obj_borrow($detail);
        }
    }

    public function create_no() {
        $this->db->order_by('id desc');
        $this->db->limit(1);
        $r = $this->db->get_where(TABLE_BORROW)->row_array();
        if (empty($r)) {
            return date('Ym') . '00001';
        } else {
            if (intval(date('Ym') . '00001') > intval($r['borrow_nid'])) {
                return date('Ym') . '00001';
            } else {
                return bcadd($r['borrow_nid'], 1, 0);
            }
        }
    }

    public function tender_detail($id) {
        $detail = $this->_tender_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $this->load->model('m_user');
            $detail['status'] = $this->get_tender_status($detail['status']);
            $detail['user'] = $this->m_user->detail($detail['user_id']);
            return new obj_tender($detail);
        }
    }

    public function tender_add($data) {
        $param = array(
            'borrow_nid' => trim($data['borrow_id']),
            'user_id' => intval($data['user_id']),
            'account' => intval($data['money']),
            'account_tender' => intval($data['money']),
            'contents' => trim($data['remark']),
            'hbagmoney' => intval($data['bouns_money']),
            'source' => trim(strtolower($data['from'])),
        );
        $param['status'] = 9; //已受理状态，需要银行存管通过后变为修改为0
        $param['addtime'] = time();
        $param['addip'] = get_ip();
        $this->db->insert(TABLE_BORROW_TENDER, $param);
        $tender_id = $this->db->insert_id();
        $tender_sn = $this->_create_tender_sn($tender_id);
        $this->db->update(TABLE_BORROW_TENDER, array('nid' => $tender_sn), array('id' => $tender_id));
        //投资时插入队列
        $this->db->insert(TABLE_QUEUE_BORROW_TENDER, array(
            'user_id' => $param['user_id'],
            'borrow_id' => $param['borrow_nid'],
            'tender_id' => $tender_id,
            'create_time' => time(),
            'activity_status' => 2,
        ));
        return $tender_id;
    }

    public function tender_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_tender_condition($condition);
        $rows = $this->db->get_where(TABLE_BORROW_TENDER)->result_array();
        return $rows;
    }

    public function tender_count($condition) {
        $this->db->select('count(1) as count');
        $this->_tender_condition($condition);
        return $this->db->get_where(TABLE_BORROW_TENDER)->row(0)->count;
    }

    private function _tender_condition($condition) {
        if (isset($condition['borrow_id'])) {
            $this->db->where('borrow_nid', $condition['borrow_id']);
        }
        if (isset($condition['status'])) {
            $this->db->where('status', $condition['status']);
        }
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['start_time']) {
            $this->db->where('addtime >= ' . intval($condition['start_time']), false, false);
        }
        if ($condition['end_time']) {
            $this->db->where('addtime <= ' . intval($condition['end_time']), false, false);
        }
        if ($condition['q']) {
            $this->db->where('( id = \'' . intval($condition['q']) . '\' or `name` like \'%' . $this->db->escape_str($condition['q'], true) . '%\')', false, false);
        }
    }

    public function _tender_detail($id) {
        $detail = $this->db->get_where(TABLE_BORROW_TENDER, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    //增大并发数需要增加位数
    private function _create_tender_sn($tender_id) {
        $tender_sn_left = 'tender_' . date('ymd') . (time() % 86400);
        if ($tender_id > 1000) {
            //方法1,每秒不超过100w的订单数,
            $tender_sn_right = str_pad($tender_id % 1000000, 6, '0', STR_PAD_LEFT);
        } else {
            //方法2,每毫秒不超过1000个订单，修饰订单号
            $str = str_pad(microtime(1) * 1000 % 1000, 3, '0', STR_PAD_LEFT);
            $tender_sn_right = $str . str_pad($tender_id % 1000, 3, '0', STR_PAD_LEFT);
        }
        return $tender_sn_left . $tender_sn_right;
    }

    public function has_tendered($user_id) {
        $this->db->limit(1);
        $this->db->where('status <> 3', false, false);
        $r = $this->db->get_where(TABLE_BORROW_TENDER, array('user_id' => intval($user_id)))->row(0);
        return empty($r) ? false : true;
    }

    public function get_tender_status($key = false) {
        $data = array(
            0 => '未计息',
            1 => '已计息',
            3 => '失败',
            9 => '已受理', //9>0|3>1
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'TENDER_ERROR', 'text' => '投资记录状态错误');
    }

    public function get_borrow_status($key = false) {
        $data = array(
            0 => '未审核',
            self::STATUS_VERIFY_ONLINE => '已上线',
            self::STATUS_VERIFY_FULL => '满标成功',
            self::STATUS_VERIFY_FAILED => '满标失败',
            self::STATUS_VERIFY_INIT => '未上线',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'BORROW_ERROR', 'text' => '标的状态错误');
    }

    public function get_borrow_category($key = false) {
        $data = array(
            6 => '聚车贷',
            7 => '新车汇',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'BORROW_ERROR', 'text' => '标的类别错误');
    }

    public function use_coupon($id, $coupon) {
        $tender = $this->_tender_detail($id);
        $borrow = $this->_detail($tender['borrow_nid']);
        $this->db->update(TABLE_BORROW_TENDER, array(
            'coupon_user_use_id' => $coupon['coupon_user_use_id'],
            'coupon_rate' => $coupon['coupon_rate'],
            'coupon_use_status' => 1,
            'coupon_amount' => round(intval($tender['account']) * $coupon['coupon_rate'] / 100 / 100 * 100 * round($borrow['borrow_period'] * 30) / 360),
                ), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function use_bouns() {
        
    }

    public function check_bouns_money($money, $bouns_money) {
        if (intval($bouns_money) < 20) {
            return $money * 0.01 >= intval($bouns_money);
        } else {
            return $money * 0.005 >= intval($bouns_money);
        }
    }

    public function max_bouns_money($money) {
        if (intval($money) < 2000) {
            return intval(floor($money * 0.01));
        } else {
            return min(intval(floor($money * 0.005)), 200);
        }
    }

    public function decrease($id, $num) {
        $this->load->library('cache_memcache');
        $key = 'BORROW_ACCOUNT_' . $id;
        $store = $this->cache_memcache->get($key);
        //未发现记录，则同步库存到缓存
        if ($store === false) {
            $detail = $this->_detail($id);
            if (intval($detail['borrow_account_wait']) <= 0) {
                return false;
            }
            $this->_set_memcache_account($id, $detail['borrow_account_wait'], false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->decrement($key, $num);
        if (!$r) {//超减，重新赋值
            $detail = $this->_detail($id);
            if (intval($detail['borrow_account_wait']) <= 0) {
                return false;
            }
            $this->cache_memcache->increment($key, $detail['borrow_account_wait'] + $this->_memcache_account_prepare);
            return false;
        }
        if ($r >= $this->_memcache_account_prepare) {//减掉用户投资金额后可投金额不小于预备量，则成功减可投金额
            $this->db->set('borrow_account_wait', 'borrow_account_wait - ' . intval($num), false);
            $this->db->set('borrow_account_yes', 'borrow_account_yes + ' . intval($num), false);
            $this->db->set('borrow_account_scale', 'floor((borrow_account_yes/account)*100)', false);
            $this->db->set('tender_times', 'tender_times+1', false);
            $this->db->update(TABLE_BORROW, array(), array('borrow_nid' => $id));
            return true;
        } else {
            $this->cache_memcache->increment($key, $num); //将预减的库存数加回来
            return false;
        }
    }

    public function increase($id, $num) {
        $this->load->library('cache_memcache');
        $key = 'BORROW_ACCOUNT_' . $id;
        $store = $this->cache_memcache->get($key);
        //未发现记录，则同步库存到缓存
        if ($store === false) {
            $detail = $this->_detail($id);
            $this->_set_memcache_account($id, $detail['borrow_account_wait'], false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->increment($key, $num);
        if (!$r) {
            //todo 非正常情况，根据实际情况处理
            return false;
        }
        $this->db->set('borrow_account_wait', 'borrow_account_wait + ' . intval($num), false);
        $this->db->set('borrow_account_yes', 'borrow_account_yes - ' . intval($num), false);
        $this->db->set('borrow_account_scale', 'floor((borrow_account_yes/account)*100)', false);
        $this->db->set('tender_times', 'tender_times-1', false);
        $this->db->update(TABLE_BORROW, array(), array('borrow_nid' => $id));
        return true;
    }

    public function _set_memcache_account($id, $value, $cover = true) {//$cover =true 默认使用set方法覆盖
        $this->load->library('cache_memcache');
        $key = 'BORROW_ACCOUNT_' . $id;
        if ($cover) {
            return $this->cache_memcache->set($key, intval($value) + $this->_memcache_account_prepare, 0);
        } else {
            return $this->cache_memcache->add($key, intval($value) + $this->_memcache_account_prepare, 0);
        }
    }

    public function _get_memcache_account($id) {
        $this->load->library('cache_memcache');
        $key = 'BORROW_ACCOUNT_' . $id;
        $store = $this->cache_memcache->get($key);
        return $store === false ? false : $store - $this->_memcache_account_prepare;
    }

    public function dorec($id) {
        $this->db->update(TABLE_BORROW, array('recommend' => 1), array('borrow_nid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function unrec($id) {
        $this->db->update(TABLE_BORROW, array('recommend' => 0), array('borrow_nid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function dotop($id) {
        $this->db->update(TABLE_BORROW, array('is_ding' => 1), array('borrow_nid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function untop($id) {
        $this->db->update(TABLE_BORROW, array('is_ding' => 0), array('borrow_nid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function on($id) {
        $this->db->update(TABLE_BORROW, array('status' => self::STATUS_VERIFY_ONLINE), array('borrow_nid' => $id, 'status' => self::STATUS_VERIFY_INIT));
        return $this->db->affected_rows() > 0;
    }

    public function off($id) {
        $this->db->update(TABLE_BORROW, array('status' => self::STATUS_VERIFY_INIT), array('borrow_nid' => $id, 'status' => self::STATUS_VERIFY_ONLINE));
        return $this->db->affected_rows() > 0;
    }

    public function find_by_title($title) {
        if (!trim($title)) {
            return false;
        }
        $detail = $this->db->get_where(TABLE_BORROW, array('name' => trim($title)))->row_array(0);
        if (!empty($detail)) {
            $detail['status'] = $this->get_borrow_status($detail['status']);
            $detail['category'] = $this->get_borrow_category($detail['borrow_type']);
            $detail['days'] = round($detail['borrow_period'] * 30);
            $detail['pic'] = explode(',', $detail['pics']);
            $detail['cards'] = explode(',', $detail['maincode']);
            return new obj_borrow($detail);
        } else {
            return false;
        }
    }

    public function get_item_for_new($condition, $order, $limit) {
        if ($order) {
            $this->db->order_by($order);
        }
        if ($limit) {
            $this->db->limit($limit);
        }
        $this->db->where('xszx', 1);
        $this->db->where_in('status', array(self::STATUS_VERIFY_ONLINE, self::STATUS_VERIFY_FULL));
        $rows = $this->db->get_where(TABLE_BORROW)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_borrow_status($v['status']);
            $rows[$k] = new obj_borrow($rows[$k]);
        }
        return $rows;
    }

    public function ba_create_id($borrow) {
        $this->db->insert(TABLE_BA_BORROW_ID, array(
            'borrow_id' => $borrow->borrow_id,
            'borrow_title' => $borrow->title,
            'create_time' => time(),
            'has_cancelled' => 2,
        ));
        return $this->db->insert_id();
    }

    public function ba_on($borrow_id, $ba_id) {
        $this->db->update(TABLE_BORROW, array('ba_status' => 1, 'ba_modify_time' => time(), 'ba_id' => $ba_id), array('borrow_nid' => $borrow_id, 'ba_status' => 2));
        return $this->db->affected_rows() > 0;
    }

    public function ba_off($borrow_id, $ba_id) {
        $this->db->update(TABLE_BORROW, array('ba_status' => 2, 'ba_modify_time' => time(), 'ba_id' => 0), array('borrow_nid' => $borrow_id, 'ba_status' => 1));
        $this->db->update(TABLE_BA_BORROW_ID, array('has_cancelled' => 1,), array('id' => $ba_id));
        return $this->db->affected_rows() > 0;
    }

    public function ba_result($ba_id, $result) {
        $this->db->update(TABLE_BA_BORROW_ID, array('result' => json_encode($result)), array('id' => $ba_id));
        return $this->db->affected_rows() > 0;
    }

    public function ba_id($borrow_id) {
        $this->db->order_by('id desc');
        $this->db->limit(1);
        return $ba_borrow = $this->db->get_where(TABLE_BA_BORROW_ID, array('borrow_id' => $borrow_id, 'has_cancelled' => 2))->row(0)->id;
    }
    
    public function get_list_type_count($list_type) {
        $condition['list_type'] = $list_type;
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        $r = $this->db->get_where(TABLE_QUEUE_BORROW)->row(0)->count;
        return $r;
    }

}
