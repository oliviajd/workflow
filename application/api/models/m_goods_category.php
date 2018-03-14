<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_goods_category
 *
 * @author win7
 */
class m_goods_category extends CI_Model implements ObjInterface{
    
    public function add($data) {}
    
    public function update($id,$data) {}
    
    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_goods_category($detail);
        }
    }
    
    public function lists($condition, $page, $size, $order) {}
    
    public function count($condition) {}
    
    public function delete($condition) {}
    
    private function _condition($condition) {}
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_GOODS_CATEGORY, array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }
}
