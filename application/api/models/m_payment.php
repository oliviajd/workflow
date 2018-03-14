<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_payment
 *
 * @author win7
 */
class m_payment extends CI_Model implements ObjInterface{
    
    const STATUS_BANK_ENABLE = 1;
    const STATUS_BANK_DISABLE = 2;
    
    public function add($data) {}
    
    public function update($id,$data) {}
    
    public function detail($id) {}
    
    public function lists($condition, $page, $size, $order) {}
    
    public function count($condition) {}
    
    public function delete($condition) {}
    
    private function _condition($condition) {}
    
    public function bank_lists($condition, $page = false, $size = false, $order = false) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_bank_condition($condition);
        $rows = $this->db->get_where(TABLE_PAYMENT_BANK)->result_array();
        foreach($rows as $k=>$v) {
            $rows[$k] = new obj_payment_bank($v);
        }
        return $rows;
    }

    public function bank_count($condition) {
        $this->db->select('count(1) as count');
        $this->_bank_condition($condition);
        return $this->db->get_where(TABLE_PAYMENT_BANK)->row(0)->count;
    }

    private function _bank_condition($condition) {
        if (isset($condition['status'])) {
            $this->db->where('status', $condition['status']);
        }
        if (isset($condition['payment'])) {
            $this->db->where('payment', $condition['payment']);
        }
    }
    
}
