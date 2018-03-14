<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of finance
 *
 * @author win7
 */
class finance extends CI_Controller {

    public function bill_add() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_finance_account');
        $this->load->model('m_finance_account_sub');

        $user_id = $this->api->user()->user_id;
        $account = $this->m_finance_account->detail($user_id);
        if (empty($account)) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
//        //todo 修改错误提示信息，请完善资料
//        if (empty($account->company)) {
//            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
//        }
        if ($this->api->in['money'] / 100 % 100 !== 0) {
            $this->api->output(false, ERR_MONEY_FORMAT_100_NO, ERR_MONEY_FORMAT_100_MSG);
        }
        if (!isIdCard($this->api->in['id_card'])) {
            $this->api->output(false, ERR_ID_CARD_FORMAT_NO, ERR_ID_CARD_FORMAT_MSG);
        }
        $account_sub = $this->m_finance_account_sub->detail($this->api->in['finance_account_sub_id']);
        if (empty($account_sub)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $param = $this->api->in;
        $param['user_id'] = $user_id;

        $param['company'] = $account_sub->company;
        $param['pay_account'] = "{$account_sub->name}  {$account_sub->bank}  {$account_sub->bank_card}";

        if ($this->m_finance_bill->is_info_exists($param, false)) {
            $this->api->output(false, ERR_ITEM_REPEAT_NO, ERR_ITEM_REPEAT_MSG);
        }

        $bill_id = $this->m_finance_bill->add($param);
        $r = $this->m_finance_bill->detail($bill_id);
        //添加操作流水
        $this->m_finance_bill->action_add(array(
            'user_id' => $user_id,
            'user_type' => 1, //用户
            'finance_bill_id' => $r->finance_bill_id,
            'title' => '提交融资单',
            'msg' => '<div class="timeline-body text-yellow">客户' . $r->name . '购买' . $r->car . '汽车，实际贷款金额' . floor($r->money / 100) . '元，实际垫付给经销商' . $r->company . '按揭款 ' . floor($r->advance / 1) . '元</div>'
        ));
        $this->api->output($r);
    }

    public function bill_update() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_finance_account');
        $this->load->model('m_finance_account_sub');

        $user_id = $this->api->user()->user_id;
        $bill_id = $this->api->in['finance_bill_id'];
        $detail = $this->m_finance_bill->detail($bill_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
//        if ($detail->user->user_id != $user_id) {
//            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
//        }
        if (!in_array($detail->status->id, array(m_finance_bill::STATUS_INIT, m_finance_bill::STATUS_VERIFY_FAILED, m_finance_bill::STATUS_VERIFY_ADD))) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($detail->has_expired->id == 1) {
            $this->api->output(false, ERR_BILL_EXPIRED_NO, ERR_BILL_EXPIRED_MSG);
        }
        $account = $this->m_finance_account->detail($user_id);
        if (empty($account)) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($this->api->in['money'] / 100 % 100 !== 0) {
            $this->api->output(false, ERR_MONEY_FORMAT_100_NO, ERR_MONEY_FORMAT_100_MSG);
        }
        if ($this->api->in['id_card'] && !isIdCard($this->api->in['id_card'])) {
            $this->api->output(false, ERR_ID_CARD_FORMAT_NO, ERR_ID_CARD_FORMAT_MSG);
        }
        
        $param = $this->api->in;
        $param['user_id'] = $user_id;
        
        if ($this->api->in['finance_account_sub_id']) {
            $account_sub = $this->m_finance_account_sub->detail($this->api->in['finance_account_sub_id']);
            if (empty($account_sub)) {
                $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
            }
            $param['company'] = $account_sub->company;
            $param['pay_account'] = "{$account_sub->name}  {$account_sub->bank}  {$account_sub->bank_card}";
        }
        
        //资料补全
        if ($param['has_added']) {
            if ($detail->status->id != m_finance_bill::STATUS_VERIFY_ADD) {
                unset($param['has_added']);
            }
        }

        if ($this->m_finance_bill->is_info_exists($param, $bill_id)) {
            $this->api->output(false, ERR_ITEM_REPEAT_NO, ERR_ITEM_REPEAT_MSG);
        }
        $update = array();
        if ($detail->status->id == m_finance_bill::STATUS_VERIFY_ADD) {
            $update = array('attach' => $param['attach']);
        } else {
            $update = $param;
        }
        $this->m_finance_bill->update($bill_id, $param);
        //如果审核失败，重置审核状态
        if ($detail->status->id == m_finance_bill::STATUS_ONLINE_FAILED) {
            $this->m_finance_bill->reset_status($bill_id);
        }
        $r = $this->m_finance_bill->detail($bill_id);
        $msg[] = '<div class="timeline-body">';
        if ($detail->money != $r->money) {
            $msg[] = "<p>实际贷款金额由<span class='text-yellow'>" . floor($detail->money / 100) . "</span>修改为<span class='text-red'>" . floor($r->money / 100) . "</span></p>";
        }
        if ($detail->name != $r->name) {
            $msg[] = "<p>客户姓名由<span class='text-yellow'>{$detail->name}</span>修改为<span class='text-red'>{$r->name}</span></p>";
        }
        if ($detail->id_card != $r->id_card) {
            $msg[] = "<p>客户身份证由<span class='text-yellow'>{$detail->id_card}</span>修改为<span class='text-red'>{$r->id_card}</span></p>";
        }
        if ($detail->car != $r->car) {
            $msg[] = "<p>汽车型号由<span class='text-yellow'>{$detail->car}</span>修改为<span class='text-red'>{$r->car}</span></p>";
        }
        if ($detail->car_type->id != $r->car_type->id) {
            $msg[] = "<p>汽车类型由<span class='text-yellow'>{$detail->car_type->text}</span>修改为<span class='text-red'>{$r->car_type->text}</span></p>";
        }
        if ($detail->company != $r->company) {
            $msg[] = "<p>经销商由<span class='text-yellow'>{$detail->company}</span>修改为<span class='text-red'>{$r->company}</span></p>";
        }
        if ($detail->pay_account != $r->pay_account) {
            $msg[] = "<p>打款账户由<span class='text-yellow'>{$detail->pay_account}</span>修改为<span class='text-red'>{$r->pay_account}</span></p>";
        }
        if ($detail->advance != $r->advance) {
            $msg[] = "<p>垫付按揭款由<span class='text-yellow'>" . floor($detail->advance / 1) . "</span>修改为<span class='text-red'>" . floor($r->advance / 1) . "</span></p>";
        }
        if ($detail->paid_time != $r->paid_time) {
            $msg[] = "<p>打款时间由<span class='text-yellow'>" . floor($detail->advance / 1) . "</span>修改为<span class='text-red'>" . floor($r->advance / 1) . "</span></p>";
        }
        if ($detail->payment_certificate != $r->payment_certificate) {
            $msg[] = "<p><span class='text-red'>上传了新的打款凭证</span></p>";
        }
        if ($detail->attach != $r->attach) {
            $msg[] = "<p><span class='text-red'>上传了新的用户资料</span></p>";
        }
        if ($detail->user_remark != $r->user_remark) {
            $msg[] = "<p>资料完整情况说明<span class='text-yellow'>{$detail->user_remark}</span>修改为<span class='text-red'>{$r->user_remark}</span></p>";
        }
        if (count($msg) == 1) {
            $msg[] = "<p><span class='text-yellow'>未做任何修改</span></p>";
        }
        $msg[] = '</div>';
        //添加操作流水
        $this->m_finance_bill->action_add(array(
            'user_id' => $user_id,
            'user_type' => 1, //用户
            'finance_bill_id' => $r->finance_bill_id,
            'title' => '修改融资单',
            'msg' => implode('', $msg),
        ));
        $this->api->output($r);
    }

    public function bill_lists() {
        $this->load->model('m_finance_bill');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order_by']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order_by'];
        }
        //$condition['user_id'] = $this->api->user()->user_id;
        $r['rows'] = $this->m_finance_bill->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_finance_bill->count($condition);
        $this->api->output($r);
    }

    public function bill_lists_admin() {
        $this->load->model('m_finance_bill');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order_by']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order_by'];
        }
        $r['rows'] = $this->m_finance_bill->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_finance_bill->count($condition);
        $this->api->output($r);
    }

    public function bill_get() {
        $this->load->model('m_finance_bill');
        $user_id = $this->api->user()->user_id;
        $detail = $this->m_finance_bill->detail($this->api->in['finance_bill_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
//        if ($detail->user->user_id != $user_id) {
//            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
//        }
        $this->api->output($detail);
    }

    public function bill_get_admin() {
        $this->load->model('m_finance_bill');
        $user_id = $this->api->user()->user_id;
        $detail = $this->m_finance_bill->detail($this->api->in['finance_bill_id']);
        do_log($this->db->all_query());
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->api->output($detail);
    }

    public function account_update() {
        $this->load->model('m_finance_account');
        $user_id = $this->api->user()->user_id;
        $detail = $this->m_finance_account->detail($user_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id == m_finance_account::STATUS_DISABLE) {
            $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
        }
        $this->m_finance_account->update($user_id, $this->api->in);
        $r = $this->m_finance_account->detail($user_id);
        $this->api->output($r);
    }

    public function account_get() {
        $this->load->model('m_finance_account');
        $user_id = $this->api->user()->user_id;
        $detail = $this->m_finance_account->detail($user_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id == m_finance_account::STATUS_DISABLE) {
            $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
        }
        $this->api->output($detail);
    }

    public function bill_pay_verify() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_borrow');
        $bill_id = $this->api->in['finance_bill_id'];
        $detail = $this->m_finance_bill->detail($bill_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->has_paid->id == 1) {
            $this->api->output(false, ERR_BILL_IS_ONLINE_NO, ERR_BILL_IS_ONLINE_MSG);
        }
        $r = $this->m_finance_bill->pay_verify($bill_id, $this->api->in['status'], $this->api->in['remark']);
        if (!$r) {
            $this->api->output(false, ERR_BILL_VERIFY_FAILED_NO, ERR_BILL_VERIFY_FAILED_MSG);
        }
        //添加操作流水
        $this->m_finance_bill->action_add(array(
            'user_id' => $this->api->user()->user_id,
            'user_type' => 5, //用户
            'finance_bill_id' => $detail->finance_bill_id,
            'title' => '财务打款',
            'msg' => ''
        ));
        $this->api->output(true);
    }

    public function bill_repay_verify() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_borrow');
        $bill_id = $this->api->in['finance_bill_id'];
        $detail = $this->m_finance_bill->detail($bill_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->has_repaid->id == 1) {
            $this->api->output(false, ERR_BILL_IS_ONLINE_NO, ERR_BILL_IS_ONLINE_MSG);
        }
        $r = $this->m_finance_bill->repay_verify($bill_id, $this->api->in['status'], $this->api->in['remark']);
        if (!$r) {
            $this->api->output(false, ERR_BILL_VERIFY_FAILED_NO, ERR_BILL_VERIFY_FAILED_MSG);
        }
        //添加操作流水
        $this->m_finance_bill->action_add(array(
            'user_id' => $this->api->user()->user_id,
            'user_type' => 5, //财务
            'finance_bill_id' => $detail->finance_bill_id,
            'title' => '收到还款',
            'msg' => ''
        ));
        $this->api->output(true);
    }

    public function bill_action_lists() {
        $this->load->model('m_finance_bill');
        $bill_id = $this->api->in['finance_bill_id'];
        $detail = $this->m_finance_bill->detail($bill_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }

        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id asc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_finance_bill->action_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_finance_bill->action_count($condition);
        $this->api->output($r);
    }

    public function bill_action_lists_admin() {
        $this->load->model('m_finance_bill');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id asc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_finance_bill->action_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_finance_bill->action_count($condition);
        $this->api->output($r);
    }
    
    public function account_sub_add() {
        $this->load->model('m_finance_account');
        $this->load->model('m_finance_account_sub');
        $user_id = $this->api->user()->user_id;
        $detail = $this->m_finance_account->detail($user_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id == m_finance_account::STATUS_DISABLE) {
            $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
        }
        $param = $this->api->in;
        $param['user_id'] = $user_id;
        $id = $this->m_finance_account_sub->add($param);
        $r = $this->m_finance_account_sub->detail($id);
        $this->api->output($r);
    }
    
    public function account_sub_update() {
        $this->load->model('m_finance_account');
        $this->load->model('m_finance_account_sub');
        $user_id = $this->api->user()->user_id;
        $account_sub_id = $this->api->in['finance_account_sub_id'];
        $detail = $this->m_finance_account_sub->detail($account_sub_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($detail->status->id == m_finance_account_sub::STATUS_DISABLE) {
            $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
        }
        $this->m_finance_account_sub->update($account_sub_id, $this->api->in);
        $r = $this->m_finance_account_sub->detail($account_sub_id);
        $this->api->output($r);
    }

    public function account_sub_get() {
        $this->load->model('m_finance_account_sub');
        $user_id = $this->api->user()->user_id;
        $account_sub_id = $this->api->in['finance_account_sub_id'];
        $detail = $this->m_finance_account_sub->detail($account_sub_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($detail->status->id == m_finance_account_sub::STATUS_DISABLE) {
            $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
        }
        $this->api->output($detail);
    }
    
    public function account_sub_lists() {
        $this->load->model('m_finance_account_sub');

        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id asc';
        } else {
            $order = $this->api->in['order'];
        }
        $condition['user_id'] = $this->api->user()->user_id;
        $r['rows'] = $this->m_finance_account_sub->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_finance_account_sub->count($condition);
        $this->api->output($r);
    }
    
    public function account_sub_delete() {
        $this->load->model('m_finance_account');
        $this->load->model('m_finance_account_sub');
        $user_id = $this->api->user()->user_id;
        $account_sub_id = $this->api->in['finance_account_sub_id'];
        $detail = $this->m_finance_account_sub->detail($account_sub_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($detail->status->id == m_finance_account_sub::STATUS_DISABLE) {
            $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
        }
        $r = $this->m_finance_account_sub->delete($account_sub_id);
        $this->api->output($r);
    }

}
