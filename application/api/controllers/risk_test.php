<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of risk_test
 *
 * @author win7
 */
class risk_test extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_risk_test');
    }

    public function add() {
        $this->api->in['user_id'] = $this->api->user()->user_id;
        if ($this->m_risk_test->detail_by_userid($this->api->user()->user_id)) {
            $this->api->output(false, ERR_ITEM_REPEAT_NO, ERR_ITEM_REPEAT_MSG);
        }
        $id = $this->m_risk_test->add($this->api->in);
        if($id){
            $r = $this->m_risk_test->detail($id);
            //增加500积分
            $this->load->model('m_credit');
            if($r->times == 1){
                $inviter_credit = $this->m_credit->increase($r->user_id, 500, array(
                    'type' => 'risk_test',
                    'item_id' => $r->user_id,
                    'remark' => "风险测评奖励",
                ));
            }
        }
        
        $this->api->output($r);
    }

    public function lists() {
    }
    
    public function update() {
        $user_id = $this->api->user()->user_id;
        if (!$this->m_risk_test->detail_by_userid($user_id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $risk_test = $this->m_risk_test->detail_by_userid($user_id);
        if($risk_test->times >= 3){
            $this->api->output(false, ERR_RISK_TEST_TIMES_NO, ERR_RISK_TEST_TIMES_MSG);
        }
        $this->m_risk_test->update($user_id, $this->api->in);
        $r = $this->m_risk_test->detail_by_userid($user_id);
        $this->api->output($r);
    }

    public function delete() {
    }
    
    public function get() {
        $user_id = $this->api->user()->user_id;
        $r = $this->m_risk_test->detail_by_userid($user_id);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
}
