<?php

/**
 * 用户银行卡模型
 *
 *
 */
class m_userbank extends CI_Model {

    const STATUS_MODIFY_SUCCESS = 1;
    const STATUS_MODIFY_FAILED = 2;
    const STATUS_MODIFY_PROCESSING = 3;
    
    public function __construct() {
        parent::__construct();
    }

    
    public function _detail($user_id) {
        $this->db->limit(1);
        return $this->db->get_where(TABLE_ACCOUNT_USERS_BANK, array('user_id' => $user_id))->row_array(0);
    }
    
    /**
     * 银行卡详情
     */
    public function detail($user_id) {
        $detail = $this->_detail($user_id);
        if (!empty($detail)) {
            $this->db->limit(1);
            $r = $this->db->get_where(TABLE_ACCOUNT_BANK, array('id' => $detail['bank']))->row_array(0);
            $detail['bank_name'] = $r['name'];
            return new obj_userbank($detail);
        } else {
            return false;
        }
    }
    
    /**
     * 银行卡修改记录详情
     */
    public function modify_log_detail($id,$condition) {
        $this->db->limit(1);
        $this->_condition($condition);
        $this->db->order_by('id desc');
        $detail = $this->db->get_where(TABLE_USERBANK_MODIFY_LOG, array('id' => $id))->row_array(0);
        if (!empty($detail)) {
                $detail['status'] = $this->get_modify_log_status($detail['status']);
                $detail['province'] = $this->get_area($detail['province']);
                $detail['city'] = $this->get_area($detail['city']);
                $detail['area'] = $this->get_area($detail['area']);
                $detail['old_province'] = $this->get_area($detail['old_province']);
                $detail['old_city'] = $this->get_area($detail['old_city']);
                $detail['old_area'] = $this->get_area($detail['old_area']);
                $detail['bank'] = $this->get_bank($detail['bank']);
                $detail['old_bank'] = $this->get_bank($detail['old_bank']);
            return new obj_userbank_modify_log($detail);
        } else {
            return false;
        }
    }
    
    public function modify_log_detail_by_userid($userid,$condition) {
        $this->db->limit(1);
        $this->_condition($condition);
        $this->db->order_by('id desc');
        $detail = $this->db->get_where(TABLE_USERBANK_MODIFY_LOG, array('user_id' => $userid))->row_array(0);
        if (!empty($detail)) {
            $detail['status'] = $this->get_modify_log_status($detail['status']);
                $detail['province'] = $this->get_area($detail['province']);
                $detail['city'] = $this->get_area($detail['city']);
                $detail['area'] = $this->get_area($detail['area']);
                $detail['old_province'] = $this->get_area($detail['old_province']);
                $detail['old_city'] = $this->get_area($detail['old_city']);
                $detail['old_area'] = $this->get_area($detail['old_area']);
                $detail['bank'] = $this->get_bank($detail['bank']);
                $detail['old_bank'] = $this->get_bank($detail['old_bank']);
            return new obj_userbank_modify_log($detail);
        } else {
            return false;
        }
    }
    
    public function _condition($condition) {
        if ($condition['is_delete']) {
            $this->db->where('is_delete', $condition['is_delete']);
        }
        if ($condition['status']) {
            $this->db->where('status', $condition['status']);
        }
        if ($condition['user_id']) {
            $this->db->where('user_id', $condition['user_id']);
        }
    }
    
    public function add_delete_log($data) {
        $param['user_id'] = trim($data['user_id']);
        $param['status'] = intval($data['status']);
        $param['account'] = trim($data['account']);
        $param['bank'] = trim($data['bank']);
        $param['branch'] = trim($data['branch']);
        $param['province'] = trim($data['province']);
        $param['city'] = trim($data['city']);
        $param['area'] = trim($data['area']);
        $param['addtime'] = trim($data['addtime']);
        $param['addip'] = trim($data['addip']);
        $param['update_time'] = trim($data['update_time']);
        $param['update_ip'] = trim($data['update_ip']);
        $param['check_status'] = trim($data['check_status']);
        $param['delete_time'] = time();
        $param['remark'] = trim($data['id']);
        $this->db->insert(TABLE_USERBANK_DELETE_LOG, $param);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function add_modify_log($data) {
        $param['bank'] = trim($data['bank']);
        $param['mobile'] = intval($data['mobile']);
        $param['user_id'] = intval($data['user_id']);
        $param['realname'] = trim($data['realname']);
        $param['status'] = intval($data['status']);
        $param['account'] = trim($data['account']);
        $param['bank'] = trim($data['bank']);
        $param['province'] = trim($data['province']);
        $param['city'] = trim($data['city']);
        $param['area'] = trim($data['area']);
        $param['reason'] = trim($data['reason']);
        $param['front_pic'] = trim($data['front_pic']);
        $param['behind_pic'] = trim($data['behind_pic']);
        $param['hand_pic'] = trim($data['hand_pic']);
        $param['newbank_pic'] = trim($data['newbank_pic']);
        $param['old_account'] = trim($data['old_account']);
        $param['old_bank'] = trim($data['old_bank']);
        $param['old_province'] = trim($data['old_province']);
        $param['old_city'] = trim($data['old_city']);
        $param['old_area'] = trim($data['old_area']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_USERBANK_MODIFY_LOG, $param);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function delete($id) {
        $this->db->delete(TABLE_ACCOUNT_USERS_BANK, array('id' => $id));  
        return $this->db->affected_rows() > 0;
    }
    
    
    public function update($id,$data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'account':
                    $param['account'] = trim($data['account']);
                    break;
                case 'bank':
                    $param['bank'] = trim($data['bank']);
                    break;
                case 'province':
                    $param['province'] = intval($data['province']);
                    break;
                case 'city':
                    $param['city'] = intval($data['city']);
                    break;
                case 'area':
                    $param['area'] = intval($data['area']);
                    break;
                case 'check_status':
                    $param['check_status'] = intval($data['check_status']);
                    break;
                default:
                    break;
            }
        }
        $param['update_time'] = time();
        $this->db->where('id', $id);
        /*$this->db->where('check_status', $id);
        $check_status = array(self::STATUS_MODIFY_SUCCESS, self::STATUS_MODIFY_FAILED);
        $this->db->where_in('check_status', $check_status);*/
        $this->db->update(TABLE_ACCOUNT_USERS_BANK,$param);
        return $this->db->affected_rows() > 0;

    }
    
    public function update_modify_log($id,$data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'status':
                    $param['status'] = intval($data['status']);
                    break;
                case 'fail_reason':
                    $param['fail_reason'] = trim($data['fail_reason']);
                    break;
                case 'advice':
                    $param['advice'] = trim($data['advice']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->where('id', $id);
        $this->db->update(TABLE_USERBANK_MODIFY_LOG,$param);
        return $this->db->affected_rows() > 0;
    }
    
    public function check_mobile_code($mobile,$content,$user_id = 0){
        // 3-发送短信
        $content = $content;
        
        $this->load->model('m_sms');
        //短信提醒
        $r = $this->m_sms->add(array(
            'user_id' => $user_id,
            'mobile' => $mobile,
            'content' => $content
        ));
        return $r;
    }
    
    public function cancel_modify($user_id){
        $this->db->where('user_id', $user_id);
        $this->db->where('status <>', 2);
        $this->db->where('check_status', self::STATUS_MODIFY_FAILED);
        $param1['check_status'] = self::STATUS_MODIFY_SUCCESS;
        $check_result = $this->db->update(TABLE_ACCOUNT_USERS_BANK,$param1);
        if($check_result){
            $param2['is_delete'] = STATUS_HAS_DELETE;
            $param2['modify_time'] = time();
            $this->db->where('user_id', $user_id);
            $this->db->where('is_delete', STATUS_NOT_DELETE);
            $this->db->where('status', self::STATUS_MODIFY_FAILED);
            $this->db->update(TABLE_USERBANK_MODIFY_LOG,$param2);
            return $this->db->affected_rows() > 0;
        }
        else{
            return false;
        }
    }
    
    public function modify_log_lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get(TABLE_USERBANK_MODIFY_LOG)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_modify_log_status($v['status']);
            $v['province'] = $this->get_area($v['province']);
            $v['city'] = $this->get_area($v['city']);
            $v['area'] = $this->get_area($v['area']);
            $v['old_province'] = $this->get_area($v['old_province']);
            $v['old_city'] = $this->get_area($v['old_city']);
            $v['old_area'] = $this->get_area($v['old_area']);
            $v['bank'] = $this->get_bank($v['bank']);
            $v['old_bank'] = $this->get_bank($v['old_bank']);
            $rows[$k] = new obj_userbank_modify_log($v);
        }
        return $rows;
    }
    
    public function modify_log_count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_USERBANK_MODIFY_LOG)->row(0)->count;
    }
    
    public function get_modify_log_status($key = false) {
        $data = array(
            1 => '审核成功',
            2 => '审核失败',
            3 => '审核中',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'MODIFY_LOG_ERROR', 'text' => '审核状态错误');
    }
    
    public function get_area($id) {
        $this->load->model('m_address');
        $area = $this->m_address->get_area($id);
        return isset($area['name']) ? array('id' => $id, 'text' => $area['name']) : array('id' => 'AREA_ERROR', 'text' => '');
    }
    
    public function get_bank($id) {
        $bank = $this->db->get_where(TABLE_ACCOUNT_BANK, array('id' => $id))->row_array();
        return isset($bank['name']) ? array('id' => $id, 'text' => $bank['name']) : array('id' => 'BANK_ERROR', 'text' => '');
    }
}
