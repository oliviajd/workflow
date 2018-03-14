<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_business_city
 *
 * @author win7
 */
class m_business_city extends CI_Model implements ObjInterface {
    
    const STATUS_CITY_ENABLE = 1;

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_status($detail['status']);
            return new obj_business_city($detail);
        }
    }

    public function lists($condition, $page = false, $size = false, $order = '') {
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_BUSINESS_CITY . '.*');
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BUSINESS_CITY)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_status($v['status']);
            $rows[$k] = new obj_business_city($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BUSINESS_CITY)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        $this->db->where(TABLE_BUSINESS_CITY . '.is_delete', STATUS_NOT_DELETE);
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_BUSINESS_CITY, array('city_id' => $id, 'is_delete' => STATUS_NOT_DELETE))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function get_status($key = false) {
        $data = array(
            1 => '开放',
            2 => '关闭',
            3 => '未开放',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'LOG_ERROR', 'text' => '日志状态错误');
    }

}
