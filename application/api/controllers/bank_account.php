<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bank_account
 *
 * @author win7
 */
class bank_account extends CI_Controller {

    private $jx;
    public $tranType = array(
        '5500' => '活期收益',
        '5504' => '靠档计息',
        '7616' => '银联代收付渠道资金转入',
        '7722' => '手续费入账',
        '7724' => 'P2P提现手续费转入',
        '7725' => 'P2P债权转让手续费转入',
        '7777' => '批量入账（基金分红）',
        '7780' => 'P2P融资',
        '7781' => 'P2P到期收益',
        '7782' => 'P2P账户批量充值',
        '7783' => 'P2P账户红包发放',
        '7785' => 'P2P债权转让资金转入',
        '7788' => 'P2P代偿还款到期收益',
        '7792' => 'P2P红包发放收益(批量红包转入)',
        '7820' => '行内渠道资金转入',
        '7822' => 'chinapay渠道资金转入',
        '7831' => 'P2P债权转让资金转入',
        '7833' => '红包转入',
        '7835' => '债权转让手续费转入',
        '7901' => '直销银行账户资金转入(中金)',
        '7905' => '直销银行账户资金转入(易宝)',
        '7906' => '直销银行账户资金转入(快钱)',
        '7907' => '直销银行账户资金转入(银盛)',
        '7909' => '直销银行账户资金转入(浙江银商)',
        '7910' => '直销银行账户资金转入(金运通)',
        '9780' => 'P2P融资手续费',
        '9781' => 'P2P到期收益手续费',
        '9785' => '批量债权转让手续费',
        '9788' => 'P2P代偿还款收益手续费',
        '9831' => 'P2P转让债权回款手续费',
    );

    public function __construct() {
        parent::__construct();
        $this->load->model('m_bank_account');
        $this->jx = new JiXin\api();
        JiXin\api_log::set(array(
            'request_jc' => $this->api->in,
            'request_time' => $_SERVER['REQUEST_TIME'],
            'start_time' => microtime(1),
        ));
    }

    //异步回调通知
    public function notify() {
//        $_POST['bgData'] = '{"bankCode":"30050000","batchNo":"135253","seqNo":"000004","txTime":"135253","channel":"000002","sign":"DVdfNY+jR+Py8opjY9b+CQPHOtf9yfz16XpPSQs3hYnD5P95oEz2jzOq+cDE3zGY1+kVe1BLcFW4DMiByVylHuPjnXXzgkvFcwbf+2+kA4yjwakJtTO2iWrUKlAAgfBkt\/kxHeG18RwOmv6tRl7OAr3WZm9hx6yZtQNSWTvEGT8=","retCode":"JX900614","version":"10","retMsg":"\u6279\u91cf\u7ed3\u675f\u503a\u6743\u4ea4\u6613\u7b14\u6570\u4e0e\u5b9e\u9645\u53c2\u6570\u7b14\u6570\u4e0d\u7b26","txCounts":0,"instCode":"00970001","txCode":"batchCreditEnd","acqRes":"","txDate":"20170608"}';
        $result = json_decode($_POST['bgData'], true);
        JiXin\api_log::set(array(
            'request_jc' => $result,
            'api_name_cn' => '异步回调',
            'api_name_en' => 'notify',
        ));
        if (empty($result)) {
            $this->_my_output_string('error');
        } else {
            if ($this->jx->sign_verify($result)) {
                JiXin\api_log::set(array(
                    'txCode' => $result['txCode'],
                    'seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'],
                    'channel' => $result['channel'],
                    'user_id' => JiXin\api_log::get_userId_by_seqNo($result['txDate'] . $result['txTime'] . $result['seqNo']),
                ));
                switch ($result['txCode']) {
                    case 'passwordReset':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调[密码重置]',
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        break;
                    case 'passwordSet':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调[密码设置]',
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        if ($result['retCode'] === '00000000') {
                            $this->m_bank_account->success_set_password($result['accountId']);
                        }
                        break;
                    case 'directRechargePlus':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调[快捷充值]',
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        if ($result['retCode'] === '00000000') {
                            $this->m_bank_account->success_recharge($result);
                            $this->m_bank_account->set_mobile($result['accountId'], $result['mobile']);
                        }
                        break;
                    //投资人自动投标签约增强
                    case 'autoBidAuthPlus':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调[自动投标签约]',
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        if ($result['retCode'] === '00000000') {
                            $this->m_bank_account->success_auto_bid_auth($result['accountId'], $result);
                        }
                        break;
                    case 'bidapply':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调[需密投标]',
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        if ($result['retCode'] === '00000000') {
                            $this->m_borrow_tender->success_tender($result);
                        }
                        break;
                    case 'batchLendPay':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调' . (isset($result['txAmount']) ? '[批量放款合法性检查]' : '[批量放款业务处理结果]'),
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        $this->load->model('m_borrow');
                        if ($result['retCode'] === '00000000') {
                            if (!isset($result['txAmount'])) {
                                //批量放款成功
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_SUCCESS, 'ba_result_full_done' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_PAY_PASS));
                                //修改投资记录的状态
                                $borrow_id = $this->db->get_where(TABLE_QUEUE_BORROW, array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo']))->row(0)->borrow_id;
                                $this->db->update(TABLE_BORROW_TENDER, array('status' => 0), array('borrow_nid' => $borrow_id, 'status' => 9));
                            } else {
                                //批量放款数据合法
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_PASS, 'ba_result_full_enable' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_PAY_SUBMIT));
                            }
                        } else {
                            if (!isset($result['txAmount'])) {
                                //批量放款失败
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_FAILED, 'ba_result_full_done' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_PAY_PASS));
                            } else {
                                //批量放款数据合法失败
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_FAILED, 'ba_result_full_enable' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_PAY_SUBMIT));
                            }
                            //todo 通知到管理员
                            //todo 数量不一致时自动重新提交
                        }
                        break;
                    case 'batchRepay':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调' . (isset($result['txAmount']) ? '[批量还款合法性检查]' : '[批量还款业务处理结果]'),
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        $this->load->model('m_borrow');
                        if ($result['retCode'] === '00000000') {
                            if (!isset($result['txAmount'])) {
                                //todo 判断是否有失败的部分
                                //批量还款成功
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_SUCCESS, 'ba_result_repay_done' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_REPAY_PASS));
                            } else {
                                //批量还款数据合法
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_PASS, 'ba_result_repay_enable' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_REPAY_SUBMIT));
                            }
                        } else {
                            if (!isset($result['txAmount'])) {
                                //批量还款成功
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_FAILED, 'ba_result_repay_done' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_REPAY_PASS));
                            } else {
                                //批量还款数据合法验证失败
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_FAILED, 'ba_result_repay_enable' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_REPAY_SUBMIT));
                            }
                            //todo 通知到管理员
                            //todo 数量不一致时自动重新提交
                        }
                        break;
                    case 'batchCreditEnd':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调' . (isset($result['txCounts']) ? '[批量结束债权合法性检查]' : '[批量结束债权业务处理结果]'),
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));
                        $this->load->model('m_borrow');
                        if ($result['retCode'] === '00000000') {
                            if (!isset($result['txCounts'])) {
                                //结束债权成功
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_SUCCESS, 'ba_result_end_done' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_END_PASS));
                            } else {
                                //结束债权数据合法
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_PASS, 'ba_result_end_enable' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_END_SUBMIT));
                            }
                        } else {
                            if (!isset($result['txCounts'])) {
                                //结束债权失败
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_FAILED, 'ba_result_end_done' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_END_PASS));
                            } else {
                                //结束债权数据合法验证失败
                                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_FAILED, 'ba_result_end_enable' => json_encode($result)), array('ba_seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'], 'ba_status' => m_borrow::STATUS_P_BA_END_SUBMIT));
                            }
                            //todo 通知到管理员
                            //todo 数量不一致时自动重新提交
                        }
                        break;
                    case 'withdraw':
                        JiXin\api_log::set(array(
                            'api_name_cn' => '异步回调[提现]',
                            'api_name_en' => "notify[{$result['txCode']}]",
                        ));

                        if ($result['retCode'] === '00000000' || $result['retCode'] === 'CE999028') {
                            $nid = $result['txDate'] . $result['txTime'] . $result['seqNo'];
                            $this->load->model('m_cash');

                            $cash = $this->m_cash->get_by_nid($nid);
                            if ($cash) {  //查到提现记录
                                if ($cash['status'] == 0) {
                                    //添加提现成功的记录
                                    //$r_check = $this->m_cash->update($cash['id'], $this->api->in);
                                    //$r = $this->m_cash->detail($cash_id);
                                    $this->load->model('m_account');
                                    //资金操作记录
                                    //提现记录
                                    $account = $this->m_account->lock($cash['user_id']);
                                    $param = array(
                                        'income' => 0,
                                        'expend' => $cash['account'],
                                        'balance' => -$cash['account'],
                                        'balance_cash' => -$cash['account'],
                                        'balance_frost' => 0,
                                        'frost' => 0,
                                        'await' => 0,
                                    );
                                    $param['user_id'] = $cash['user_id'];
                                    $param['type'] = 'cash_success';
                                    $param['money'] = $cash['account'];
                                    $param['remark'] = '提现成功' . $cash['account'] . '元';
                                    $param['to_userid'] = 0;
                                    $this->m_account->add_log($param);
                                    $this->m_account->unlock($cash['user_id']);
                                    $account = $this->m_account->lock($cash['user_id']);
                                    //手续费记录
                                    $param = array(
                                        'income' => 0,
                                        'expend' => $cash['fee'],
                                        'balance' => -$cash['fee'],
                                        'balance_cash' => -$cash['fee'],
                                        'balance_frost' => 0,
                                        'frost' => 0,
                                        'await' => 0,
                                    );
                                    $param['user_id'] = $cash['user_id'];
                                    $param['type'] = 'cash_fee';
                                    $param['money'] = $cash['fee'];
                                    $param['remark'] = "提现扣除手续费" . $cash['fee'] . "元";
                                    $param['to_userid'] = 0;
                                    $this->m_account->add_log($param);
                                    $this->m_account->unlock($cash['user_id']);
                                    //TODO 用户操作记录
                                    //将该用户其他未审核记录设置为非首次提现

                                    $update_param = array(
                                        'id' => $cash['id'],
                                        'status' => 1
                                    );
                                    if (!$this->m_cash->get_success_by_userid($cash['user_id'])) {
                                        $update_param['is_first'] = 1;
                                    }
                                    $r_check = $this->m_cash->update($cash['id'], $update_param);

                                    $this->load->model('m_user');
                                    $this->m_user->update_card_bank_cnaps($cash['cardBankCnaps'], $cash['user_id']);
                                    //$r = $this->m_cash->detail($cash_id);

                                    /* $condition['is_first'] = 0;
                                      $this->db->where('user_id', intval($cash['user_id']));
                                      $this->db->where('status <> ', 1);
                                      $this->db->update(TABLE_CASH, $condition); */
                                    //do_log($this->db->last_query());
                                }
                            } else {
                                $this->api->output(false, ERR_CASH_NOT_EXISTS_NO, ERR_CASH_NOT_EXISTS_MSG);
                            }
                        } else {
                            /*
                            $nid = $result['txDate'] . $result['txTime'] . $result['seqNo'];
                            $this->load->model('m_cash');

                            $cash = $this->m_cash->get_by_nid($nid);
                            if ($cash) {  //查到提现记录
                                if ($cash['status'] == 0) {//将待审核的记录设置为提现失败
                                    $this->load->model('m_account');
                                    //资金操作记录
                                    //提现记录
                                    $account = $this->m_account->lock($cash['user_id']);
                                    $param = array(
                                        'income' => 0,
                                        'expend' => 0,
                                        'balance_cash' => $cash['account'],
                                        'balance_frost' => 0,
                                        'frost' => -$cash['account'],
                                        'await' => 0,
                                    );
                                    $param['user_id'] = $cash['user_id'];
                                    $param['type'] = 'cash_false';
                                    $param['money'] = $cash['account'];
                                    $param['remark'] = '提现失败' . $cash['account'] . '元,错误代码' . $result['retCode'];
                                    $param['to_userid'] = 0;
                                    $this->m_account->add_log($param);
                                    $this->m_account->unlock($cash['user_id']);

                                    //更新提现记录为失败
                                    $update_param = array(
                                        'id' => $cash['id'],
                                        'status' => 2
                                    );
                                    $r_check = $this->m_cash->update($cash['id'], $update_param);
                                }
                            } else {
                                $this->api->output(false, ERR_CASH_NOT_EXISTS_NO, ERR_CASH_NOT_EXISTS_MSG);
                            }
                             * 
                             */
                        }
                        break;
                }
                $this->_my_output_string('success');
            } else {
                $this->_my_output_string('error');
            }
        }
    }

    //同步回调
    public function return_url() {
        //$result = '{"version":"10","txCode":"directRechargePlus","bankCode":"30050000","instCode":"00970001","txDate":"20170620","txTime":"164256","seqNo":"000114","channel":"000001","retCode":"CE999038","retMsg":"","accountId":"6212462050000800038","mobile":"15655101250","txAmount":"5000.00","acqRes":"123","sign":"35nFSqdUmMh2ARWFXwN1jUYt1KY7i6GvhICZLeJchz4ECWM2aB6DqmUUyX7eXKrb8y\/nbO7xbgC2gM7TPNoIVNgBFQhvrapw1pIMUoeh5tb2UtnW63fAqRoic+iKjV6oYfIQDYeazDhVMRPpYcV4BXXy0kdwZDPhFZXKnTQE9SU="}';
        //$result = json_decode($result,true);
        $result = $_REQUEST;
        JiXin\api_log::set(array(
            'request_jc' => $result,
            'api_name_cn' => '同步回调',
            'api_name_en' => 'return',
            'user_id' => 0,
        ));
        if (empty($result)) {
            $this->_my_output_string('error');
        } else {
            JiXin\api_log::set(array(
                'txCode' => $result['txCode'],
                'seqNo' => $result['txDate'] . $result['txTime'] . $result['seqNo'],
                'channel' => $result['channel'],
                'user_id' => JiXin\api_log::get_userId_by_seqNo($result['txDate'] . $result['txTime'] . $result['seqNo']),
            ));
            switch ($result['txCode']) {
                case 'passwordSet':
                    JiXin\api_log::set(array(
                        'api_name_cn' => '同步回调[密码设置]',
                        'api_name_en' => "return[{$result['txCode']}]",
                    ));
                    $this->load->view('return/success_set_password.html');
                    break;
                case 'passwordSetNew':
                    JiXin\api_log::set(array(
                        'api_name_cn' => '同步回调[密码设置][新用户]',
                        'api_name_en' => "return[{$result['txCode']}]",
                    ));
                    $this->load->view('return/success_set_password_new.html');
                    break;
                case 'withdraw':
                    JiXin\api_log::set(array(
                        'api_name_cn' => '同步回调[用户提现]',
                        'api_name_en' => "return[{$result['txCode']}]",
                    ));
                    $this->load->view('return/success_withdraw.html');
                    break;
                default :
                    $this->load->view('return/success.html');
                    break;
            }
        }
    }

    //开通个人存管账户
    public function account_open() {
        JiXin\api_log::set(array(
            'api_name_cn' => '开通个人存管账户',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));

        $this->load->model('m_account');
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id == m_bank_account::STATUS_ACCOUNT_ENABLE || $ba->status->id == m_bank_account::STATUS_ACCOUNT_DISABLE) {
            $this->_my_output(false, ERR_ACCOUNT_HAS_OPENED_NO, ERR_ACCOUNT_HAS_OPENED_MSG);
        }
        if ($ba->status->id == m_bank_account::STATUS_ACCOUNT_PROCESSING) {
            $this->_my_output(false, ERR_ACCOUNT_PROCESSING_NO, ERR_ACCOUNT_PROCESSING_MSG);
        }
        $mobile = $this->api->user()->mobile;
        if (!is_mobile($mobile)) {
            //非正常账户，不可开立存管账户
            $this->_my_output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
        }
        $this->api->in['mobile'] = $mobile;
        if (!$this->m_bank_account->start_open($user_id, $this->api->in)) {
            //其他请求正在开户中，加乐观锁失败
            $this->_my_output(false, ERR_BA_ACCOUNT_OPEN_ERROR_NO, ERR_BA_ACCOUNT_OPEN_ERROR_MSG);
        }
        $r = $this->jx->accountOpenPlus(array(
            'channel' => $this->api->in['channel'],
            'idType' => '01', //01身份证
            'idNo' => $this->api->in['id_No'],
            'name' => $this->api->in['realname'],
            'mobile' => $mobile,
            'cardNo' => $this->api->in['card_No'],
            'acctUse' => '000000', //个人担保账户
            'lastSrvAuthCode' => $this->api->in['sms_auth'], //通过请求发送短信验证码接口获取
            'smsCode' => $this->api->in['sms_code'], //手机接收到短信验证码
        ));
        if ($r['retcode'] === '00000000') {//验证成功
            $this->m_bank_account->complete_open($user_id, array('account_id' => $r['result']['accountId'], 'card_No' => $this->api->in['card_No']));
            $this->m_bank_account->card_bind($r['result']['accountId'], $this->api->in['card_No']);
            //$this->m_account->add(array('user_id' => $user_id));
            $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
        } else {
            $this->m_bank_account->stop_open($user_id);
            $this->_my_output(false, $r['retcode'], $r['msg']);
        }
    }

    //发送验证码
    public function sms_code_apply() {
        JiXin\api_log::set(array(
            'api_name_cn' => '发送验证码',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $mobile = $this->api->in['mobile'];
        if (!is_mobile($mobile)) {
            $this->_my_output(false, ERR_WRONG_FORMAT_NO, ERR_WRONG_FORMAT_MSG);
        }
        if ($this->api->in['srv_code'] == 'directRecharge') {
            $ba = $this->m_bank_account->detail($this->api->user()->user_id);

            $r = $this->jx->smsCodeApply(array(
                'mobile' => $mobile,
                'srvTxCode' => $this->api->in['srv_code'] . 'Online',
                'reqType' => '2',
                'cardNo' => $ba->card_No,
            ));
        } else {
            $r = $this->jx->smsCodeApply(array(
                'mobile' => $mobile,
                'srvTxCode' => $this->api->in['srv_code'] . 'Plus',
            ));
        }

        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //设置交易密码
    public function password_set() {
        JiXin\api_log::set(array(
            'api_name_cn' => '设置交易密码',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        if ($ba->has_set_password->id == 1) {
            $this->_my_output(false, ERR_ACOOUNT_PASSWORD_HAS_SET_NO, ERR_ACOOUNT_PASSWORD_HAS_SET_MSG);
        }
        $r = $this->jx->passwordSet(array(
            'accountId' => $ba->account_id,
            'idNo' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $ba->mobile,
            'is_new' => $user_id > 49070,
        ));
        $this->_my_output_string($r);
    }

    //重设交易密码
    public function password_reset() {
        JiXin\api_log::set(array(
            'api_name_cn' => '重设交易密码',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        if ($ba->has_set_password->id == 2) {
            $this->_my_output(false, ERR_ACOOUNT_PASSWORD_NOT_SET_NO, ERR_ACOOUNT_PASSWORD_NOT_SET_MSG);
        }
        $r = $this->jx->passwordReset(array(
            'accountId' => $ba->account_id,
            'idNo' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $ba->mobile,
        ));
        $this->_my_output_string($r);
    }

    //绑卡
    public function card_bind() {
        JiXin\api_log::set(array(
            'api_name_cn' => '绑卡',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        if ($ba->has_bind_card->id != 2) {
            $this->_my_output(false, ERR_BA_CARD_HAS_BIND_NO, ERR_BA_CARD_HAS_BIND_MSG);
        }
        $r = $this->jx->cardBindPlus(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
            'idNo' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $ba->mobile,
            'cardNo' => $this->api->in['card_No'],
            'lastSrvAuthCode' => $this->api->in['sms_auth'], //通过请求发送短信验证码接口获取
            'smsCode' => $this->api->in['sms_code'], //手机接收到短信验证码
        ));
        if ($r['retcode'] === '00000000') {
            $this->m_bank_account->card_bind($ba->account_id, $this->api->in['card_No']);
        }
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //解绑
    public function card_unbind() {
        JiXin\api_log::set(array(
            'api_name_cn' => '解绑卡',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        if ($ba->has_bind_card->id != 1) {
            $this->_my_output(false, ERR_BA_CARD_NOT_BIND_NO, ERR_BA_CARD_NOT_BIND_MSG);
        }
        $r = $this->jx->cardUnbind(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
            'idNo' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $ba->mobile,
            'cardNo' => $this->api->in['card_No'],
        ));
        if ($r['retcode'] === '00000000') {
            $r_update = $this->m_bank_account->card_bind_cancel($ba->account_id);
        }
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //充值
    public function recharge() {
        JiXin\api_log::set(array(
            'api_name_cn' => '充值',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        $txDate = date('Ymd');
        $txTime = date('His');
        $seqNo = JiXin\counter::auto_id();

        $r = $this->jx->directRechargePlus(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
            'idNo' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $this->api->in['mobile'],
            'cardNo' => $ba->card_No,
            'txAmount' => sprintf('%.2f', $this->api->in['money'] / 100),
            'smsSeq' => $this->api->in['sms_auth'], //通过请求发送短信验证码接口获取
            'smsCode' => $this->api->in['sms_code'], //手机接收到短信验证码
            'txDate' => $txDate,
            'txTime' => $txTime,
            'seqNo' => $seqNo,
        ));
        $param['nid'] = trim($txDate . $txTime . $seqNo);
        if (!$this->m_bank_account->get_recharge_by_nid($param['nid'])) {
            $param['user_id'] = $user_id;
            $param['money'] = sprintf('%.2f', $this->api->in['money'] / 100);
            $param['balance'] = sprintf('%.2f', $this->api->in['money'] / 100);
            $param['remark'] = '存管快捷充值';
            $param['ba_id'] = $ba->account_id;
            $param['ba_channel'] = $this->api->in['channel'];
            $param['ba_id_No'] = $ba->id_No;
            $param['ba_name'] = $ba->realname;
            $param['ba_mobile'] = $this->api->in['mobile'];
            $param['ba_card_No'] = $ba->card_No;
            $param['ba_tx_Amount'] = sprintf('%.2f', $this->api->in['money'] / 100);
            $this->m_bank_account->add_recharge($param);
        }
        if ($r['retcode'] === '00000000') {//成功
            $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
        } else {
            $this->_my_output(false, $r['retcode'], $r['msg']);
        }
    }

    //提现
    public function withdraw() {
        JiXin\api_log::set(array(
            'api_name_cn' => '提现',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        /* if ($ba->cash_control == 2) {
          echo '<script>alert("' . ERR_CASH_CONTROL_MSG . '");</script>';
          exit;
          } */
        if ($ba->has_set_password == 2) {
            $this->_my_output(false, ERR_ACOOUNT_PASSWORD_NOT_SET_NO, ERR_ACOOUNT_PASSWORD_NOT_SET_MSG);
        }
        if ($this->api->in['routeCode'] == 2) {
            $match_str = '/\d{12}/';
            if (!isset($this->api->in['cardBankCnaps']) || !preg_match($match_str, $this->api->in['cardBankCnaps'])) {
                $this->_my_output(false, ERR_CARD_BANK_CNAPS_NO, ERR_CARD_BANK_CNAPS_MSG);
            }
        }
        $this->load->model('m_account');
        $this->load->model('m_cash');
        //$account = $this->m_account->detail($user_id);
        $txDate = date('Ymd');
        $txTime = date('His');
        $seqNo = JiXin\counter::auto_id();
        //冻结资金
        $this->load->library('lock');
        //验证用户余额并冻结对应金额 mysql事物不能嵌套，所以金额验证放在最后
        $account = $this->m_account->lock($user_id);
        if (empty($account)) {//加锁失败
            $this->_my_output(false, ERR_CASH_TOO_FAST_NO, ERR_CASH_TOO_FAST_MSG);
        }
        if ($account->balance < $this->api->in['money'] / 100) {
            $this->_my_output(false, ERR_ACCOUNT_NOT_ENOUGH_NO, ERR_ACCOUNT_NOT_ENOUGH_MSG);
        }
        //添加提现记录
        $cash_id = $this->m_cash->add(array(
            'user_id' => $user_id,
            'nid' => $txDate . $txTime . $seqNo,
            'account' => sprintf('%.2f', $this->api->in['money'] / 100),
            'bank_id' => $ba->card_No,
            'fee' => 0,
            'source' => $this->api->in['source'],
            'balance' => $account->balance,
            'card_id' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $ba->mobile,
            'routeCode' => $this->api->in['routeCode'],
            'cardBankCnaps' => $this->api->in['cardBankCnaps']
        ));
        if (!$cash_id) {
            $this->_my_output(false, ERR_CASH_FAILED_NO, ERR_CASH_FAILED_MSG, true);
        }
        /*
        //将虚拟冻结资金进行实际冻结
        $this->m_account->decrease($user_id, sprintf('%.2f', $this->api->in['money'] / 100), 'cash', $user_id, array(
            'remark' => "提现[{$nid}]冻结",
        ));
         * 
         */
        $this->m_account->unlock($user_id);
        //发送请求
        $r = $this->jx->withdraw(array(
            'txDate' => $txDate,
            'txTime' => $txTime,
            'seqNo' => $seqNo,
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
            'idNo' => $ba->id_No,
            'name' => $ba->realname,
            'mobile' => $ba->mobile,
            'cardNo' => $ba->card_No,
            'txAmount' => sprintf('%.2f', $this->api->in['money'] / 100),
            'txFee' => 0,
            //'routeCode' => $this->api->in['money'] / 100 % 3, //todo选择提现渠道 0-本行通道,1-银联通道,2-人行通道,空-自动选择
            //'cardBankCnaps' => $this->api->in['money'] / 100 % 3 == 2 ? '102331009206' : '',
            'routeCode' => $this->api->in['routeCode'], //0-本行通道,1-银联通道,2-人行通道,空-自动选择
            'cardBankCnaps' => $this->api->in['cardBankCnaps'], //联行号
            'lastSrvAuthCode' => $this->api->in['sms_auth'], //通过请求发送短信验证码接口获取
            'smsCode' => $this->api->in['sms_code'], //手机接收到短信验证码
        ));
        $this->_my_output(array('cash_id' => $cash_id, 'form' => $r));
    }

    public function withdraw_cancel() {
        JiXin\api_log::set(array(
            'api_name_cn' => '提现取消',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));

        $this->load->model('m_cash');
        $this->load->model('m_account');

        $cash_id = $this->api->in['cash_id'];
        $detail = $this->m_cash->detail($cash_id);
        $user_id = $this->api->user()->user_id;
        if (empty($detail)) {
            $this->_my_output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user_id != $user_id) {
            $this->_my_output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($detail->status->id != m_cash::STATUS_VERIFY_INIT && $detail->status->id != m_cash::STATUS_VERIFY_PROCESSING) {
            $this->_my_output(false, ERR_ACTION_REPEAT_NO, ERR_ACTION_REPEAT_MSG);
        }
        //提现记录
        $this->m_cash->update($cash_id, array('status' => m_cash::STATUS_VERIFY_CANCEL));
        //资金操作记录
        $lock = $this->m_account->lock($detail->user_id);
        if (empty($lock)) {
            $this->_my_output(false, ERR_TRY_AGAIN_NO, ERR_TRY_AGAIN_MSG);
        }
        $param = array(
            'income' => 0,
            'expend' => 0,
            'balance_cash' => $detail->total,
            'balance_frost' => 0,
            'frost' => -$detail->total,
            'await' => 0,
        );
        $param['user_id'] = intval($detail->user_id);
        $param['type'] = 'cash_cancel';
        $param['money'] = $detail->total;
        $param['remark'] = '提现取消' . $detail->total . '元';
        $param['to_userid'] = 0;
        $this->m_account->add_log($param);
        $this->m_account->unlock($detail->user_id);
        $r = $this->m_cash->detail($cash_id);
        $this->_my_output($r);
    }

    //查询余额
    public function balance_query() {
        JiXin\api_log::set(array(
            'api_name_cn' => '查询余额',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        $r = $this->jx->balanceQuery(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
        ));
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //查询余额(管理后台)
    public function balance_query_admin() {
        $condition = $this->api->in;
        $user_id = $this->m_bank_account->find_user_id($condition['q']);
        if (!$user_id) {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }

        JiXin\api_log::set(array(
            'api_name_cn' => '查询余额',
            'api_name_en' => __FUNCTION__,
            'user_id' => $user_id,
        ));

        if ($condition['q']) {
            if (strlen($condition['q']) == 19) {
                $ba_id = $condition['q'];
            } else {
                $ba_id = $this->m_bank_account->find($condition['q']);
            }
        } else {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }


        $r = $this->jx->balanceQuery(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba_id,
        ));
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //同步余额
    public function sync_balance_query() {
        JiXin\api_log::set(array(
            'api_name_cn' => '查询余额',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) { //未开户
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        $this->load->model('m_account');
        $account = $this->m_account->detail($user_id);  //资金表
        if ($account) {
            $startDate = $account->ba_inpDate ? $account->ba_inpDate : '20170329';  //上次查询的时间
        }
        $endDate = date('Ymd', time());

        $r = $this->jx->accountDetailsQuery(array(
            'channel' => '000002', //$this->api->in['channel'],
            'accountId' => $ba->account_id,
            'startDate' => '20170331',//$startDate,
            'endDate' => '20170402',//$endDate,
            'type' => '0',
            'tranType' => '',
            'pageNum' => '1',
            'pageSize' => '99',
        ));
        if ($r['result']['subPacks']) {
            $array = json_decode($r['result']['subPacks'], true);
            if (is_array($array)) {
                $reverse = array_reverse($array);
                foreach ($reverse as $k => $v) {
                    $this->load->model('m_account_log');
                    if (!$this->m_account_log->get_by_traceNo($v['traceNo'])) {//该流水号不在资金记录中
                        if ($v['txFlag'] === '+') {   //交易金额符号,是充值
                            $type_array = $this->get_account_type($v['tranType']);
                            if (!empty($type_array['type'])) {
                                //添加资金记录
                                $param = array(
                                    'income' => $v['txAmount'],
                                    'expend' => 0,
                                    'balance_cash' => $v['txAmount'],
                                    'balance_frost' => 0,
                                    'frost' => 0,
                                    'await' => 0,
                                    'total' => 0,
                                );
                                $param['user_id'] = intval($user_id);
                                $param['type'] = 'recharge';  //交易类型
                                $param['money'] = $v['txAmount'];
                                $param['remark'] = "存管_" . $v['describe'] . $v['txAmount'] . '元';
                                $param['to_userid'] = intval($user_id);

                                $param['ba_id'] = $v['accountId'];
                                $param['ba_accDate'] = $v['accDate'];
                                $param['ba_inpDate'] = $v['inpDate'];
                                $param['ba_relDate'] = $v['relDate'];
                                $param['ba_inpTime'] = $v['inpTime'];
                                $Y = substr($v['relDate'], 0, 4);
                                $m = substr($v['relDate'], 4, 2);
                                $d = substr($v['relDate'], 6, 2);
                                $H = substr($v['inpTime'], 0, 2);
                                $i = substr($v['inpTime'], 2, 2);
                                $s = substr($v['inpTime'], 4, 2);

                                $param['addtime'] = mktime($H, $i, $s, $m, $d, $Y);

                                $param['ba_traceNo'] = $v['traceNo'];
                                $param['ba_tranType'] = $v['tranType'];
                                $param['ba_tranTypeMsg'] = $this->tranType[$v['tranType']] ? $this->tranType[$v['tranType']] : '';
                                $type_array = $this->get_account_type($v['tranType']);
                                $param['ba_type'] = $type_array['type'];

                                //资金转出的交易类型
                                /* $type_array6 = array('2616','2820');    //提现
                                  $type_array7 = array('2780','2789','2831');    //扣款
                                  $type_array8 = array('2781','2788');    //还款
                                  $type_array9 = array('2792','2793','2833');    //红包
                                  $type_array10 = array('4616','4780','4781','4788','4820');    //手续费 */




                                $param['ba_orFlag'] = $v['orFlag'];
                                $param['ba_txFlag'] = $v['txFlag'];
                                $param['ba_orFlag'] = $v['orFlag'];
                                $param['ba_currBal'] = $v['currBal'];
                                $param['ba_forAccountId'] = $v['forAccountId'];
                                $this->load->model('m_account');
                                if ($this->m_account->lock($user_id)) {
                                    $this->m_account->add_log($param);
                                    $this->m_account->unlock($user_id);
                                }
                            }
                        }
                    }
                }
            } else {
                if ($array['txFlag'] === '+') {   //交易金额符号,是充值
                }
            }
        }

        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    public function get_account_type($type) {   //高端方法
        $type_array = array(
            1 => array('7616', '7820', '7822', '7782'), //绑定卡充值
            //2 => array('5500', '5504', '7777', '7781', '7788', '7792'), //收益类
            //3 => array('7783', '7792', '7833'), //红包
            //4 => array('7722', '7724', '7725', '7835', '9780', '9785', '9788', '9831'), //收入手续费
            //5 => array('7780', '7785', '7831', '7901', '7905', '7906', '7907', '7909', '7910'), //转入费
            5 => array('7780'),
        );
        $map = array();
        foreach ($type_array as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $map[$v2] = array(
                    'type' => $k,
                    'sub_type' => $k2,
                );
            }
        }
        return $map[$type];
    }

    //查询资金明细
    public function account_details_query() {
        $condition = $this->api->in;
        $user_id = $this->m_bank_account->find_user_id($condition['q']);
        if (!$user_id) {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
        JiXin\api_log::set(array(
            'api_name_cn' => '查询资金明细',
            'api_name_en' => __FUNCTION__,
            'user_id' => $user_id,
        ));

        if ($condition['q']) {
            if (strlen($condition['q']) == 19) {
                $ba_id = $condition['q'];
            } else {
                $ba_id = $this->m_bank_account->find($condition['q']);
            }
        } else {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }

        $endDate = date('Ymd', time());
        $r = $this->jx->accountDetailsQuery(array(
            'channel' => '000002', //$this->api->in['channel'],
            'accountId' => $ba_id,
            'startDate' => '20170329', //$condition['startDate'] ? $condition['startDate'] : '20170329',
            'endDate' => '20170331', //$condition['endDate'] ? $condition['endDate'] : $endDate,
            'type' => $condition['type'] ? $condition['type'] : 0,
            'tranType' => $condition['tranType'],
            'pageNum' => $condition['page'] ? $condition['page'] : 1,
            'pageSize' => $condition['size'] ? $condition['size'] : 20,
        ));
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //自动投标授权
    public function auto_bid_auth() {
        JiXin\api_log::set(array(
            'api_name_cn' => '自动投标授权',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        if ($ba->has_set_password->id == 2) {
            $this->_my_output(false, ERR_ACOOUNT_PASSWORD_NOT_SET_NO, ERR_ACOOUNT_PASSWORD_NOT_SET_MSG);
        }
        if ($ba->auto_bid->id == 1) {
            $this->_my_output(false, ERR_BA_AUTO_BID_HAS_SET_NO, ERR_BA_AUTO_BID_HAS_SET_MSG);
        }
        $action_id = $this->m_bank_account->action_add(array(
            'action' => 'autoBidAuthPlus',
            'user_id' => $user_id,
            'request' => json_encode(array(
                'channel' => $this->api->in['channel'],
                'txAmount' => sprintf('%.2f', $this->api->in['limit_max_money_single'] / 100),
                'totAmount' => sprintf('%.2f', $this->api->in['limit_max_money_total'] / 100),
                'lastSrvAuthCode' => $this->api->in['sms_auth'], //通过请求发送短信验证码接口获取
                'smsCode' => $this->api->in['sms_code'], //手机接收到短信验证码
            )),
            'ip' => get_ip(),
        ));
        $action = $this->m_bank_account->action_detail($action_id);
        $r = $this->jx->autoBidAuthPlus(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
            'orderId' => $action->order_sn,
            'txAmount' => sprintf('%.2f', $this->api->in['limit_max_money_single'] / 100),
            'totAmount' => sprintf('%.2f', $this->api->in['limit_max_money_total'] / 100),
            'lastSrvAuthCode' => $this->api->in['sms_auth'], //通过请求发送短信验证码接口获取
            'smsCode' => $this->api->in['sms_code'], //手机接收到短信验证码
        ));
        $this->_my_output_string($r);
    }

    //撤销自动投标授权
    public function auto_bid_auth_cancel() {
        JiXin\api_log::set(array(
            'api_name_cn' => '撤销自动投标授权',
            'api_name_en' => __FUNCTION__,
            'user_id' => $this->api->user()->user_id,
        ));
        $user_id = $this->api->user()->user_id;
        $ba = $this->m_bank_account->detail($user_id);
        if ($ba->status->id != m_bank_account::STATUS_ACCOUNT_ENABLE) {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        if ($ba->auto_bid->id == 2) {
            $this->_my_output(false, ERR_BA_AUTO_BID_NOT_SET_NO, ERR_BA_AUTO_BID_NOT_SET_MSG);
        }
        //todo 解除前检查是否有未完成的自动投资，如果有，则不能解除
        $action_id = $this->m_bank_account->action_add(array(
            'action' => 'autoBidAuthCancel',
            'user_id' => $user_id,
            'request' => json_encode(array(
                'channel' => $this->api->in['channel'],
                'orgOrderId' => $ba->auto_bid_order_sn,
            )),
            'ip' => get_ip(),
        ));
        $action = $this->m_bank_account->action_detail($action_id);
        $r = $this->jx->autoBidAuthCancel(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba->account_id,
            'orderId' => $action->order_sn,
            'orgOrderId' => $ba->auto_bid_order_sn,
        ));
        if ($r['retcode'] === '00000000') {//成功
            $this->m_bank_account->cancel_auto_bid_auth($ba->account_id, $r['result']);
            $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
        } else {
            $this->_my_output(false, $r['retcode'], $r['msg']);
        }
    }

    private function _my_output($result, $code = false, $msg = false) {
        JiXin\api_log::set(array(
            'response' => array(
                'result' => $result,
                'error_no' => $code,
                'error_msg' => $msg
            ),
        ));
        JiXin\api_log::write();
        $this->api->output($result, $code, $msg);
    }

    private function _my_output_string($string) {
        JiXin\api_log::set(array(
            'response' => $string,
        ));
        JiXin\api_log::write();
        $this->api->output_string($string);
    }

    //查询投资申请
    public function bid_apply_query() {
        $condition = $this->api->in;
        $user_id = $this->m_bank_account->find_user_id($condition['q']);
        if (!$user_id) {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
        JiXin\api_log::set(array(
            'api_name_cn' => '查询投资申请',
            'api_name_en' => __FUNCTION__,
            'user_id' => $user_id,
        ));

        if ($condition['q']) {
            if (strlen($condition['q']) == 19) {
                $ba_id = $condition['q'];
            } else {
                $ba_id = $this->m_bank_account->find($condition['q']);
            }
        } else {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }


        $r = $this->jx->bidApplyQuery(array(
            'channel' => $this->api->in['channel'],
            'accountId' => $ba_id,
            'orgOrderId' => $condition['orgOrderId'],
        ));
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //借款人标的信息查询
    public function debt_details_query() {
        $condition = $this->api->in;
        $user_id = $this->m_bank_account->find_user_id($condition['q']);
        if (!$user_id) {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
        JiXin\api_log::set(array(
            'api_name_cn' => '标的信息查询',
            'api_name_en' => __FUNCTION__,
            'user_id' => $user_id,
        ));

        if ($condition['q']) {
            if (strlen($condition['q']) == 19) {
                $ba_id = $condition['q'];
            } else {
                $ba_id = $this->m_bank_account->find($condition['q']);
            }
        } else {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }

        $endDate = date('Ymd', time());
        $r = $this->jx->debtDetailsQuery(array(
            'channel' => $condition['channel'], //$this->api->in['channel'],
            'accountId' => $ba_id,
            'productId' => ARY2::_10_to_38($condition['productId']),
            'state' => '0',
            'startDate' => $condition['startDate'] ? $condition['startDate'] : '20140101',
            'endDate' => $condition['endDate'] ? $condition['endDate'] : $endDate,
            'pageNum' => $condition['page'] ? $condition['page'] : 1,
            'pageSize' => $condition['size'] ? $condition['size'] : 20,
        ));
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //投资人债权明细查询
    public function credit_details_query() {
        $condition = $this->api->in;
        $user_id = $this->m_bank_account->find_user_id($condition['q']);
        if (!$user_id) {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
        JiXin\api_log::set(array(
            'api_name_cn' => '债权明细查询',
            'api_name_en' => __FUNCTION__,
            'user_id' => $user_id,
        ));

        if ($condition['q']) {
            if (strlen($condition['q']) == 19) {
                $ba_id = $condition['q'];
            } else {
                $ba_id = $this->m_bank_account->find($condition['q']);
            }
        } else {
            $this->_my_output(false, ERR_BA_ACCOUNT_NOT_OPENED_NO, ERR_BA_ACCOUNT_NOT_OPENED_MSG);
        }
        $endDate = date('Ymd', time());
        $r = $this->jx->creditDetailsQuery(array(
            'channel' => $condition['channel'], //$this->api->in['channel'],
            'accountId' => $ba_id,
            'productId' => ARY2::_10_to_38($condition['productId']),
            'state' => '0',
            'startDate' => $condition['startDate'] ? $condition['startDate'] : '20140101',
            'endDate' => $condition['endDate'] ? $condition['endDate'] : $endDate,
            'pageNum' => $condition['page'] ? $condition['page'] : 1,
            'pageSize' => $condition['size'] ? $condition['size'] : 20,
        ));
        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

    //文件下载
    public function fileDownload() {
        JiXin\api_log::set(array(
            'api_name_cn' => '文件下载',
            'api_name_en' => __FUNCTION__,
            'user_id' => 0,
        ));

        $r = $this->jx->fileDownload(array(
            'txDate' => $this->api->in['txDate']
        ));

        $this->_my_output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
    }

}
