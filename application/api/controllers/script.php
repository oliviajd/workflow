<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of script
 *
 * @author win7
 */
class script extends CI_Controller {

    public function flow_recharge() {
        $this->load->model('m_order');
        $this->load->model('m_flow_recharge');
        $rows = $this->m_flow_recharge->lists(array('status' => m_flow_recharge::STATUS_RECHARGE_INIT), 1, 10, 'id asc');
        $result = array();
        foreach ($rows as $k => $v) {
            $this->m_flow_recharge->recharge_start($v['id']);

            $r = $this->m_flow_recharge->recharge($v);
            if ($r['code'] != 'M0001') {//请求失败
                $this->m_flow_recharge->recharge_process($v['id'], $r);
                $this->m_flow_recharge->recharge_failed($v['id'], $r);
                //TODO 退抽奖次数
                //此处暂不做失败处理，以订单号在连连的查询为主
                //$this->m_flow_recharge->recharge_failed($v['id'], '请求支付失败,未执行查询');
            } else {
                $this->m_flow_recharge->recharge_process($v['id'], $r);
            }
        }
        $this->api->output(true);
    }

    public function phone_recharge() {
        $this->load->model('m_order');
        $this->load->model('m_phone_recharge');
        $rows = $this->m_phone_recharge->lists(array('status' => m_phone_recharge::STATUS_RECHARGE_INIT), 1, 10, 'id asc');
        $result = array();
        foreach ($rows as $k => $v) {
            $this->m_phone_recharge->recharge_start($v['id']);
            $this->m_order->shipping($v['oid'], array(
                'shipping_company' => 'LLPAY',
                'shipping_sn' => 'LLPAY' . $v['id']
            ));
            $r = $this->m_phone_recharge->recharge($v);
            if ($r['status'] != 1) {//请求支付失败
                $this->m_phone_recharge->recharge_process($v['id'], $r);
                //此处暂不做失败处理，以订单号在连连的查询为主
                //$this->m_phone_recharge->recharge_failed($v['id'], '请求支付失败,未执行查询');
            } else {
                $this->m_phone_recharge->recharge_process($v['id'], $r);
            }
        }
        $this->api->output(true);
    }

    public function phone_recharge_finish() {
        $this->load->model('m_order');
        $this->load->model('m_phone_recharge');
        $rows = $this->m_phone_recharge->lists(array('status' => m_phone_recharge::STATUS_RECHARGE_PROCESSING), 1, 10, 'id asc');
        foreach ($rows as $k => $v) {
            $r = $this->m_phone_recharge->query($v);
            switch ($r['body']['status']) {
                case 'SUCCESS'://充值成功
                    $this->m_phone_recharge->recharge_finish($v['id'], $r);
                    break;
                case 'PROCESS'://处理中
                    //todo 是否记录查询次数? 每分钟查询一次，超过20分钟设为失败
                    if (time() - $v['modify_time'] > 20 * 60) {
                        $this->m_phone_recharge->recharge_failed($v['id'], $r);
                        //退积分 退商品 退充值限额 退用户商品购买数
                        $order = $this->m_order->detail($v['oid']);
                        $this->m_goods->increase($v['iid'], 1);
                        $this->m_order->decrease_goods_user_buy($v['user_id'], $v['iid'], $v['num']);
                        foreach ($order->order_goods as $k2 => $v2) {
                            if ($v2->iid == $v['iid']) {
                                //暂不退积分
                                $recharge = json_decode($v['option'], true);
                                $this->m_phone_recharge->user_month_limit_increase($v['user_id'], intval($recharge['face_price']) * 1);
                            }
                        }
                    }
                    $this->m_phone_recharge->increase_query_times($v['id']);
                    break;
                case 'FAILED'://充值失败
                    $this->m_phone_recharge->recharge_failed($v['id'], $r);
                    //退积分 退商品 退充值限额 退用户商品购买数 关闭订单
                    $type = 'order_refund';
                    $order = $this->m_order->detail($v['oid']);
                    $this->m_goods->increase($v['iid'], 1);
                    $this->m_order->decrease_goods_user_buy($v['user_id'], $v['iid'], 1);
                    foreach ($order->order_goods as $k2 => $v2) {
                        if ($v2->iid == $v['iid']) {
                            $this->m_credit->increase($order->user->user_id, $v2->price, array(
                                'type' => $type,
                                'item_id' => $v['oid'],
                                'remark' => "积分商城退款[{$v2->title}({$v2->iid})]",
                            ));
                            $recharge = json_decode($v2->option, true);
                            $this->m_phone_recharge->user_month_limit_increase($v['user_id'], intval($recharge['face_price']) * 1);
                        }
                    }
                    break;
                default :
                    //未查到记录
                    $this->m_phone_recharge->recharge_failed($v['id'], $r);
                    //退积分 退商品 退充值限额 退用户商品购买数
                    $type = 'order_refund';
                    $order = $this->m_order->detail($v['oid']);
                    $this->m_goods->increase($v['iid'], 1);
                    $this->m_order->decrease_goods_user_buy($v['user_id'], $v['iid'], 1);
                    foreach ($order->order_goods as $k2 => $v2) {
                        if ($v2->iid == $v['iid']) {
                            $this->m_credit->increase($order->user->user_id, $v2->price, array(
                                'type' => $type,
                                'item_id' => $v['oid'],
                                'remark' => "积分商城退款[{$v2->title}({$v2->iid})]",
                            ));
                            $recharge = json_decode($v2->option, true);
                            $this->m_phone_recharge->user_month_limit_increase($v['user_id'], intval($recharge['face_price']) * 1);
                        }
                    }
                    $this->m_phone_recharge->increase_query_times($v['id']);
                    break;
            }
            //判断该订单下的充值是否都已完成
            $order_status = $this->m_phone_recharge->order_status($v['oid']);
            $success = false;
            $failed = false;
            $close = false;
            $order_status_map = array();
            $order_count = count($order_status);
            foreach ($order_status as $k2 => $v2) {
                if (!isset($order_status_map[$v2['status']])) {
                    $order_status_map[$v2['status']] = 0;
                }
                $order_status_map[$v2['status']] += 1;
            }
            if ($order_status_map[m_phone_recharge::STATUS_RECHARGE_SUCCESS] == $order_count) {//订单已全部成功
                $this->m_order->finish($v['oid']);
            } else if ($order_status_map[m_phone_recharge::STATUS_RECHARGE_FAILED] == $order_count) {//订单已全部失败
                $this->m_order->failed($v['oid'], array('remark_sys' => '订单中的所有商品未成功充值'));
                echo $this->db->last_query();
            } else if ($order_status_map[m_phone_recharge::STATUS_RECHARGE_SUCCESS] + $order_status_map[m_phone_recharge::STATUS_RECHARGE_FAILED] == $order_count) {//订单已全部完成
                $this->m_order->close($v['oid'], array('remark_sys' => '订单中的部分商品未成功充值'));
            } else {
                //未完成则继续执行队列
            }
//            if ($this->m_phone_recharge->is_order_all_success($v['oid'])) {//订单已全部成功
//                $this->m_order->finish($v['oid']);
//            } else if ($this->m_phone_recharge->is_order_failed($v['oid'])) {
//                //未完成则继续执行队列
//                $this->m_order->failed($v['oid'], array('remark_sys' => '订单中的所有商品未成功充值'));
//            } else if ($this->m_phone_recharge->is_order_finish($v['oid'])) {//订单部分成功
//                $this->m_order->close($v['oid'], array('remark_sys' => '订单中的部分商品未成功充值'));
//            }
        }
        $this->api->output(true);
    }

    public function invest_account_process() {
        set_time_limit(0);
        $this->db->select('max(id) as max');
        $max = $this->db->get_where(TABLE_BORROW_TENDER)->row(0)->max;
        $this->db->select('max(borrow_tender_id) as max');
        $start = intval($this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->row(0)->max);
        $borrows = array();
        $users = array();
        while ($start < $max) {
            //获取用户投资信息
            $this->db->order_by('id asc');
            $this->db->where('id > ' . $start, false, false);
            $logs = $this->db->get_where(TABLE_BORROW_TENDER, array(), 1000)->result_array();
            foreach ($logs as $k2 => $v2) {
                if (!isset($borrows[$v2['borrow_nid']])) {
                    $borrow = $this->db->get_where(TABLE_BORROW, array('borrow_nid' => $v2['borrow_nid']))->row_array();
                    $borrows[$v2['borrow_nid']] = $borrow;
                } else {
                    $borrow = $borrows[$v2['borrow_nid']];
                }
                if (!isset($users[$v2['user_id']])) {
                    $user = $this->db->get_where(TABLE_USER_INFO, array('user_id' => $v2['user_id']))->row_array();
                    $users[$v2['user_id']] = $user;
                } else {
                    $user = $users[$v2['user_id']];
                }
                $i = 1;
                $days = 30;
                switch (floatval($borrow['borrow_period'])) {
                    case 1:
                        $i = 1;
                        $days = 30;
                        break;
                    case 0.33:
                        $i = 3;
                        $days = 10;
                        break;
                    case 0.5:
                        $i = 2;
                        $days = 15;
                        break;
                    default:
                        break;
                }
                $date_start = $v2['addtime'];
                $avg = floor($v2['account'] / $days);
                $avg_achievement = floor($avg / $i);
                $params = array();
                for ($d = 0; $d < $days; $d++) {
                    $date_current = $date_start + 3600 * 24 * $d;
                    if ($d == $days - 1) {
                        $avg = $v2['account'] - ($days - 1) * $avg;
                    }
                    if ($d == $days - 1) {
                        $avg_achievement = ceil($v2['account'] / $i) - ($days - 1) * $avg_achievement;
                    }
                    $params[] = array(
                        'borrow_nid' => $borrow['borrow_nid'],
                        'borrow_title' => $borrow['name'],
                        'user_id' => $v2['user_id'],
                        'borrow_tender_id' => $v2['id'],
                        'times' => $d + 1,
                        'avg_add' => $d == 0 ? $avg : 0,
                        'days' => $days,
                        'amount' => $v2['account'],
                        'amount_achievement' => ceil($v2['account'] / $i),
                        'avg' => $avg,
                        'avg_achievement' => $avg_achievement,
                        'year' => date('Y', $date_current),
                        'month' => date('m', $date_current),
                        'day' => date('d', $date_current),
                        'ymd' => date('Ymd', $date_current),
                        'ym' => date('Ym', $date_current),
                        'owner_user_id' => intval($user['mer_userid'] ? $user['mer_userid'] : ($user['invite_userid'] == '7088' ? 0 : $user['invite_userid'])), //聚车账号特殊处理
                        'inviter_user_id' => $user['invite_userid'],
                        'manager_user_id' => $user['mer_userid'],
                        'invest_time' => date('Ymd', $v2['addtime']),
                        'create_time' => time(),
                    );
                }
                $this->db->insert_batch(TABLE_INVEST_ACCOUNT_STAT_DAILY, $params);
            }
            break;
        }
        $this->api->output(true);
    }

    //红包相关的定时操作
    public function bouns_interval() {
        $this->load->model('m_bouns');
        //红包过期
        $this->db->where('endtime < ' . time(), false, false);
        $this->db->update(TABLE_BOUNS_USER, array('status' => m_bouns::STATUS_USER_EXPIRE), array('status' => m_bouns::STATUS_USER_NOT_USE));
        //红包合并锁定状态超时解锁
        $this->api->output($this->db->affected_rows());
    }

    //加息券相关的定时操作
    public function coupon_interval() {
        $this->load->model('m_coupon');
        //红包过期
        $this->db->where('expire_time < ' . time(), false, false);
        $this->db->update(TABLE_COUPON_USER, array('status' => m_coupon::STATUS_USER_EXPIRE), array('status' => m_coupon::STATUS_USER_NOT_USE));
        $this->api->output($this->db->affected_rows());
    }

    public function coupon_activity_161124() {
        $this->load->library('admin_runner');
        if ($_SERVER['SERVER_NAME'] == 'api.car.com') {
            $coupon_id = array(
                '1' => 10,
                '2' => 11,
                '3' => 12,
                '4' => 13,
            );
        } else if ($_SERVER['SERVER_NAME'] == 'apitest.ifcar99.com') {
            $coupon_id = array(
                '1' => 16,
                '2' => 17,
                '3' => 18,
                '4' => 19,
            );
        } else if ($_SERVER['SERVER_NAME'] == 'api.ifcar99.com') {
            $coupon_id = array(
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
            );
        }
        //第一张
        $this->db->where('num > 1', false, false);
        $this->db->where('status_1', 2);
        $rows_1 = $this->db->get_where(TABLE_COUPON_ACTIVITY_161124)->result_array();
        foreach ($rows_1 as $k => $v) {
            $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_1' => 4), array('id' => $v['id']));
            //发放加息券
            $this->load->library('admin_runner');
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/coupon/send';

            $result = curl_upload($url, array(
                'coupon_id' => $coupon_id[1],
                'user_id' => $v['user_id'],
                'remark' => '邀请送加息券',
                'token' => $this->admin_runner->get_token()
            ));
            $obj = json_decode($result);

            if ($obj->error_no == 200) {
                //发放成功
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_1' => 5), array('id' => $v['id']));
            } else {
                //失败
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_1' => 2), array('id' => $v['id']));
            }
        }
        //第二张
        $this->db->where('num > 4', false, false);
        $this->db->where('status_2', 2);
        $rows_2 = $this->db->get_where(TABLE_COUPON_ACTIVITY_161124)->result_array();
        foreach ($rows_2 as $k => $v) {
            $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_2' => 4), array('id' => $v['id']));
            //发放加息券
            $this->load->library('admin_runner');
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/coupon/send';

            $result = curl_upload($url, array(
                'coupon_id' => $coupon_id[2],
                'user_id' => $v['user_id'],
                'remark' => '邀请送加息券',
                'token' => $this->admin_runner->get_token()
            ));
            $obj = json_decode($result);

            if ($obj->error_no == 200) {
                //发放成功
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_2' => 5), array('id' => $v['id']));
            } else {
                //失败
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_2' => 2), array('id' => $v['id']));
            }
        }
        //第三张
        $this->db->where('num > 7', false, false);
        $this->db->where('status_3', 2);
        $rows_3 = $this->db->get_where(TABLE_COUPON_ACTIVITY_161124)->result_array();
        foreach ($rows_3 as $k => $v) {
            $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_3' => 4), array('id' => $v['id']));
            //发放加息券
            $this->load->library('admin_runner');
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/coupon/send';

            $result = curl_upload($url, array(
                'coupon_id' => $coupon_id[3],
                'user_id' => $v['user_id'],
                'remark' => '邀请送加息券',
                'token' => $this->admin_runner->get_token()
            ));
            $obj = json_decode($result);

            if ($obj->error_no == 200) {
                //发放成功
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_3' => 5), array('id' => $v['id']));
            } else {
                //失败
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_3' => 2), array('id' => $v['id']));
            }
        }
        //第四张
        $this->db->where('num > 11', false, false);
        $this->db->where('status_4', 2);
        $rows_4 = $this->db->get_where(TABLE_COUPON_ACTIVITY_161124)->result_array();
        foreach ($rows_4 as $k => $v) {
            $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_4' => 4), array('id' => $v['id']));
            //发放加息券
            $this->load->library('admin_runner');
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/coupon/send';

            $result = curl_upload($url, array(
                'coupon_id' => $coupon_id[4],
                'user_id' => $v['user_id'],
                'remark' => '邀请送加息券',
                'token' => $this->admin_runner->get_token()
            ));
            $obj = json_decode($result);

            if ($obj->error_no == 200) {
                //发放成功
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_4' => 5), array('id' => $v['id']));
            } else {
                //失败
                $this->db->update(TABLE_COUPON_ACTIVITY_161124, array('status_4' => 2), array('id' => $v['id']));
            }
        }
        $this->api->output(true);
    }

    //标的数据同步第三方
    public function borrow_third_party() {
        ignore_user_abort();
        $this->load->model('m_script');
        $this->load->model('m_user');
        $this->load->model('m_borrow');
        $this->load->model('m_bouns');
        $this->load->model('m_credit');
        $this->db->db_debug = TRUE;

        $this->load->config('myconfig');
        $config = $this->config->item('51cunzheng');

        require_once APPPATH . 'libraries/AospClient.php';

        $pay_method = array(
            0 => '按月等额',
            1 => '按季还款',
            2 => '到期还本还息',
            3 => '按月还息到期还本',
        );

        $this->db->select(TABLE_QUEUE_BORROW . '.*');
        $this->db->join(TABLE_BORROW, TABLE_BORROW . '.id = ' . TABLE_QUEUE_BORROW . '.borrow_auto_id', 'inner');
        $this->db->where(TABLE_BORROW . '.status', m_borrow::STATUS_VERIFY_ONLINE);
        $rows = $this->db->get_where(TABLE_QUEUE_BORROW, array('ancun' => 2))->result_array();

        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW, array('ancun' => 3, 'modify_time' => time()), array('id' => $v['id']));
            $borrow = $this->m_borrow->detail_by_auto_id($v['borrow_auto_id']);
            //安存
            $ancun = array(
                'projectNo' => $borrow->borrow_id,
                'yearRate' => sprintf('%.2f', $borrow->rate) . '%',
                'projectAmount' => sprintf('%.2f元', $borrow->money_total),
                'projectPeriod' => $borrow->days . '天',
                'startAmount' => sprintf('%.2f元', max(100.00, $borrow->limit_lower_money)),
                'payMethod' => $pay_method[$borrow->repay_way],
                'checkPassTime' => date("Y-m-d H:i:s", $borrow->create_time),
                'publishTime' => date("Y-m-d H:i:s", $borrow->create_time),
                'stopTime' => date("Y-m-d H:i:s", max($borrow->create_time, $borrow->limit_on_time) + $borrow->expire),
                'borrower' => '浙江杭标控股集团有限公司',
                'borrowerOrg' => '09516489-1',
                'carInfo' => $borrow->desc['user_desc'],
            );
            $aospRequest = new AospRequest();
            $aospRequest->setItemKey("I-0081002");
            $aospRequest->setFlowNo("X-0255001");
            $aospRequest->setData($ancun);

            $aospClient = new AospClient($config['api_url'], $config['key'], $config['secret']);
            $aospResponse = $aospClient->save($aospRequest);
            $ancunData = $aospResponse->getData();
            $ancunCode = $aospResponse->getCode();

            if ($ancunCode == 100000) {
                $ancunRecord = $ancunData['recordNo'];
                $this->db->update(TABLE_BORROW, array('recordNo' => $ancunRecord), array('id' => $v['borrow_auto_id']));
            }
            $this->db->update(TABLE_QUEUE_BORROW, array('ancun' => 1, 'modify_time' => time(), 'ancun_result' => json_encode($ancunData)), array('id' => $v['id']));
        }
        $this->api->output(true);
        //融途
    }

    //投标数据对接第三方平台
    public function borrow_tender_third_party() {
        ignore_user_abort();
        $this->load->model('m_script');
        $this->load->model('m_user');
        $this->load->model('m_borrow');
        $this->load->model('m_bouns');
        $this->load->model('m_credit');
        $this->db->db_debug = TRUE;

        $this->load->config('myconfig');
        $config = $this->config->item('51cunzheng');
        $config_rongtu = $this->config->item('rongtu');

        require_once APPPATH . 'libraries/AospClient.php';

        $pay_method = array(
            0 => '按月等额',
            1 => '按季还款',
            2 => '到期还本还息',
            3 => '按月还息到期还本',
        );

        $rows = $this->db->get_where(TABLE_QUEUE_BORROW_TENDER, array('ancun' => 2))->result_array();

        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW_TENDER, array('ancun' => 3, 'modify_time' => time()), array('id' => $v['id']));
            $borrow = $this->m_borrow->detail($v['borrow_id']);
            $tender = $this->m_borrow->tender_detail($v['tender_id']);
            $user = $this->m_user->detail($v['user_id'], true);
            //安存
            $ancun = array(
                'userName' => $user->realname,
                'mobile' => $user->mobile,
                'idcardNo' => $user->id_card,
                'registerTime' => date("Y-m-d H:i:s", $user->register_time),
                'approveSuccessTime' => date("Y-m-d H:i:s", $user->verify_time),
                'projectNo' => $borrow->borrow_id,
                'yearRate' => sprintf('%.2f', $borrow->rate) . '%',
                'projectAmount' => sprintf('%.2f元', $borrow->money_total),
                'projectTerm' => $borrow->days . '天',
                'startAmount' => sprintf('%.2f', max($borrow->limit_lower_money, 100.00)),
                'payMethod' => $pay_method[$borrow->repay_way],
                'payAmount' => sprintf('%.2f元', $tender->money),
                'redEnvelope' => sprintf('%.2f元', intval($tender->money_bouns)),
                'paySuccessTime' => date("Y-m-d H:i:s", $tender->create_time),
                'buyTime' => date("Y-m-d H:i:s", $tender->create_time),
            );
            $aospRequest = new AospRequest();
            $aospRequest->setItemKey("I-0081001");
            $aospRequest->setFlowNo("X-0248001");
            $aospRequest->setData($ancun);
            $aospClient = new AospClient($config['api_url'], $config['key'], $config['secret']);
            $aospResponse = $aospClient->save($aospRequest);
            $ancunData = $aospResponse->getData();
            $ancunCode = $aospResponse->getCode();

            if ($ancunCode == 100000) {
                $ancunRecord = $ancunData['recordNo'];
                $this->db->update(TABLE_BORROW_TENDER, array('recordNo' => $ancunRecord), array('id' => $v['tender_id']));
            }
            $this->db->update(TABLE_QUEUE_BORROW_TENDER, array('ancun' => 1, 'modify_time' => time(), 'ancun_result' => json_encode($ancunData)), array('id' => $v['id']));
            //融途
            $rongtu = array(
                'borrowid' => $borrow->borrow_id,
                'name' => $borrow->title,
                'url' => "https://www.ifcar99.com/?invest&nid=full_success&article_id=" . $borrow->borrow_id,
                'isday' => '1',
                'timelimit' => '0',
                'timelimitday' => $borrow->days,
                'account' => $borrow->money_total,
                'owner' => $borrow->title,
                'apr' => $borrow->rate,
                'award' => 0,
                'partaccount' => 0,
                'funds' => 0,
                'repaymentType' => $borrow->repay_way == 3 ? 3 : 1,
                'type' => 1,
                'addtime' => 0,
                'sumTender' => $borrow->money_get,
                'startmoney' => 100,
                'tenderTimes' => $borrow->tender_times,
            );

            for ($i = 0; $i < 30; $i++) {
                $days[] = date("Y-m-d", strtotime(' -' . $i . 'day'));
                $days_i[] = date("m-d", strtotime(' -' . $i . 'day'));
            }
            $list = array();
            foreach ($days_i as $key => $val) {
                $starttime = strtotime($days[$key] . " 00:00:00");
                $endtime = strtotime($days[$key] . " 23:59:59");
                $apr_result = $this->db->query("select avg(borrow_apr) as avg from " . TABLE_BORROW . " where addtime > " . $starttime . " and addtime < " . $endtime)->row(0)->avg;
                $list['apr_data'][$val] = $apr_result ? number_format($apr_result, 2, '.', '') : 0;
                $count_result = $this->db->query("select avg(account) as avg from " . TABLE_BORROW . " where addtime > " . $starttime . " and addtime < " . $endtime)->row(0)->avg;
                $list['count_data'][$val] = $count_result ? number_format(($count_result / 10000), 2, '.', '') : 0;
                $dcount_result = $this->db->query("select avg(account) as avg from " . TABLE_BORROW . " where addtime > " . $starttime . " and addtime < " . $endtime . " and borrow_success_time > 0")->row(0)->avg;
                $list['dcount_data'][$val] = $dcount_result ? number_format(($dcount_result / 10000), 2, '.', '') : 0;
            }
            $account = $this->db->query("select sum(account) as sum from " . TABLE_BORROW . " where status = 3")->row(0)->sum;
            $list['time_data'] = "['1-3个月'," . number_format(($account / 10000), 2, '.', '') . "],['4-6个月',0],['7-12个月',0],['12个月以上',0]";
            $list['cj_data'] = number_format(($account / 10000), 2, '.', '');
            $ac_list = $this->db->query("select sum(account) as sum from " . TABLE_BORROW . " where status = 3 and borrow_success_time > 0 and borrow_end_time >" . time())->row(0)->sum;
            $list['dh_data'] = number_format(($ac_list / 10000), 2, '.', '');
            $starttime = strtotime(date("Y-m-d", strtotime('-1day')) . " 00:00:00");
            $endtime = strtotime(date("Y-m-d", strtotime('-1day')) . " 23:59:59");
            $apr_re = $this->db->query("select avg(borrow_apr) as avg from " . TABLE_BORROW . " where addtime > " . $starttime . " and addtime < " . $endtime)->row(0)->avg;
            $list['avg_apr'] = number_format($apr_re, 2, '.', '');
            //do_script_log($list);
            /*
              curl_upload($config_rongtu['test_url'], array(
              'dangan_id' => $config_rongtu['key'],
              'borrow' => json_encode($rongtu),
              'list' => json_encode($list),
              ));
             * 
             */
        }
        //网贷天眼
        $this->api->output(true);
    }

    public function borrow_tender_ba() {
        ignore_user_abort();
        $this->load->model('m_script');
        $this->load->model('m_bouns');
        $this->load->model('m_borrow_tender');
        $this->load->library('borrow_process');
        $this->db->db_debug = TRUE;
        $this->db->limit(10);
        $rows = $this->db->get_where(TABLE_BA_TENDER_NA, array('status' => m_borrow_tender::STATUS_P_BA_INIT))->result_array();
        $jx = new JiXin\api();
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_BA_TENDER_NA, array('status' => m_borrow_tender::STATUS_P_BA_START, 'modify_time' => time()), array('id' => $v['id']));
//            $borrow = $this->m_borrow->detail($v['borrow_id']);
            JiXin\api_log::set(array(
                'request_jc' => $this->api->in,
                'request_time' => time(),
                'start_time' => microtime(1),
                'api_name_cn' => '免密投标',
                'api_name_en' => 'bidAutoApply',
                'user_id' => $v['user_id'],
            ));
            $r = $jx->bidAutoApply(array(
                'channel' => $v['channel'], //交易渠道
                'accountId' => $v['accountId'], //电子账号
                'orderId' => $v['orderId'], //订单ID
                'txAmount' => $v['txAmount'], //交易金额
                'productId' => $v['productId'], //标的号
                'contOrderId' => $v['contOrderId'], //自动投标签约订单号
            ));
            JiXin\api_log::set(array(
                'response' => array(
                    'result' => $r['result'],
                    'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                    'error_msg' => $r['msg'],
                ),
            ));
            if ($r['retcode'] === '00000000') {
                JiXin\api_log::write();
                $this->db->queries = array();
                $this->db->query_times = array();
                //发送红包
                if ($v['bouns_user_id'] > 0) {
                    JiXin\api_log::set(array(
                        'request_jc' => $this->api->in,
                        'request_time' => time(),
                        'start_time' => microtime(1),
                        'api_name_cn' => '发送红包',
                        'api_name_en' => 'voucherPay',
                        'user_id' => $v['user_id'],
                    ));
                    $user_bouns = $this->m_bouns->user_detail($v['bouns_user_id']);
                    //金额大于200的为非法红包 不发放
                    if ($user_bouns->money <= 200) {
                        //红包发放记录
                        if ($this->m_bouns->start_pay($v['bouns_user_id'])) {
                            $r2 = $jx->voucherPay(array(
                                'channel' => '000002', //交易渠道
                                'accountId' => JiXin\config::bouns_accountId, //红包账号
                                'txAmount' => sprintf('%.2f', $user_bouns->money), //红包金额
                                'forAccountId' => $v['accountId'], //接收方账号
                                'desLineFlag' => '1', //是否使用交易描述1-使用0-不使用
                                'desLine' => '使用红包' . $v['bouns_user_id'], //交易描述,选填
                            ));
                            if ($r2['retcode'] === '00000000') {
                                $this->m_bouns->success_pay($v['bouns_user_id'], $r2);
                            } else {
                                $this->m_bouns->failed_pay($v['bouns_user_id'], $r2);
                            }
                        }
                    } else {
                        
                    }
                    JiXin\api_log::set(array(
                        'response' => array(
                            'result' => $r2['result'],
                            'error_no' => $r2['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r2['retcode'],
                            'error_msg' => $r2['msg'],
                        ),
                    ));
                    JiXin\api_log::write();
                    $this->db->queries = array();
                    $this->db->query_times = array();
                }
                $this->db->update(TABLE_BA_TENDER_NA, array('status' => m_borrow_tender::STATUS_P_BA_SUCCESS, 'modify_time' => time(), 'result' => print_r($r, true), 'authCode' => $r['result']['authCode']), array('id' => $v['id']));
                $this->db->update(TABLE_BORROW_TENDER, array('status' => 0), array('id' => $v['tender_id']));
            } else {
                JiXin\api_log::write();
                $this->db->queries = array();
                $this->db->query_times = array();
                //todo 重试机制
                $this->db->update(TABLE_BA_TENDER_NA, array('status' => m_borrow_tender::STATUS_P_BA_FAILED, 'modify_time' => time(), 'result' => print_r($r, true)), array('id' => $v['id']));
                $this->db->update(TABLE_BORROW_TENDER, array('status' => 3), array('id' => $v['tender_id']));
                //todo 回退机制
//                $this->borrow_process->load_string($v['process']);
//                $this->borrow_process->clean();
            }
        }
        $this->api->output(true);
    }

    //投标活动脚本
    public function borrow_tender_activity() {
        ignore_user_abort();
        $this->load->model('m_script');
        $this->db->db_debug = TRUE;
        //本人投资积分
        $this->db->limit(10);
        $rows = $this->db->get_where(TABLE_QUEUE_BORROW_TENDER, array('activity_status' => 2))->result_array();

        $this->load->model('m_user');
        $this->load->model('m_borrow');
        $this->load->model('m_bouns');
        $this->load->model('m_credit');
        $this->load->model('m_experience');
        $this->load->model('m_prize_chance');
        $this->load->model('m_message');
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW_TENDER, array('activity_status' => 3, 'modify_time' => time()), array('id' => $v['id']));
            $borrow = $this->m_borrow->detail($v['borrow_id']);
            $tender = $this->m_borrow->tender_detail($v['tender_id']);
            //判断是否首投
            $this->db->limit(1);
            $this->db->where('status <> 3', false, false);
            $this->db->where('id < ', $v['tender_id'], false);
            $has_tendered = $this->db->get_where(TABLE_BORROW_TENDER, array('user_id' => $v['user_id']))->row(0)->id ? true : false;
            $bouns_id = false;
            $inviter_credit = false;
            $tender_credit = false;
            if (!$has_tendered) {   //首次投资
                //查找邀请人
                $this->db->limit(1);
                $this->db->where('status <> 3', false, false);
                $inviter = $this->db->get_where(TABLE_USER_FRIENDS_INVITE, array('friends_userid' => $v['user_id']))->row(0)->user_id; //查找邀请人
                //首投邀请人红包、积分
                if ($inviter) {
                    //红包
                    $bouns = $this->m_script->get_value(__FUNCTION__, 'INVITER_BOUNS');
                    $money = $tender->money >= 1000 ? ($tender->money >= 5000 ? ($tender->money >= 10000 ? ($tender->money >= 50000 ? 100 : 50) : 30) : 10) : 0;
                    if ($bouns && $money) {
                        $bouns_id = $this->m_bouns->add(array(
                            'creator' => 'admin',
                            'money' => $money,
                            'num_current' => 1,
                            'num_total' => 1,
                            'start_time' => time(),
                            'expire' => 3600 * 24 * 90,
                            'use_channel' => 'all',
                            'use_type' => 0,
                            'remark' => '邀请人红包'
                        ));
                        $this->m_bouns->send_to_user($bouns_id, $inviter);
                    }
                    //五一活动1红包
                    $activity_time = time();
                    if ($activity_time < 1493913600 && $tender->money_bouns) {   //本次投资使用了红包
                        $activity_bouns_id = $this->m_bouns->add(array(
                            'creator' => 'admin',
                            'money' => $tender->money_bouns,
                            'num_current' => 1,
                            'num_total' => 1,
                            'start_time' => time(),
                            'expire' => 3600 * 24 * 7, //有效期7天
                            'use_channel' => 'all',
                            'use_type' => 0,
                            'remark' => '好友的名义（五一活动）'
                        ));
                        $this->m_bouns->send_to_user($activity_bouns_id, $inviter);
                    }
                    //积分
                    $credit1 = $this->m_script->get_value(__FUNCTION__, 'INVITER_CREDIT');
                    if (1) {
                        $inviter_credit = $this->m_credit->increase($inviter, 500, array(
                            'type' => 'tender_invite',
                            'item_id' => $tender->tender_id,
                            'remark' => "邀请用户" . $v['user_id'] . "首投积分",
                        ));
                    }
                    //邀请人首投体验金
                    if (1) {
                        $experience_inviter_id = 3;
                        $experience_inviter_money = 5000;
                        $experience_inviter = $this->m_experience->detail($experience_inviter_id);
                        if (!empty($experience_inviter) && $experience_inviter->status->id == m_experience::STATUS_EXPERIENCE_ON) {
                            //累计发放额度验证
                            if ($this->m_experience->increase($experience_inviter_id, $experience_inviter_money)) {
                                $user = $this->m_user->detail($v['user_id']);
                                $this->m_experience->send_to_user($experience_inviter_id, $experience_inviter_money, $inviter, '邀请好友' . substr_replace($user->mobile, '****', 3, 4));
                            }
                        }
                    }
                }
                //首投体验金
                if (1) {
                    $experience_first_id = 6;
                    $experience_first_money = min($tender->money * 2, 400000);
                    $experience_first = $this->m_experience->detail($experience_first_id);
                    if ($experience_first_money <= 400000) {
                        if (!empty($experience_first) && $experience_first->status->id == m_experience::STATUS_EXPERIENCE_ON) {
                            //累计发放额度验证
                            if ($this->m_experience->increase($experience_first_id, $experience_first_money)) {
                                $this->m_experience->send_to_user($experience_first_id, $experience_first_money, $tender->user_id, '投资奖励');
                            }
                        }
                    } else {
                        do_script_log('金额大于40w，首投2倍奖励金发放失败！', $v);
                    }
                }
            }
            if (time() < 1485100799 + 31) {
                $experience_id = 4;
                $experience_money = $tender->money * 1;
                $experience = $this->m_experience->detail($experience_id);
                if (!empty($experience) && $experience->status->id == m_experience::STATUS_EXPERIENCE_ON) {
                    //累计发放额度验证
                    if ($this->m_experience->increase($experience_id, $experience_money)) {
                        $this->m_experience->send_to_user($experience_id, $experience_money, $tender->user_id, '投资送体验金');
                    }
                }
            }
            $credit2 = $this->m_script->get_value(__FUNCTION__, 'TENDER_CREDIT');
            if ($credit2) {
                $tender_credit = floor(($tender->money / 100) * $borrow->period);
                $this->m_credit->increase($v['user_id'], $tender_credit, array(
                    'type' => 'tender_success',
                    'item_id' => $tender->tender_id,
                    'remark' => "投资积分",
                ));
            }
            //投资成功发送站内信
            $this->m_message->send_admin(array(
                'receiver_id' => $v['user_id'],
                'title' => '投资提醒',
                'text' => '您于 ' . date('Y-m-d H:i:s', time()) . ' 成功投资' . $tender->money . 元,
            ));
            //增加抽奖机会
            if ($borrow->days == 15 && $tender->money >= 2000) {
                $chance = floor($tender->money / 2000);
                $activity_id = 2;
                if (!$this->m_prize_chance->detail($v['user_id'], $activity_id)) {
                    $this->m_prize_chance->add(array(
                        'user' => $v['user_id'],
                        'chance' => $chance,
                        'activity_id' => $activity_id
                    ));
                } else {
                    $this->m_prize_chance->increase($v['user_id'], $activity_id, $chance);
                }
            }
            //发送微信消息
            if (1) {    //TODO  还没提交到测试
                $this->load->model('m_wechat_msg');
                $r = $this->m_wechat_msg->add_tender_msg($v['user_id'], $borrow->title, $tender->money);
            }

            $result = json_encode(array('bouns' => $bouns_id ? $bouns_id : false, 'inviter_credit' => !!$inviter_credit, 'tender_credit' => $tender_credit));
            $this->db->update(TABLE_QUEUE_BORROW_TENDER, array('activity_status' => 1, 'modify_time' => time(), 'activity_result' => $result), array('id' => $v['id']));
        }
        //var_dump(__FUNCTION__,$this->db->all_query());
        //
        $this->api->output(true);
    }

    //自动投标
    public function borrow_tender_auto() {
        
    }

    //满标审核，存管放款请求
    public function borrow_tender_verify_ba() {
        ignore_user_abort();
        set_time_limit(0);
        require_once APPPATH . 'libraries/equal_interest.php';
        $this->load->model('m_borrow');
        $this->load->model('m_script');
        $this->load->model('m_account');
        $this->load->model('m_user');
        $this->load->model('m_bank_account');
        $this->db->db_debug = TRUE;

        $jx = new JiXin\api();

        $this->db->limit(10);
        $this->db->select(TABLE_BORROW . '.*,' . TABLE_QUEUE_BORROW . '.id as qid', false);
        $this->db->order_by(TABLE_BORROW . '.id', 'asc');
        $this->db->where(TABLE_BORROW . '.status', m_borrow::STATUS_VERIFY_ONLINE);
        $this->db->where(TABLE_BORROW . '.borrow_account_yes >= ', TABLE_BORROW . '.account', false);
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_QUEUE_BORROW . '.borrow_auto_id = ' . TABLE_BORROW . '.id', 'INNER');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', m_borrow::STATUS_P_INIT);
        $this->db->where(TABLE_QUEUE_BORROW . '.ba_status', m_borrow::STATUS_P_BA_INIT);
        $rows = $this->db->get_where(TABLE_BORROW)->result_array();
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_START), array('id' => $v['qid']));
            //验证满标真实性
            //tender金额
            $this->db->select('sum(account) as sum,sum(hbagmoney) as bouns_money,count(1) as count');
            //只有状态为0的才有效
            $tender = $this->db->get_where(TABLE_BORROW_TENDER, array('borrow_nid' => $v['borrow_nid'], 'status' => 0))->row(0);
            $tender_sum = $tender->sum;
            $bouns_money_sum = $tender->bouns_money;
            //流水金额
            $this->db->select('sum(money) as sum,count(1) as count');
            $money = $this->db->get_where(TABLE_ACCOUNT_LOG, array('borrow_nid' => $v['borrow_nid'], 'type' => 'tender'))->row(0);
            $money_sum = $money->sum + $bouns_money_sum;
            if ($money_sum != $tender_sum || $money_sum != $v['borrow_account_yes'] || $money_sum != $v['account']) {
                //金额不匹配，满标审核失败
                //尝试自动处理
                //情况1 投标金额与记账金额相等，但是小于满标金额
                if ($tender->count == $money->count && $money_sum == $tender_sum & $money_sum < $v['borrow_account_yes'] && $money_sum < $v['account']) {
                    //这里会存在标的金额已减，但是投资记录未插入的情况，所以暂不做退回操作
//                    $this->m_borrow->_set_memcache_account($v['borrow_nid'], $v['account'] - $money_sum, true);
//                    $this->db->set('borrow_account_wait', $v['account'] - $money_sum);
//                    $this->db->set('borrow_account_yes', $money_sum);
//                    $this->db->set('borrow_account_scale', 'floor((borrow_account_yes/account)*100)', false);
//                    $this->db->set('tender_times', $tender->count);
//                    $this->db->update(TABLE_BORROW, array(), array('borrow_nid' => $v['borrow_nid']));
                }
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_INIT), array('id' => $v['qid']));
                continue;
            }
            //配置请求数组subPacks
            $tender_lists = $this->db->get_where(TABLE_BA_TENDER_NA, array('borrow_id' => $v['borrow_nid'], 'status' => 1))->result();
            $subPacks = array();
            foreach ($tender_lists as $k2 => $v2) {
                //生成放款订单号
                $action_id = $this->m_bank_account->action_add(array(
                    'action' => 'batchLendPay',
                    'user_id' => $v2->user_id,
                    'request' => json_encode(array(
                        'accountId' => $v2->accountId,
                        'txAmount' => sprintf('%.2f', $v2->txAmount),
                        'bidFee' => '0.00',
                        'debtFee' => '0.00',
                        'forAccountId' => $v['ba_account_id'],
                        'productId' => $v2->productId,
                        'authCode' => $v2->authCode,
                    )),
                    'ip' => get_ip(),
                ));
                $action = $this->m_bank_account->action_detail($action_id);
                $subPacks[] = array(
                    'accountId' => $v2->accountId,
                    'orderId' => $action->order_sn,
                    'txAmount' => sprintf('%.2f', $v2->txAmount),
                    'bidFee' => '0.00',
                    'debtFee' => '0.00',
                    'forAccountId' => $v['ba_account_id'],
                    'productId' => $v2->productId,
                    'authCode' => $v2->authCode,
                );
            }
            JiXin\api_log::set(array(
                'request_jc' => $this->api->in,
                'request_time' => time(),
                'start_time' => microtime(1),
                'api_name_cn' => '批量放款',
                'api_name_en' => 'batchLendPay',
                'user_id' => $this->api->user()->user_id,
            ));
            $seqNo = JiXin\counter::auto_id();
            $txDate = date('Ymd');
            $txTime = date('His');
            $r = $jx->batchLendPay(array(
                'txDate' => $txDate, //日期
                'txTime' => $txTime, //时间
                'seqNo' => $seqNo, //时间
                'txAmount' => $tender_sum, //原交易的金额
                'txCounts' => count($tender_lists), //本批次所有交易笔数
                'subPacks' => $subPacks,
                'batchNo' => $seqNo, //批次号，单日不能重复，回调时需要该队列号
            ));
            JiXin\api_log::set(array(
                'response' => array(
                    'result' => $r['result'],
                    'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                    'error_msg' => $r['msg'],
                ),
            ));
            if ($r['result']['received'] === 'success') {
                $this->db->update(TABLE_BORROW, array('reverify_time' => time()), array('id' => $v['id']));
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_SUBMIT, 'ba_result_full_submit' => json_encode($r), 'ba_seqNo' => $txDate . $txTime . $seqNo), array('id' => $v['qid']));

                //满标发送站内信
                $send_message = $this->m_message->send_admin(array(
                    'receiver_id' => $v['id'],
                    'title' => '满标提醒',
                    'text' => '您投资的 ' . $v['name'] . ' 项目已于 ' . date('Y-m-d H:i:s', time()) . ' 满标开始计息',
                ));
            } else {
                //todo 重试机制
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_PAY_FAILED, 'ba_result_full_submit' => json_encode($r), 'ba_seqNo' => $txDate . $txTime . $seqNo), array('id' => $v['qid']));
                //todo 回退机制
//                $this->borrow_process->load_string($v['process']);
//                $this->borrow_process->clean();
            }
            JiXin\api_log::write();
            $this->db->queries = array();
            $this->db->query_times = array();
        }
        $this->api->output(true);
    }

    //满标审核
    public function borrow_tender_verify() {
        ignore_user_abort();
        set_time_limit(0);
        require_once APPPATH . 'libraries/equal_interest.php';
        $this->load->model('m_borrow');
        $this->load->model('m_script');
        $this->load->model('m_account');
        $this->db->db_debug = TRUE;

        $this->db->limit(10);
        $this->db->select(TABLE_BORROW . '.*,' . TABLE_QUEUE_BORROW . '.id as qid', false);
        $this->db->order_by(TABLE_BORROW . '.id', 'asc');
        $this->db->where(TABLE_BORROW . '.status', m_borrow::STATUS_VERIFY_ONLINE);
        $this->db->where(TABLE_BORROW . '.borrow_account_yes >= ', TABLE_BORROW . '.account', false);
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_QUEUE_BORROW . '.borrow_auto_id = ' . TABLE_BORROW . '.id', 'INNER');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', m_borrow::STATUS_P_INIT);
        $this->db->where(TABLE_QUEUE_BORROW . '.ba_status', m_borrow::STATUS_P_BA_PAY_SUCCESS); //存管放款结束后才能进入此步骤
        $rows = $this->db->get_where(TABLE_BORROW)->result_array();
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW, array('status' => m_borrow::STATUS_P_PAY_START), array('id' => $v['qid']));
            //验证满标真实性
            //tender金额
            $this->db->select('sum(account) as sum,sum(hbagmoney) as bouns_money,count(1) as count');
            //投标数据只有状态为0的才有效
            $tender = $this->db->get_where(TABLE_BORROW_TENDER, array('borrow_nid' => $v['borrow_nid'], 'status' => 0))->row(0);
            $tender_sum = $tender->sum;
            $bouns_money_sum = $tender->bouns_money;
            //流水金额
            $this->db->select('sum(money) as sum,count(1) as count');
            $money = $this->db->get_where(TABLE_ACCOUNT_LOG, array('borrow_nid' => $v['borrow_nid'], 'type' => 'tender'))->row(0);
            $money_sum = $money->sum + $bouns_money_sum;
            if ($money_sum != $tender_sum || $money_sum != $v['borrow_account_yes'] || $money_sum != $v['account']) {
                //金额不匹配，满标审核失败
                //尝试自动处理
                //情况1 投标金额与记账金额相等，但是小于满标金额
                if ($tender->count == $money->count && $money_sum == $tender_sum & $money_sum < $v['borrow_account_yes'] && $money_sum < $v['account']) {
                    //这里会存在标的金额已减，但是投资记录未插入的情况，所以暂不做退回操作
//                    $this->m_borrow->_set_memcache_account($v['borrow_nid'], $v['account'] - $money_sum, true);
//                    $this->db->set('borrow_account_wait', $v['account'] - $money_sum);
//                    $this->db->set('borrow_account_yes', $money_sum);
//                    $this->db->set('borrow_account_scale', 'floor((borrow_account_yes/account)*100)', false);
//                    $this->db->set('tender_times', $tender->count);
//                    $this->db->update(TABLE_BORROW, array(), array('borrow_nid' => $v['borrow_nid']));
                }
                $this->db->update(TABLE_QUEUE_BORROW, array('status' => 2), array('id' => $v['qid']));
                continue;
            }
            //验证通过后进行满标动作
            //1. 修改满标状态
            $this->db->update(TABLE_BORROW, array(
                'reverify_remark' => '满标自动审核',
                'reverify_bank_time' => time(),
                'status' => m_borrow::STATUS_VERIFY_FULL,
                'borrow_full_status' => 1,
                    ), array(
                'id' => $v['id']
            ));
            //2. 加入repay还款计划表
            $_equal = array();
            $_equal["account"] = $v["account"];
            $_equal["period"] = $v["borrow_period"];
            $_equal["apr"] = $v["borrow_apr"];
            $_equal["style"] = $v["borrow_style"];
            $equal_result = EqualInterest($_equal);
            foreach ($equal_result as $k2 => $v2) {
                $this->db->insert(TABLE_BORROW_REPAY, array(
                    'addtime' => time(),
                    'addip' => get_ip(),
                    'user_id' => $v['user_id'],
                    'status' => 1,
                    'borrow_nid' => $v['borrow_nid'],
                    'repay_period' => $k2, //1个月的标，此值为0
                    'repay_time' => $v2['repay_time'],
                    'repay_account' => $v2['account_all'],
                    'repay_interest' => $v2['account_interest'],
                    'repay_capital' => $v2['account_capital'],
                ));
            }
            $repay_times = count($equal_result);
            $tenders = $this->db->get_where(TABLE_BORROW_TENDER, array('borrow_nid' => $v['borrow_nid']))->result_array();
            //$recover_time = strtotime(round($v["borrow_period"] * 30) . "days", time());
            foreach ($tenders as $k2 => $v2) {
                $_equal = array();
                $_equal["account"] = $v2['account'];
                $_equal["period"] = $v["borrow_period"];
                $_equal["apr"] = $v["borrow_apr"];
                $_equal["style"] = $v["borrow_style"];
                $_equal["type"] = "";
                $equal_result = EqualInterest($_equal);
                foreach ($equal_result as $k3 => $v3) {
                    $this->db->insert(TABLE_BORROW_RECOVER, array(
                        'addtime' => time(),
                        'addip' => get_ip(),
                        'user_id' => $v2['user_id'],
                        'status' => 1,
                        'borrow_nid' => $v['borrow_nid'],
                        'borrow_userid' => $v['user_id'],
                        'tender_id' => $v2['id'],
                        'recover_period' => $k3,
                        'recover_time' => $v3['repay_time'],
                        'recover_account' => $v3['account_all'],
                        'recover_interest' => $v3['account_interest'],
                        'recover_capital' => $v3['account_capital'],
                    ));
                }
                $recover_times = count($equal_result);
                //第五步,更新投资标的信息
                $_equal["type"] = "all";
                $equal_result = EqualInterest($_equal);
                $this->db->update(TABLE_BORROW_TENDER, array(
                    'status' => 1,
                    'recover_account_all' => $equal_result['account_total'],
                    'recover_account_interest' => $equal_result['interest_total'],
                    'recover_account_wait' => $equal_result['account_total'],
                    'recover_account_interest_wait' => $equal_result['interest_total'],
                    'recover_account_capital_wait' => $equal_result['capital_total'],
                        ), array(
                    'id' => $v2['id']
                ));
                //扣除资金
                $num = $v2['account'];
                $param = array(
                    'income' => 0,
                    'expend' => $num,
                    'balance_cash' => 0,
                    'balance_frost' => 0,
                    'frost' => - $num,
                    'await' => 0,
                    'total' => 0,
                );
                $param['user_id'] = intval($v2['user_id']);
                $param['type'] = 'tender_success';
                $param['money'] = $num;
                $param['remark'] = "投标[{$v['name']}]成功投资金额扣除";
                $param['borrow_id'] = $v['borrow_nid'];
                $param['tender_id'] = $v2['id'];
                $param['to_userid'] = intval($v['user_id']);
                $param["capital"] = $equal_result['capital_total'];
                $param["interest"] = $equal_result['interest_total'];
                $this->m_account->lock($v2['user_id']);
                //todo lock失败的时候的回滚操作
                $this->m_account->add_log($param);
                $this->m_account->unlock($v2['user_id']);
                $this->m_account->lock($v2['user_id']);
                //增加待收金额
                $num2 = $equal_result['account_total'];
                $param2 = array(
                    'income' => 0,
                    'expend' => 0,
                    'balance_cash' => 0,
                    'balance_frost' => 0,
                    'frost' => 0,
                    'await' => $num2,
                    'total' => 0,
                );
                $param2['user_id'] = intval($v2['user_id']);
                $param2['type'] = 'tender_success_frost';
                $param2['money'] = $num2;
                $param2['remark'] = "投标[{$v['name']}]成功待收金额增加";
                $param2['borrow_id'] = $v['borrow_nid'];
                $param2['tender_id'] = $v2['id'];
                $param2['to_userid'] = intval($v['user_id']);
                $param2["capital"] = $equal_result['capital_total'];
                $param2["interest"] = $equal_result['interest_total'];
                $this->m_account->add_log($param2);
                $this->m_account->unlock($v2['user_id']);
                //加息应收金额
                if ($v2['coupon_use_status'] == 1) {
                    $this->m_account->lock($v2['user_id']);
                    $num3 = round($v2['coupon_amount'] / 100, 2); //待收金额
                    $param3 = array(
                        'income' => 0,
                        'expend' => 0,
                        'balance_cash' => 0,
                        'balance_frost' => 0,
                        'frost' => 0,
                        'await' => $num3,
                        'total' => 0,
                    );
                    $param3['user_id'] = intval($v2['user_id']);
                    $param3['type'] = 'tender_success_coupon';
                    $param3['money'] = $num3;
                    $param3['remark'] = "投标[{$v['name']}]加息成功" . ($v2['coupon_rate'] / 100) . '%';
                    $param3['borrow_id'] = $v['borrow_nid'];
                    $param3['tender_id'] = $v2['id'];
                    $param3['to_userid'] = intval($v['user_id']);
                    $param3["capital"] = $equal_result['capital_total'];
                    $param3["interest"] = $num3;
                    $this->m_account->add_log($param3);
                    $this->m_account->unlock($v2['user_id']);
                }
                //todo 短信提醒 PS：投标成功暂无短信提醒
                //todo 投资用户日志
                //todo 第三方平台回款信息
            }
            //更新回款信息;
            $now_time = time();
            $end_time = round($v["borrow_period"] * 30) * 3600 * 24 + $now_time;

            if ($v["borrow_style"] == 1) {
                $each_time = "每三个月后" . date("d", $now_time) . "日";
                $next_time = 30 * 3 * 3600 * 24 + $now_time;
            } else {
                $each_time = "每月" . date("d", $now_time) . "日";
                $next_time = 30 * 1 * 3600 * 24 + $now_time;
            }
            $_equal = array();
            $_equal["account"] = $v['account'];
            $_equal["period"] = $v["borrow_period"];
            $_equal["apr"] = $v["borrow_apr"];
            $_equal["style"] = $v["borrow_style"];
            $_equal["type"] = "all";
            $equal_result = EqualInterest($_equal);
            $this->db->update(TABLE_BORROW, array(
                'repay_account_all' => $equal_result['account_total'],
                'repay_account_interest' => $equal_result['interest_total'],
                'repay_account_capital' => $equal_result['capital_total'],
                'repay_account_wait' => $equal_result['account_total'],
                'repay_account_interest_wait' => $equal_result['interest_total'],
                'repay_account_capital_wait' => $equal_result['capital_total'],
                'repay_last_time' => $end_time,
                'repay_next_time' => $next_time,
                'borrow_success_time' => $now_time,
                'repay_each_time' => $each_time,
                'repay_times' => $repay_times,
                    ), array(
                'id' => $v['id']
            ));
            $this->db->update(TABLE_QUEUE_BORROW, array('status' => m_borrow::STATUS_P_PAY_SUCCESS), array('id' => $v['qid']));
        }
        do_script_log(__FUNCTION__, $this->db->all_query());
        $this->api->output($v);
    }

    //回款前一天的16点冻结融资人账户金额用于回款
    public function borrow_tender_freeze_before_repay_ba() {
        ignore_user_abort();

        $jx = new JiXin\api();
        $this->load->model('m_borrow');
        $this->load->model('m_account');
        $this->load->model('m_user');
        $this->load->model('m_bank_account');
        //提早一天去冻结还款
        $repay_time = time() + 3600 * 24 * 365;

        $this->db->limit(10);
        $this->db->order_by('id asc');
        $this->db->where('repay_time < ' . $repay_time, false, false);
        $this->db->select(TABLE_BORROW_REPAY . '.*,' . TABLE_QUEUE_BORROW . '.id as qid', false);
        $this->db->join(TABLE_BORROW, TABLE_BORROW . '.borrow_nid = ' . TABLE_BORROW_REPAY . '.borrow_nid', 'inner');
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_BORROW . '.id = ' . TABLE_QUEUE_BORROW . '.borrow_auto_id', 'inner');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', m_borrow::STATUS_P_PAY_SUCCESS);
        $this->db->where(TABLE_QUEUE_BORROW . '.ba_status', m_borrow::STATUS_P_BA_PAY_SUCCESS);
        $rows = $this->db->get_where(TABLE_BORROW_REPAY, array('repay_status' => 0))->result_array(); //查找待回款标
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_FREEZE_START), array('id' => $v['qid']));
            $borrow = $this->m_borrow->detail($v['borrow_nid']);
            //判断标的是否正常
            if ($borrow->status->id != m_borrow::STATUS_VERIFY_FULL) {
                //todo 异常回款记录报警
                $this->db->update(TABLE_BORROW_REPAY, array('repay_status' => 9), array('id' => $v['id']));
                continue;
            }
            $fee = $borrow->money_total * 0.06 / 12 * ($borrow->days / 30); //适用一次还付本息的标的，利差年化6%
            //生成放款订单号
            $action_id = $this->m_bank_account->action_add(array(
                'action' => 'balanceFreeze',
                'user_id' => $v2->user_id,
                'request' => json_encode(array(
                    'accountId' => $borrow->ba_account_id,
                    'txAmount' => $v['repay_account'] + $fee,
                )),
                'ip' => get_ip(),
            ));
            $action = $this->m_bank_account->action_detail($action_id);
            JiXin\api_log::set(array(
                'request_jc' => $this->api->in,
                'request_time' => time(),
                'start_time' => microtime(1),
                'api_name_cn' => '提早冻结还款金额',
                'api_name_en' => 'balanceFreeze',
                'user_id' => $this->api->user()->user_id,
            ));
            $r = $jx->balanceFreeze(array(
                'accountId' => $borrow->ba_account_id,
                'txAmount' => $v['repay_account'] + $fee,
                'orderId' => $action->order_sn,
            ));
            JiXin\api_log::set(array(
                'response' => array(
                    'result' => $r['result'],
                    'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                    'error_msg' => $r['msg'],
                ),
            ));
            if ($r['retcode'] === '00000000') {
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_FREEZE_SUCCESS), array('id' => $v['qid']));
            } else {
                //todo 重试机制
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_FREEZE_FAILED), array('id' => $v['qid']));
                //todo 回退机制
            }
            JiXin\api_log::write();
            $this->db->queries = array();
            $this->db->query_times = array();
        }
        $this->api->output(true);
    }

    //存管回款处理
    public function borrow_tender_repay_ba() {
        ignore_user_abort();

        $jx = new JiXin\api();
        $this->load->model('m_borrow');
        $this->load->model('m_account');
        $this->load->model('m_user');
        $this->load->model('m_bank_account');
        //todo 提早一天去冻结还款
        $repay_time = time() + 3600 * 24 * 365;

        $this->db->limit(10);
        $this->db->order_by('id asc');
        $this->db->where('repay_time < ' . $repay_time, false, false);
        $this->db->select(TABLE_BORROW_REPAY . '.*,' . TABLE_QUEUE_BORROW . '.id as qid', false);
        $this->db->join(TABLE_BORROW, TABLE_BORROW . '.borrow_nid = ' . TABLE_BORROW_REPAY . '.borrow_nid', 'inner');
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_BORROW . '.id = ' . TABLE_QUEUE_BORROW . '.borrow_auto_id', 'inner');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', m_borrow::STATUS_P_PAY_SUCCESS);
        $this->db->where(TABLE_QUEUE_BORROW . '.ba_status', m_borrow::STATUS_P_BA_REPAY_FREEZE_SUCCESS);
        $rows = $this->db->get_where(TABLE_BORROW_REPAY, array('repay_status' => 0))->result_array(); //查找待回款标
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_START), array('id' => $v['qid']));
            $borrow = $this->m_borrow->detail($v['borrow_nid']);
            //判断标的是否正常
            if ($borrow->status->id != m_borrow::STATUS_VERIFY_FULL) {
                //todo 异常回款记录报警
                $this->db->update(TABLE_BORROW_REPAY, array('repay_status' => 9), array('id' => $v['id']));
                continue;
            }
            //配置请求数组subPacks
            $subPacks = array();
            $tender_sum = 0.00;
            $this->db->select(TABLE_BORROW_RECOVER . '.id as rid,recover_capital,recover_interest,' . TABLE_BA_TENDER_NA . '.*', false);
            $this->db->join(TABLE_BORROW_RECOVER, TABLE_BA_TENDER_NA . '.tender_id = ' . TABLE_BORROW_RECOVER . '.tender_id', 'inner');
            $this->db->where(TABLE_BORROW_RECOVER . '.recover_time < ' . $repay_time, false, false);
            $tender_lists = $this->db->get_where(TABLE_BA_TENDER_NA, array('borrow_id' => $v['borrow_nid']))->result();
            $fee = $borrow->money_total * 0.06 / 12 * ($borrow->days / 30); //适用一次还付本息的标的，利差年化6%
            foreach ($tender_lists as $k2 => $v2) {
                $tender = $this->m_borrow->_tender_detail($v2->tender_id);
                if ($tender['recover_account_wait'] <= 0) {
                    //todo 异常回款记录报警
                    $this->db->update(TABLE_BORROW_RECOVER, array('recover_status' => 9), array('id' => $v2->rid));
                    continue;
                }
                if ($tender['status'] != 1) {
                    //todo 异常回款记录报警
                    $this->db->update(TABLE_BORROW_RECOVER, array('recover_status' => 9), array('id' => $v2->rid));
                    continue;
                }
                //生成放款订单号
                $action_id = $this->m_bank_account->action_add(array(
                    'action' => 'batchRepay',
                    'user_id' => $v2->user_id,
                    'request' => json_encode(array(
                        'accountId' => $borrow->ba_account_id,
                        'txAmount' => sprintf('%.2f', $v2->recover_capital),
                        'intAmount' => sprintf('%.2f', $v2->recover_interest),
                        'txFeeOut' => sprintf('%.2f', round($fee * $v2->recover_capital / $borrow->money_total, 2)),
                        'txFeeIn' => '0.00',
                        'forAccountId' => $v2->accountId,
                        'productId' => $v2->productId,
                        'authCode' => $v2->authCode,
                    )),
                    'ip' => get_ip(),
                ));
                $action = $this->m_bank_account->action_detail($action_id);
                $subPacks[] = array(
                    'accountId' => $borrow->ba_account_id,
                    'orderId' => $action->order_sn,
                    'txAmount' => sprintf('%.2f', $v2->recover_capital),
                    'intAmount' => sprintf('%.2f', $v2->recover_interest),
                    'txFeeOut' => sprintf('%.2f', round($fee * $v2->recover_capital / $borrow->money_total, 2)),
                    'txFeeIn' => '0.00',
                    'forAccountId' => $v2->accountId,
                    'productId' => $v2->productId,
                    'authCode' => $v2->authCode,
                );
                $tender_sum += $v2->recover_capital;
            }
            JiXin\api_log::set(array(
                'request_jc' => $this->api->in,
                'request_time' => time(),
                'start_time' => microtime(1),
                'api_name_cn' => '批量还款',
                'api_name_en' => 'batchRepay',
                'user_id' => $this->api->user()->user_id,
            ));
            $seqNo = JiXin\counter::auto_id();
            $txDate = date('Ymd');
            $txTime = date('His');
            $r = $jx->batchRepay(array(
                'txDate' => $txDate, //日期
                'txTime' => $txTime, //时间
                'seqNo' => $seqNo, //时间
                'txAmount' => $tender_sum, //原交易的金额
                'txCounts' => count($subPacks), //本批次所有交易笔数
                'subPacks' => $subPacks,
                'batchNo' => $seqNo, //批次号，单日不能重复，回调时需要该队列号
            ));
            JiXin\api_log::set(array(
                'response' => array(
                    'result' => $r['result'],
                    'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                    'error_msg' => $r['msg'],
                ),
            ));
            if ($r['result']['received'] === 'success') {
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_SUBMIT, 'ba_result_repay_submit' => json_encode($r), 'ba_seqNo' => $txDate . $txTime . $seqNo), array('id' => $v['qid']));
            } else {
                //todo 重试机制
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_REPAY_FAILED, 'ba_result_repay_submit' => json_encode($r), 'ba_seqNo' => $txDate . $txTime . $seqNo), array('id' => $v['qid']));
                //todo 回退机制
            }
            JiXin\api_log::write();
            $this->db->queries = array();
            $this->db->query_times = array();
        }
        $this->api->output(true);
    }

    //到期回款
    public function borrow_tender_repay() {
        ignore_user_abort();
        $this->load->model('m_borrow');
        $this->load->model('m_account');
        $this->load->model('m_user');
        $this->load->model('m_sms');
        $repay_time = time() + 3600 * 24 * 365;

        $jx = new JiXin\api();

        $this->db->limit(10);
        $this->db->order_by('id asc');
        $this->db->where('repay_time < ' . $repay_time, false, false);
        $this->db->select(TABLE_BORROW_REPAY . '.*,' . TABLE_QUEUE_BORROW . '.id as qid', false);
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_BORROW_REPAY . '.borrow_nid = ' . TABLE_QUEUE_BORROW . '.borrow_id', 'inner');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', m_borrow::STATUS_P_PAY_SUCCESS);
        $this->db->where(TABLE_QUEUE_BORROW . '.ba_status', m_borrow::STATUS_P_BA_REPAY_SUCCESS);
        $rows = $this->db->get_where(TABLE_BORROW_REPAY, array('repay_status' => 0))->result_array(); //查找待回款标
        foreach ($rows as $k1 => $v1) {
            $this->db->update(TABLE_QUEUE_BORROW, array('status' => m_borrow::STATUS_P_REPAY_START), array('id' => $v1['qid']));
            $borrow = $this->m_borrow->_detail($v1['borrow_nid']);
            //判断标的是否正常
            if ($borrow['status'] != m_borrow::STATUS_VERIFY_FULL) {
                //todo 异常回款记录报警
                $this->db->update(TABLE_BORROW_REPAY, array('repay_status' => 9), array('id' => $v1['id']));
                continue;
            }
            $this->db->where('recover_time < ' . $repay_time, false, false);
            $tender_lists = $this->db->get_where(TABLE_BORROW_RECOVER, array('borrow_nid' => $v1['borrow_nid']))->result_array();
            foreach ($tender_lists as $k => $v) {
                $tender = $this->m_borrow->_tender_detail($v['tender_id']);
                $user = $this->m_user->detail($v['user_id']);
                if ($tender['recover_account_wait'] <= 0) {
                    //todo 异常回款记录报警
                    $this->db->update(TABLE_BORROW_RECOVER, array('recover_status' => 9), array('id' => $v['id']));
                    continue;
                }
                if ($tender['status'] != 1) {
                    //todo 异常回款记录报警
                    $this->db->update(TABLE_BORROW_RECOVER, array('recover_status' => 9), array('id' => $v['id']));
                    continue;
                }
                $this->db->set('recover_account_yes', 'recover_account', false);
                $this->db->set('recover_capital_yes', 'recover_capital', false);
                $this->db->set('recover_interest_yes', 'recover_interest', false);
                $this->db->update(TABLE_BORROW_RECOVER, array(
                    'recover_yestime' => time(),
                    'status' => 1,
                    'recover_status' => 1
                        ), array('id' => $v['id']));
                //回款资金
                $num = $v['recover_account'];
                $param = array(
                    'income' => $num,
                    'expend' => 0,
                    'balance_cash' => $num,
                    'balance_frost' => 0,
                    'frost' => 0,
                    'await' => -$num,
                    'total' => 0,
                );
                $param['user_id'] = intval($v['user_id']);
                $param['type'] = 'tender_repay_yes';
                $param['money'] = $num;
                $param['remark'] = "投标[{$borrow['name']}]成功投资金额扣除";
                $param['borrow_id'] = $v['borrow_nid'];
                $param['tender_id'] = $v['tender_id'];
                $param['to_userid'] = intval($v['user_id']);
                $param["capital"] = $v['recover_capital'];
                $param["interest"] = $v['recover_interest'];
                $this->m_account->lock($v['user_id']);
                //todo lock失败的时候的回滚操作
                $this->m_account->add_log($param);
                $this->m_account->unlock($v['user_id']);
                //加息
                if ($tender['coupon_use_status'] == 1) {
                    $this->m_account->lock($v['user_id']);
                    $num3 = round($tender['coupon_amount'] / 100, 2); //待收金额
                    $param3 = array(
                        'income' => $num3,
                        'expend' => 0,
                        'balance_cash' => $num3,
                        'balance_frost' => 0,
                        'frost' => 0,
                        'await' => -$num3,
                        'total' => 0,
                    );
                    $param3['user_id'] = intval($v['user_id']);
                    $param3['type'] = 'tender_repay_yes_coupon';
                    $param3['money'] = $num3;
                    $param3['remark'] = "[{$borrow['name']}]加息收到还款";
                    $param3['borrow_id'] = $v['borrow_nid'];
                    $param3['tender_id'] = $v['tender_id'];
                    $param3['to_userid'] = intval($v['user_id']);
                    $param3["capital"] = $v['recover_capital'];
                    $param3["interest"] = $num3;
                    $this->m_account->add_log($param3);
                    $this->m_account->unlock($v['user_id']);
                    JiXin\api_log::set(array(
                        'request_jc' => $this->api->in,
                        'request_time' => time(),
                        'start_time' => microtime(1),
                        'api_name_cn' => '还款加息',
                        'api_name_en' => 'voucherPay[coupon]',
                        'user_id' => $v['user_id'],
                    ));
                    $r2 = $jx->voucherPay(array(
                        'channel' => '000002', //交易渠道
                        'accountId' => JiXin\config::bouns_accountId, //红包账号
                        'txAmount' => sprintf('%.2f', $tender['coupon_amount'] / 100), //加息金额
                        'forAccountId' => $user->bank_account->account_id, //接收方账号
                        'desLineFlag' => '1', //是否使用交易描述1-使用0-不使用
                        'desLine' => '加息成功' . $tender['coupon_user_use_id'], //交易描述,选填
                    ));
                    JiXin\api_log::set(array(
                        'response' => array(
                            'result' => $r2['result'],
                            'error_no' => $r2['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r2['retcode'],
                            'error_msg' => $r2['msg'],
                        ),
                    ));
                    $this->db->queries = array();
                    $this->db->query_times = array();
                    JiXin\api_log::write();
                }
                $this->db->set('recover_times', 'recover_times+1', false);
                $this->db->set('recover_account_yes', "recover_account_yes + {$v['recover_account']}", false);
                $this->db->set('recover_account_capital_yes', "recover_account_capital_yes + {$v['recover_capital']}", false);
                $this->db->set('recover_account_interest_yes', "recover_account_interest_yes + {$v['recover_interest']}", false);
                $this->db->set('recover_account_wait', "recover_account_wait - {$v['recover_account']}", false);
                $this->db->set('recover_account_capital_wait', "recover_account_capital_wait + {$v['recover_capital']}", false);
                $this->db->set('recover_account_interest_wait', "recover_account_interest_wait + {$v['recover_interest']}", false);
                $this->db->update(TABLE_BORROW_TENDER, array(), array('id' => $v['tender_id']));

                $this->db->set('repay_account_yes', "repay_account_yes + {$v['recover_account']}", false);
                $this->db->set('repay_account_capital_yes', "repay_account_capital_yes + {$v['recover_capital']}", false);
                $this->db->set('repay_account_interest_yes', "repay_account_interest_yes + {$v['recover_interest']}", false);
                $this->db->set('repay_account_wait', "repay_account_wait - {$v['recover_account']}", false);
                $this->db->set('repay_account_capital_wait', "repay_account_capital_wait + {$v['recover_capital']}", false);
                $this->db->set('repay_account_interest_wait', "repay_account_interest_wait + {$v['recover_interest']}", false);
                $this->db->update(TABLE_BORROW, array(), array('borrow_nid' => $v['borrow_nid']));

                $this->db->set('repay_account_yes', "repay_account_yes + {$v['recover_account']}", false);
                $this->db->set('repay_capital_yes', "repay_capital_yes + {$v['recover_capital']}", false);
                $this->db->set('repay_interest_yes', "repay_interest_yes + {$v['recover_interest']}", false);
                $this->db->update(TABLE_BORROW_REPAY, array(
                    'repay_yestime' => time(),
                    'status' => 1,
                        ), array(
                    'borrow_nid' => $v['borrow_nid'],
                    'repay_period' => $v['recover_period']
                ));
                //短信提醒
                $content = "尊敬的用户：您好！您投资的{$borrow['name']}项目已于今日" . date("H:i") . "回款，合计{$v['recover_account']}元，请您及时查询以便投资，感谢您对我们的持续关注！如果您有任何问题，欢迎致电客服热线:4001-123-990，祝您生活愉快，谢谢！";
                $this->m_sms->add(array(
                    'user_id' => $user->user_id,
                    'mobile' => $user->mobile,
                    'content' => $content
                ));
                //发送微信消息
                if (1) {
                    $this->load->model('m_wechat_msg');
                    $r = $this->m_wechat_msg->add_repay_msg($v['user_id'], $borrow['name'], $v['recover_capital'], $v['recover_interest'], time());
                }
            }
            //如果这个标的repay表还有待回款记录，插入一条新数据到queue_borrow表，并以pid字段区分主从
            $borrow_current = $this->db->get_where(TABLE_BORROW, array('borrow_nid' => $borrow->borrow_id))->row(0);
            if ($borrow_current->repay_account_wait > 0) {
                $queue = $this->db->get_where(TABLE_QUEUE_BORROW, array('id' => $v1['qid']))->row(0);
                $this->db->insert(TABLE_QUEUE_BORROW, array(
                    'borrow_auto_id' => $queue->borrow_auto_id,
                    'borrow_id' => $queue->borrow_id,
                    'status' => m_borrow::STATUS_P_PAY_SUCCESS,
                    'ba_status' => m_borrow::STATUS_P_BA_PAY_SUCCESS,
                    'pid' => $queue->id,
                    'third_status' => $queue->third_status,
                    'ancun' => $queue->ancun,
                    'tongdun' => $queue->tongdun,
                    'create_time' => time(),
                ));
            }
            $this->db->update(TABLE_QUEUE_BORROW, array('status' => m_borrow::STATUS_P_REPAY_SUCCESS), array('id' => $v1['qid']));
        }
        $this->api->output(true);
    }

    //结束债权
    public function borrow_tender_finish_ba() {
        ignore_user_abort();
        $this->load->model('m_borrow');
        $this->load->model('m_account');
        $this->load->model('m_user');
        $this->load->model('m_bank_account');

        $jx = new JiXin\api();

        $this->db->limit(10);
        $this->db->order_by(TABLE_BORROW . '.id asc');
        $this->db->select(TABLE_BORROW . '.*', false);
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_BORROW . '.borrow_nid = ' . TABLE_QUEUE_BORROW . '.borrow_id', 'inner');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', m_borrow::STATUS_P_REPAY_SUCCESS);
        $this->db->where(TABLE_QUEUE_BORROW . '.ba_status', m_borrow::STATUS_P_BA_REPAY_SUCCESS);
        $this->db->group_by('id');
        $rows = $this->db->get_where(TABLE_BORROW, array('repay_account_wait' => 0))->result_array(); //查找已完全回款的标
        foreach ($rows as $k => $v) {
            $this->db->queries = array();
            $this->db->query_times = array();
            $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_START), array('borrow_id' => $v['borrow_nid']));
            //判断标的是否正常
            if ($v['status'] != m_borrow::STATUS_VERIFY_FULL) {
                //todo 异常回款记录报警
                continue;
            }
            $subPacks = array();
            $tender_lists = $this->db->get_where(TABLE_BA_TENDER_NA, array('borrow_id' => $v['borrow_nid']))->result();
            foreach ($tender_lists as $k2 => $v2) {
                //生成结束债权订单号
                $action_id = $this->m_bank_account->action_add(array(
                    'action' => 'batchCreditEnd',
                    'user_id' => $v2->user_id,
                    'request' => json_encode(array(
                        'accountId' => $v['ba_account_id'],
                        'forAccountId' => $v2->accountId,
                        'productId' => $v2->productId,
                        'authCode' => $v2->authCode,
                    )),
                    'ip' => get_ip(),
                ));
                $action = $this->m_bank_account->action_detail($action_id);
                $subPacks[] = array(
                    'orderId' => $action->order_sn,
                    'accountId' => $v['ba_account_id'],
                    'forAccountId' => $v2->accountId,
                    'productId' => $v2->productId,
                    'authCode' => $v2->authCode,
                );
            }
            JiXin\api_log::set(array(
                'request_jc' => $this->api->in,
                'request_time' => time(),
                'start_time' => microtime(1),
                'api_name_cn' => '批量结束债权',
                'api_name_en' => 'batchCreditEnd',
                'user_id' => $this->api->user()->user_id,
            ));
            $seqNo = JiXin\counter::auto_id();
            $txDate = date('Ymd');
            $txTime = date('His');
            $r = $jx->batchCreditEnd(array(
                'txDate' => $txDate, //日期
                'txTime' => $txTime, //时间
                'seqNo' => $seqNo, //时间
                'txCounts' => count($tender_lists), //本批次所有交易笔数
                'subPacks' => $subPacks,
                'batchNo' => $seqNo, //批次号，单日不能重复，回调时需要该队列号
            ));
            JiXin\api_log::set(array(
                'response' => array(
                    'result' => $r['result'],
                    'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                    'error_msg' => $r['msg'],
                ),
            ));
            if ($r['result']['received'] === 'success') {
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_SUBMIT, 'ba_result_end_submit' => json_encode($r), 'ba_seqNo' => $txDate . $txTime . $seqNo), array('borrow_id' => $v['borrow_nid']));
            } else {
                //todo 重试机制
                $this->db->update(TABLE_QUEUE_BORROW, array('ba_status' => m_borrow::STATUS_P_BA_END_FAILED, 'ba_result_end_submit' => json_encode($r), 'ba_seqNo' => $txDate . $txTime . $seqNo), array('borrow_id' => $v['borrow_nid']));
                //todo 回退机制
            }
            JiXin\api_log::write();
        }
        $this->api->output(true);
    }

    //监控可投标的状态
    public function borrow_monitor() {
        //查询可投标
        ignore_user_abort();
        set_time_limit(0);
        $this->load->model('m_borrow');
        $this->load->model('m_script');
        $this->load->model('m_account');
        $this->load->model('m_error');
        $this->db->db_debug = TRUE;

        $notice_to = array(
            array(
                'user_id' => '10999',
                'mobile' => '13429131116',
            ),
        );

        $this->db->limit(100);
        $this->db->select(TABLE_BORROW . '.*,' . TABLE_QUEUE_BORROW . '.id as qid', false);
        $this->db->order_by(TABLE_BORROW . '.id', 'asc');
        $this->db->where(TABLE_BORROW . '.status', m_borrow::STATUS_VERIFY_ONLINE);
        $this->db->join(TABLE_QUEUE_BORROW, TABLE_QUEUE_BORROW . '.borrow_auto_id = ' . TABLE_BORROW . '.id', 'INNER');
        $this->db->where(TABLE_QUEUE_BORROW . '.status', 2);
        $rows = $this->db->get_where(TABLE_BORROW)->result_array();
        foreach ($rows as $k => $v) {
            $now = time();
            //判断剩余可投额度是否一致
            $wait_mem = $this->m_borrow->_get_memcache_account($v['borrow_nid']);
            $wait_db1 = intval($v['account']) - intval($v['borrow_account_yes']);
            $wait_db2 = intval($v['borrow_account_wait']);
            //1可投额度=总额和已投额度的差值 2memcache中的可投额度为false 或者等于可投额度  3可投额度需要大于0
            if ($wait_db1 == $wait_db2 && ($wait_mem !== false ? $wait_db1 == $wait_mem : true) && $wait_db2 >= 0) {
                //如果可投为0，应该满标成功，检查多久未进行满标动作
                if ($wait_db2 == 0) {
                    //最后次投标时间
                    $this->db->limit(1);
                    $this->db->order_by('id desc');
                    $tender = $this->db->get_where(TABLE_BORROW_TENDER, array('borrow_nid' => $v['borrow_nid']))->row(0);
                    if ($now - $tender->addtime > 2 * 60) {//超过2分钟没满标 进行短信提醒
                        if (!$this->m_error->is_exists(array(
                                    'item_type' => 'borrow',
                                    'item_id' => $v['borrow_nid'],
                                    'error_no' => 'BORROW_REVERIFY_FAILD', //满标审核异常
                                ))) {
                            $this->m_error->add(array(
                                'notice_to' => $notice_to,
                                'item_type' => 'borrow',
                                'item_id' => $v['borrow_nid'],
                                'error_no' => 'BORROW_REVERIFY_FAILD', //满标审核异常
                                'error_msg' => "{$v['name']} 已投满，超时<EXPIRE_TIME/>未进行满标，请检查！",
                                'repeat_notice' => 1
                            ));
                        }
                    }
                }
            } else {
                if ($wait_db1 != $wait_db2) {
                    if (!$this->m_error->is_exists(array(
                                'item_type' => 'borrow',
                                'item_id' => $v['borrow_nid'],
                                'error_no' => 'BORROW_AMOUNT_NOT_MATCH', //标的待投、已投、总金额异常
                            ))) {
                        $this->m_error->add(array(
                            'notice_to' => $notice_to,
                            'item_type' => 'borrow',
                            'item_id' => $v['borrow_nid'],
                            'error_no' => 'BORROW_AMOUNT_NOT_MATCH', //标的待投、已投、总金额异常
                            'error_msg' => "{$v['name']} 数据库中标的待投（{$v['borrow_account_wait']}）+已投（{$v['borrow_account_yes']}）不等于总金额（{$v['account']}），请检查！",
                            'repeat_notice' => 1
                        ));
                    }
                } else if ($wait_db2 < 0) {
                    if (!$this->m_error->is_exists(array(
                                'item_type' => 'borrow',
                                'item_id' => $v['borrow_nid'],
                                'error_no' => 'BORROW_AMOUNT_WAIT_ERROR', //标的待投金额小于0
                            ))) {
                        $this->m_error->add(array(
                            'notice_to' => $notice_to,
                            'item_type' => 'borrow',
                            'item_id' => $v['borrow_nid'],
                            'error_no' => 'BORROW_AMOUNT_WAIT_ERROR', //标的待投金额小于0
                            'error_msg' => "{$v['name']} 数据库中待投金额小于0：{$wait_db2}，请检查！",
                            'repeat_notice' => 1
                        ));
                    }
                } else if ($wait_db1 != $wait_mem) {
                    if (!$this->m_error->is_exists(array(
                                'item_type' => 'borrow',
                                'item_id' => $v['borrow_nid'],
                                'error_no' => 'BORROW_AMOUNT_WAIT_NOT_MATCH', //标的数据库中的待投金额和缓存中的待投金额不一致
                            ))) {
                        $this->m_error->add(array(
                            'notice_to' => $notice_to,
                            'item_type' => 'borrow',
                            'item_id' => $v['borrow_nid'],
                            'error_no' => 'BORROW_AMOUNT_WAIT_NOT_MATCH', //标的数据库中的待投金额和缓存中的待投金额不一致
                            'error_msg' => "{$v['name']} 实际金额为：{$wait_db1}，可投金额为：{$wait_mem}，存在异常，请检查！",
                            'repeat_notice' => 0
                        ));
                    }
                } else {
                    
                }
            }
        }
        $this->api->output(true);
    }

    //文件同步到upyun
    public function file_sync_to_upyun() {
        ignore_user_abort();
        $this->load->model('m_file');
        $this->load->library('upyun');
        $rows = $this->m_file->lists(array('sync_to_upyun' => 1, 'sync_to_upyun_status' => array(2, 4)), 1, 10, 'id asc');
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_FILE, array('sync_to_upyun_status' => 3), array('fid' => $v->fid));
            $filename = trim(parse_url($v->url, PHP_URL_PATH));
            $filedata = file_get_contents(FCPATH . $filename);
            try {
                $this->upyun->writeFile($filename, $filedata, True);
                $this->db->update(TABLE_FILE, array('sync_to_upyun_status' => 1), array('fid' => $v->fid));
            } catch (Exception $e) {
                $this->db->update(TABLE_FILE, array('sync_to_upyun_status' => 4), array('fid' => $v->fid));
            }
        }
        $this->api->output(true);
    }

    //短信队列
    public function sms_send() {
        $this->load->model('m_sms');
        $rows = $this->m_sms->lists(array('status' => m_sms::STATUS_SEND_INIT), 1, 10, 'id asc');
        foreach ($rows as $k => $v) {
            $this->m_sms->send_start($v->sms_id);
            $r = $this->m_sms->send($v->mobile, $v->content);
            if ($r['code'] != 2) {//请求发送失败
                $this->m_sms->send_failed($v->sms_id, $r);
            } else {
                $this->m_sms->send_success($v->sms_id, $r);
            }
        }
        $this->api->output(true);
    }

    //微信消息队列
    public function wechat_send() {
        $this->load->model('m_wechat_msg');
        $this->load->config('myconfig');
        $this->load->library('wechat_lib');
        $rows = $this->m_wechat_msg->lists(array('status' => m_wechat_msg::STATUS_SEND_INIT), 1, 10, 'id asc');

        foreach ($rows as $k => $v) {
            $this->m_wechat_msg->send_start($v->id);
            $config = $this->config->item('wechat');
            $access_token_obj = $this->wechat_lib->getAccessToken($config['mp_appid'], $config['mp_secret']);
            $access_token = $access_token_obj->access_token;
            $r = $this->m_wechat_msg->send($v->wx_openid, $v->template_id, $v->url, json_decode($v->data, true), $access_token);
            $array = json_decode($r, true);
            if ($array['errcode'] != 0) {//请求发送失败
                $this->m_wechat_msg->send_failed($v->id, $r);
            } else {
                $this->m_wechat_msg->send_success($v->id, $r);
            }
        }
        $this->api->output(true);
    }

    //米咖网相关
    public function mika18() {
        $this->load->model('m_script');
        $this->load->model('m_user');
        $this->load->library('admin_runner');

        //注册数据推送
        $key_last_user_id = 'LAST_USER_ID';
        if (!$this->m_script->isset_key(__FUNCTION__, $key_last_user_id)) {
            $this->m_script->set_value(__FUNCTION__, $key_last_user_id, 0);
        }
        $last_user_id = $this->m_script->get_value(__FUNCTION__, $key_last_user_id);
        $this->db->where('user_id > ' . $last_user_id, false, false);
        $this->db->order_by('user_id asc');
        $users = $this->db->get_where(TABLE_USER_THIRD, array('third_name' => 'mika18'), 10)->result_array();
        foreach ($users as $k => $v) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/mika18/send/register';
            curl_get($url, array(
                'user_id' => $v['user_id'],
                'token' => $this->admin_runner->get_token()
            ));
            $this->m_script->set_value(__FUNCTION__, $key_last_user_id, $v['user_id']);
        }
        //投标数据推送
        $key_last_tender_id = 'LAST_TENDER_ID';
        if (!$this->m_script->isset_key(__FUNCTION__, $key_last_tender_id)) {
            $this->m_script->set_value(__FUNCTION__, $key_last_tender_id, 0);
        }
        $last_tender_id = $this->m_script->get_value(__FUNCTION__, $key_last_tender_id);
        $this->db->select(TABLE_BORROW_TENDER . '.*', false);
        $this->db->where(TABLE_BORROW_TENDER . '.id > ', $last_tender_id, false);
        $this->db->order_by('id asc');
        $this->db->limit(10);
        $this->db->join(TABLE_USER_THIRD, TABLE_USER_THIRD . '.user_id = ' . TABLE_BORROW_TENDER . '.user_id', 'inner');
        $rows = $this->db->get_where(TABLE_BORROW_TENDER, array(
                    TABLE_USER_THIRD . '.third_name' => 'mika18'
                ))->result_array();
        foreach ($rows as $k => $v) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/mika18/send/tender';
            curl_get($url, array(
                'tender_id' => $v['id'],
                'token' => $this->admin_runner->get_token()
            ));
            $this->m_script->set_value(__FUNCTION__, $key_last_tender_id, $v['id']);
        }
        $this->api->output(true);
    }

    public function recharge_third_party() {
        ignore_user_abort();
        $this->load->model('m_script');
        $this->load->model('m_recharge');
        $this->load->model('m_user');
        $this->db->db_debug = TRUE;

        $this->load->config('myconfig');
        $config = $this->config->item('51cunzheng');

        require_once APPPATH . 'libraries/AospClient.php';

        $rows = $this->db->get_where(TABLE_QUEUE_RECHARGE, array('ancun' => 2))->result_array();

        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_RECHARGE, array('ancun' => 3, 'modify_time' => time()), array('id' => $v['id']));
            $order = $this->m_recharge->detail($v['log_id']);
            $user = $this->m_user->detail($order->user_id, true);
            //安存
            $ancun = array(
                'rechargeAccount' => $order->user_id,
                'rechargeUserName' => $user->realname,
                'rechargeIdCard' => $user->id_card,
                'rechargeChannel' => '连连支付',
                'rechargeAmount' => sprintf('%.2f元', $order->money),
                'rechargeOperateTime' => date("Y-m-d H:i:s", $order->create_time),
                'rechargeSuccessTime' => date("Y-m-d H:i:s", $order->verify_time),
                'rechargeSerialNo' => $order->order_sn,
            );
            $aospRequest = new AospRequest();
            $aospRequest->setItemKey("I-0081001");
            $aospRequest->setFlowNo("X-0248001");
            $aospRequest->setData($ancun);
            $aospClient = new AospClient($config['api_url'], $config['key'], $config['secret']);
            $aospResponse = $aospClient->save($aospRequest);
            $ancunData = $aospResponse->getData();
            $ancunCode = $aospResponse->getCode();

            if ($ancunCode == 100000) {
                $ancunRecord = $ancunData['recordNo'];
                $this->db->update(TABLE_ACCOUNT_RECHARGE, array('recordNo' => $ancunRecord), array('id' => $v['log_id']));
            }
            $this->db->update(TABLE_QUEUE_RECHARGE, array('ancun' => 1, 'modify_time' => time(), 'ancun_result' => json_encode($ancunData)), array('id' => $v['id']));
        }
        $this->api->output(true);
    }

    //体验金相关定时脚本，体验金过期，收益日结
    public function experience_interval() {
        $this->load->model('m_experience');
        //过期
        $this->db->where('expire_time < ' . time(), false, false);
        $this->db->update(TABLE_EXPERIENCE_USER, array('status' => m_experience::STATUS_USER_EXPIRE), array('status' => m_experience::STATUS_USER_INIT));
        //体验结束
        //$this->db->where('end_time < ' . time(), false, false);
        if ($_SERVER['SERVER_NAME'] == 'api.car.com') {
            $this->db->where('end_time < ' . (time() + 23.5 * 3600), false, false);
        } else if ($_SERVER['SERVER_NAME'] == 'apitest.ifcar99.com') {
            $this->db->where('end_time < ' . (time() + 23.5 * 3600), false, false);
        } else if ($_SERVER['SERVER_NAME'] == 'api.ifcar99.com') {
            $this->db->where('end_time < ' . time(), false, false);
        } else if ($_SERVER['SERVER_NAME'] == 'api_lsk.com') {
            $this->db->where('end_time < ' . time(), false, false);
        } else {
            $this->db->where('end_time < ' . time(), false, false);
        }
        $this->db->update(TABLE_EXPERIENCE_USER, array('status' => m_experience::STATUS_USER_USED), array('status' => m_experience::STATUS_USER_IN_USE));
        //清算已结束的体验金
        $this->db->limit(100);
        $this->db->where('profit_unget > 0', false, false);
        $rows = $this->db->get_where(TABLE_EXPERIENCE_USER, array('status' => m_experience::STATUS_USER_USED))->result();
        foreach ($rows as $k => $v) {
            $this->db->trans_start();
            $profit = $v->profit_unget;
            $this->db->update(TABLE_EXPERIENCE_USER, array(
                'profit_last_time' => $v->end_time,
                'profit_unget' => 0
                    ), array('id' => $v->id, 'status' => m_experience::STATUS_USER_USED));
            if ($this->db->affected_rows() < 1) {
                continue;
            }
            $this->m_experience->log_add(array(
                'user_id' => $v->user_id,
                'experience_user_id' => $v->id,
                'money' => $profit,
                'type' => m_experience::TYPE_EXPERIENCE_IN
            ));
            $this->db->set('money', 'money +' . $profit, false);
            $this->db->set('money_total', 'money_total +' . $profit, false);
            $this->db->update(TABLE_EXPERIENCE_ACCOUNT, array(), array('user_id' => $v->user_id));
            $this->db->trans_complete();
        }
        //未结束部分做日收益结算
        $this->db->limit(100);
        $time = time();
        $this->db->where('end_time > ' . $time, false, false);
        $this->db->where('profit_last_time < ' . ($time - 86400), false, false); //一天 86400
        $this->db->where('profit_unget > 0', false, false);
        $rows = $this->db->get_where(TABLE_EXPERIENCE_USER, array('status' => m_experience::STATUS_USER_IN_USE))->result();
        foreach ($rows as $k => $v) {
            $this->db->trans_begin();
            $end_time = min($time, $v->end_time);
            $seconds = $end_time - max(array($v->start_time, $v->profit_last_time));
            $profit = 0;
            if ($v->end_time <= $time) {//已完成体验
                //应该不会执行到这里
                $profit = $v->profit_unget;
                $this->db->set('status', m_experience::STATUS_USER_USED);
                $this->db->set('profit_unget', 0);
            } else {
                $profit = min($v->profit_unget, $this->m_experience->profit($v->money, $v->rate, $seconds));
                $this->db->set('profit_unget', 'profit_unget - ' . $profit, false);
            }
            $this->db->update(TABLE_EXPERIENCE_USER, array(
                'profit_last_time' => $time
                    ), array('id' => $v->id, 'status' => m_experience::STATUS_USER_IN_USE));
            if ($this->db->affected_rows() < 1) {
                $this->db->trans_rollback();
                continue;
            }
            $this->m_experience->log_add(array(
                'user_id' => $v->user_id,
                'experience_user_id' => $v->id,
                'money' => $profit,
                'type' => m_experience::TYPE_EXPERIENCE_IN
            ));
            $this->db->set('money', 'money +' . $profit, false);
            $this->db->set('money_total', 'money_total +' . $profit, false);
            $this->db->update(TABLE_EXPERIENCE_ACCOUNT, array(), array('user_id' => $v->user_id));
            $this->db->trans_commit();
        }
        $this->api->output(true);
    }

    //用户注册相关
    public function user_register() {
        $this->load->model('m_script');
        $this->load->model('m_experience');
        $this->load->model('m_inviter');
        $this->load->model('m_bouns');

        $key_last_id = 'LAST_USER_ID';
        $last_id = $this->m_script->get_value(__FUNCTION__, $key_last_id);
        $this->db->limit(100);
        $this->db->where('user_id > ' . $last_id, false, false);
        $this->db->order_by('user_id asc');
        $rows = $this->db->get_where(TABLE_USER)->result();
        foreach ($rows as $k => $v) {
            //分配客户经理
            $r1 = $this->m_inviter->find_parents($v->user_id);
            $inviter_uid = count($r1) > 0 ? $r1[count($r1) - 1] : 0;
            if ($inviter_uid > 0 && ($this->m_inviter->is_without($inviter_uid) || $this->m_inviter->is_manager($inviter_uid))) {//有邀请人，并且在居间名单中，直接分配客户经理
                $this->m_inviter->set_manager($v->user_id, $inviter_uid);
            } else {
                $this->m_inviter->assign_manager($v->user_id);
            }
            //发放新手红包
            $bouns = array(
                '2355' => 1,
                '2356' => 1,
                '2357' => 5,
                '2358' => 5,
                '2359' => 5,
                '2360' => 5,
                '2361' => 5,
                '2362' => 5,
                '2363' => 5,
            );
            foreach ($bouns as $k2 => $v2) {
                $this->m_bouns->send_to_user($k2, $v->user_id, $v2);
            }
            //发放体验金
            $experience_first_id = 1;
            $experience_first_money = 10000; //注册送1w体验金
            $experience_first = $this->m_experience->detail($experience_first_id);
            if (!empty($experience_first) && $experience_first->status->id == m_experience::STATUS_EXPERIENCE_ON) {
                //累计发放额度验证
                if ($this->m_experience->increase($experience_first_id, $experience_first_money)) {
                    $this->m_experience->send_to_user($experience_first_id, $experience_first_money, $v->user_id, '注册奖励');
                }
            }
            //查找邀请人
            $this->db->limit(1);
            $this->db->where('status <> 3', false, false);
            $inviter = $this->db->get_where(TABLE_USER_FRIENDS_INVITE, array('friends_userid' => $v->user_id))->row(0)->user_id; //查找邀请人
            //今日头条邀请送1w体验金
            if ($inviter) {
                if ($inviter == 48788) {
                    $experience_inviter_id = 7;
                    $experience_inviter_money = 10000;
                    $experience_inviter = $this->m_experience->detail($experience_inviter_id);
                    if (!empty($experience_inviter) && $experience_inviter->status->id == m_experience::STATUS_EXPERIENCE_ON) {
                        //累计发放额度验证
                        if ($this->m_experience->increase($experience_inviter_id, $experience_inviter_money)) {
                            $this->m_experience->send_to_user($experience_inviter_id, $experience_inviter_money, $v->user_id, '今日头条活动奖励');
                        }
                    }
                }
            } else {
                $experience_inviter_id = 11;
                $experience_inviter_money = 5888;
                $experience_inviter = $this->m_experience->detail($experience_inviter_id);
                if (!empty($experience_inviter) && $experience_inviter->status->id == m_experience::STATUS_EXPERIENCE_ON) {
                    //累计发放额度验证
                    if ($this->m_experience->increase($experience_inviter_id, $experience_inviter_money)) {
                        $this->m_experience->send_to_user($experience_inviter_id, $experience_inviter_money, $v->user_id, '新用户体验金');
                    }
                }
            }
            $this->m_script->set_value(__FUNCTION__, $key_last_id, $v->user_id);
        }
        /*
          $this->load->model('m_coupon');
          $key_last_user_id2 = 'LAST_USER_ID_FOR_COUPON';
          if (!$this->m_script->isset_key(__FUNCTION__, $key_last_user_id2)) {
          $this->m_script->set_value(__FUNCTION__, $key_last_user_id2, 0);
          }
          $last_id2 = $this->m_script->get_value(__FUNCTION__, $key_last_user_id2);
          $this->db->limit(100);
          $this->db->where('user_id > ' . $last_id2, false, false);
          $this->db->order_by('user_id asc');
          $rows = $this->db->get_where(TABLE_USER)->result();
          foreach ($rows as $k => $v) {
          //发放加息券
          $coupon_id = 11;
          $cu_id = $this->m_coupon->send_to_user($coupon_id, $v->user_id, 'VIP加息券');
          $this->m_coupon->user_auto_use($cu_id);
          $this->m_script->set_value(__FUNCTION__, $key_last_user_id2, $v->user_id);
          }
         * 
         */
        $this->api->output(true);
    }

    //融资平台相关
    public function finance_borrow_full() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_borrow');
        //满标查询
        $rows = $this->m_finance_bill->lists(array('has_online' => 1, 'has_full' => 2));
        foreach ($rows as $k => $v) {
            $borrow = $this->m_borrow->detail($v->borrow_id);
            if ($borrow->money_unget == 0) {
                $this->m_finance_bill->full($v->finance_bill_id, 1);
                //添加操作流水
                $this->m_finance_bill->action_add(array(
                    'user_id' => 0,
                    'user_type' => 0, //系统
                    'finance_bill_id' => $v->finance_bill_id,
                    'title' => '满标成功',
                    'msg' => "满标时间：" . date('Y-m-d H:i:s') . " 满标金额： " . floor($v->money / 100) . "元"
                ));
            }
        }
        //融资单过期,未上线的可以过期
        $rows2 = $this->m_finance_bill->lists(array('has_online' => 2, 'has_expired' => 2));
        foreach ($rows2 as $k => $v) {
            if (time() > strtotime(date('Y-m-d 23:59:59', $v->paid_time)) + 3600 * 24 * 5) {
                $this->db->update(TABLE_FINANCE_BILL, array('has_expired' => 1), array('id' => $v->finance_bill_id));
                //添加操作流水
                $this->m_finance_bill->action_add(array(
                    'user_id' => 0,
                    'user_type' => 0, //系统
                    'finance_bill_id' => $v->finance_bill_id,
                    'title' => '融资单过期',
                    'msg' => "融资单已过期！"
                ));
            }
        }
        //融资单募资天数
        $rows3 = $this->m_finance_bill->lists(array('has_online' => 1, 'borrow_days' => 0));
        foreach ($rows3 as $k => $v) {
            $borrow = $this->m_borrow->detail($v->borrow_id);
            if ($borrow->money_get > 0) {
                $this->m_finance_bill->set_borrow($v->finance_bill_id, array(
                    'borrow_id' => $borrow->borrow_id,
                    'borrow_title' => $borrow->title,
                    'borrow_days' => $borrow->days,
                ));
                //添加操作流水
                $this->m_finance_bill->action_add(array(
                    'user_id' => 0,
                    'user_type' => 0, //系统
                    'finance_bill_id' => $v->finance_bill_id,
                    'title' => '融资单过期',
                    'msg' => "融资单已过期！"
                ));
            }
        }
        $this->api->output(true);
    }

    public function lists() {
        $this->load->model('m_script');
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'status asc,id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_script->lists($condition, $page = false, $size = false, $order);
        $r['total'] = $this->m_script->count($condition);
        $this->api->output($r);
    }

    public function run() {
        $this->load->model('m_script');
        $r = $this->m_script->run($this->api->in['script_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output($this->m_script->last_run_error($this->api->in['script_id']), ERR_FAILED_NO, ERR_FAILED_MSG);
        }
    }

    public function getAccessToken($appid, $secret) {

        $config = $this->config->item('wechat');
        $url = $config['api_url'] . 'cgi-bin/token';
        $result = curl_upload_https($url, array(
            'appid' => $appid,
            'secret' => $secret,
            'grant_type' => 'client_credential'
        ));
        $obj = json_decode($result);
        return $obj;
    }

    public function send_activity_bouns() {
        //五一活动1红包
        $this->load->model('m_activity');
        $this->load->model('m_bouns');
        $list = $this->m_activity->activity_lists();
        $bouns = 0;
        $bouns_2 = 0;
        $bouns_3 = 0;
        if ($list) {
            foreach ($list as $k => $v) {

                if ($v['money'] >= 150000) {
                    $bouns = 100;
                    $bouns_2 = 100;
                    $bouns_3 = 100;
                } else if ($v['money'] >= 100000) {
                    $bouns = 100;
                    $bouns_2 = 100;
                    $bouns_3 = 0;
                } else if ($v['money'] >= 50000) {
                    $bouns = 50;
                    $bouns_2 = 100;
                    $bouns_3 = 0;
                } else if ($v['money'] >= 30000) {
                    $bouns = 80;
                    $bouns_2 = 0;
                    $bouns_3 = 0;
                } else if ($v['money'] >= 10000) {
                    $bouns = 40;
                    $bouns_2 = 0;
                    $bouns_3 = 0;
                } else if ($v['money'] >= 5000) {
                    $bouns = 20;
                    $bouns_2 = 0;
                    $bouns_3 = 0;
                } else {
                    $bouns = 0;
                    $bouns_2 = 0;
                    $bouns_3 = 0;
                }
                if ($bouns) {   //本次投资使用了红包
                    /* $activity_bouns_id = $this->m_bouns->add(array(
                      'creator' => 'admin',
                      'money' => $bouns,
                      'num_current' => 1,
                      'num_total' => 1,
                      'start_time' => time(),
                      'expire' => 3600 * 24 * 7,     //有效期7天
                      'use_channel' => 'all',
                      'use_type' => 0,
                      'remark' => '劳动者的名义（五一活动）'
                      ));
                      $this->m_bouns->send_to_user($activity_bouns_id, $v['user_id']); */
                    do_script_log($v['user_id'] . ':' . $bouns . '/n');
                }
                if ($bouns_2) {   //本次投资使用了红包
                    /* $activity_bouns_id = $this->m_bouns->add(array(
                      'creator' => 'admin',
                      'money' => $bouns_2,
                      'num_current' => 1,
                      'num_total' => 1,
                      'start_time' => time(),
                      'expire' => 3600 * 24 * 7,     //有效期7天
                      'use_channel' => 'all',
                      'use_type' => 0,
                      'remark' => '劳动者的名义（五一活动）'
                      ));
                      $this->m_bouns->send_to_user($activity_bouns_id, $v['user_id']); */
                    do_script_log($v['user_id'] . ':' . $bouns_2 . '/n');
                }
                if ($bouns_3) {   //本次投资使用了红包
                    /* $activity_bouns_id = $this->m_bouns->add(array(
                      'creator' => 'admin',
                      'money' => $bouns_3,
                      'num_current' => 1,
                      'num_total' => 1,
                      'start_time' => time(),
                      'expire' => 3600 * 24 * 7,     //有效期7天
                      'use_channel' => 'all',
                      'use_type' => 0,
                      'remark' => '劳动者的名义（五一活动）'
                      ));
                      $this->m_bouns->send_to_user($activity_bouns_id, $v['user_id']); */
                    do_script_log($v['user_id'] . ':' . $bouns_3 . '/n');
                }
                $param['status'] = 1;
                $this->m_activity->update_activity_bouns($v['id'], $param);
            }
            $this->api->output(true);
        } else {
            $this->api->output(true);
        }
    }

    public function cash_fail() {
        $this->load->model('m_cash');
        $this->load->model('m_account');
        $cash_array = $this->m_cash->uncheck_cash_list();
        foreach ($cash_array as $k => $cash) {
            if ($cash['status'] == 0 && $cash['mobile'] && (($cash['addtime'] + 180) < time())) {//超时三分钟将待审核的记录设置为提现失败
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
        }
        $this->api->output(true);
    }

}
