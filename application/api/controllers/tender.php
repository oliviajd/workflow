<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tender
 *
 * @author win7
 */
class tender extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_borrow_tender');
    }

    public function add() {
        //关闭数据库报错功能，以方便捕获异常
        $this->db->db_debug = FALSE;
        $this->load->library('cache_memcache');
//        $this->cache_memcache->set('TEST_1205',0);
//        exit;
        //避免网络不好时用户断开连接后脚本中断
        ignore_user_abort();
        set_time_limit(90);
        $this->load->model('m_user');
        $this->load->model('m_bouns');
        $this->load->model('m_coupon');
        $this->load->model('m_borrow');
        $this->load->model('m_account');
        $this->load->library('lock');
        $this->load->library('borrow_process');

        $user_id = $this->api->user()->user_id;
        $borrow_id = $this->api->in['borrow_id'];
        $money = $this->api->in['money'];
        //看压力测试情况决定是否锁定投标动作
//        $lock = "TENDER_{$user_id}_{$borrow_id}";
//        if (!$this->lock->get($lock,5)) {
//            $this->_output(false, ERR_TENDER_TOO_FAST_NO, ERR_TENDER_TOO_FAST_MSG.',请稍候5秒钟');
//        }
        //检查标是否已同步银行存管
        $ba_borrow_id = $this->m_borrow->ba_id($borrow_id);
        if (empty($ba_borrow_id)) {
            $this->_output(false, ERR_BORROW_DISABLE_NO, ERR_BORROW_DISABLE_MSG, true);
        }
        //检查用户是否可用
        $user = $this->m_user->detail($user_id);
        if ($user->status->id != m_user::STATUS_USER_ENABLE) {
            $this->_output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG, true);
        }
        //检查用户是否已经开通存管
        if ($user->bank_account->status->id != 1) {
            $this->_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG, true);
        }
        //检查用户是否已经设置自动投标
        if ($user->bank_account->auto_bid->id != 1) {
            $this->_output(false, ERR_BA_AUTO_BID_NOT_SET_NO, ERR_BA_AUTO_BID_NOT_SET_MSG, true);
        }
        $borrow = $this->m_borrow->detail($borrow_id);
        if (empty($borrow)) {
            $this->_output(false, ERR_ITEM_NOT_EXISTS_NO, "BORROW[{$borrow_id}]" . ERR_ITEM_NOT_EXISTS_MSG, true);
        }
        if ($borrow->status->id == m_borrow::STATUS_VERIFY_FULL) {
            $this->_output(false, ERR_BORROW_HAS_FULL_NO, ERR_BORROW_HAS_FULL_MSG, true);
        }
        if ($borrow->status->id != m_borrow::STATUS_VERIFY_ONLINE) {
            $this->_output(false, ERR_BORROW_DISABLE_NO, ERR_BORROW_DISABLE_MSG, true);
        }
        if ($borrow->limit_on_time > 0 && $borrow->limit_on_time > time()) {
            $this->_output(false, ERR_BORROW_ON_TIME_LIMIT_NO, ERR_BORROW_ON_TIME_LIMIT_MSG, true);
        }
        if ($borrow->expire > 0 && max($borrow->limit_on_time, $borrow->create_time) + $borrow->expire < time()) {
            $this->_output(false, ERR_BORROW_EXPIRE_NO, ERR_BORROW_EXPIRE_MSG, true);
        }
        if ($borrow->limit_upper_money > 0 && $borrow->limit_upper_money < $money) {
            $this->_output(false, ERR_TENDER_UPPER_MONEY_LIMIT_NO, ERR_TENDER_UPPER_MONEY_LIMIT_MSG, true);
        }
        if ($borrow->limit_lower_money > 0 && $borrow->limit_lower_money > $money) {
            $this->_output(false, ERR_TENDER_LOWER_MONEY_LIMIT_NO, ERR_TENDER_LOWER_MONEY_LIMIT_MSG, true);
        }
        if ($borrow->is_for_new_comer == 1 && $this->m_borrow->has_tendered($user_id)) {
            $this->_output(false, ERR_BORROW_FOR_NEW_COMMER_NO, ERR_BORROW_FOR_NEW_COMMER_MSG, true);
        }
        if ($borrow->is_for_single == 1 && $borrow->money_total != $money) {
            $this->_output(false, ERR_BORROW_FOR_SINGLE_NO, ERR_BORROW_FOR_SINGLE_MSG, true);
        }
        if ($borrow->is_for_single == 1 && $this->api->in['coupon_user_id'] > 0) {
            $this->_output(false, ERR_BORROW_NO_COUPON_NO, ERR_BORROW_NO_COUPON_MSG, true);
        }
        if ($this->api->in['money'] % 100 !== 0) {
            $this->_output(false, ERR_TENDER_MONEY_FORMAT_100_NO, ERR_TENDER_MONEY_FORMAT_100_MSG, true);
        }
        if ($this->api->in['money'] < 100) {
            $this->_output(false, ERR_TENDER_MONEY_LOWER_LIMIT_NO, ERR_TENDER_MONEY_LOWER_LIMIT_MSG . "[{$this->api->in['money']}]", true);
        }
        if ($this->api->in['money'] > $borrow->money_total) {
            $this->_output(false, ERR_TENDER_MONEY_UPPER_LIMIT_NO, ERR_TENDER_MONEY_UPPER_LIMIT_MSG, true);
        }
        //验证用户交易密码，PC端暂不验证
        if (strtolower($this->api->in['from']) != 'pc') {
//            if (!$this->m_user->has_pay_password($user_id)) {
//                $this->_output(false, ERR_PAY_PASSWORD_NOT_SET_NO, ERR_PAY_PASSWORD_NOT_SET_MSG, true);
//            }
//            if (!$this->m_user->check_pay_password($user_id, $this->api->in['pay_password_md5'])) {
//                $this->_output(false, ERR_WRONG_PAY_PASSWORD_NO, ERR_WRONG_PAY_PASSWORD_MSG, true);
//            }
        }
        //验证标的余额并冻结
        if (!$this->m_borrow->decrease($borrow_id, $money)) {
            $this->_output(false, ERR_BORROW_AMOUNT_NOT_ENOUGH_NO, ERR_BORROW_AMOUNT_NOT_ENOUGH_MSG, true);
        } else {
            $this->borrow_process->push(array(
                'type' => 'borrow_decrease',
                'item' => array('borrow_id' => $borrow_id, 'money' => $money)
            ));
        }
        //验证加息券并锁定
        if ($this->api->in['coupon_user_id']) {
            $detail = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
            if (empty($detail)) {
                $this->_output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG, true);
            }
            //单人满标不能使用
            if ($borrow->is_for_single == 1) {
                $this->_output(false, ERR_COUPON_SINGLE_BORROW_LIMIT_NO, ERR_COUPON_SINGLE_BORROW_LIMIT_MSG, true);
            }
            //15天标不能使用
            if ($borrow->days != 30) {
                $this->_output(false, ERR_COUPON_DAYS_LIMIT_NO, ERR_COUPON_DAYS_LIMIT_MSG, true);
            }
            if ($detail->user_id != $user_id) {
                $this->_output(false, ERR_COUPON_NOT_MATCH_OWNER_NO, ERR_COUPON_NOT_MATCH_OWNER_MSG, true);
            }
            switch ($detail->status->id) {
                case m_coupon::STATUS_USER_USED:
                    $this->_output(false, ERR_COUPON_USED_NO, ERR_COUPON_USED_MSG, true);
                    break;
                case m_coupon::STATUS_USER_EXPIRE:
                    $this->_output(false, ERR_COUPON_EXPIRE_TIME_LIMIT_NO, ERR_COUPON_EXPIRE_TIME_LIMIT_MSG, true);
                    break;
                case m_coupon::STATUS_USER_CLOSED:
                    $this->_output(false, ERR_COUPON_CLOSED_NO, ERR_COUPON_CLOSED_MSG, true);
                    break;
            }
            if ($detail->use_times && $detail->unuse_times < 1) {
                $this->_output(false, ERR_COUPON_NO_USE_TIMES_NO, ERR_COUPON_NO_USE_TIMES_MSG, true);
            }
            if ($detail->enable_time > 0 && time() < $detail->enable_time) {
                $this->_output(false, ERR_COUPON_ON_TIME_LIMIT_NO, ERR_COUPON_ON_TIME_LIMIT_MSG, true);
            }
            if ($detail->disable_time > 0 && time() > $detail->disable_time) {
                $this->_output(false, ERR_COUPON_OFF_TIME_LIMIT_NO, ERR_COUPON_OFF_TIME_LIMIT_MSG, true);
            }
            if ($detail->expire_time > 0 && time() > $detail->expire_time) {
                $this->_output(false, ERR_COUPON_EXPIRE_TIME_LIMIT_NO, ERR_COUPON_EXPIRE_TIME_LIMIT_MSG, true);
            }
            if ($detail->limit_upper_money > 0 && $this->api->in['money'] > $detail->limit_upper_money) {
                $this->_output(false, ERR_COUPON_UPPER_MONEY_LIMIT_NO, ERR_COUPON_UPPER_MONEY_LIMIT_MSG, true);
            }
            if ($detail->limit_lower_money > 0 && $this->api->in['money'] < $detail->limit_lower_money) {
                $this->_output(false, ERR_COUPON_LOWER_MONEY_LIMIT_NO, ERR_COUPON_LOWER_MONEY_LIMIT_MSG, true);
            }
            $lock_id = $this->m_coupon->use_lock($this->api->in['coupon_user_id']);
            $this->borrow_process->push(array(
                'type' => 'coupon',
                'item' => array('coupon_lock_id' => $lock_id)
            ));
        }
        //验证红包并锁定
        if (trim($this->api->in['bouns_user_ids'])) {
            $bouns = $this->m_bouns->merge_start($user_id, explode(',', $this->api->in['bouns_user_ids']));
            if (!empty($bouns)) {
                $this->borrow_process->push(array(
                    'type' => 'bouns',
                    'item' => array('bouns_user_id' => $bouns['bouns_user_id'], 'user_id' => $user_id)
                ));
                if ($bouns['limit_lower_money'] == 0 && $bouns['limit_upper_money'] == 0) {
                    if (!$this->m_borrow->check_bouns_money($this->api->in['money'], $bouns['money'])) {
                        $this->_output(false, ERR_TENDER_BOUNS_OUT_OF_LIMIT_NO, ERR_TENDER_BOUNS_OUT_OF_LIMIT_MSG, true);
                    }
                } else {
                    if ($this->api->in['money'] < $bouns['limit_lower_money'] + $bouns['org_limit_lower_money']) {
                        $this->_output(false, ERR_TENDER_BOUNS_OUT_OF_LIMIT_NO, '您选择的红包中包含活动红包，根据活动规则您需要投资金额满' . ($bouns['limit_lower_money'] + $bouns['org_limit_lower_money']) . '元才能使用' . $bouns['money'] .'元红包！', true);
                    }
                }
                //暂不检查红包对应标的的天数限制
            } else {
                $this->_output(false, ERR_TENDER_FAILED_TO_LOCK_BOUNS_NO, ERR_TENDER_FAILED_TO_LOCK_BOUNS_MSG, true);
            }
        }
        //验证用户余额并冻结对应金额 mysql事物不能嵌套，所以金额验证放在最后
        $account = $this->m_account->lock($user_id);
        if (empty($account)) {//加锁失败
            $this->_output(false, ERR_TENDER_TOO_FAST_NO, ERR_TENDER_TOO_FAST_MSG, true);
        } else {
            
        }
        if ($account->balance < $money) {
            $this->_output(false, ERR_ACCOUNT_NOT_ENOUGH_NO, ERR_ACCOUNT_NOT_ENOUGH_MSG, true);
        } else {
            $this->borrow_process->push(array(
                'type' => 'account_lock',
                'item' => array('user_id' => $user_id, 'money' => $money)
            ));
        }
        //添加投资记录
        $tender_id = $this->m_borrow->tender_add(array(
            'borrow_id' => $borrow_id,
            'user_id' => $user_id,
            'money' => $money,
            'remark' => $this->api->in['remark'],
            'bouns_user_id' => $bouns['bouns_user_id'],
            'bouns_money' => $bouns['money'],
            'from' => $this->api->in['from'],
        ));
        if (!$tender_id) {
            $this->_output(false, ERR_TENDER_FAILED_NO, ERR_TENDER_FAILED_MSG, true);
        }
        //将虚拟冻结资金进行实际冻结，红包金额直接减掉
        $this->m_account->decrease($user_id, $money - intval($bouns['money']), 'tender', $borrow->user_id, array(
            'remark' => "投标[{$borrow->title}]冻结",
            'borrow_id' => $borrow_id,
            'tender_id' => $tender_id,
            'bouns' => $bouns,
        ));
        $this->m_account->unlock($user_id);
        //成功使用红包
        if (trim($this->api->in['bouns_user_ids'])) {
            $this->m_bouns->merge_success($user_id, $bouns['bouns_user_id'], $borrow);
        }
        //成功使用加息券
        if ($this->api->in['coupon_user_id']) {
            $this->m_coupon->make_use($lock_id, $tender_id, $borrow->title);
        }
        //释放锁
//        $this->lock->release($lock);
        //生成投资订单号
        $action_id = $this->m_bank_account->action_add(array(
            'action' => 'autoBidAuthPlus',
            'user_id' => $user_id,
            'request' => json_encode(array(
                'channel' => $this->api->in['from'] == 'pc' ? JiXin\config::CHANNEL_WEB : JiXin\config::CHANNEL_APP,
                'accountId' => $user->bank_account->account_id, //电子账号
                'txAmount' => $this->api->in['money'],
                'productId' => ARY2::_10_to_38($ba_borrow_id),
                'contOrderId' => $user->bank_account->auto_bid_order_sn, //自动投标签约订单号
            )),
            'ip' => get_ip(),
        ));
        $action = $this->m_bank_account->action_detail($action_id);
        //在这里记录已完成的投资步骤
        $process = $this->borrow_process->to_string();
        $this->db->insert(TABLE_BA_TENDER_NA, array(
            'type' => '1',
            'user_id' => $user_id,
            'status' => 2,
            'borrow_id' => $borrow_id,
            'tender_id' => $tender_id,
            'ba_borrow_id' => ARY2::_10_to_38($ba_borrow_id),
            'ba_tender_id' => $action->order_sn,
            'process' => $process,
            'channel' => $this->api->in['from'] == 'pc' ? JiXin\config::CHANNEL_WEB : JiXin\config::CHANNEL_APP, //交易渠道
            'accountId' => $user->bank_account->account_id, //电子账号
            'orderId' => $action->order_sn, //订单ID
            'txAmount' => $this->api->in['money'], //交易金额
            'productId' => ARY2::_10_to_38($ba_borrow_id), //标的号
            'contOrderId' => $user->bank_account->auto_bid_order_sn, //自动投标签约订单号
            'bouns_user_id' => $this->api->in['bouns_user_ids'] ? $bouns['bouns_user_id'] : 0,
            'coupon_user_id' => $this->api->in['coupon_user_id'] ? $lock_id : 0,
            'create_time' => time(),
        ));
        $this->db->query('commit');
        $tender = $this->m_borrow->tender_detail($tender_id);
        $this->_output($tender);
    }

    //未签自动投资的用户投标
    public function add_redirect() {
        return;
        JiXin\api_log::set(array(
            'request_jc' => $this->api->in,
            'request_time' => $_SERVER['REQUEST_TIME'],
            'start_time' => microtime(1),
            'api_name_cn' => '验密投标',
            'api_name_en' => 'bidapply',
            'user_id' => $this->api->user()->user_id,
        ));
        //验密前 弱验证
        //只验证额度、红包、加息等是否有效，而不锁定，验密后重新验证
        //关闭数据库报错功能，以方便捕获异常
        $this->db->db_debug = FALSE;
        $this->load->library('cache_memcache');
        //避免网络不好时用户断开连接后脚本中断
        ignore_user_abort();
        set_time_limit(90);
        //todo 跳转前把每个步骤写入数据库，遇到超时情况进行回退
        $this->load->model('m_user');
        $this->load->model('m_bouns');
        $this->load->model('m_coupon');
        $this->load->model('m_borrow');
        $this->load->model('m_account');
        $this->load->library('lock');
        $this->load->library('borrow_process');

        $user_id = $this->api->user()->user_id;
        $borrow_id = $this->api->in['borrow_id'];
        $money = $this->api->in['money'];
        $user = $this->m_user->detail($user_id);
        if ($user->status->id != m_user::STATUS_USER_ENABLE) {
            $this->_output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG, true);
        }
        //todo 检查用户是否已经设置自动投标
        if ($user->bank_account->auto_bid->id != 2) {
            $this->_output(false, ERR_BA_AUTO_BID_HAS_SET_NO, ERR_BA_AUTO_BID_HAS_SET_MSG, true);
        }
        $borrow = $this->m_borrow->detail($borrow_id);
        if (empty($borrow)) {
            $this->_output(false, ERR_ITEM_NOT_EXISTS_NO, "BORROW[{$borrow_id}]" . ERR_ITEM_NOT_EXISTS_MSG, true);
        }
        if ($borrow->status->id == m_borrow::STATUS_VERIFY_FULL) {
            $this->_output(false, ERR_BORROW_HAS_FULL_NO, ERR_BORROW_HAS_FULL_MSG, true);
        }
        if ($borrow->status->id != m_borrow::STATUS_VERIFY_ONLINE) {
            $this->_output(false, ERR_BORROW_DISABLE_NO, ERR_BORROW_DISABLE_MSG, true);
        }
        if ($borrow->limit_on_time > 0 && $borrow->limit_on_time > time()) {
            $this->_output(false, ERR_BORROW_ON_TIME_LIMIT_NO, ERR_BORROW_ON_TIME_LIMIT_MSG, true);
        }
        if ($borrow->expire > 0 && max($borrow->limit_on_time, $borrow->create_time) + $borrow->expire < time()) {
            $this->_output(false, ERR_BORROW_EXPIRE_NO, ERR_BORROW_EXPIRE_MSG, true);
        }
        if ($borrow->limit_upper_money > 0 && $borrow->limit_upper_money < $money) {
            $this->_output(false, ERR_TENDER_UPPER_MONEY_LIMIT_NO, ERR_TENDER_UPPER_MONEY_LIMIT_MSG, true);
        }
        if ($borrow->limit_lower_money > 0 && $borrow->limit_lower_money > $money) {
            $this->_output(false, ERR_TENDER_LOWER_MONEY_LIMIT_NO, ERR_TENDER_LOWER_MONEY_LIMIT_MSG, true);
        }
        if ($borrow->is_for_new_comer == 1 && $this->m_borrow->has_tendered($user_id)) {
            $this->_output(false, ERR_BORROW_FOR_NEW_COMMER_NO, ERR_BORROW_FOR_NEW_COMMER_MSG, true);
        }
        if ($borrow->is_for_single == 1 && $borrow->money_total != $money) {
            $this->_output(false, ERR_BORROW_FOR_SINGLE_NO, ERR_BORROW_FOR_SINGLE_MSG, true);
        }
        if ($borrow->is_for_single == 1 && $this->api->in['coupon_user_id'] > 0) {
            $this->_output(false, ERR_BORROW_NO_COUPON_NO, ERR_BORROW_NO_COUPON_MSG, true);
        }
        if ($this->api->in['money'] % 100 !== 0) {
            $this->_output(false, ERR_TENDER_MONEY_FORMAT_100_NO, ERR_TENDER_MONEY_FORMAT_100_MSG, true);
        }
        if ($this->api->in['money'] < 100) {
            $this->_output(false, ERR_TENDER_MONEY_LOWER_LIMIT_NO, ERR_TENDER_MONEY_LOWER_LIMIT_MSG . "[{$this->api->in['money']}]", true);
        }
        if ($this->api->in['money'] > $borrow->money_total) {
            $this->_output(false, ERR_TENDER_MONEY_UPPER_LIMIT_NO, ERR_TENDER_MONEY_UPPER_LIMIT_MSG, true);
        }
        //验证用户交易密码，PC端暂不验证
        if (strtolower($this->api->in['from']) != 'pc') {
            if (!$this->m_user->has_pay_password($user_id)) {
                $this->_output(false, ERR_PAY_PASSWORD_NOT_SET_NO, ERR_PAY_PASSWORD_NOT_SET_MSG, true);
            }
            if (!$this->m_user->check_pay_password($user_id, $this->api->in['pay_password_md5'])) {
                $this->_output(false, ERR_WRONG_PAY_PASSWORD_NO, ERR_WRONG_PAY_PASSWORD_MSG, true);
            }
        }
        //验证标的余额并冻结
        if (!$this->m_borrow->decrease($borrow_id, $money)) {
            $this->_output(false, ERR_BORROW_AMOUNT_NOT_ENOUGH_NO, ERR_BORROW_AMOUNT_NOT_ENOUGH_MSG, true);
        } else {
            $this->borrow_process->push(array(
                'type' => 'borrow_decrease',
                'item' => array('borrow_id' => $borrow_id, 'money' => $money)
            ));
        }
        //验证加息券并锁定
        if ($this->api->in['coupon_user_id']) {
            $detail = $this->m_coupon->user_detail($this->api->in['coupon_user_id']);
            if (empty($detail)) {
                $this->_output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG, true);
            }
            //单人满标不能使用
            if ($borrow->is_for_single == 1) {
                $this->_output(false, ERR_COUPON_SINGLE_BORROW_LIMIT_NO, ERR_COUPON_SINGLE_BORROW_LIMIT_MSG, true);
            }
            //15天标不能使用
            if ($borrow->days != 30) {
                $this->_output(false, ERR_COUPON_DAYS_LIMIT_NO, ERR_COUPON_DAYS_LIMIT_MSG, true);
            }
            if ($detail->user_id != $user_id) {
                $this->_output(false, ERR_COUPON_NOT_MATCH_OWNER_NO, ERR_COUPON_NOT_MATCH_OWNER_MSG, true);
            }
            switch ($detail->status->id) {
                case m_coupon::STATUS_USER_USED:
                    $this->_output(false, ERR_COUPON_USED_NO, ERR_COUPON_USED_MSG, true);
                    break;
                case m_coupon::STATUS_USER_EXPIRE:
                    $this->_output(false, ERR_COUPON_EXPIRE_TIME_LIMIT_NO, ERR_COUPON_EXPIRE_TIME_LIMIT_MSG, true);
                    break;
                case m_coupon::STATUS_USER_CLOSED:
                    $this->_output(false, ERR_COUPON_CLOSED_NO, ERR_COUPON_CLOSED_MSG, true);
                    break;
            }
            if ($detail->use_times && $detail->unuse_times < 1) {
                $this->_output(false, ERR_COUPON_NO_USE_TIMES_NO, ERR_COUPON_NO_USE_TIMES_MSG, true);
            }
            if ($detail->enable_time > 0 && time() < $detail->enable_time) {
                $this->_output(false, ERR_COUPON_ON_TIME_LIMIT_NO, ERR_COUPON_ON_TIME_LIMIT_MSG, true);
            }
            if ($detail->disable_time > 0 && time() > $detail->disable_time) {
                $this->_output(false, ERR_COUPON_OFF_TIME_LIMIT_NO, ERR_COUPON_OFF_TIME_LIMIT_MSG, true);
            }
            if ($detail->expire_time > 0 && time() > $detail->expire_time) {
                $this->_output(false, ERR_COUPON_EXPIRE_TIME_LIMIT_NO, ERR_COUPON_EXPIRE_TIME_LIMIT_MSG, true);
            }
            if ($detail->limit_upper_money > 0 && $this->api->in['money'] > $detail->limit_upper_money) {
                $this->_output(false, ERR_COUPON_UPPER_MONEY_LIMIT_NO, ERR_COUPON_UPPER_MONEY_LIMIT_MSG, true);
            }
            if ($detail->limit_lower_money > 0 && $this->api->in['money'] < $detail->limit_lower_money) {
                $this->_output(false, ERR_COUPON_LOWER_MONEY_LIMIT_NO, ERR_COUPON_LOWER_MONEY_LIMIT_MSG, true);
            }
            $lock_id = $this->m_coupon->use_lock($this->api->in['coupon_user_id']);
            $this->borrow_process->push(array(
                'type' => 'coupon',
                'item' => array('coupon_lock_id' => $lock_id)
            ));
        }
        //验证红包并锁定
        if (trim($this->api->in['bouns_user_ids'])) {
            $bouns = $this->m_bouns->merge_start($user_id, explode(',', $this->api->in['bouns_user_ids']));
            if (!empty($bouns)) {
                $this->borrow_process->push(array(
                    'type' => 'bouns',
                    'item' => array('bouns_user_id' => $bouns['bouns_user_id'], 'user_id' => $user_id)
                ));
                if (!$this->m_borrow->check_bouns_money($this->api->in['money'], $bouns['money'])) {
                    $this->_output(false, ERR_TENDER_BOUNS_OUT_OF_LIMIT_NO, ERR_TENDER_BOUNS_OUT_OF_LIMIT_MSG, true);
                }
                //暂不检查红包对应标的的天数限制
            } else {
                $this->_output(false, ERR_TENDER_FAILED_TO_LOCK_BOUNS_NO, ERR_TENDER_FAILED_TO_LOCK_BOUNS_MSG, true);
            }
        }
        //验证用户余额并冻结对应金额 mysql事物不能嵌套，所以金额验证放在最后
//        $account = $this->m_account->lock($user_id);
//        if (empty($account)) {//加锁失败
//            $this->_output(false, ERR_TENDER_TOO_FAST_NO, ERR_TENDER_TOO_FAST_MSG, true);
//        } else {
//            
//        }
//        if ($account->balance < $money) {
//            $this->_output(false, ERR_ACCOUNT_NOT_ENOUGH_NO, ERR_ACCOUNT_NOT_ENOUGH_MSG, true);
//        } else {
//            $this->borrow_process->push(array(
//                'type' => 'account_lock',
//                'item' => array('user_id' => $user_id, 'money' => $money)
//            ));
//        }
        //在这里准备跳转银行输交易密码
        $ba = $this->m_bank_account->detail($user_id);
        $ba_borrow_id = $this->m_borrow->ba_id($borrow_id);
        $action_id = $this->m_bank_account->action_add(array(
            'action' => 'autoBidAuthPlus',
            'user_id' => $user_id,
            'request' => json_encode(array(
                'channel' => $this->api->in['from'] == 'pc' ? JiXin\config::CHANNEL_WEB : JiXin\config::CHANNEL_APP,
                'accountId' => $ba->account_id,
                'txAmount' => $this->api->in['money'],
                'productId' => ARY2::_10_to_38($ba_borrow_id),
            )),
            'ip' => get_ip(),
        ));
        $action = $this->m_bank_account->action_detail($action_id);
        //todo  在这里记录已完成的投资步骤
        $process = $this->borrow_process->to_string();
        $this->db->insert(TABLE_BA_TENDER_NA, array(
            'type' => '2',
            'status' => 2,
            'borrow_id' => $borrow_id,
            'ba_borrow_id' => ARY2::_10_to_38($ba_borrow_id),
            'ba_tender_id' => $action->order_sn,
            'process' => $process,
            'create_time' => time(),
        ));
        $jx = new JiXin\api();
        $r = $jx->bidapply(array(
            'channel' => $this->api->in['from'] == 'pc' ? JiXin\config::CHANNEL_WEB : JiXin\config::CHANNEL_APP,
            'accountId' => $ba->account_id,
            'orderId' => $action->order_sn,
            'txAmount' => $this->api->in['money'],
            'productId' => ARY2::_10_to_38($ba_borrow_id),
        ));
        JiXin\api_log::set(array(
            'response' => $r,
        ));
        JiXin\api_log::write();
        //输出跳转页面
        $this->api->output_string($r);
    }

    private function _output($r, $no = false, $msg = false, $clean = false) {
        if ($clean !== false) {
            $this->borrow_process->clean();
        }
        do_log(array('tender_sql'=>$this->db->all_query()));
        $this->api->output($r, $no, $msg);
    }
    
    public function get() {
        $r = $this->m_borrow_tender->detail($this->api->in['id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_BORROW_TENDER_NOT_EXISTS_NO, ERR_BORROW_TENDER_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
        do_log($this->api->in);
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        if(isset($condition['full_flag'])){
            $condition['status'] = ($condition['full_flag'] == 1) ? array(1,3) : array(0,9);//查先是否满标
        }
        $r['rows'] = $this->m_borrow_tender->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_borrow_tender->count($condition);
        $this->api->output($r);
    }

    public function bouns_recommend() {
        $this->load->model('m_bouns');
        $this->load->model('m_borrow');

        $money = $this->api->in['money'];
        $borrow_id = $this->api->in['borrow_id'];

        $borrow = $this->m_borrow->detail($borrow_id);
        if (empty($borrow)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }

        $user_id = $this->api->user()->user_id;
        $bouns = $this->m_bouns->user_lists(array(
            'user_id' => $user_id,
            'status' => m_bouns::STATUS_USER_NOT_USE
        ));
        $available = array();
        foreach ($bouns as $k => $v) {
            if ($this->m_borrow->check_bouns_money($money, $v->money)) {
                $available[] = $v;
            }
        }
        $this->api->output(array(
            'max' => $this->m_borrow->max_bouns_money($money),
            'available' => $available,
            'total' => count($available)
        ));
    }

    public function total_tender_lists() {
        $this->load->model('m_borrow_tender');
        $condition['starttime'] = intval($this->api->in['starttime']) > 0 ? intval($this->api->in['starttime']) : 0;
        $condition['endtime'] = intval($this->api->in['endtime']) > 0 ? intval($this->api->in['endtime']) : time();
        $r = $this->m_borrow_tender->tender_lists($condition);
        $this->api->output($r);
    }

    public function today_tender_lists() {
        $this->load->model('m_borrow_tender');
        $condition['starttime'] = intval(strtotime(date("Y-m-d"), time()));
        $condition['endtime'] = time();
        $r = $this->m_borrow_tender->tender_lists($condition);
        $this->api->output($r);
    }

    public function self_total_tender() {
        $this->load->model('m_borrow_tender');
        $condition['starttime'] = intval($this->api->in['starttime']) > 0 ? intval($this->api->in['starttime']) : 0;
        $condition['endtime'] = intval($this->api->in['endtime']) > 0 ? intval($this->api->in['endtime']) : time();
        $condition['user_id'] = $this->api->user()->user_id;
        $r = $this->m_borrow_tender->total_tender($condition);
        $this->api->output($r);
    }

}
