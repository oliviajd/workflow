<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_item_instance
 *
 * @author win7
 */
class m_item_instance extends CI_Model implements ObjInterface {

    const STATUS_ITEM_INSTANCE_START = 1;
    const STATUS_ITEM_INSTANCE_COMPLETE = 2;
    const STATUS_ITEM_INSTANCE_STOP = 3;

    public function add($data) {
        $param = array();
        $param['process_id'] = intval($data['process_id']);
        $param['process_instance_id'] = intval($data['process_instance_id']);
        $param['item_id'] = intval($data['item_id']);
        $param['role_id'] = trim($data['role_id']);
        $param['user_id'] = intval($data['user_id']);
        $param['status'] = self::STATUS_ITEM_INSTANCE_START;
        $param['is_locked'] = 0;        //>0表示被这个id的用户占用，=0表示可用
        $param['has_completed'] = 2;    //1已完成，2未完成
        $param['has_stoped'] = 2;       //1已停止，2未停止
        $param['create_time'] = time();
        $this->db->insert(TABLE_PROCESS_ITEM_INSTANCE, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function end($data){
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('has_stoped' => '1'), array('process_instance_id' => intval($data['process_instance_id'])));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['has_completed'] = $this->get_complete_status($detail['has_completed']);
            return new obj_item_instance($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by(TABLE_PROCESS_ITEM_INSTANCE .'.'. $order, 'desc'); 
            // $this->db->order_by($order);
        } else{
            $table = TABLE_PROCESS_EXTENDS . intval($condition['process_id']);  
            $this->db->order_by($table . '.create_time', 'desc');   
        }
        $this->db->select('*,' . TABLE_PROCESS_ITEM_INSTANCE . '.id as id, ' . TABLE_PROCESS_ITEM_INSTANCE . '.create_time as receive_time' , false);
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_PROCESS_ITEM_INSTANCE)->result_array();
        foreach ($rows as $k => $v) {
            $v['has_completed'] = $this->get_complete_status($v['has_completed']);
            $rows[$k] = new obj_item_instance($v);
            $rows[$k]->complete_time = $v['complete_time'];
            $rows[$k]->stop_time = $v['stop_time'];
            $rows[$k]->create_time = $v['create_time'];
            $rows[$k]->lock_time = $v['lock_time'];
            $rows[$k]->receive_time = $v['receive_time'];
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_PROCESS_ITEM_INSTANCE)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['role_id']) {
            is_array($condition['role_id']) ? $this->db->where_in('role_id', $condition['role_id']) : $this->db->where('role_id', $condition['role_id']);
        }
        if ($condition['process_id']) {
            $this->db->where(TABLE_PROCESS_ITEM_INSTANCE . '.process_id', intval($condition['process_id']));
        }
        if ($condition['item_id']) {
            is_array($condition['item_id']) ? $this->db->where_in('item_id', $condition['item_id']) : $this->db->where('item_id', $condition['item_id']);
        }
        if ($condition['has_completed']) {
            $this->db->where('has_completed', $condition['has_completed']);
        }
        if ($condition['has_stoped']) {
            $this->db->where('has_stoped', $condition['has_stoped']);
        }
        if ($condition['has_locked']) {
            $condition['has_locked'] == 1 ? $this->db->where('is_locked > 0', false, false) : $this->db->where('is_locked', 0);
        }
        $table = TABLE_PROCESS_EXTENDS . intval($condition['process_id']);
        $this->db->join($table, "{$table}.process_id = " . TABLE_PROCESS_ITEM_INSTANCE . '.process_id AND ' ."{$table}.process_instance_id = " . TABLE_PROCESS_ITEM_INSTANCE . '.process_instance_id', 'INNER');
        if ($condition['remote_condition']) {
            foreach ($condition['remote_condition'] as $k => $v) {
                switch (strtolower($k)) {
                    case 'start_time':
                        $this->db->where($table . '.create_time >=' . intval($v), false, false);
                        break;
                    case 'end_time':
                        $this->db->where($table . '.create_time <=' . intval($v), false, false);
                        break;
                    case 'name':
                        $this->db->like($table . '.name', $v, 'both');
                        break;
                    case 'customer_name':
                        $this->db->like($table . '.customer_name', $v, 'both');
                        break;
                    case 'request_no':
                        $this->db->like($table . '.request_no', $v, 'both');
                        break;
                    case 'shopname':
                        $this->db->like($table . '.shopname', $v, 'both');
                        break;
                    case 'credit_city_like':
                        $this->db->like($table . '.credit_city', $v, 'both');
                        break;
                    default:
                        !is_array($v) ? $this->db->where("{$table}.{$k}", $v) : $this->db->where_in("{$table}.{$k}", $v);
                        break;
                }
            }
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_PROCESS_ITEM_INSTANCE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function get_status($key = false) {
        $data = array(
            self::STATUS_ITEM_INSTANCE_START => '进行中',
            self::STATUS_ITEM_INSTANCE_COMPLETE => '完成',
            self::STATUS_ITEM_INSTANCE_STOP => '停止',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'STATUS_ERROR', 'text' => '状态错误');
    }

    public function get_complete_status($key = false) {
        $data = array(
            1 => '已完成',
            2 => '未完成',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'STATUS_ERROR', 'text' => '状态错误');
    }

}
