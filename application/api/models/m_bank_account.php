<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_bank_account
 *
 * @author win7
 */
class m_bank_account extends CI_Model implements ObjInterface {

    const STATUS_ACCOUNT_ENABLE = 1;
    const STATUS_ACCOUNT_INIT = 2;
    const STATUS_ACCOUNT_DISABLE = 3;
    const STATUS_ACCOUNT_PROCESSING = 5;

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $this->load->model('m_bank_card');
        $detail = $this->_detail($id);
        if (!empty($detail)) {
            $detail['ba_status'] = $this->get_status($detail['ba_status']);
            $detail['ba_auto_bid'] = $this->get_auto_bid_status($detail['ba_auto_bid']);
            $detail['ba_has_set_password'] = $this->get_password_status($detail['ba_has_set_password']);
            $detail['ba_has_bind_card'] = $this->get_bind_card_status($detail['ba_has_bind_card']);
            $detail['ba_card'] = $this->m_bank_card->detail($detail['ba_card_No']);
            return new obj_bank_account($detail);
        } else {
            return false;
        }
    }

    public function lists($condition, $page, $size, $order) {
        
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    private function _detail($id) {
        $this->db->limit(1);
        return $this->db->get_where(TABLE_USER, array('user_id' => $id))->row_array(0);
    }

    public function start_open($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'realname':
                    $param['ba_realname'] = trim($data['realname']);
                    break;
                case 'mobile':
                    $param['ba_mobile'] = trim($data['mobile']);
                    break;
                case 'id_No':
                    $param['ba_id_No'] = trim($data['id_No']);
                    break;
                case 'card_No':
                    $param['ba_card_No'] = trim($data['card_No']);
                    break;
                default:
                    break;
            }
        }
        $param['ba_status'] = self::STATUS_ACCOUNT_PROCESSING;
        $this->db->update(TABLE_USER, $param, array('user_id' => $id, 'ba_status' => self::STATUS_ACCOUNT_INIT));
        return $this->db->affected_rows() > 0;
    }

    public function complete_open($id, $data) {
        try {
            $this->load->model('m_bank_card');
            $card = $this->m_bank_card->api_query($data['card_No']);
            if ($card) {
                $this->m_bank_card->add($card);
            } else {
                $this->m_bank_card->add(array('bankno'=>$data['card_No']));
            }
        } catch (Exception $ex) {
            do_log(array('查询卡片信息失败' => $ex->getMessage()));
        }
        $this->db->update(TABLE_USER, array(
            'ba_status' => self::STATUS_ACCOUNT_ENABLE,
            'ba_open_time' => time(),
            'ba_id' => $data['account_id'],
                ), array('user_id' => $id, 'ba_status' => self::STATUS_ACCOUNT_PROCESSING));
        return $this->db->affected_rows();
    }

    public function stop_open($id) {
        $this->db->update(TABLE_USER, array('ba_status' => self::STATUS_ACCOUNT_INIT), array('user_id' => $id, 'ba_status' => self::STATUS_ACCOUNT_PROCESSING));
        return $this->db->affected_rows() > 0;
    }

    //乐观锁
    public function success_set_password($account_id) {
        $this->db->update(TABLE_USER, array('ba_has_set_password' => 1), array('ba_id' => $account_id, 'ba_has_set_password' => 2));
        return $this->db->affected_rows() > 0;
    }

    public function success_recharge($data) {
        $data['nid'] = trim($data['txDate'] . $data['txTime'] . $data['seqNo']);
        if ($this->get_recharge_by_nid($data['nid'])) {
            $this->db->update(TABLE_ACCOUNT_RECHARGE, array('status' => 1, 'result' => json_encode($data)), array('status' => 0, 'nid' => $data['nid'], 'ba_id' => $data['accountId']));
        } else {
            do_log('recharge_notify:' . $data);
        }
    }

    public function set_mobile($account_id, $mobile) {
        $this->db->update(TABLE_USER, array('ba_mobile' => $mobile), array('ba_id' => $account_id));
        return $this->db->affected_rows() > 0;
    }

    public function get_status($key = false) {
        $data = array(
            1 => '正常',
            2 => '未开启',
            3 => '关闭',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'STATUS_ERROR', 'text' => '状态错误');
    }

    public function get_auto_bid_status($key = false) {
        $data = array(
            1 => '已签约',
            2 => '未签约',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'STATUS_ERROR', 'text' => '状态错误');
    }

    public function get_bind_card_status($key = false) {
        $data = array(
            1 => '已绑定',
            2 => '未绑定',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'STATUS_ERROR', 'text' => '状态错误');
    }

    public function get_password_status($key = false) {
        $data = array(
            1 => '已设置',
            2 => '未设置',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'STATUS_ERROR', 'text' => '状态错误');
    }

    public function action_add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['action'] = trim($data['action']);
        $param['request'] = trim($data['request']);
        $param['ip'] = trim($data['ip']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_BA_ACTIONS, $param);
        $id = $this->db->insert_id();
        $order_sn = create_order_sn($id, 'ACT');
        $this->db->update(TABLE_BA_ACTIONS, array('order_sn' => $order_sn), array('id' => $id));
        return $id;
    }

    public function action_update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'result':
                    $param['result'] = trim($data['result']);
                    break;
                case 'receive_time':
                    $param['receive_time'] = intval($data['receive_time']);
                    break;
                default:
                    break;
            }
        }
        $param['ba_status'] = self::STATUS_ACCOUNT_PROCESSING;
        $this->db->update(TABLE_USER, $param, array('user_id' => $id, 'ba_status' => self::STATUS_ACCOUNT_INIT));
        return $this->db->affected_rows() > 0;
    }

    public function action_detail($id) {
        $detail = $this->_action_detail($id);
        if (!empty($detail)) {
            return new obj_bank_account_action($detail);
        } else {
            return false;
        }
    }

    public function action_lists($condition, $page, $size, $order) {
        
    }

    public function action_count($condition) {
        
    }

    public function action_delete($condition) {
        
    }

    private function _action_condition($condition) {
        
    }

    private function _action_detail($id) {
        $this->db->limit(1);
        return $this->db->get_where(TABLE_BA_ACTIONS, array('id' => $id))->row_array(0);
    }

    public function success_auto_bid_auth($account_id, $data) {
        $action = $this->db->get_where(TABLE_BA_ACTIONS, array('order_sn' => $data['orderId']))->row(0);
        if (empty($action) || $action->receive_time > 0) {
            return false;
        }
        $this->db->update(TABLE_BA_ACTIONS, array('result' => json_encode($data), 'receive_time' => time()), array('id' => $action->id));
        $this->db->update(TABLE_USER, array(
            'ba_auto_bid' => 1,
            'ba_auto_bid_order_sn' => $action->order_sn,
            'ba_auto_bid_max_money_single' => $data['txAmount'] * 100,
                ), array('ba_id' => $account_id));
        return $this->db->affected_rows() > 0;
    }

    public function cancel_auto_bid_auth($account_id, $data) {
        $action = $this->db->get_where(TABLE_BA_ACTIONS, array('order_sn' => $data['orderId']))->row(0);
        if (empty($action) || $action->receive_time > 0) {
            return false;
        }
        $this->db->update(TABLE_BA_ACTIONS, array('result' => json_encode($data), 'receive_time' => time()), array('id' => $action->id));
        $this->db->update(TABLE_USER, array(
            'ba_auto_bid' => 2,
            'ba_auto_bid_order_sn' => '',
            'ba_auto_bid_max_money_single' => 0,
                ), array('ba_id' => $account_id));
        return $this->db->affected_rows() > 0;
    }

    public function card_bind($account_id, $card_No) {
        $this->db->update(TABLE_USER, array('ba_has_bind_card' => 1, 'ba_card_No' => $card_No), array('ba_id' => $account_id, 'ba_has_bind_card' => 2, 'ba_status' => self::STATUS_ACCOUNT_ENABLE));
        return $this->db->affected_rows() > 0;
    }

    public function card_bind_cancel($account_id) {
        $this->db->update(TABLE_USER, array('ba_has_bind_card' => 2), array('ba_id' => $account_id, 'ba_has_bind_card' => 1, 'ba_status' => self::STATUS_ACCOUNT_ENABLE));
        return $this->db->affected_rows() > 0;
    }

    public function add_recharge($data) {
        $param = array();
        $param['user_id'] = trim($data['user_id']);
        $param['money'] = trim($data['money']);
        $param['balance'] = trim($data['balance']);
        $param['remark'] = trim($data['remark']);
        $param['ba_id'] = trim($data['ba_id']);
        $param['ba_channel'] = trim($data['ba_channel']);
        $param['ba_id_No'] = trim($data['ba_id_No']);
        $param['ba_name'] = trim($data['ba_name']);
        $param['ba_mobile'] = trim($data['ba_mobile']);
        $param['ba_card_No'] = trim($data['ba_card_No']);
        $param['ba_tx_Amount'] = trim($data['ba_tx_Amount']);
        $param['nid'] = trim($data['nid']);
        $param['addtime'] = time();
        $this->db->insert(TABLE_ACCOUNT_RECHARGE, $param);
        $id = $this->db->insert_id();
        return $id;
    }

    public function get_recharge_by_nid($nid) {
        $this->db->limit(1);
        $r = $this->db->get_where(TABLE_ACCOUNT_RECHARGE, array('nid' => $nid))->row_array(0);
        return $r;
    }

    public function find($string) {
        $this->load->model('m_user');
        $users = array();
        $ba_id = $this->db->get_where(TABLE_USER, array('user_id' => intval($string)))->row(0)->ba_id;
        if (!$ba_id) {
            $ba_id = $this->db->get_where(TABLE_USER, array('mobile' => trim($string)))->row(0)->ba_id;
        }

        return $ba_id;
    }

    public function find_user_id($string) {
        $this->load->model('m_user');
        $user_id = $this->db->get_where(TABLE_USER, array('mobile' => trim($string)))->row(0)->user_id;
        if (!$user_id) {
            $user_id = $this->db->get_where(TABLE_USER, array('ba_id' => trim($string)))->row(0)->user_id;
            if (!$user_id) {
                $user_id = $this->db->get_where(TABLE_USER, array('user_id' => trim($string)))->row(0)->user_id;
            }
        }

        return $user_id;
    }

}
