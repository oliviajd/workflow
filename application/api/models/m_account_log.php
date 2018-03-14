<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_account_log
 *
 * @author lsk
 */
class m_account_log extends CI_Model implements ObjInterface{
    
    public function add($data) {}
    
    public function update($id, $data) {}
    
    public function detail($id) {}
    
    public function lists($condition='', $page='', $size='', $order='') {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_ACCOUNT_LOG)->result_array();
        foreach ($rows as $k => $v) {
            $v['realname'] = $this->db->get_where(TABLE_USER,array( 'user_id' => $v['user_id']))->row(0)->ba_realname;
            $v['mobile'] = $this->db->get_where(TABLE_USER,array( 'user_id' => $v['user_id']))->row(0)->mobile;
            $v['ba_type'] = $this->get_ba_type($v['ba_type']);
            $v['sign'] = $this->get_sign($v['type']);
            $v['type'] = $this->get_type($v['type']);
            $rows[$k] = new obj_account_log($v);
        }
        
        return $rows;
    }
    
    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_ACCOUNT_LOG)->row(0)->count;
    }
    
    public function delete($condition) {}
    
    private function _condition($condition) {
        if ($condition['borrow_id']) {
            is_array($condition['borrow_id']) ? $this->db->where_in('borrow_nid', $condition['borrow_id']) : $this->db->where('borrow_nid', $condition['borrow_id']);
        }
        if ($condition['type']) {
            is_array($condition['type']) ? $this->db->where_in('type', $condition['type']) : $this->db->where('type', $condition['type']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['starttime']) {
            $this->db->where('addtime >= ', $condition['starttime']);
        }
        if ($condition['endtime']) {
            $this->db->where('addtime < ', $condition['endtime']);
        }
        if ($condition['recharge_flag']) {
            $this->db->where_in('ba_type', array('1','5'));
        }
        if ($condition['ba_type']) {
            is_array($condition['ba_type']) ? $this->db->where_in('ba_type', $condition['ba_type']) : $this->db->where('ba_type', $condition['ba_type']);
        }
        if ($condition['ba_id']) {
            is_array($condition['ba_id']) ? $this->db->where_in('ba_id', $condition['ba_id']) : $this->db->where('ba_id', $condition['ba_id']);
        }
    }
    
    private function _detail($id) {}
    
    public function get($condition,$order) {
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $this->db->limit(1);
        $r = $this->db->get_where(TABLE_ACCOUNT_LOG)->row_array();
        return $r;
    }
    
    public function get_by_traceNo($traceNo) {
        $this->db->limit(1);
        $r = $this->db->get_where(TABLE_ACCOUNT_LOG,array('ba_traceNo' => $traceNo))->row_array();
        return $r;
    }
    
    public function get_ba_type($key = false) {
        $data = array(
            1 => '充值',
            2 => '收益',
            3 => '红包',
            4 => '手续费',
            5 => '转入费',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => ERR_ACCOUNT_LOG_TYPE_NO, 'text' => ERR_ACCOUNT_LOG_TYPE_MSG);
    }
    
    public function get_type($key = false) {
        $data = array(
            'recharge' => '充值',
            'cash_frost' => '提现冻结',
            'tender_success' => '投资成功',
            'borrow_success' => '借款成功',
            'tender_success_frost' => '投资金额解冻',
            'tender_repay_yes' => '投资收到还款',
            'vip_success' => 'Vip会员费',
            'borrow_repay' => '还款',
            'tender' => '投标冻结',
            'fengxianchi_tender' => '投资风险金',
            'fengxianchi_borrow' => '借款风险金',
            'borrow_success_manage' => '成交费',
            'borrow_success_account' => '账户管理费',
            'borrow_change_sell_fee' => '债权转让成交费',
            'borrow_change_sell' => '债权转让成功',
            'cash_fee' => '提现手续费',
            'recharge_fee' => '充值手续费',
            'borrow_repay_late' => '逾期罚息',
            'cash' => '申请提现',
            'tender_spread' => '投资推广',
            'borrow_spread' => '借款推广',
            'system_repayment' => '网站垫付',
            'tender_late_repay_yes' => '客户逾期还款罚息收入',
            'fengxianchi' => '利息风险金',
            'borrow_change_buy' => '债权购买',
            'borrow_change_buy_fee' => '债权购买手续费',
            'tender_false' => '所投资借款标复审失败',
            'fengxianchi_dianfu' => '逾期垫付扣除',
            'realname_fee' => '实名认证费',
            'borrow_advance_repay' => '借款提前还款',
            'tender_advance_repay_yes' => '投资提前还款',
            'tender_advance_repay_interest' => '投资提前还款违约金收入',
            'borrow_interest_advance_repay' => '提前还款扣除违约金',
            'cash_success' => '提现成功',
            'cash_false' => '提现失败',
            'cash_cancel' => '取消提现',
            'tender_user_cancel' => '投资撤回',
            'web_daicha' => '网站信用报告代查费',
            'tender_spread_add' => '投资推广费',
            'borrow_spread_add' => '借款推广费',
            'online_recharge' => '在线充值',
            'realname_fee' => '实名认证费',
            'edu_fee' => '学历认证费',
            'tender_award_add' => '投标奖励',
            'recharge_jiangli' => '线下充值奖励',
            'borrow_award_lower' => '扣除借款奖励',
            'change_add' => '添加资金变动',
            'change_lessen' => '减少资金变动',
            'llcz_charge' => '话费充值冻结',
            'llcz_success' => '话费充值成功',
            'llcz_failed' => '话费充值失败',
            'srjl' => '生日奖励',
            'hdjl' => '活动加息',
            'xjjl' => '现金红包',
            'tender_success_coupon' => '投标加息成功',
            'tender_repay_yes_coupon' => '投标加息回款',
            'experience_transfer' => '收到体验金收益'
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 0, 'text' => '未知类型');
    }
    
    public function get_sign($key) {
        $data = array(
            'recharge' => '+',
            'cash_frost' => '',
            'tender_success' => '-',
            'borrow_success' => '+',
            'tender_success_frost' => '',
            'tender_repay_yes' => '+',
            'vip_success' => '-',
            'borrow_repay' => '-',
            'tender' => '',
            'fengxianchi_tender' => '-',
            'fengxianchi_borrow' => '-',
            'borrow_success_manage' => '-',
            'borrow_success_account' => '-',
            'borrow_change_sell_fee' => '-',
            'borrow_change_sell' => '+',
            'cash_fee' => '-',
            'recharge_fee' => '-',
            'borrow_repay_late' => '-',
            'cash' => '',
            'tender_spread' => '-',
            'borrow_spread' => '-',
            'system_repayment' => '-',
            'tender_late_repay_yes' => '+',
            'fengxianchi' => '',
            'borrow_change_buy' => '-',
            'borrow_change_buy_fee' => '-',
            'tender_false' => '+',
            'fengxianchi_dianfu' => '-',
            'realname_fee' => '-',
            'borrow_advance_repay' => '-',
            'tender_advance_repay_yes' => '+',
            'tender_advance_repay_interest' => '',
            'borrow_interest_advance_repay' => '',
            'cash_success' => '-',
            'cash_false' => '',
            'cash_cancel' => '',
            'tender_user_cancel' => '+',
            'web_daicha' => '-',
            'tender_spread_add' => '-',
            'borrow_spread_add' => '-',
            'online_recharge' => '+',
            'realname_fee' => '-',
            'edu_fee' => '-',
            'tender_award_add' => '+',
            'recharge_jiangli' => '+',
            'borrow_award_lower' => '+',
            'change_add' => '+',
            'change_lessen' => '-',
            'llcz_charge' => '',
            'llcz_success' => '-',
            'llcz_failed' => '',
            'srjl' => '+',
            'hdjl' => '+',
            'xjjl' => '+',
            'tender_success_coupon' => '',
            'tender_repay_yes_coupon' => '+',
            'experience_transfer' => '+'
        );
        return isset($data[$key]) ? $data[$key] : '';
    }

}
