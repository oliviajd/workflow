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
class m_finance_bill extends CI_Model implements ObjInterface {

    const STATUS_SUCCESS = 1;
    const STATUS_INIT = 2;
    const STATUS_VERIFY_SUCCESS = 3;
    const STATUS_VERIFY_FAILED = 4;
    const STATUS_VERIFY_ADD = 5; //审核通过不影响后续上标动作，但是需要用户补充照片
    const STATUS_ONLINE_SUCCESS = 3;
    const STATUS_ONLINE_FAILED = 4;
    const STATUS_ONLINE_INIT = 2;

    public function add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['status'] = self::STATUS_INIT;
        $param['money'] = intval($data['money']);
        $param['car'] = trim($data['car']);
        $param['name'] = trim($data['name']);
        $param['id_card'] = trim($data['id_card']);
        $param['car_type'] = intval($data['car_type']);
        $param['finance_account_sub_id'] = intval($data['finance_account_sub_id']);
        $param['company'] = trim($data['company']);
        $param['advance'] = intval($data['advance']);
        $param['payment_certificate'] = trim(parse_url($data['payment_certificate'], PHP_URL_PATH));
        $param['attach'] = trim(parse_url($data['attach'], PHP_URL_PATH));
        $param['paid_time'] = intval($data['paid_time']);
        $param['pay_account'] = trim($data['pay_account']);
        $param['user_remark'] = trim($data['user_remark']);
        $param['version'] = 1;
        $param['create_time'] = time();
        $this->db->insert(TABLE_FINANCE_BILL, $param);
        $id = $this->db->insert_id();
        //$bill_sn = create_order_sn($id, 'FB');
        //生成合同编号
        $count1 = $this->count(array(
            'start_time' => strtotime(date('Y-m-d 00:00:00')),
            'end_id' => $id,
        ));
        $bill_sn = 'JCJR' . date('Ymd') . sprintf("%03d", $count1);
        $this->db->update(TABLE_FINANCE_BILL, array('bill_sn' => $bill_sn), array('id' => $id));
        return $id;
    }

    public function update($id, $data) {
        $detail = $this->_detail($id);
        unset($detail['id']);
        $detail['create_time'] = time();
        //记录日志
        $this->db->insert(TABLE_FINANCE_BILL_HISTORY, $detail);
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'money':
                    $param['money'] = intval($data['money']);
                    break;
                case 'name':
                    $param['name'] = trim($data['name']);
                    break;
                case 'id_card':
                    $param['id_card'] = trim($data['id_card']);
                    break;
                case 'car':
                    $param['car'] = trim($data['car']);
                    break;
                case 'car_type':
                    $param['car_type'] = intval($data['car_type']);
                    break;
                case 'advance':
                    $param['advance'] = trim($data['advance']);
                    break;
                case 'payment_certificate':
                    $param['payment_certificate'] = trim(parse_url($data['payment_certificate'], PHP_URL_PATH));
                    break;
                case 'paid_time':
                    $param['paid_time'] = trim($data['paid_time']);
                    break;
                case 'pic':
                    $param['pic'] = array();
                    $pics = explode(',', $data['pic']);
                    foreach ($pics as $k2 => $v2) {
                        $param['pic'][] = trim(parse_url($v2, PHP_URL_PATH));
                    }
                    $param['pic'] = implode(',', $param['pic']);
                    break;
                case 'attach':
                    $param['attach'] = array();
                    $attaches = explode(',', $data['attach']);
                    foreach ($attaches as $k2 => $v2) {
                        $param['attach'][] = trim(parse_url($v2, PHP_URL_PATH));
                    }
                    $param['attach'] = implode(',', $param['attach']);
                    break;
                case 'user_remark':
                    $param['user_remark'] = trim($data['user_remark']);
                    break;
                case 'finance_account_sub_id':
                    $param['finance_account_sub_id'] = intval($data['finance_account_sub_id']);
                    break;
                case 'company':
                    $param['company'] = trim($data['company']);
                    break;
                case 'pay_account':
                    $param['pay_account'] = trim($data['pay_account']);
                    break;
                case 'has_added':
                    if (in_array(intval($data['has_added']), array(1, 2))) {
                        $param['has_added'] = intval($data['has_added']);
                    }
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->set('version', 'version + 1', false);
        $this->db->update(TABLE_FINANCE_BILL, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $this->load->model('m_user');
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_bill_status($detail['status']);
            $detail['online_status'] = $this->get_bill_online_status($detail['online_status']);
            $detail['has_verified'] = $this->get_verify_status($detail['has_verified']);
            $detail['has_online'] = $this->get_online_status($detail['has_online']);
            $detail['has_paid'] = $this->get_paid_status($detail['has_paid']);
            $detail['has_repaid'] = $this->get_repay_status($detail['has_repaid']);
            $detail['has_full'] = $this->get_full_status($detail['has_full']);
            $detail['has_expired'] = $this->get_expire_status($detail['has_expired']);
            $detail['has_added'] = $this->get_added_status($detail['has_added']);
            $detail['car_type'] = $this->get_car_type($detail['car_type']);
            $detail['user'] = $this->m_user->detail($detail['user_id']);
            $detail['pic'] = explode(',', $detail['pic']);
            $detail['attach'] = explode(',', $detail['attach']);
            return new obj_finance_bill($detail);
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
        $rows = $this->db->get_where(TABLE_FINANCE_BILL)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_bill_status($v['status']);
            $v['online_status'] = $this->get_bill_online_status($v['online_status']);
            $v['has_verified'] = $this->get_verify_status($v['has_verified']);
            $v['has_online'] = $this->get_online_status($v['has_online']);
            $v['has_paid'] = $this->get_paid_status($v['has_paid']);
            $v['has_repaid'] = $this->get_repay_status($v['has_repaid']);
            $v['has_full'] = $this->get_full_status($v['has_full']);
            $v['has_expired'] = $this->get_expire_status($v['has_expired']);
            $v['has_added'] = $this->get_added_status($v['has_added']);
            $v['car_type'] = $this->get_car_type($v['car_type']);
            $v['pic'] = explode(',', $v['pic']);
            $v['attach'] = explode(',', $v['attach']);
            $rows[$k] = new obj_finance_bill($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_FINANCE_BILL)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['online_status']) {
            is_array($condition['online_status']) ? $this->db->where_in('online_status', $condition['online_status']) : $this->db->where('online_status', $condition['online_status']);
        }
        if ($condition['has_verified']) {
            $this->db->where('has_verified', $condition['has_verified']);
        }
        if ($condition['has_online']) {
            $this->db->where('has_online', $condition['has_online']);
        }
        if ($condition['has_paid']) {
            $this->db->where('has_paid', $condition['has_paid']);
        }
        if ($condition['has_repaid']) {
            $this->db->where('has_repaid', $condition['has_repaid']);
        }
        if ($condition['has_full']) {
            $this->db->where('has_full', $condition['has_full']);
        }
        if ($condition['has_expired']) {
            $this->db->where('has_expired', $condition['has_expired']);
        }
        if ($condition['has_added']) {
            $this->db->where('has_added', $condition['has_added']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if (isset($condition['start_time'])) {
            $this->db->where('create_time >=' . intval($condition['start_time']), false, false);
        }
        if (isset($condition['end_time'])) {
            $this->db->where('create_time <=' . intval($condition['end_time']), false, false);
        }
        if (isset($condition['start_id'])) {
            $this->db->where('id >=' . intval($condition['start_id']), false, false);
        }
        if (isset($condition['end_id'])) {
            $this->db->where('id <=' . intval($condition['end_id']), false, false);
        }
        if (isset($condition['borrow_days'])) {
            $this->db->where('borrow_days', $condition['borrow_days']);
        }
        if ($condition['q']) {
            $q = str_replace('\'','\\\'',$condition['q']);
            $q = str_replace('\\','\\\\',$q);
            $q = str_replace('_',"\_",$q);
            $q = str_replace('%',"\%",$q);
            $this->db->where("( borrow_title like '%{$q}%' or bill_sn like '%{$q}%' or `name` like '%{$q}%' )", false, false);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_FINANCE_BILL, array('id' => $id, 'is_delete' => STATUS_NOT_DELETE))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function is_info_exists($info, $id = false) {
        if ($id) {
            $this->db->where('id <> ' . intval($id), false, false);
        }
        $this->db->where('status <> ' . self::STATUS_VERIFY_FAILED, false, false);
        return !!$this->db->get_where(TABLE_FINANCE_BILL, array(
                    'id_card' => $info['id_card'],
                    'car' => trim($info['car']),
                ))->row(0)->id;
    }

    public function get_bill_status($key = false) {
        $data = array(
            self::STATUS_SUCCESS => '募资中',
            self::STATUS_INIT => '审核中',
            self::STATUS_VERIFY_SUCCESS => '风控审核通过',
            self::STATUS_VERIFY_FAILED => '风控审核失败',
            self::STATUS_VERIFY_ADD => '需要补充资料',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'BILL_ERROR', 'text' => '融资单状态错误');
    }

    public function get_bill_online_status($key = false) {
        $data = array(
            self::STATUS_ONLINE_SUCCESS => '上标审核通过',
            self::STATUS_ONLINE_FAILED => '上标审核失败',
            self::STATUS_ONLINE_INIT => '上标审核中',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'BILL_ERROR', 'text' => '融资单状态错误');
    }

    public function get_verify_status($key = false) {
        $data = array(
            1 => '已审核',
            2 => '未审核',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'VERIFY_ERROR', 'text' => '融资单状态错误');
    }

    public function get_online_status($key = false) {
        $data = array(
            1 => '已上标',
            2 => '未上标',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'VERIFY_ERROR', 'text' => '融资单状态错误');
    }

    public function get_paid_status($key = false) {
        $data = array(
            1 => '已打款',
            2 => '未打款',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'VERIFY_ERROR', 'text' => '融资单状态错误');
    }

    public function get_repay_status($key = false) {
        $data = array(
            1 => '已还款',
            2 => '未还款',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'VERIFY_ERROR', 'text' => '融资单状态错误');
    }

    public function get_full_status($key = false) {
        $data = array(
            1 => '已满标',
            2 => '未满标',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'VERIFY_ERROR', 'text' => '融资单状态错误');
    }

    public function get_expire_status($key = false) {
        $data = array(
            1 => '已过期',
            2 => '未过期',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'EXPIRE_ERROR', 'text' => '融资单状态错误');
    }

    public function get_added_status($key = false) {
        $data = array(
            1 => '已补全',
            2 => '未补全',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'EXPIRE_ERROR', 'text' => '融资单状态错误');
    }

    public function get_car_type($key = false) {
        $data = array(
            1 => '新车',
            2 => '二手车',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'CAR_ERROR', 'text' => '融资单状态错误');
    }

    public function verify($id, $status, $remark) {
        switch (intval($status)) {
            case self::STATUS_VERIFY_SUCCESS:
                $this->db->set('has_verified', 1);
                $this->db->set('verify_success_time', time());
                break;
            case self::STATUS_VERIFY_FAILED:
                $this->db->set('has_verified', 2);
                $this->db->set('verify_success_time', 0);
                break;
            case self::STATUS_VERIFY_ADD:
                $this->db->set('has_verified', 1);
                $this->db->set('verify_success_time', time());
                break;
            default :
                return false;
        }
        $this->db->update(TABLE_FINANCE_BILL, array(
            'status' => $status,
            'verify_remark' => trim($remark),
                ), array(
            'id' => $id
        ));
        $r = $this->db->affected_rows() > 0;
        $this->db->update(TABLE_FINANCE_BILL, array(
            'online_status' => 2,
                ), array(
            'id' => $id,
            'has_online' => 2,
        ));
        return $r;
    }

    public function pay_verify($id, $status, $remark) {
        switch (intval($status)) {
            case 1:
                $this->db->set('has_paid', 1);
                $this->db->set('pay_success_time', time());
                break;
            case 2:
                $this->db->set('has_paid', 2);
                $this->db->set('pay_success_time', 0);
                break;
            default :
                return false;
        }
        $this->db->update(TABLE_FINANCE_BILL, array(
            'pay_remark' => trim($remark),
                ), array(
            'id' => $id
        ));
        return $this->db->affected_rows() > 0;
    }

    public function repay_verify($id, $status, $remark) {
        switch (intval($status)) {
            case 1:
                $this->db->set('has_repaid', 1);
                $this->db->set('repay_success_time', time());
                break;
            case 2:
                $this->db->set('has_repaid', 2);
                $this->db->set('repay_success_time', 0);
                break;
            default :
                return false;
        }
        $this->db->update(TABLE_FINANCE_BILL, array(
            'repay_remark' => trim($remark),
                ), array(
            'id' => $id
        ));
        return $this->db->affected_rows() > 0;
    }

    public function reset_status($id) {
        $this->db->update(TABLE_FINANCE_BILL, array(
            'status' => self::STATUS_INIT,
                ), array(
            'id' => $id,
            'status' => self::STATUS_VERIFY_FAILED
        ));
        return $this->db->affected_rows() > 0;
    }

    public function reset_verify_status($id) {
        $this->db->update(TABLE_FINANCE_BILL, array(
            'status' => self::STATUS_ONLINE_INIT,
                ), array(
            'id' => $id,
            'status' => self::STATUS_ONLINE_FAILED
        ));
        return $this->db->affected_rows() > 0;
    }

    public function online($id, $status, $cards, $remark) {
        switch (intval($status)) {
            case self::STATUS_ONLINE_SUCCESS:
                $this->db->set('has_online', 1);
                $this->db->set('online_status', self::STATUS_ONLINE_SUCCESS);
                $this->db->set('online_success_time', time());
                break;
            case self::STATUS_ONLINE_FAILED:
                $this->db->set('has_online', 2);
                $this->db->set('online_status', self::STATUS_ONLINE_FAILED);
                $this->db->set('online_success_time', 0);
                break;
            default :
                return false;
        }
        $this->db->update(TABLE_FINANCE_BILL, array(
            'online_remark' => trim($remark),
            'cards' => trim($cards),
                ), array(
            'id' => $id
        ));
        return $this->db->affected_rows() > 0;
    }

    public function full($id, $status) {
        switch (intval($status)) {
            case 1:
                $this->db->set('has_full', 1);
                $this->db->set('full_success_time', time());
                break;
            case 2:
                $this->db->set('has_full', 2);
                $this->db->set('full_success_time', 0);
                break;
            default :
                return false;
        }
        $this->db->update(TABLE_FINANCE_BILL, array(), array(
            'id' => $id
        ));
        return $this->db->affected_rows() > 0;
    }

    public function set_borrow($id, $borrow) {
        $this->db->where('(borrow_id is null or borrow_id = \'' . $borrow['borrow_id'] . '\')', false, false);
        $this->db->update(TABLE_FINANCE_BILL, array(
            'borrow_id' => trim($borrow['borrow_id']),
            'borrow_title' => trim($borrow['borrow_title']),
            'borrow_days' => intval($borrow['borrow_days']),
                ), array(
            'id' => $id,
        ));
        return $this->db->affected_rows() > 0;
    }

    public function is_set_borrow($id) {
        return !!trim($this->db->get_where(TABLE_FINANCE_BILL, array(
                            'id' => $id,
                        ))->row(0)->borrow_id);
    }

    public function sync_borrow_name($id, $borrow_id, $borrow_title) {
        $this->db->update(TABLE_FINANCE_BILL, array(
            'borrow_title' => trim($borrow['borrow_title']),
                ), array(
            'id' => $id,
            'borrow_id' => $borrow_id,
        ));
        return $this->db->affected_rows() > 0;
    }

    public function action_add($data) {
        $this->load->model('m_user');
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['user_type'] = intval($data['user_type']);
        $user = $this->m_user->detail($param['user_id']);
        $param['realname'] = $user->realname;
        $param['title'] = trim($data['title']);
        $param['msg'] = trim($data['msg']);
        $param['finance_bill_id'] = intval($data['finance_bill_id']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_FINANCE_USER_ACTION, $param);
        $id = $this->db->insert_id();
        return $id;
    }

    public function action_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        var_dump($condition);
        $this->_action_condition($condition);
        $rows = $this->db->get_where(TABLE_FINANCE_USER_ACTION)->result();
        return $rows;
    }

    public function action_count($condition) {
        $this->db->select('count(1) as count');
        $this->_action_condition($condition);
        return $this->db->get_where(TABLE_FINANCE_USER_ACTION)->row(0)->count;
    }

    public function _action_condition($condition) {
        if ($condition['finance_bill_id']) {
            is_array($condition['finance_bill_id']) ? $this->db->where_in('finance_bill_id', $condition['finance_bill_id']) : $this->db->where('finance_bill_id', $condition['finance_bill_id']);
        }
    }

}
