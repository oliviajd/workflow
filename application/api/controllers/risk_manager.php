<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of risk_manager
 *
 * @author win7
 */
class risk_manager extends CI_Controller {

    public function bill_verify() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_borrow');
        $this->load->model('m_risk_stats');
        $bill_id = $this->api->in['finance_bill_id'];
        $detail = $this->m_finance_bill->detail($bill_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->has_online->id == 1) {
            $this->api->output(false, ERR_BILL_IS_ONLINE_NO, ERR_BILL_IS_ONLINE_MSG);
        }
        $this->m_finance_bill->update($bill_id, array('pic' => $this->api->in['pic']));
        $r = $this->m_finance_bill->verify($bill_id, $this->api->in['status'], $this->api->in['remark']);
        if (!$r) {
            $this->api->output(false, ERR_BILL_VERIFY_FAILED_NO, ERR_BILL_VERIFY_FAILED_MSG);
        }
        if ($this->api->in['status'] == 4) {
            $msg = '<div class="timeline-body text-red">融资单审核失败：' . strip_tags($this->api->in['remark']) . '</div>';
        } else if ($this->api->in['status'] == 5) {
            $msg = '<div class="timeline-body text-green">融资单审核通过但需补充资料：' . strip_tags($this->api->in['remark']) . '</div>';
        } else {
            $msg = '<div class="timeline-body text-green">融资单审核通过：' . strip_tags($this->api->in['remark']) . '</div>';
        }
        //添加操作流水
        $this->m_finance_bill->action_add(array(
            'user_id' => $this->api->user()->user_id,
            'user_type' => 2, //用户
            'finance_bill_id' => $detail->finance_bill_id,
            'title' => '风控审核',
            'msg' => $msg,
        ));
        //添加风控审核统计信息
        if ($this->api->in['status'] == 3 || $this->api->in['status'] == 5) {
            if (!$this->m_risk_stats->is_bill_exists($detail->finance_bill_id)) {
                $this->m_risk_stats->add(array(
                    'user_id' => $this->api->user()->user_id,
                    'finance_bill_id' => $detail->finance_bill_id,
                ));
            }
        }
        $this->api->output(true);
    }

}
