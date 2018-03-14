<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of order
 *
 * @author win7
 */
class order extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_order');
    }

    public function add() {
        $type = 'order';
        $this->load->model('m_credit_type');
        $this->load->model('m_phone_recharge');
        $this->load->model('m_goods_zone');
        $this->load->library('order_process');
        $credit_type = $this->m_credit_type->detail_by_nid($type);
        //判断积分商城功能是否开放
        if (empty($credit_type)) {
            $this->api->output(false, ERR_MALL_OFF_NO, ERR_MALL_OFF_MSG);
        }

        $param = $this->api->in;
        $user_id = $param['user_id'] = $this->api->user()->user_id;
        $price = 0;
        //查询商品金额
        $this->load->model('m_goods');
        $order_goods = json_decode($this->api->in['order_goods'], true);
        if (empty($order_goods)) {
            $this->api->output(false, ERR_ORDER_EMPTY_NO, ERR_ORDER_EMPTY_MSG);
        }
        //禁止的商品类别
        $forbidden = array(6); //6 积分
        $forbidden_zones = $this->m_goods_zone->lists(array('use_for' => 'prize'));
        $forbidden_zone_obj = array();
        foreach ($forbidden_zones as $k => $v) {
            $forbidden_zone_obj[$v->zone_id] = true;
        }
        //每个订单限制充值卡的数量为1
        $card_limit = 1;
        $card_num = 0;
        foreach ($order_goods as $k => $v) {
            //判断订单商品数量为0
            if (intval($v['num']) < 1) {
                $this->api->output(false, ERR_ORDER_GOODS_NUM_EMPTY_NO, "[{$v['iid']}]" . ERR_ORDER_GOODS_NUM_EMPTY_MSG);
            }
            $goods = $this->m_goods->detail($v['iid']);
            //判断商品状态
            if ($goods->status->id != m_goods::STATUS_GOODS_ON) {
                $this->api->output(false, ERR_GOODS_OFF_NO, "[{$goods->iid}]" . ERR_GOODS_OFF_MSG);
            }
            //判断商品类别
            if (in_array($goods->category->id, $forbidden)) {
                $this->api->output(false, ERR_NOT_ALLOWED_CATEGORY_NO, "[{$goods->iid}]" . ERR_NOT_ALLOWED_CATEGORY_MSG);
            }
            //判断商品是否在抽奖专区中
            $zone_obj = $this->m_goods->get_zones($goods->iid);
            $allow = true;
            foreach ($zone_obj as $k2 => $v2) {
                if (isset($forbidden_zones[$v2->zone_id])) {
                    $allow = false;
                }
            }
            if (!$allow) {
                $this->api->output(false, ERR_NOT_ALLOWED_CATEGORY_NO, "[{$goods->iid}]" . ERR_NOT_ALLOWED_CATEGORY_MSG);
            }
            $order_goods[$k]['price'] = $goods->price;
            $order_goods[$k]['price_retail'] = $goods->price_retail;
            $order_goods[$k]['title'] = $goods->title;
            $order_goods[$k]['title_small'] = $goods->title_small;
            $order_goods[$k]['cid'] = $goods->category->id;
            $order_goods[$k]['option'] = $goods->option;
            $price += $goods->price * $v['num'];
            if (intval($goods->limit_on_time) > 0 && $goods->limit_on_time > $_SERVER['REQUEST_TIME']) {
                $this->api->output($goods->iid, ERR_GOODS_TIME_LIMIT_BEGIN_NO, ERR_GOODS_TIME_LIMIT_BEGIN_MSG); //还未到商品开放购买时间
            }
            if (intval($goods->limit_off_time) > 0 && $goods->limit_off_time < $_SERVER['REQUEST_TIME']) {
                $this->api->output($goods->iid, ERR_GOODS_TIME_LIMIT_END_NO, ERR_GOODS_TIME_LIMIT_END_MSG); //已超过商品截止购买时间
            }
            if (intval($goods->limit) > 0) {
                //预设购买数量
                $if_success_buy = $this->m_order->increase_goods_user_buy($user_id, $v['iid'], $v['num']);
                //增加购买量肯定执行成功，所以不放在if else里面
                $this->order_process->push(array(
                    'type' => 'goods_user_buy',
                    'item' => array('user_id' => $user_id, 'iid' => $v['iid'], 'num' => $v['num'])
                ));
                if ($if_success_buy > $goods->limit) {
                    //减去预购数量
                    $this->order_process->clean();
                    $this->api->output($if_success_buy - $v['num'], ERR_BEYOND_THE_LIMIT_NO, ERR_BEYOND_THE_LIMIT_MSG); //超出购买数量
                }
            }
            //下单减库存，防止超卖
            if ($this->m_goods->decrease($goods->iid, $v['num'])) {
                //库存充足
                $goods_store = array(
                    'type' => 'goods_store',
                    'item' => array('iid' => $v['iid'], 'num' => $v['num'])
                );
                $this->order_process->push($goods_store);
            } else {
                //库存不足，返回已预减的商品库存数，并返回错误
                $this->order_process->clean();
                $this->api->output(false, ERR_OUT_OF_STOCK_NO, "[{$goods->iid}]" . ERR_OUT_OF_STOCK_MSG);
            }
            //话费直冲商品下单修改累计额度，防止充值总额超过限制
            if ($goods->category->id == 5) {
                $card_num += $v['num'];
                if ($card_num > $card_limit) {
                    $this->order_process->clean(); //清空已处理数据
                    $this->api->output(false, ERR_BEYOND_PER_ORDER_LIMIT_NO, "[iid：{$goods->iid}=>limit：{$card_limit}]" . ERR_BEYOND_PER_ORDER_LIMIT_MSG);
                }
                $recharge = json_decode($goods->option, true);
                //预减用户充值限额
                if ($this->m_phone_recharge->user_month_limit_decrease($user_id, intval($recharge['face_price']) * $v['num'])) {
                    $this->order_process->push(array(
                        'type' => 'phone_month_limit',
                        'item' => array('iid' => $v['iid'], 'face_price' => $recharge['face_price'], 'num' => $v['num'])
                    ));
                } else {
                    $this->order_process->clean(); //清空已处理数据
                    $this->api->output(false, ERR_BEYOND_USER_MONTH_LIMIT_NO, "[{$goods->iid}]" . ERR_BEYOND_USER_MONTH_LIMIT_MSG);
                }
            }
        }
        $param['order_goods'] = $order_goods;
        //拷贝地址,避免用户修改地址时影响已下的订单
        $this->load->model('m_address');
        $param['address_id'] = $this->m_address->copy($param['address_id']);
        $param['type'] = 'mall';
        $oid = $this->m_order->add($param);
        $this->m_order->pay_start($oid);
        //订单自动使用积分支付
        $this->load->model('m_credit');
        //减掉积分
        $c_r = $this->m_credit->decrease($param['user_id'], $price, array(
            'type' => $type,
            'item_id' => $oid,
            'remark' => '积分商城兑换',
        ));
        if ($c_r) {
            //余额充足，完成支付
            $this->m_order->pay_finish($oid);
            //更新商品出售数量
            foreach ($order_goods as $k2 => $v2) {
                $this->m_goods->increase_sold($v2['iid'], $v2['num']);
            }
        } else {
            //余额不足
            $this->m_order->pay_failed($oid);
            //将扣除的商品数量退回库存，将预设的用户购买数量从记录中减去，##所有下单时改变的量需要在这里回滚！！##
            $this->order_process->clean(); //清空已处理数据
//            foreach ($order_goods as $k2 => $v2) {
//                $this->m_goods->increase($v2['iid'], $v2['num']);
//                $this->m_order->decrease_goods_user_buy($user_id, $v2['iid'], $v2['num']);
//                $recharge = json_decode($v2['option'], true);
//                $this->m_phone_recharge->user_month_limit_increase($user_id, intval($recharge['face_price']) * $v2['num']);
//            }
            $this->api->output(false, ERR_CREDIT_NOT_ENOUGH_NO, ERR_CREDIT_NOT_ENOUGH_MSG);
        }
        //按商品分类发货
        $auto_shipping = true;
        $auto_finish = true;
        foreach ($order_goods as $k => $v) {
            switch ($v['cid']) {
                case 1://实物商品
                    $auto_shipping = false;
                    $auto_finish = false;
                    break;
                case 3://红包
                    //加到用户红包中
                    $bouns = json_decode($v['option'], true);
                    $this->load->model('m_bouns');
                    if ($bouns['id']) {
                        $bouns_id = $bouns['id'];
                    } else {
                        $bouns_id = $this->m_bouns->add(array(
                            'creator' => 'admin',
                            'money' => $bouns['money'],
                            'num_current' => $v['num'],
                            'num_total' => $v['num'],
                            'start_time' => time(),
                            'expire' => 3600 * 24 * intval($bouns['expire']),
                            'use_channel' => $bouns['use_channel'],
                            'use_type' => intval($bouns['use_limit']),
                            'remark' => trim($v['title_small']) ? trim($v['title_small']) : '积分兑换红包',
                        ));
                    }
                    for ($i = 0; $i < $v['num']; $i++) {
                        $this->m_bouns->send_to_user($bouns_id, $param['user_id']);
                    }
                    break;
                case 4://加息券
                    break;
                case 5://充值卡
                    //自动充值，写入异步充值队列
                    $r = $this->m_phone_recharge->join_recharge_queue($oid);
                    $auto_shipping = false;
                    $auto_finish = false;
                    /*
                     * 充值并非即时返回充值结果，需要异步处理
                      if (!$r) {
                      //todo 定时器查询是否成功，不成功则退回
                      $auto_shipping = false;
                      $this->m_goods->increase($v['iid'], $v['num']);
                      $this->m_order->decrease_goods_user_buy($user_id, $v['iid'], $v['num']);
                      $recharge = json_decode($v['option'], true);
                      $this->m_phone_recharge->user_month_limit_increase($user_id, intval($recharge['face_price']) * $v['num']);
                      }
                     * 
                     */
                    //todo 定时器查询是否成功，成功则完成订单，不成功则退回，并关闭订单
                    break;
                case 6://积分
                    break;
            }
        }
        //可以自动发货
        if ($auto_shipping) {
            $this->m_order->shipping($oid, array(
                'shipping_company' => 'AUTO_SHIPPING',
                'shipping_sn' => 'NO_SN',
            ));
        }
        //可自动完成
        if ($auto_finish) {
            $this->m_order->finish($oid);
        }
        $r = $this->m_order->detail($oid);
        $this->api->output($r);
    }

    public function add_prize() {
        $type = 'order';
        $this->load->model('m_credit_type');
        $this->load->model('m_goods_zone');
        $this->load->model('m_phone_recharge');
        $this->load->library('order_process');
        $credit_type = $this->m_credit_type->detail_by_nid($type);
        //判断积分商城功能是否开放
        if (empty($credit_type)) {
            $this->api->output(false, ERR_MALL_OFF_NO, ERR_MALL_OFF_MSG);
        }

        $allowed_zones = $this->m_goods_zone->lists(array('use_for' => 'prize'));
        $allowed_zone_obj = array();
        foreach ($allowed_zones as $k => $v) {
            $allowed_zone_obj[$v->zone_id] = true;
        }

        $param = $this->api->in;
        $user_id = $param['user_id'];
        $price = 0;
        //查询商品金额
        $this->load->model('m_goods');
        $order_goods = json_decode($this->api->in['order_goods'], true);
        if (empty($order_goods)) {
            $this->api->output(false, ERR_ORDER_EMPTY_NO, ERR_ORDER_EMPTY_MSG);
        }
        foreach ($order_goods as $k => $v) {
            //判断订单商品数量为0
            if (intval($v['num']) < 1) {
                $this->api->output(false, ERR_ORDER_GOODS_NUM_EMPTY_NO, "[{$v['iid']}]" . ERR_ORDER_GOODS_NUM_EMPTY_MSG);
            }
            $goods = $this->m_goods->detail($v['iid']);
            //判断商品状态
            if ($goods->status->id != m_goods::STATUS_GOODS_ON) {
                $this->api->output(false, ERR_GOODS_OFF_NO, "[{$goods->iid}]" . ERR_GOODS_OFF_MSG);
            }
            //判断商品是否在抽奖专区中
            $zone_obj = $this->m_goods->get_zones($goods->iid);
            $allow = false;
            foreach ($zone_obj as $k2 => $v2) {
                if (isset($allowed_zone_obj[$v2->zone_id])) {
                    $allow = true;
                }
            }
            if (!$allow) {
                $this->api->output(false, ERR_NOT_ALLOWED_CATEGORY_NO, "[{$goods->iid}]" . ERR_NOT_ALLOWED_CATEGORY_MSG);
            }
            $order_goods[$k]['price'] = $goods->price;
            $order_goods[$k]['price_retail'] = $goods->price_retail;
            $order_goods[$k]['title'] = $goods->title;
            $order_goods[$k]['title_small'] = $goods->title_small;
            $order_goods[$k]['cid'] = $goods->category->id;
            $order_goods[$k]['option'] = $goods->option;
            $price += $goods->price * $v['num'];
            //下单减库存，防止超卖
            if ($this->m_goods->decrease($goods->iid, $v['num'])) {
                //库存充足
                $goods_store = array(
                    'type' => 'goods_store',
                    'item' => array('iid' => $v['iid'], 'num' => $v['num'])
                );
                $this->order_process->push($goods_store);
            } else {
                //库存不足，返回已预减的商品库存数，并返回错误
                $this->order_process->clean();
                $this->api->output(false, ERR_OUT_OF_STOCK_NO, "[{$goods->iid}]" . ERR_OUT_OF_STOCK_MSG);
            }
        }
        $param['order_goods'] = $order_goods;
        //拷贝地址,避免用户修改地址时影响已下的订单
        $this->load->model('m_address');
        $param['address_id'] = $this->m_address->copy($param['address_id']);
        $param['type'] = 'prize';
        $oid = $this->m_order->add($param);
        $this->m_order->pay_start($oid);
        //订单自动使用积分支付
        $this->load->model('m_credit');
        //减掉积分
        $c_r = $this->m_credit->decrease($user_id, $price, array(
            'type' => $type,
            'item_id' => $oid,
            'remark' => '抽奖奖品发放',
        ));
        if ($c_r) {
            //余额充足，完成支付
            $this->m_order->pay_finish($oid);
            //更新商品出售数量
            foreach ($order_goods as $k2 => $v2) {
                $this->m_goods->increase_sold($v2['iid'], $v2['num']);
            }
        } else {
            //余额不足
            $this->m_order->pay_failed($oid);
            //将扣除的商品数量退回库存，将预设的用户购买数量从记录中减去，##所有下单时改变的量需要在这里回滚！！##
            $this->order_process->clean(); //清空已处理数据
            $this->api->output(false, ERR_CREDIT_NOT_ENOUGH_NO, ERR_CREDIT_NOT_ENOUGH_MSG);
        }
        //按商品分类发货
        $auto_shipping = true;
        $auto_finish = true;
        foreach ($order_goods as $k => $v) {
            switch ($v['cid']) {
                case 1://实物商品
                    $auto_shipping = false;
                    $auto_finish = false;
//                    if ($v['iid'] == '64') {
//                        $this->load->model('m_prize_chance');
//                        $this->m_prize_chance->increase($user_id,2,1);
//                        $auto_shipping = true;
//                        $auto_finish = true;
//                    }
                    break;
                case 3://红包
                    //加到用户红包中
                    $bouns = json_decode($v['option'], true);
                    $this->load->model('m_bouns');
                    if ($bouns['id']) {
                        $bouns_id = $bouns['id'];
                    } else {
                        $bouns_id = $this->m_bouns->add(array(
                            'creator' => 'admin',
                            'money' => $bouns['money'],
                            'num_current' => $v['num'],
                            'num_total' => $v['num'],
                            'start_time' => time(),
                            'expire' => 3600 * 24 * intval($bouns['expire']),
                            'use_channel' => $bouns['use_channel'],
                            'use_type' => intval($bouns['use_limit']),
                            'remark' => trim($v['title_small']) ? trim($v['title_small']) : '抽奖红包',
                        ));
                    }
                    for ($i = 0; $i < $v['num']; $i++) {
                        $this->m_bouns->send_to_user($bouns_id, $user_id);
                    }
                    break;
                case 4://加息券
                    break;
                case 5://充值卡
                    //自动充值，写入异步充值队列
                    $r = $this->m_phone_recharge->join_recharge_queue($oid);
                    $auto_shipping = false;
                    $auto_finish = false;
                    /*
                     * 充值并非即时返回充值结果，需要异步处理
                     * 定时器查询是否成功，成功则完成订单，不成功则退回，并关闭订单
                     */
                    break;
                case 6://积分
                    $credit = json_decode($v['option'], true);
                    for ($i = 0; $i < $v['num']; $i++) {
                        $c_r2 = $this->m_credit->increase($user_id, $credit['num'], array(
                            'type' => 'win_prize',
                            'item_id' => $oid,
                            'remark' => '抽奖奖励:' . $credit['num'] . '积分',
                        ));
                    }
                    break;
            }
        }
        //可以自动发货
        if ($auto_shipping) {
            $this->m_order->shipping($oid, array(
                'shipping_company' => 'AUTO_SHIPPING',
                'shipping_sn' => 'NO_SN',
            ));
        }
        //可自动完成
        if ($auto_finish) {
            $this->m_order->finish($oid);
        }
        $r = $this->m_order->detail($oid);
        $this->api->output($r);
    }

    public function get() {
        $detail = $this->m_order->detail($this->api->in['oid']);
        if (!$detail) {
            $this->api->output(false, ERR_ORDER_NOT_EXISTS_NO, ERR_ORDERNOT_EXISTS_MSG);
        }
        if ($detail->user->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $this->api->output($detail);
    }

    public function get_admin() {
        $detail = $this->m_order->detail($this->api->in['oid']);
        if (!$detail) {
            $this->api->output(false, ERR_ORDER_NOT_EXISTS_NO, ERR_ORDERNOT_EXISTS_MSG);
        }
        $this->api->output($detail);
    }

    public function get_sn() {
        $detail = $this->m_order->detail_by_sn($this->api->in['order_sn']);
        if (!$detail) {
            $this->api->output(false, ERR_ORDER_NOT_EXISTS_NO, ERR_ORDERNOT_EXISTS_MSG);
        }
        if ($detail->user->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $this->api->output($detail);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'oid desc';
        } else {
            $order = $this->api->in['order'];
        }
        if (!$this->api->in['status']) {
            $condition['status'] = array(
                'PROCESS',
                'SHIPPING',
                'FINISH',
                'CLOSE'
            );
        }
        $condition['type'] = 'mall';
        $r['rows'] = $this->m_order->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_order->count($condition);
        $this->api->output($r);
    }

    public function lists_admin() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'oid desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_order->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_order->count($condition);
        $this->api->output($r);
    }

    public function shipping() {
        //TODO权限
        $r = $this->m_order->shipping($this->api->in['oid'], $this->api->in);
        $r_order = $this->m_order->detail($this->api->in['oid']);
        //积分商城发货发送站内信
        $this->load->model('m_message');
        $send_message = $this->m_message->send_admin(array(
            'receiver_id' => $r_order->user->user_id,
            'title' => '商品发货通知',
            'text' => '您的商品已于 '.date('Y-m-d H:i:s',time()).' 发货，订单号: '.$r_order->order_sn,
        ));
        $this->api->output($r);
    }

    public function finish() {
        //TODO权限
        $r = $this->m_order->finish($this->api->in['oid']);
        $this->api->output($r);
    }

}
