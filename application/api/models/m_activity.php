<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_activity
 *
 * @author win7
 */
class m_activity extends CI_Model implements ObjInterface {

    const STATUS_ACTIVITY_ON = 1;
    const STATUS_ACTIVITY_OFF = 2;
    const STATUS_ACTIVITY_INIT = 3;

    public function add($data) {
        $param['title'] = trim($data['title']);
        $param['remark'] = trim($data['remark']);
        $param['limit_on_time'] = intval($data['limit_on_time']);
        $param['limit_off_time'] = intval($data['limit_off_time']);
        $param['status'] = intval($data['status']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_ACTIVITY, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'remark':
                    $param['remark'] = trim($data['remark']);
                    break;
                case 'limit_on_time':
                    $param['limit_on_time'] = intval($data['limit_on_time']);
                    break;
                case 'limit_off_time':
                    $param['limit_off_time'] = intval($data['limit_off_time']);
                    break;
                case 'status':
                    if (in_array(intval($data['status']), array(self::STATUS_ACTIVITY_INIT, self::STATUS_ACTIVITY_ON, self::STATUS_ACTIVITY_OFF))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_ACTIVITY, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_activity_status($detail['status']);
            return new obj_activity($detail);
        }
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
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_ACTIVITY)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_ACTIVITY, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }
    
    public function get_activity_status($key = false) {
        $data = array(
            1 => '开放',
            2 => '关闭',
            3 => '未开放',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'ACTIVITY_ERROR', 'text' => '活动状态错误');
    }
    
    public function activity_lists() {
        $this->db->where('status',0);
        $rows = $this->db->get_where(TABLE_ACTIVITY_4_25)->result_array();
        return $rows;
    }
    
    public function update_activity_bouns($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'status':
                    if (in_array(intval($data['status']), array(1,2))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_ACTIVITY_4_25, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }
    
    public function wish_sum() {
        $this->load->model('m_script');
        if($this->m_script->isset_key(__FUNCTION__,'LAST_WISH_ORDER_NUM')){
            $r = $this->m_script->get_value(__FUNCTION__,'LAST_WISH_ORDER_NUM');
            return $r;
        }
        else{
            return false;
        }
    }
    
    public function wish_add($data) {
        //每个用户限制发5次
        $this->db->select('count(1) as count');
        $count = $this->db->get_where(TABLE_ACTIVITY_WISH,array('user_id' => $this->api->user()->user_id))->row(0)->count;
        if(intval($count) >= 5){
            $this->api->output(false, ERR_COUNT_ERROR_NO, ERR_COUNT_ERROR_MSG);
        }
        else{
            $this->db->trans_begin();   //事务开始
            $sql = "SELECT value FROM " . TABLE_SCRIPT_VALUES . " WHERE `method_name` = 'wish_sum' AND `key` = 'LAST_WISH_ORDER_NUM' ";   //查询当前总祝福数
            $sum = $this->db->query($sql)->row_array(0);
            if($sum === false){
                $this->db->trans_rollback();
                return false;
            }
            $sum = intval($sum['value']);
            if($sum == 0){
                $new_sum = $sum + 1;
                $sql = "UPDATE ".TABLE_SCRIPT_VALUES." set `value` = `value`+1  WHERE `method_name` = 'wish_sum' AND `key` = 'LAST_WISH_ORDER_NUM'";
            }
            elseif($sum <= 50){
                $new_sum = $sum + 1;
                $sql = "UPDATE ".TABLE_SCRIPT_VALUES." set `value` = `value`+1  WHERE `method_name` = 'wish_sum' AND `key` = 'LAST_WISH_ORDER_NUM' AND `value` = ".$sum;  
            }
            else{
                $plus = mt_rand(1,9);
                $new_sum = $sum + $plus;
                $sql = "UPDATE ".TABLE_SCRIPT_VALUES." set `value` = `value`+".$plus."  WHERE `method_name` = 'wish_sum' AND `key` = 'LAST_WISH_ORDER_NUM' AND `value` = ".$sum;
            }
            
            $update_count = $this->db->query($sql); //更新总祝福数
            
            if($update_count === false){
                $this->db->trans_rollback();
                return false;
            }
            
            $param['user_id'] = $data['user_id'];
            $param['mobile'] = $data['mobile'];
            $param['content'] = $data['content'];
            $param['ordernum'] = $new_sum;
            $param['create_time'] = time();
            $this->db->insert(TABLE_ACTIVITY_WISH, $param);
            $r = $this->db->insert_id();
            if($r === false){
                $this->db->trans_rollback();
                return false;
            }
            $this->db->trans_commit();
            return $new_sum;
        }
        
    }
    
    public function wish_list($data) {
        $this->db->limit($data['limit']);
        $this->db->order_by('id desc');
        $rows = $this->db->get_where(TABLE_ACTIVITY_WISH)->result_array();
        return $rows;
    }

}
