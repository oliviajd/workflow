<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of cash
 *
 * @author win7
 */
class cash extends CI_Controller {
    
    const STATUS_VERIFY_INIT = 0;
    const STATUS_VERIFY_SUCCESS = 1;
    const STATUS_VERIFY_FAILED = 2;
    const STATUS_VERIFY_PROCESSING = 3;

    public function __construct() {
        parent::__construct();
        $this->load->model('m_cash');
    }

    public function add() {
    }
    
    public function check() {
        $cash_id = $this->api->in['cash_id'];
        $status = $this->api->in['status'];
        $detail = $this->m_cash->detail($cash_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id == self::STATUS_VERIFY_INIT || $detail->status->id == self::STATUS_VERIFY_PROCESSING) {
            $r_check = $this->m_cash->update($cash_id, $this->api->in);
            $r = $this->m_cash->detail($cash_id);
            if($r_check && $status == self::STATUS_VERIFY_SUCCESS){
                $this->load->model('m_account');
                //资金操作记录
                //提现记录
                $account = $this->m_account->lock($detail->user_id);
                $param = array(
                    'income' => 0,
                    'expend' => $detail->total,
                    'balance' => 0,
                    'balance_cash' => 0,
                    'balance_frost' => 0,
                    'frost' => -$detail->total,
                    'await' => 0,
                );
                $param['user_id'] = intval($detail->user_id);
                $param['type'] = 'cash_success';
                $param['money'] = $detail->total;
                $param['remark'] = '提现成功'.$detail->total.'元';
                $param['to_userid'] = 0;
                $this->m_account->add_log($param);
                $this->m_account->unlock($detail->user_id);
                $account = $this->m_account->lock($detail->user_id);
                //手续费记录
                $param = array(
                    'income' => 0,
                    'expend' => $detail->fee,
                    'balance' => 0,
                    'balance_cash' => 0,
                    'balance_frost' => 0,
                    'frost' => -$detail->fee,
                    'await' => 0,
                );
                $param['user_id'] = intval($detail->user_id);
                $param['type'] = 'cash_fee';
                $param['money'] = $detail->fee;
                $param['remark'] = "提现扣除手续费".$detail->fee."元";
                $param['to_userid'] = 0;
                $this->m_account->add_log($param);
                $this->m_account->unlock($detail->user_id);
                //TODO 用户操作记录
                
                //将该用户其他未审核记录设置为非首次提现
                $condition['is_first'] = 0;
                $this->db->where('user_id',intval($detail->user_id));
                $this->db->where('status <> ',1);
                $this->db->update(TABLE_CASH, $condition);
                //do_log($this->db->last_query());
            }
            elseif($r_check && $status == self::STATUS_VERIFY_FAILED){
                $this->load->model('m_account');
                //资金操作记录
                //提现记录
                $account = $this->m_account->lock($detail->user_id);
                $param = array(
                    'income' => 0,
                    'expend' => 0,
                    'balance_cash' => $detail->total,
                    'balance_frost' => 0,
                    'frost' => -$detail->total,
                    'await' => 0,
                );
                $param['user_id'] = intval($detail->user_id);
                $param['type'] = 'cash_false';
                $param['money'] = $detail->total;
                $param['remark'] = '提现失败'.$detail->total.'元';
                $param['to_userid'] = 0;
                $this->m_account->add_log($param);
                $this->m_account->unlock($detail->user_id);
                
                //TODO 用户操作记录
            }
        }
        
        //do_log($this->db->all_query());
        $this->api->output($r);
    }

    public function update() {
        $cash_id = $this->api->in['cash_id'];
        $detail = $this->m_cash->detail($cash_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        
        $this->m_cash->update($cash_id, $this->api->in);
        $r = $this->m_cash->detail($cash_id);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_borrow->detail($this->api->in['borrow_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function delete() {
    }

    public function lists() {
        $this->load->model('m_user');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['q']) {
            $users = $this->m_user->find($condition['q']);      //包含通过用户名、手机、真实姓名查找用户ID
            if(!empty($users)){
                $condition['user_id'] = $users;
            }
            else{
                $condition['bank_id'] = trim($condition['q']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_cash->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_cash->count($condition);
        $this->api->output($r);
    }

}
