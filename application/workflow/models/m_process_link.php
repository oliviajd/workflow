<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_process_link
 *
 * @author win7
 */
class m_process_link extends CI_Model implements ObjInterface{
    
    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_activity_status($detail['status']);
            return new obj_process_link($detail);
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
        $rows = $this->db->get_where(TABLE_PROCESS_LINK)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_process_link($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_PROCESS_LINK)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_PROCESS_LINK, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['process_id']) {
            $this->db->where('process_id', $condition['process_id']);
        }
        if (isset($condition['current_id'])) {
            $this->db->where('current_id', $condition['current_id']);
        }
        if (isset($condition['prev_id'])) {
            $this->db->where('prev_id', $condition['prev_id']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_PROCESS_LINK, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }
    
}
