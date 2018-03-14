<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of wangdaizhijia
 *
 * @author win7
 */
class mingjinwang extends CI_Controller {

    const KEY = 'ifcar99@mingjinwang#2017';

    public function __construct() {
        parent::__construct();
    }

    public function borrow_lists() {
        $this->load->model('m_borrow');
        $page = intval($this->api->in['page_index']) > 0 ? intval($this->api->in['page_index']) : 1;
        $size = intval($this->api->in['page_size']) > 0 ? intval($this->api->in['page_size']) : 100;
        if ($this->api->in['status'] == 1) {//已完成
            $condition = array(
                'status' => m_borrow::STATUS_VERIFY_FULL,
            );
        } else if ($this->api->in['status'] == 2) {//未完成
            $condition = array(
                'status' => m_borrow::STATUS_VERIFY_ONLINE,
            );
        } else {
            $condition = array(
                'status' => array(m_borrow::STATUS_VERIFY_ONLINE, m_borrow::STATUS_VERIFY_FULL),
            );
        }
        $condition['success_start_time'] = strtotime($this->api->in['time_from']);
        $condition['success_end_time'] = strtotime($this->api->in['time_to']);
        $rows_tmp = $this->m_borrow->lists($condition, $page, $size, 'id asc');
        $count = $this->m_borrow->count($condition);
        $rows = array();
        foreach ($rows_tmp as $k => $v) {
            $rows[] = array(
                'id' => $v->borrow_id,
                'link' => '?invest&nid=full_success&article_id=' . $v->borrow_id, //标的链接
                'title' => $v->title,
                'username' => substr(md5($v->borrow_id . self::KEY), 8, 16), //发标人ID
                'userid' => substr(md5($v->borrow_id . self::KEY), 8, 16), //发标人ID
                'borrow_type' => '6',
                'amount' => floatval(sprintf('%.2f', $v->money_total)),
                'interest' => floatval(sprintf('%.4f', $v->rate / 100)),
                'period' => $v->days,
                'period_type' => 0,
                'repay_type' => '3',
                /*
                 * 0:代表其他；
                  1:按月等额本息还款；
                  2:按月付息,到期还本,
                  3:按天计息，一次性还本付息；
                  4:按月计息，一次性还本付息；
                  5: 按季等额本息还款
                  6: 按月等额还本，到期付息
                  7：按季等额还本，到期付息
                  8：按季付息，到期还本
                 */
                'process' => floatval(sprintf('%.2f', round($v->money_get / $v->money_total, 2))),
                'verify_time' => date('Y-m-d H:i:s', $v->create_time),
                'reverify_time' => date('Y-m-d H:i:s', $v->full_time),
                'invest_count' => intval($v->tender_times),
                'status' => $v->status->id == m_borrow::STATUS_VERIFY_FULL ? 1 : 0,
                'amount_used_desc' => $v->desc->desc, //借款用途
            );
        }
        $result = array(
            'result_code' => 1,
            'result_msg' => '获取数据成功',
            'page_count' => ceil($count / $size),
            'page_index' => $page,
            'data' => $rows,
        );
        echo json_encode($result);
    }

    public function tender_lists() {
        $this->load->model('m_borrow');
        $page = intval($this->api->in['page_index']) > 0 ? intval($this->api->in['page_index']) : 1;
        $size = intval($this->api->in['page_size']) > 0 ? intval($this->api->in['page_size']) : 100;

        $tender_rows_tmp = $this->m_borrow->tender_lists(array('borrow_id' => $this->api->in['id']));
        $count = count($tender_rows_tmp);
        $tender_rows = array();
        foreach ($tender_rows_tmp as $k2 => $v2) {
            $tender_rows[] = array(
                'id' => $this->api->in['id'],
                'link' => '?invest&nid=full_success&article_id=' . $this->api->in['id'], //标的链接
                'username' => substr(md5($v2['user_id'] . self::KEY), 8, 16),
                'userid' => substr(md5($v2['user_id'] . self::KEY), 8, 16),
                'type' => '手动',
                'money' => floatval(sprintf('%.2f', $v2['account'])),
                'account' => floatval(sprintf('%.2f', $v2['account'])),
                'add_time' => date('Y-m-d H:i:s', $v2['addtime']),
                'status' => '成功',
            );
        }
        $result = array(
            'result_code' => 1,
            'result_msg' => '获取数据成功',
            'page_count' => ceil($count / $size),
            'page_index' => $page,
            'data' => $tender_rows,
        );
        echo json_encode($result);
    }

}
