<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_balance_account
 *
 * @author lsk
 */
class m_balance_account extends CI_Model implements ObjInterface {
    
    const STATUS_BALANCE_INIT = 0;
    const STATUS_BALANCE_CORRECT = 1;
    const STATUS_BALANCE_PROCESS = 2;
    const STATUS_BALANCE_ERROR = 3;

    public function add($data) {
        $param['user_id'] = intval($data['user_id']);
        $param['remark'] = trim($data['remark']);
        $param['status'] = intval($data['status']);
        $param['create_time'] = time();
        $param['account_log_id'] = intval($data['account_log_id']);
        $this->db->insert(TABLE_BALANCE_ACCOUNT, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'remark':
                    $param['remark'] = trim($data['remark']);
                    break;
                case 'status':
                    if (in_array(intval($data['status']), array(self::STATUS_BALANCE_CORRECT, self::STATUS_BALANCE_PROCESS, self::STATUS_BALANCE_ERROR))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                case 'account_log_id':
                    $param['account_log_id'] = trim($data['account_log_id']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_balance_account_status($detail['status']);
            return new obj_balance_account($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_BALANCE_ACCOUNT . '.*');
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BALANCE_ACCOUNT)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_balance_account_status($v['status']);
            $rows[$k] = new obj_balance_account($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BALANCE_ACCOUNT)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['daterange']) {
            
            $daterange = explode('-',$condition['daterange'] );
            
            if(is_array($daterange)){
                $starttime = strtotime(str_replace("/","-",$daterange[0]));
                $endtime = strtotime(str_replace("/","-",$daterange[1]));
                if( strtotime($daterange[0]) &&  strtotime($daterange[1]) ){
                    $this->db->where('modify_time >=', $starttime);
                    $this->db->where('modify_time <', $endtime);
                }
            }
            
        }
    }
    
    private function _detail($user_id) {
        $detail = $this->db->get_where(TABLE_BALANCE_ACCOUNT, array('user_id' => $user_id))->row_array();
        return empty($detail) ? false : $detail;
    }
    
    public function get_balance_account_status($key = false) {
        $data = array(
            0 => '未对账',
            1 => '对账完成',
            2 => '对账中',
            3 => '对账出现异常',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => ERR_BALANCE_STATUS_NO, 'text' => ERR_BALANCE_STATUS_MSG);
    }

}
