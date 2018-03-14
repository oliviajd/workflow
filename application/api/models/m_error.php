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
class m_error extends CI_Model implements ObjInterface {

    const STATUS_NOT_RESOLVED = 1;
    const STATUS_RESOLVED = 2;

    public function add($data) {
        $param = array();
        $param['notice_to_user_id'] = implode(',', array_column($data['notice_to'], 'user_id'));
        $param['notice_to_mobile'] = implode(',', array_column($data['notice_to'], 'mobile'));
        $param['status'] = self::STATUS_NOT_RESOLVED;
        $param['item_type'] = strtoupper($data['item_type']);
        $param['item_id'] = $data['item_id'];
        $param['error_no'] = $data['error_no'];
        $param['error_msg'] = $data['error_msg'];
        $param['has_noticed'] = 0;
        $param['repeat_notice'] = intval($data['repeat_notice']) > 0 ? intval($data['repeat_notice']) : 0;
        $param['last_time'] = 0;
        $param['create_time'] = time();
        $this->db->insert(TABLE_ERRORS, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_ACTIVITY . '.*');
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_ACTIVITY)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_activity_status($v['status']);
            $rows[$k] = new obj_activity($v);
        }
        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    public function is_exists($condition) {
        $this->db->limit(1);
        $exists = $this->db->get_where(TABLE_ERRORS, array(
                    'item_type' => strtoupper($condition['item_type']),
                    'item_id' => $condition['item_id'],
                    'error_no' => $condition['error_no'],
                ))->row_array(0);
        return empty($exists) ? false : $exists;
    }

}
