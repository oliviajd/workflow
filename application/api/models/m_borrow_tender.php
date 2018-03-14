<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_borrow_tender
 *
 * @author lsk
 */
class m_borrow_tender extends CI_Model implements ObjInterface{
    
    const STATUS_P_BA_INIT = 2;
    const STATUS_P_BA_START = 3;
    const STATUS_P_BA_SUCCESS = 1;
    const STATUS_P_BA_FAILED = 4;
    
    public function add($data) {}
    
    public function update($id, $data) {}
    
    public function detail($id) {
        $detail = $this->_detail($id);
        if(empty($detail)){
            return false;
        }else{
            $this->db->select('account,borrow_period,borrow_apr,borrow_style,name,reverify_time');
            $borrow = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $detail['borrow_nid']))->row_array(0);
            $detail['borrow_name'] = $borrow['name'];
            $detail['reverify_time'] = $borrow['reverify_time'];
            //满标之前计算预期利息
            if($detail['recover_account_interest'] == '0.00'){
                require_once APPPATH . 'libraries/equal_interest.php';
                $_equal = array(
                    'account' => $detail['account'],
                    'period' => $borrow['borrow_period'],
                    'apr' => $borrow['borrow_apr'],
                    'style' => $borrow['borrow_style'],
                    'type' => 'all'
                );
                $equal_result = EqualInterest($_equal);
                $detail['recover_account_interest'] = sprintf("%.2f", $equal_result['interest_total']);
            }
            //预期回款时间
            $detail['recover_time'] = $this->db->get_where(TABLE_BORROW_RECOVER,array('tender_id' => $detail['id']))->row(0)->recover_time;
            
        }
        return new obj_tender($detail);
    }
    
    public function lists($condition='', $page='', $size='', $order='') {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BORROW_TENDER)->result_array();
        foreach ($rows as $k => $v) {
            //满标之前计算预期利息
            if($v['recover_account_interest'] == '0.00'){
                require_once APPPATH . 'libraries/equal_interest.php';
                $this->db->select('account,borrow_period,borrow_apr,borrow_style');
                $borrow = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $v['borrow_nid']))->row_array(0);
                $_equal = array(
                    'account' => $v['account'],
                    'period' => $borrow['borrow_period'],
                    'apr' => $borrow['borrow_apr'],
                    'style' => $borrow['borrow_style'],
                    'type' => 'all'
                );
                $equal_result = EqualInterest($_equal);
                $rows[$k]['recover_account_interest'] = sprintf("%.2f", $equal_result['interest_total']);
            }
            $rows[$k]['recover_time'] = $this->db->get_where(TABLE_BORROW_RECOVER,array('tender_id' => $v['id']))->row(0)->recover_time;
            $rows[$k]['borrow_name'] = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $v['borrow_nid']))->row(0)->name;
        }
        
        return $rows;
    }
    
    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BORROW_TENDER)->row(0)->count;
    }
    
    public function delete($condition) {}
    
    private function _condition($condition) {
        if ($condition['borrow_id']) {
            is_array($condition['borrow_id']) ? $this->db->where_in('borrow_nid', $condition['borrow_id']) : $this->db->where('borrow_nid', $condition['borrow_id']);
        }
        if ($condition['before_addtime']) {
            $this->db->where('addtime <', $condition['before_addtime']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in(TABLE_BORROW_TENDER.'.user_id', $condition['user_id']) : $this->db->where(TABLE_BORROW_TENDER.'.user_id', $condition['user_id']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in(TABLE_BORROW_TENDER.'.status', $condition['status']) : $this->db->where(TABLE_BORROW_TENDER.'.status', $condition['status']);
        }
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_BORROW_TENDER,array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }
    
    public function get($condition,$order) {
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $this->db->limit(1);
        $r = $this->db->get_where(TABLE_BORROW_TENDER)->row_array();
        $r['reverify_bank_time'] = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $r['borrow_nid']))->row(0)->reverify_bank_time;
        return $r;
    }
    
    public function user_lists($condition='', $page='', $size='', $order='') {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $this->db->join(TABLE_USER,  TABLE_BORROW_TENDER. '.user_id = ' . TABLE_USER . '.user_id', 'LEFT');
        $rows = $this->db->get_where(TABLE_BORROW_TENDER)->result_array();
        
        return $rows;
    }
    
    public function user_count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        $this->db->join(TABLE_USER,  TABLE_BORROW_TENDER. '.user_id = ' . TABLE_USER . '.user_id', 'LEFT');
        return $this->db->get_where(TABLE_BORROW_TENDER)->row(0)->count;
    }
    
    public function tender_lists($condition) {
        $this->db->order_by('money desc');
        $this->db->select(TABLE_BORROW_TENDER.'.user_id,'.TABLE_USER.'.mobile,'.TABLE_USER_REALNAME.'.realname,SUM('.TABLE_BORROW_TENDER.'.account) as money');
        if ($condition['starttime']) {
            $this->db->where(TABLE_BORROW_TENDER.'.addtime >=', $condition['starttime']);
        }
        if ($condition['endtime']) {
            $this->db->where(TABLE_BORROW_TENDER.'.addtime <', $condition['endtime']);
        }
        $this->db->where(TABLE_BORROW . '.borrow_period', 1);
        $this->db->join(TABLE_USER,TABLE_BORROW_TENDER.'.user_id = '.TABLE_USER.'.user_id','left');
        $this->db->join(TABLE_USER_REALNAME,TABLE_BORROW_TENDER.'.user_id = '.TABLE_USER_REALNAME.'.user_id','left');
        $this->db->join(TABLE_BORROW, TABLE_BORROW_TENDER . '.borrow_nid = ' . TABLE_BORROW . '.borrow_nid', 'inner');
        $this->db->group_by(TABLE_BORROW_TENDER.'.user_id');
        $rows = $this->db->get(TABLE_BORROW_TENDER)->result_array();
        return empty($rows[0]['user_id']) ? false : $rows;
    }
    
    public function total_tender($condition) {
        //$this->db->order_by('money desc');
        $this->db->select(TABLE_BORROW_TENDER . '.user_id,' . TABLE_USER . '.mobile,' . TABLE_USER_REALNAME . '.realname,SUM(' . TABLE_BORROW_TENDER . '.account) as money');
        if ($condition['starttime']) {
            $this->db->where(TABLE_BORROW_TENDER . '.addtime >=', $condition['starttime']);
        }
        if ($condition['endtime']) {
            $this->db->where(TABLE_BORROW_TENDER . '.addtime <', $condition['endtime']);
        }
        if ($condition['user_id']) {
            $this->db->where(TABLE_BORROW_TENDER . '.user_id', $condition['user_id']);
        }
        $this->db->where(TABLE_BORROW . '.borrow_period', 1);
        $this->db->join(TABLE_USER, TABLE_BORROW_TENDER . '.user_id = ' . TABLE_USER . '.user_id', 'left');
        $this->db->join(TABLE_USER_REALNAME, TABLE_BORROW_TENDER . '.user_id = ' . TABLE_USER_REALNAME . '.user_id', 'left');
        $this->db->join(TABLE_BORROW, TABLE_BORROW_TENDER . '.borrow_nid = ' . TABLE_BORROW . '.borrow_nid', 'inner');
        //$this->db->group_by(TABLE_BORROW_TENDER.'.user_id');
        $rows = $this->db->get(TABLE_BORROW_TENDER)->result_array();
        return empty($rows[0]['user_id']) ? false : $rows;
    }
    
    public function lastest_repay($user_id) {
        //$this->db->order_by('money desc');
        $this->db->where('user_id' , $user_id);
        $this->db->where('recover_time > ' . time(), false, false);
        $this->db->order_by('recover_time desc');
        $rows = $this->db->get_where(TABLE_BORROW_RECOVER, array('recover_status' => 0))->result_array(); //查找待回款记录
        
        $this->db->where('user_id' , $user_id);
        $this->db->select('sum(recover_account) as sum_account');
        $this->db->where('recover_time > ' . time(), false, false);
        $sum_account = $this->db->get_where(TABLE_BORROW_RECOVER, array('recover_status' => 0))->row(0)->sum_account; //查找待回款记录总金额
        
        $this->db->where('user_id' , $user_id);
        $this->db->select('sum(recover_capital) as sum_capital');
        $this->db->where('recover_time > ' . time(), false, false);
        $sum_capital = $this->db->get_where(TABLE_BORROW_RECOVER, array('recover_status' => 0))->row(0)->sum_capital; //查找待回款记录总本金
        
        $this->db->where('user_id' , $user_id);
        $this->db->select('sum(recover_interest) as sum_interest');
        $this->db->where('recover_time > ' . time(), false, false);
        $sum_interest = $this->db->get_where(TABLE_BORROW_RECOVER, array('recover_status' => 0))->row(0)->sum_interest; //查找待回款记录总利息
        
        $this->db->where('user_id' , $user_id);
        $this->db->select('count(1) as count');
        $this->db->where('recover_time > ' . time(), false, false);
        $count = $this->db->get_where(TABLE_BORROW_RECOVER, array('recover_status' => 0))->row(0)->count; //查找待回款记录数量
        
        $result['rows'] = $rows;
        $result['count'] = $count;
        $result['sum_account'] = $sum_account;
        $result['sum_capital'] = $sum_capital;
        $result['sum_interest'] = $sum_interest;
        
        //$rows = $this->db->get(TABLE_BORROW_TENDER)->result_array();
        return $result;
    }
    
}
