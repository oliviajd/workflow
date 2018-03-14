<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author win7
 */
class m_risk_stats extends CI_Model implements ObjInterface {

    public function add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['finance_bill_id'] = intval($data['finance_bill_id']);
        $param['ym'] = date('Ym');
        $param['create_time'] = time();
        $this->db->insert(TABLE_RISK_STATS, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    public function is_bill_exists($finance_bill_id) {
        return $this->db->get_where(TABLE_RISK_STATS, array('finance_bill_id' => $finance_bill_id))->row(0)->id > 0;
    }

    public function monthly_lists($condition, $page = false, $size = false, $order = false) {
        if ($page !== false && $size !== false) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition_monthly($condition);
        $this->db->select('user_id,count(1) as count,ym');
        $this->db->group_by('ym,user_id');
        $rows = $this->db->get_where(TABLE_RISK_STATS)->result_array();
        return $rows;
    }

    public function monthly_count($condition) {
        $this->db->select('1', false);
        $this->_condition_monthly($condition);
        $this->db->group_by('ym,user_id');
        $rows = $this->db->get_where(TABLE_RISK_STATS)->result_array();
        return count($rows);
    }

    private function _condition_monthly($condition) {
        
    }

}
