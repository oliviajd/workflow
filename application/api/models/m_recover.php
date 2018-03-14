<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_recover
 *
 * @author lsk
 */
class m_recover extends CI_Model implements ObjInterface{
    
    public function add($data) {}
    
    public function update($id, $data) {}
    
    public function detail($id) {
        $detail = $this->_detail($id);
        if(empty($detail)){
            return false;
        }else{
            /*$this->db->select('name,reverify_time,reverify_bank_time');
            $borrow = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $detail['borrow_nid']))->row_array(0);
            $detail['borrow_name'] = $borrow['name'];
            $detail['reverify_time'] = $borrow['reverify_time'];
            $detail['reverify_bank_time'] = $borrow['reverify_bank_time'];*/
            
            $detail['coupon_amount'] = $this->db->get_where(TABLE_BORROW_TENDER,array('id' => $detail['tender_id']))->row(0)->coupon_amount;
        }
        return new obj_recover($detail);
    }
    
    public function lists($condition='', $page='', $size='', $order='') {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BORROW_RECOVER)->result_array();
        foreach ($rows as $k => $v) {
            /*$this->db->select('name,reverify_time,reverify_bank_time');
            $borrow = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $v['borrow_nid']))->row_array(0);
            $rows[$k]['borrow_name'] = $borrow['name'];
            $rows[$k]['reverify_time'] = $borrow['reverify_time'];
            $rows[$k]['reverify_bank_time'] = $borrow['reverify_bank_time'];*/
            
            $coupon_amount = $this->db->get_where(TABLE_BORROW_TENDER,array('id' => $v['tender_id']))->row(0)->coupon_amount;
            
            $rows[$k]['coupon_amount'] = $this->db->get_where(TABLE_BORROW_TENDER,array('id' => $v['tender_id']))->row(0)->coupon_amount;
            $rows[$k]['overdue'] = $this->db->get_where(TABLE_BORROW,array('borrow_nid' => $v['borrow_nid']))->row(0)->overdue;
        }
        
        return $rows;
    }
    
    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BORROW_RECOVER)->row(0)->count;
    }
    
    public function sum($condition) {
        $this->db->select('sum(recover_account) as account_sum,sum(recover_interest) as interest_sum,sum(coupon_amount) as coupon_sum');
        $this->db->join(TABLE_BORROW_TENDER, TABLE_BORROW_TENDER.'.id = '.TABLE_BORROW_RECOVER.'.tender_id');
        if (isset($condition['recover_status'])) {
            is_array($condition['recover_status']) ? $this->db->where_in('recover_status', $condition['recover_status']) : $this->db->where('recover_status', $condition['recover_status']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in(TABLE_BORROW_RECOVER.'.user_id', $condition['user_id']) : $this->db->where(TABLE_BORROW_RECOVER.'.user_id', $condition['user_id']);
        }
        $r = $this->db->get_where(TABLE_BORROW_RECOVER)->row_array(0);
        return $r;
    }
    
    public function range_sum($condition) {
        $this->db->select('sum(recover_account) as account_sum ,sum(recover_interest) as interest_sum , sum('.TABLE_BORROW_RECOVER.'.recover_account_yes) as yes_account_sum,sum('.TABLE_BORROW_RECOVER.'.recover_interest_yes) as yes_interest_sum,sum(coupon_amount) as coupon_sum');
        $this->db->join(TABLE_BORROW_TENDER, TABLE_BORROW_TENDER.'.id = '.TABLE_BORROW_RECOVER.'.tender_id');
        $this->_condition($condition);
        $r_1 = $this->db->get_where(TABLE_BORROW_RECOVER)->row_array(0);
        
        $this->db->select('sum(coupon_amount) as yes_coupon_sum');
        $this->db->join(TABLE_BORROW_TENDER, TABLE_BORROW_TENDER.'.id = '.TABLE_BORROW_RECOVER.'.tender_id');
        $this->_condition($condition);
        $this->db->where(TABLE_BORROW_RECOVER.'.recover_account_yes > ',0);
        $r_2 = $this->db->get_where(TABLE_BORROW_RECOVER)->row_array(0);
        $r = array_merge($r_1, $r_2);
        return $r;
    }
    
    public function delete($condition) {}
    
    private function _condition($condition) {
        if ($condition['borrow_id']) {
            is_array($condition['borrow_id']) ? $this->db->where_in(TABLE_BORROW_RECOVER.'.borrow_nid', $condition['borrow_id']) : $this->db->where(TABLE_BORROW_RECOVER.'.borrow_nid', $condition['borrow_id']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in(TABLE_BORROW_RECOVER.'.user_id', $condition['user_id']) : $this->db->where(TABLE_BORROW_RECOVER.'.user_id', $condition['user_id']);
        }
        
        if (isset($condition['recover_status'])) {
            is_array($condition['recover_status']) ? $this->db->where_in('recover_status', $condition['recover_status']) : $this->db->where('recover_status', $condition['recover_status']);
        }
        if (isset($condition['search_date'])&& strlen($condition['search_date']) == 6) {
            $start_year = intval(substr($condition['search_date'],0,4));
            $start_month = intval(substr($condition['search_date'],4,2));
            $end_month = ($start_month == 12) ? 1 : $start_month + 1;
            $end_year = ($start_month == 12) ? $start_year + 1 : $start_year;
            $starttime = mktime(0,0,0,$start_month,1,$start_year);
            $endtime = mktime(0,0,0,$end_month,1,$end_year);
            $this->db->where('recover_time >=', $starttime);
            $this->db->where('recover_time <', $endtime);
        }
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_BORROW_RECOVER,array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }
    
}
