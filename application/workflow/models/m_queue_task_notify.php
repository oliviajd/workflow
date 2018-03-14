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
class m_queue_task_notify extends CI_Model implements ObjInterface {

    public function add($data) {
        $param = array();
        $param['process_id'] = intval($data['process_id']);
        $param['process_instance_id'] = intval($data['process_instance_id']);
        $param['item_id'] = intval($data['item_id']);
        $param['item_instance_id'] = intval($data['item_instance_id']);
        $param['url'] = trim($data['url']);
        $param['param'] = json_encode($data['param']);
        $param['remark'] = trim($data['remark']);
        $param['status'] = 2;
        $param['create_time'] = time();
        $this->db->insert(TABLE_QUEUE_TASK_NOTIFY, $param);
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
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_QUEUE_TASK_NOTIFY)->result();
        foreach ($rows as $k => $v) {
            $rows[$k]->param = json_decode($v->param, true);
        }
        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

}
