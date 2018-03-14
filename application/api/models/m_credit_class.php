<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_credit_class
 *
 * @author win7
 */
class m_credit_class extends CI_Model implements ObjInterface{
    
    public function add($data) {}
    
    public function update($id,$data) {}
    
    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['class'] = array();
            return new obj_credit_class($detail);
        }
    }
    
    public function detail_by_nid($nid) {
        $detail = $this->_detail_by_nid($nid);
        if (empty($detail)) {
            return false;
        } else {
            $detail['class'] = array();
            return new obj_credit_class($detail);
        }
    }
    
    public function lists($condition, $page, $size, $order) {}
    
    public function count($condition) {}
    
    public function delete($condition) {}
    
    private function _condition($condition) {}
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_CREDIT_CLASS, array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }
    
    private function _detail_by_nid($nid) {
        $detail = $this->db->get_where(TABLE_CREDIT_CLASS, array('nid' => $nid))->row_array(0);
        return empty($detail) ? false : $detail;
    }
    
}
