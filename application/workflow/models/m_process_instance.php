<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_process_instance
 *
 * @author win7
 */
class m_process_instance extends CI_Model implements ObjInterface {

    const STATUS_PROCESS_INSTANCE_START = 1;            //流程实例开始
    const STATUS_PROCESS_INSTANCE_COMPLETE = 2;         //流程实例完成
    const STATUS_PROCESS_INSTANCE_STOP = 3;             //流程实例结束

    //增加任务实例
    public function add($data) {
        $param = array();
        $param['process_id'] = intval($data['process_id']);
        $param['user_id'] = intval($data['user_id']);
        $param['title'] = trim($data['title']);
        $param['status'] = self::STATUS_PROCESS_INSTANCE_START;
        $param['has_completed'] = 2;               //1为已完成，2为未完成
        $param['has_stoped'] = 2;                  //1为已结束，2为未结束
        $param['create_time'] = time();
        $this->db->insert(TABLE_PROCESS_INSTANCE, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    //根据任务实例id获取任务实例详情
    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
//            $detail['status'] = $this->get_status($detail['status']);
            return new obj_process_instance($detail);
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
        $rows = $this->db->get_where(TABLE_PROCESS_INSTANCE)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_process_instance($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_PROCESS_INSTANCE)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_PROCESS_INSTANCE, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_PROCESS_INSTANCE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function start($id) {
        $this->load->model('m_item');
        $items = $this->next($id);       //寻找接下来要实现的任务数组
        $task_ids = array();
        foreach ($items as $k => $v) {
            $task_id = $this->m_item->run($id, $v, array());
            if ($task_id) {
                $task_ids[] = $task_id;
            }
        }
        return $task_ids;
    }

    public function prev($id, $item_id = 0) {
        $this->load->model('m_process_link');
        $detail = $this->detail($id);
        if ($item_id == 0) {
            $links = array();
        } else {
            $links = $this->m_process_link->lists(array(
                'process_id' => $detail->process_id,
                'current_id' => $item_id
            ));
        }
        $r = array();
        $links_map = array();
        foreach ($links as $k => $v) {
            $links_map[$v->prev_id] = $this->m_item->detail($v->prev_id);
        }
//        foreach ($links as $k => $v) {
//            $r[] = $this->m_item->detail($v->next_id);
//        }
        return array_values($links_map);
    }
    
    //寻找接下来要实现的任务数组，id为流程实例id
    public function next($id, $item_id = 0) {
        $this->load->model('m_process_link');
        $detail = $this->detail($id);
        if ($item_id == 0) {    //如果是start一个任务
            $links = $this->m_process_link->lists(array(       
                'process_id' => $detail->process_id,
                'prev_id' => 0
            ));
        } else {
            $links = $this->m_process_link->lists(array(
                'process_id' => $detail->process_id,
                'current_id' => $item_id
            ));
        }
        // $r = array();
        $links_map = array();
        foreach ($links as $k => $v) {
            $links_map[$v->next_id] = $this->m_item->detail($v->next_id);
        }
//        foreach ($links as $k => $v) {
//            $r[] = $this->m_item->detail($v->next_id);
//        }
        return array_values($links_map);
    }

    public function set_value($data) {
        $param = array(
            'process_id' => intval($data['process_id']),
            'process_instance_id' => intval($data['process_instance_id']),
            'item_id' => intval($data['item_id']),
            'item_instance_id' => intval($data['item_instance_id']),
            'role_id' => intval($data['role_id']),
            'user_id' => intval($data['user_id']),
            'key' => trim($data['key']),
            'value' => trim($data['value']),
            'create_time' => time(),
        );
        $exists = $this->db->get_where(TABLE_PROCESS_INSTANCE_VALUE, array('process_instance_id' => $data['process_instance_id'], 'key' => trim($data['key'])))->row(0);
        if ($exists) {
            $this->db->update(TABLE_PROCESS_INSTANCE_VALUE, $param, array('id' => $exists->id));
            return $exists->id;
        } else {
            $this->db->insert(TABLE_PROCESS_INSTANCE_VALUE, $param);
            return $this->db->insert_id();
        }
    }

    public function get_value($condition) {
        $values = array();
        $result = $this->db->get_where(TABLE_PROCESS_INSTANCE_VALUE, $condition)->result_array();
        foreach ($result as $k => $v) {
            $values[$v['key']] = $v;
        }
        return $values;
    }

}
