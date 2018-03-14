<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_account
 *
 * @author lsk
 */
class m_account extends CI_Model implements ObjInterface {

    private $_memcache_account_prepare = 100000000;
    private $locks = array();

    public function add_log($data) {
        $user_id = intval($data['user_id']);
        $account = $this->has_locked($user_id);
        if (!$account) {
            return false;
        }
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['type'] = trim($data['type']);
        $param['nid'] = '';
        $param['money'] = $data['money'];
        $param['remark'] = trim($data['remark']);
        $param['to_userid'] = intval($data['to_userid']);

        //红包
        $param['hbag_new'] = intval($data['bouns_money']);
        $param['hbag_old'] = $account->bouns_used;
        $param['hbag'] = $param['hbag_new'] + $param['hbag_old'];

        //提现
        $param['balance_cash_new'] = $data['balance_cash'];
        $param['balance_cash_old'] = $account->balance_cash;
        $param['balance_cash'] = $param['balance_cash_new'] + $param['balance_cash_old'];

        //冻结
        $param['balance_frost_new'] = $data['balance_frost'];
        $param['balance_frost_old'] = $account->balance_frost;
        $param['balance_frost'] = $param['balance_frost_new'] + $param['balance_frost_old'];

        //余额
        $param['balance_new'] = $param['balance_cash_new'] + $param['balance_frost_new'];
        $param['balance_old'] = $account->balance;
        $param['balance'] = $param['balance_new'] + $param['balance_old'];

        //收入
        $param['income_new'] = $data['income'];
        $param['income_old'] = $account->income;
        $param['income'] = $param['income_new'] + $param['income_old'];

        //支出
        $param['expend_new'] = $data['expend'];
        $param['expend_old'] = $account->expend;
        $param['expend'] = $param['expend_new'] + $param['expend_old'];

        //冻结
        $param['frost_new'] = $data['frost'] + intval($data['bouns_money']);
        $param['frost_old'] = $account->frost;
        $param['frost'] = $param['frost_new'] + $param['frost_old'];

        //待收
        $param['await_new'] = $data['await'];
        $param['await_old'] = $account->await;
        $param['await'] = $param['await_new'] + $param['await_old'];

        //总金额
        $param['total_old'] = $account->total;
        $param['total'] = $param['balance'] + $param['frost'] + $param['await'];

        //利息等等..
        $param['capital'] = $data['capital'];
        $param['interest'] = $data['interest'];
        //冗余字段
        $param['borrow_nid'] = $data['borrow_id'];
        $param['tender_id'] = $data['tender_id'];
        $param['recover_id'] = $data['recover_id'];

        //存管

        do_log($data['ba_id']);
        $param['ba_id'] = $data['ba_id'];
        $param['ba_accDate'] = $data['ba_accDate'];
        $param['ba_inpDate'] = $data['ba_inpDate'];
        $param['ba_relDate'] = $data['ba_relDate'];
        $param['ba_inpTime'] = $data['ba_inpTime'];
        $param['ba_traceNo'] = $data['ba_traceNo'];
        $param['ba_tranType'] = $data['ba_tranType'];
        $param['ba_tranTypeMsg'] = $data['ba_tranTypeMsg'];
        $param['ba_orFlag'] = $data['ba_orFlag'];
        $param['ba_txFlag'] = $data['ba_txFlag'];
        $param['ba_orFlag'] = $data['ba_orFlag'];
        $param['ba_currBal'] = $data['ba_currBal'];
        $param['ba_forAccountId'] = $data['ba_forAccountId'];
        $param['ba_type'] = $data['ba_type'];
        if ($data['ba_inpDate']) {    //记录上一次查询存管交易明细的时间
            $this->db->update(TABLE_ACCOUNT, array(
                'ba_inpDate' => $data['ba_inpDate']
                    ), array('user_id' => $user_id));
        }


        if($data['ba_inpTime']){
            $param['addtime'] = $data['addtime'];
        }else{
            $param['addtime'] = time();
        }
            
        $param['addip'] = get_ip();
        $this->db->insert(TABLE_ACCOUNT_LOG, $param);
        $id = $this->db->insert_id();
        $this->db->update(TABLE_ACCOUNT_LOG, array('nid' => $this->_create_log_sn($id)), array('id' => $id));
        //更新资金表
        $this->db->update(TABLE_ACCOUNT, array(
            'income' => $param['income'],
            'expend' => $param['expend'],
            'balance_cash' => $param['balance_cash'],
            'balance_frost' => $param['balance_frost'],
            'balance' => $param['balance'],
            'frost' => $param['frost'],
            'await' => $param['await'],
            'total' => $param['total'],
                ), array('user_id' => $user_id));
        //todo update后修改lock中的account的各项值
//        do_log(array('data'=>$data,'account'=>$account,'param'=>$param),$this->db->all_query());
        return $id;
    }

    public function add($data) {
        $param['user_id'] = intval($data['user_id']);
        $this->db->insert(TABLE_ACCOUNT, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            //已回款本金总额
            $this->db->select('sum(recover_capital) as recover_capital_sum');
            $detail['recover_capital_sum'] = $this->db->get_where(TABLE_BORROW_RECOVER, array('user_id' => $id,'recover_status' => 1))->row(0)->recover_capital_sum;
            //已回款利息总额
            $this->db->select('sum(recover_interest) as recover_interest_sum');
            $detail['recover_interest_sum'] = $this->db->get_where(TABLE_BORROW_RECOVER, array('user_id' => $id,'recover_status' => 1))->row(0)->recover_interest_sum;
            //银行处理中总资金
            $this->db->select('SUM('.TABLE_BORROW_TENDER.'.account) as ba_processing');
            $this->db->join(TABLE_QUEUE_BORROW,TABLE_QUEUE_BORROW.'.borrow_id = '.TABLE_BORROW_TENDER.'.borrow_nid');
            $this->db->where_in(TABLE_QUEUE_BORROW.'.ba_status', array('BA_PAY_START','BA_PAY_SUBMIT','BA_PAY_PASS'));
            $detail['ba_processing'] = sprintf("%.2f",$this->db->get_where(TABLE_BORROW_TENDER, array(TABLE_BORROW_TENDER.'.user_id' => $id))->row(0)->ba_processing);
            do_log('银行处理中总资金');
            do_log($this->db->last_query());
            do_log($detail['ba_processing']);
            //体验金收益
            $detail['experience_interest'] = sprintf("%.2f",$this->db->get_where(TABLE_EXPERIENCE_ACCOUNT, array(TABLE_EXPERIENCE_ACCOUNT.'.user_id' => $id))->row(0)->money);
            
            //待回款本金总额
            $this->db->select('sum(recover_capital) as recover_capital_sum');
            $detail['ready_recover_capital_sum'] = $this->db->get_where(TABLE_BORROW_RECOVER, array('user_id' => $id,'recover_status' => 0))->row(0)->recover_capital_sum;
            //待回款利息总额
            $this->db->select('sum(recover_interest) as recover_interest_sum');
            $detail['ready_recover_interest_sum'] = $this->db->get_where(TABLE_BORROW_RECOVER, array('user_id' => $id,'recover_status' => 0))->row(0)->recover_interest_sum;
            //待回款加息总额
            $this->db->select('sum('.TABLE_BORROW_TENDER.'.coupon_amount) as recover_coupon_amount');
            $this->db->join(TABLE_BORROW_TENDER,TABLE_BORROW_TENDER.'.id = '.TABLE_BORROW_RECOVER.'.tender_id');
            $detail['ready_recover_coupon_amount'] = $this->db->get_where(TABLE_BORROW_RECOVER, array(TABLE_BORROW_RECOVER.'.user_id' => $id,TABLE_BORROW_RECOVER.'.recover_status' => 0))->row(0)->recover_coupon_amount;
            //银行存管迁移资金
            $detail['balance_old'] = $this->db->get_where(TABLE_ACCOUNT_OLD, array(TABLE_ACCOUNT_OLD.'.user_id' => $id))->row(0)->balance;
            $detail['transfer_money'] = $detail['balance_old'];
            return new obj_account($detail);
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
        $rows = $this->db->get_where(TABLE_ACCOUNT)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_account($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_ACCOUNT)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_ACCOUNT, array('user_id' => $id))->row_array();
        //echo $this->db->last_query();
        return empty($detail) ? false : $detail;
    }

    public function lock($user_id) {
        $this->db->query('begin');
        $handle = $this->db->query("SELECT * FROM " . TABLE_ACCOUNT . " WHERE user_id = {$user_id} FOR UPDATE");
        if ($handle === false) {//加锁失败
            $this->db->query('rollback');
            return false;
        }
        $account = $handle->row_array();
        if (empty($account)) {
            $this->db->insert(TABLE_ACCOUNT, array('user_id' => $user_id, 'total' => 0));
            $this->db->query('commit');
            $handle = $this->db->query("SELECT * FROM " . TABLE_ACCOUNT . " WHERE user_id = {$user_id} FOR UPDATE");
            if ($handle === false) {//加锁失败
                $this->db->query('rollback');
                return false;
            }
            $account = $handle->row_array();
        }
        $this->locks[$user_id] = new obj_account($account);
        return $this->locks[$user_id];
    }

    public function has_locked($user_id) {
        return isset($this->locks[$user_id]) ? $this->locks[$user_id] : false;
    }

    public function unlock($user_id, $rollback = false) {
        unset($this->locks[$user_id]);
        if ($rollback) {
            $this->db->query('rollback');
        } else {
            $this->db->query('commit');
        }
    }

    public function decrease($id, $num, $type, $to_user_id, $remarks) {
        $param = array(
            'income' => 0,
            'expend' => 0,
            'balance_cash' => 0,
            'balance_frost' => - $num,
            'frost' => $num,
            'await' => 0,
            'total' => 0,
        );
        $param['user_id'] = intval($id);
        $param['type'] = strtolower($type);
        $param['money'] = $num;
        $param['remark'] = trim($remarks['remark']);
        $param['borrow_id'] = $remarks['borrow_id'];
        $param['tender_id'] = $remarks['tender_id'];
        if (!empty($remarks['bouns'])) {
            $param['bouns_money'] = intval($remarks['bouns']['money']);
        }
        $param['to_userid'] = intval($to_user_id);
        return $this->add_log($param);
    }

    //增大并发数需要增加位数
    private function _create_log_sn($id) {
        $tender_sn_left = date('ymd') . (time() % 86400);
        if ($id > 1000) {
            //方法1,每秒不超过100w的订单数,
            $tender_sn_right = str_pad($id % 1000000, 6, '0', STR_PAD_LEFT);
        } else {
            //方法2,每毫秒不超过1000个订单，修饰订单号
            $str = str_pad(microtime(1) * 1000 % 1000, 3, '0', STR_PAD_LEFT);
            $tender_sn_right = $str . str_pad($id % 1000, 3, '0', STR_PAD_LEFT);
        }
        return $tender_sn_left . $tender_sn_right;
    }
    
    //用户收益详情
    public function get_income($user_id) {
        //已到账项目收益,已到账加息收益
        $this->db->select('sum('.TABLE_BORROW_RECOVER.'.recover_interest_yes) as yes_sum,sum(coupon_amount) as yes_coupon_sum');
        $this->db->join(TABLE_BORROW_TENDER, TABLE_BORROW_TENDER.'.id = '.TABLE_BORROW_RECOVER.'.tender_id');
        $r_1 = $this->db->get_where(TABLE_BORROW_RECOVER,array(TABLE_BORROW_RECOVER.'.user_id' => $user_id,'recover_status' => 1))->row_array(0);
        
        do_log($this->db->last_query());
        //已到账红包收益
        $this->db->select('sum(hbagmoney) as yes_bouns_sum');
        $r_2 = $this->db->get_where(TABLE_BORROW_TENDER,array('user_id' => $user_id))->row_array(0);
        //待收项目收益,待收加息收益
        $this->db->select('sum(recover_interest) as collect_sum,sum(coupon_amount) as collect_coupon_sum');
        $this->db->join(TABLE_BORROW_TENDER, TABLE_BORROW_TENDER.'.id = '.TABLE_BORROW_RECOVER.'.tender_id');
        $r_3 = $this->db->get_where(TABLE_BORROW_RECOVER,array(TABLE_BORROW_RECOVER.'.user_id' => $user_id,'recover_status' => 0))->row_array(0);
        
        //体验金收益
        $this->db->select('money_total as experience_sum');
        $r_4 = $this->db->get_where(TABLE_EXPERIENCE_ACCOUNT,array('user_id' => $user_id))->row_array(0);
        
        $r = array_merge($r_1, $r_2,$r_3,$r_4);
        return $r;
    }
    
    //用户待收金额
    public function get_wait($user_id) {
        //待收项目本金,待收利息,待收加息
        $this->db->select('sum(recover_account_wait) as wait_account_sum,sum(recover_account_interest_wait) as wait_interest_sum,sum(coupon_amount) as wait_coupon_sum');
        $this->db->where('recover_account_wait >',0);
        $r = $this->db->get_where(TABLE_BORROW_TENDER,array('user_id' => $user_id))->row_array(0);
        return $r;
    }

}
