<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dailuopan
 *
 * @author win7
 */
class dailuopan extends CI_Controller {

    const KEY = 'ifcar99@dailuopan';

    public function __construct() {
        parent::__construct();
    }

    // 登录
    public function login() {
        $this->load->model('m_user');
        //后台添加访问权限
        $this->api->in['from'] = 'dailuopan';
        $this->api->in['device'] = 'dailuopan';
        if ($this->m_user->is_loginname_exists($this->api->in['username'])) {
            $user_id = $this->m_user->check($this->api->in['username'], $this->api->in['password']);
            if (!$user_id) {
                $this->api->output(false, ERR_WRONG_PASSWORD_NO, ERR_WRONG_PASSWORD_MSG);
            }
            if (!$this->m_user->check_from($user_id, $this->api->in['from'])) {
                $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
            }
            $user = $this->m_user->detail($user_id);
            if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
            }
            $token = $this->m_user->login($user_id, $this->api->in['from'], $this->api->in['device']);
            $result = array(
                'result' => 1,
                'resultmsg' => '',
                'data' => array(
                    'token' => $token->token,
                )
            );
            echo json_encode($result);
        } else {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }

    public function borrow_lists() {
        $this->load->model('m_borrow');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = array(
            'status' => m_borrow::STATUS_VERIFY_FULL,
        );
        $condition['start_time'] = strtotime($this->api->in['date']);
        $condition['end_time'] = $condition['start_time'] + 3600 * 24 - 1;
        $rows_tmp = $this->m_borrow->lists($condition, $page, $size, 'id asc');
        $count = $this->m_borrow->count($condition);
        $rows = array();
        $ways_map = array(
            '2' => 1,
            '3' => 5,
        );
        foreach ($rows_tmp as $k => $v) {
            if ($v->money_unget < 0) {
                continue;
            }
            $tender_rows_tmp = $this->m_borrow->tender_lists(array('borrow_id' => $v->borrow_id));
            $tender_rows = array();
            foreach ($tender_rows_tmp as $k2 => $v2) {
                $tender_rows[] = array(
                    'subscribeUserName' => substr(md5($v2['user_id'] . self::KEY), 8, 16),
                    'amount' => sprintf('%.2f', $v2['account']),
                    'validAmount' => sprintf('%.2f', $v2['account']),
                    'addDate' => date('Y-m-d H:i:s', $v2['addtime']),
                    'status' => 1,
                    'type' => 0,
                );
            }
            $rows[] = array(
                'projectId' => $v->borrow_id,
                'title' => $v->title,
                'amount' => sprintf('%.2f', $v->money_total),
                'schedule' => sprintf('%.2f', round($v->money_get * 100 / $v->money_total, 2)),
                'interestRate' => sprintf('%.2f', $v->rate) . '%',
                'deadline' => $v->days,
                'deadlineUnit' => '天',
                'reward' => 0,
                'type' => '车商贷',
                /*
                 * 还款方式
                 * 1：到期还本息(到期还本付息，一次性还本付息，按日计息到期还本,一次性付款、秒还)
                 * 2：每月等额本息(按月分期，按月等额本息)
                 * 3：每季分期（按季分期，按季等额本息）
                 * 5：每月付息到期还本(先息后本)
                 * 6：等额本金(按月等额本金)
                 * 7：每季付息到期还本（按季付息到期还本）
                 * 8：每月付息分期还本
                 * 9：先付息到期还本
                 */
                'repaymentType' => $ways_map[$v->repay_way],
                'subscribes' => $tender_rows, //投资记录
                'userName' => substr(md5($v->borrow_id . self::KEY), 8, 16), //发标人ID
                'amountUsedDesc' => $v->desc->desc, //借款用途
                'loanUrl' => 'https://www.ifcar99.com/?invest&nid=full_success&article_id=' . $v->borrow_id, //标的链接
                'successTime' => date('Y-m-d H:i:s', $v->full_time),
                'publishTime' => date('Y-m-d H:i:s', $v->create_time),
            );
        }
        $result = array(
            'result' => 1,
            'resultmsg' => '',
            'totalPage' => ceil($count / $size),
            'currentPage' => $page,
            'borrowList' => $rows,
        );
        echo json_encode($result);
    }

}
