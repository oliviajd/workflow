<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of coupon
 *
 * @author win7
 */
class coupon extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_coupon');
    }

    public function get() {
        $r = $this->m_coupon->detail($this->api->in['coupon_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function add() {
        $param = $this->api->in;
        $param['admin_user_id'] = $this->api->user()->user_id;

        $coupon_id = $this->m_coupon->add($param);
        $detail = $this->m_coupon->detail($coupon_id);
        $this->api->output($detail);
    }

    public function update() {
        $coupon_id = $this->api->in['coupon_id'];
        $param = $this->api->in;

        $old = $this->m_coupon->detail($coupon_id);
        if (empty($old)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_coupon->update($coupon_id, $param);
        $detail = $this->m_coupon->detail($coupon_id);
        $this->api->output($detail);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $condition['status'] = m_coupon::STATUS_ON;
        $r['rows'] = $this->m_coupon->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_coupon->count($condition);
        $this->api->output($r);
    }

    public function lists_admin() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_coupon->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_coupon->count($condition);
        $this->api->output($r);
    }

    public function delete() {
        $detail = $this->m_coupon->detail($this->api->in['coupon_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $r = $this->m_coupon->delete($this->api->in['coupon_id']);
        $this->api->output($r);
    }

    public function send() {
        $detail = $this->m_coupon->detail($this->api->in['coupon_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //检查库存
        if ($detail->check_store == 1 && $detail->store < 1) {
            $this->api->output(false, ERR_COUPON_OUT_OF_STORE_NO, ERR_COUPON_OUT_OF_STORE_MSG);
        }
        $is_sent = $this->m_coupon->is_sent($this->api->in['coupon_id'], $this->api->in['user_id']);
        if ($is_sent) {
            $this->api->output(false, ERR_COUPON_HAS_SENT_NO, ERR_COUPON_HAS_SENT_MSG);
        }
        //减库存
        if ($detail->check_store == 1) {
            if (!$this->m_coupon->decrease($this->api->in['coupon_id'])) {
                $this->api->output(false, ERR_COUPON_OUT_OF_STORE_NO, ERR_COUPON_OUT_OF_STORE_MSG);
            }
        }
        $cu_id = $this->m_coupon->send_to_user($this->api->in['coupon_id'], $this->api->in['user_id'], $this->api->in['remark']);
        $r = $this->m_coupon->user_detail($cu_id);
        $this->api->output($r);
    }
    
    public function send_lists_admin() {
        $this->load->model('m_user');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($this->api->in['user']) {
            $user_ids = $this->m_user->find($this->api->in['user']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_coupon->user_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_coupon->user_count($condition);
        $this->api->output($r);
    }

    public function use_lock() {
        $detail = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user_id != $this->api->in['user_id']) {
            $this->api->output(false, ERR_COUPON_NOT_MATCH_OWNER_NO, ERR_COUPON_NOT_MATCH_OWNER_MSG);
        }
        switch ($detail->status->id) {
            case m_coupon::STATUS_USER_USED:
                $this->api->output(false, ERR_COUPON_USED_NO, ERR_COUPON_USED_MSG);
                break;
            case m_coupon::STATUS_USER_EXPIRE:
                $this->api->output(false, ERR_COUPON_EXPIRE_TIME_LIMIT_NO, ERR_COUPON_EXPIRE_TIME_LIMIT_MSG);
                break;
            case m_coupon::STATUS_USER_CLOSED:
                $this->api->output(false, ERR_COUPON_CLOSED_NO, ERR_COUPON_CLOSED_MSG);
                break;
        }
        if ($detail->use_times && $detail->unuse_times < 1) {
            $this->api->output(false, ERR_COUPON_NO_USE_TIMES_NO, ERR_COUPON_NO_USE_TIMES_MSG);
        }
        if ($detail->enable_time > 0 && time() < $detail->enable_time) {
            $this->api->output(false, ERR_COUPON_ON_TIME_LIMIT_NO, ERR_COUPON_ON_TIME_LIMIT_MSG);
        }
        if ($detail->disable_time > 0 && time() > $detail->disable_time) {
            $this->api->output(false, ERR_COUPON_OFF_TIME_LIMIT_NO, ERR_COUPON_OFF_TIME_LIMIT_MSG);
        }
        if ($detail->expire_time > 0 && time() > $detail->expire_time) {
            $this->api->output(false, ERR_COUPON_EXPIRE_TIME_LIMIT_NO, ERR_COUPON_EXPIRE_TIME_LIMIT_MSG);
        }
        if ($detail->limit_upper_money > 0 && $this->api->in['money'] > $detail->limit_upper_money) {
            $this->api->output(false, ERR_COUPON_UPPER_MONEY_LIMIT_NO, ERR_COUPON_UPPER_MONEY_LIMIT_MSG);
        }
        if ($detail->limit_lower_money > 0 && $this->api->in['money'] < $detail->limit_lower_money) {
            $this->api->output(false, ERR_COUPON_LOWER_MONEY_LIMIT_NO, ERR_COUPON_LOWER_MONEY_LIMIT_MSG);
        }
        $lock_id = $this->m_coupon->use_lock($this->api->in['coupon_user_id']);
        $this->api->output(array('lock_id' => $lock_id));
    }

    public function use_unlock() {
        $detail = $this->m_coupon->use_detail($this->api->in['lock_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $r = $this->m_coupon->use_unlock($this->api->in['lock_id']);
        $this->api->output($r);
    }

    public function make_use() {
        $this->load->model('m_borrow');
        $use_detail = $this->m_coupon->use_detail($this->api->in['lock_id']);
        if (empty($use_detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, "[LOCK:{$this->api->in['lock_id']}]" . ERR_ITEM_NOT_EXISTS_MSG);
        }
        //检查标的投资记录是否存在
        $tender = $this->m_borrow->tender_detail($this->api->in['use_for']);
        if (empty($tender)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, "[TENDER:{$this->api->in['use_for']}]" . ERR_ITEM_NOT_EXISTS_MSG);
        }
        //检查加息券用户ID和使用者是否匹配
        if ($tender->user->user_id != $use_detail->user_id) {
            $this->api->output(false, ERR_COUPON_NOT_MATCH_OWNER_NO, ERR_COUPON_NOT_MATCH_OWNER_MSG);
        }
        
        $borrow = $this->m_borrow->detail($tender->borrow_id);
        $r = $this->m_coupon->make_use($this->api->in['lock_id'], $this->api->in['use_for'], $borrow->title);
        if (!$r) {
            $this->api->output(false, ERR_COUPON_NO_USE_TIMES_NO, ERR_COUPON_NO_USE_TIMES_MSG);
        } else {
            $this->api->output($r);
        }
    }
    
    public function user_close() {
        $r = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
        if ($r) {
            $r = $this->m_coupon->user_close($this->api->in['coupon_user_id']);
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    public function user_open() {
        $r = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
        if ($r) {
            $r = $this->m_coupon->user_open($this->api->in['coupon_user_id']);
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    public function user_auto_use() {
        $detail = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $r = $this->m_coupon->user_auto_use($this->api->in['coupon_user_id']);
        $this->api->output($r);
    }
    
    public function user_auto_use_cancel() {
        $detail = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $r = $this->m_coupon->user_auto_use_cancel($this->api->in['coupon_user_id']);
        $this->api->output($r);
    }

    public function user_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_coupon->user_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_coupon->user_count($condition);
        $this->api->output($r);
    }

    public function user_use_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_coupon->use_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_coupon->use_count($condition);
        $this->api->output($r);
    }

}
