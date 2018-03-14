<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_cash
 *
 * @author win7
 */
class m_cash extends CI_Model implements ObjInterface {

    const STATUS_VERIFY_INIT = 0;
    const STATUS_VERIFY_SUCCESS = 1;
    const STATUS_VERIFY_FAILED = 2;
    const STATUS_VERIFY_PROCESSING = 3;
    const STATUS_VERIFY_CANCEL = 4;
    
    

    public function add($data) {
        $param['user_id'] = trim($data['user_id']);
        $param['nid'] = trim($data['nid']);
        $param['status'] = self::STATUS_VERIFY_INIT;
        $param['account'] = trim($data['account']);
        $param['bank'] = trim($data['bank']);
        $param['bank_id'] = trim($data['bank_id']);
        $param['total'] = trim($data['account']);
        $param['credited'] = trim($data['account']);
        $param['fee'] = trim($data['fee']);
        $param['source'] = trim($data['source']);
        $param['is_first'] = trim($data['is_first']) ? $data['is_first'] : 0;
        $param['balance'] = trim($data['balance']);
        $param['card_id'] = trim($data['card_id']);
        $param['name'] = trim($data['name']);
        $param['mobile'] = trim($data['mobile']);
        $param['routeCode'] = trim($data['routeCode']);
        $param['cardBankCnaps'] = trim($data['cardBankCnaps']);

        $param['addtime'] = time();
        $this->db->insert(TABLE_CASH, $param);
        $id = $this->db->insert_id();
        return $id;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'nid':
                    $param['nid'] = trim($data['nid']);
                    break;
                case 'user_id':
                    $param['user_id'] = trim($data['user_id']);
                    break;
                case 'account':
                    $param['account'] = trim($data['account']);
                    break;
                case 'status':
                   if (in_array(intval($data['status']), array(self::STATUS_VERIFY_INIT, self::STATUS_VERIFY_SUCCESS, self::STATUS_VERIFY_FAILED,self::STATUS_VERIFY_PROCESSING,self::STATUS_VERIFY_CANCEL))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                case 'bank':
                    $param['bank'] = trim($data['bank']);
                    break;
                case 'branch':
                    $param['branch'] = trim($data['branch']);
                    break;
                case 'province':
                    $param['province'] = intval($data['province']);
                    break;
                case 'city':
                    $param['city'] =  intval($data['city']);
                    break;
                case 'total':
                    $param['total'] = trim($data['total']);
                    break;
                case 'credited':
                    $param['credited'] = trim($data['credited']);
                    break;
                case 'fee':
                    $param['fee'] = trim($data['fee']);
                    break;
                case 'verify_remark':
                    $param['verify_remark'] = trim($data['verify_remark']);
                    break;
                case 'source':
                    $param['source'] = trim($data['source']);
                    break;
                case 'is_first':
                    $param['is_first'] = trim($data['is_first']);
                    break;
                case 'balance':
                    $param['balance'] = trim($data['balance']);
                    break;
                case 'card_id':
                    $param['card_id'] = trim($data['card_id']);
                    break;
                case 'name':
                    $param['name'] = trim($data['name']);
                    break;
                case 'mobile':
                    $param['mobile'] = trim($data['mobile']);
                    break;
                case 'routeCode':
                    $param['routeCode'] = trim($data['routeCode']);
                    break;
                case 'cardBankCnaps':
                    $param['cardBankCnaps'] = trim($data['cardBankCnaps']);
                    break;
                default:
                    break;
            }
        }
        $param['verify_userid'] = $this->api->user()->user_id;
        $param['verify_time'] = time();
        $this->db->update(TABLE_CASH, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
            $detail = $this->_detail($id);
            if (empty($detail)) {
                return false;
            } else {
                $detail['status'] = $this->get_cash_status($detail['status']);
                return new obj_cash($detail);
            }
    }

    public function lists($condition, $page, $size, $order) {
        $this->load->model('m_address');
        $this->load->model('m_user');
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_CASH)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_cash_status($v['status']);
            
            $account_users = $this->db->get_where(TABLE_ACCOUNT_USERS_BANK, array('user_id' => $v['user_id']))->result_array(0);
            $rows[$k]['province'] = $this->m_address->get_area($account_users[0]['province']);
            $rows[$k]['city'] = $this->m_address->get_area($account_users[0]['city']);
            
            $rows[$k]['user'] = $this->m_user->detail($v['user_id']);
            
            $rows[$k] = new obj_cash($rows[$k]);
        }
        
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_CASH)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
        if (isset($condition['status'])) {
            $this->db->where('status', $condition['status']);
        }
        if (isset($condition['user_id'])) {
            count($condition['user_id']) > 1 ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id'][0]);
        }
        if (isset($condition['bank_id'])) {
            $this->db->where('bank_id', trim($condition['bank_id']));
        }
    }

    public function _detail($id) {
        $detail = $this->db->get_where(TABLE_CASH, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }
    
    public function get_cash_status($key = false) {
        $data = array(
            self::STATUS_VERIFY_INIT => '未审核',
            self::STATUS_VERIFY_SUCCESS => '已审核',
            self::STATUS_VERIFY_FAILED => '已失败',
            self::STATUS_VERIFY_PROCESSING => '审核中',
            self::STATUS_VERIFY_CANCEL => '取消',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'CASH_ERROR', 'text' => '提现状态错误');
    }
    
    public function uncheck_item($user_id){
        $this->db->where('user_id',$user_id);
        $this->db->where('status',0);
        $r = $this->db->get_where(TABLE_CASH)->row(0);
        return $r;
    }
    
    public function get_by_nid($nid) {
            $detail = $this->db->get_where(TABLE_CASH, array('nid' => $nid))->row_array(0);
            return empty($detail) ? false : $detail;
    }
    
    public function get_success_by_userid($user_id) {
            $detail = $this->db->get_where(TABLE_CASH, array('user_id' => $user_id,'status' => 1))->row_array(0);
            return empty($detail) ? false : $detail;
    }
    
    public function uncheck_cash_list(){
        $this->db->where('mobile IS NOT NULL',false ,false);
        $this->db->where('status',0);
        $r = $this->db->get_where(TABLE_CASH)->result_array();
        return $r;
    }

}
