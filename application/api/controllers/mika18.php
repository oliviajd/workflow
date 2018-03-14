<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of mika18
 *
 * @author win7
 */
class mika18 extends CI_Controller {

    //const API_URL = 'http://www.mika18.com/Ceshi/cps.html';
    const API_URL = 'https://www.mika18.com/Track/cps.html';//正式地址
    const KEY = 'ifcar99@mika18';

    public function __construct() {
        parent::__construct();
    }

    public function borrow_lists() {
        $this->load->model('m_borrow');
        $rows = $this->m_borrow->lists(array('status' => m_borrow::STATUS_VERIFY_ONLINE), false, false, 'id desc');
        $result = array();
        foreach ($rows as $k => $v) {
//            var_dump($v);exit;
            if ($v->money_unget <= 0) {
                continue;
            }
            $result[] = array(
                'bname' => $v->title,
                'jkje' => sprintf('%.2f', $v->money_total),
                'nhsy' => sprintf('%.2f', $v->rate),
                'dkqx' => $v->days . '天',
                'tzjd' => sprintf('%.2f', round($v->money_get * 100 / $v->money_total, 2)),
                'hkfs' => '到期还本还息',
                'ktje' => sprintf('%.2f', $v->money_unget),
                'qtje' => sprintf('%.2f', max($v->limit_lower_money, 100.00)),
                'key' => md5(self::KEY)
            );
        }
        echo json_encode($result);
    }

    public function index() {
        $this->db->insert(TABLE_USER_THIRD, array(
            'user_id' => 0,
            'third_name' => 'mika18',
            'third_value' => json_encode(array(
                'zid' => $this->api->in['zid'],
                'uid' => $this->api->in['uid'],
            )),
            'create_time' => time(),
            'from_url' => $_SERVER['HTTP_REFERER'],
            'ip' => get_ip(),
        ));
        $id = $this->db->insert_id();
        $key = md5(rand(1000, 9999) . 'mika18' . $id);
        $this->db->update(TABLE_USER_THIRD, array('key' => md5($key)), array('id' => $id));
        setcookie('utm_source', 'mika18', time() + 3600 * 24 * 30, '/');
        setcookie('uid', $this->api->in['uid'], time() + 3600 * 24 * 30, '/');
        setcookie('zid', $this->api->in['zid'], time() + 3600 * 24 * 30, '/');
        header('location:https://www.ifcar99.com/index.php?user&q=reg&from=' . $key);
        exit;
    }

    //传入tender_id
    public function send_tender() {
        $this->load->model('m_borrow');
        $this->load->model('m_user');

        $tender = $this->m_borrow->tender_detail($this->api->in['tender_id']);
        if (!$tender) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }

        $user = $this->m_user->detail($tender->user_id);

        $borrow = $this->m_borrow->detail($tender->borrow_id);

        if ($borrow->days < 30) {
            $this->api->output(false);
        }

        $user_third = $this->db->limit(1)->get_where(TABLE_USER_THIRD, array(
                    'user_id' => $user->user_id,
                    'third_name' => 'mika18',
                ))->row_array(0);

        $user_third_value = json_decode($user_third['third_value'], true);

        //判断是否30天项目首投
        $this->db->limit(1);
        $this->db->select(TABLE_BORROW_TENDER . '.id');
        $this->db->where(TABLE_BORROW_TENDER . '.status <> 3', false, false);
        $this->db->where(TABLE_BORROW_TENDER . '.id < ', $this->api->in['tender_id'], false);
        $this->db->join(TABLE_BORROW, TABLE_BORROW . '.borrow_nid = ' . TABLE_BORROW_TENDER . '.borrow_nid', 'inner');
        $this->db->where(TABLE_BORROW . '.borrow_period', 1);
        $has_tendered = $this->db->get_where(TABLE_BORROW_TENDER, array(TABLE_BORROW_TENDER . '.user_id' => $tender->user_id))->row(0)->id ? true : false;

        //正式只推送首单
        if ($has_tendered) {
            $this->api->output(false);
        }

        $param = array(
            'action' => 'create',
            'goodsmark' => $has_tendered ? 2 : 3, //3首单，2其他单
            'username' => $user->mobile,
            'usermobile' => $user->mobile,
            'sig' => md5(self::KEY),
            'goodsnh' => sprintf('%.2f', $borrow->rate),
            'goodsname' => "名称:{$borrow->title},周期:{$borrow->days}天",
            'status' => sprintf("【%.2f元：已付款】", $tender->money - $tender->money_bouns),
            'zid' => $user_third_value['zid'],
            'uid' => $user_third_value['uid'],
        );
        $r = curl_get(self::API_URL, $param);
        do_log(__FUNCTION__,self::API_URL, $param,array('result'=>$r));
        $this->api->output(true);
    }

    public function send_register() {
        $this->load->model('m_user');

        $user = $this->m_user->detail($this->api->in['user_id']);
        if (!$user) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }

        $user_third = $this->db->limit(1)->get_where(TABLE_USER_THIRD, array(
                    'user_id' => $user->user_id,
                    'third_name' => 'mika18',
                ))->row_array(0);

        $user_third_value = json_decode($user_third['third_value'], true);

        $param = array(
            'action' => 'create',
            'goodsmark' => 1,
            'username' => $user->mobile,
            'usermobile' => $user->mobile,
            'sig' => md5(self::KEY),
            'zid' => $user_third_value['zid'],
            'uid' => $user_third_value['uid'],
        );
        $r = curl_get(self::API_URL, $param);
        do_log(__FUNCTION__,self::API_URL, $param,array('result'=>$r));
        $this->api->output(true);
    }

}
