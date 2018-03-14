<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of order_process
 *
 * @author win7
 */
class order_process {

    private $queue = array();
    private $CI;

    public function __construct() {
        $this->CI = get_instance();
    }

    /*
     * data = array(
     *      'type'=>''
     *      'item'=>array
     * )
     */

    public function push($data) {
        $this->queue[] = $data;
    }

    public function clean() {
        if (empty($this->queue)) {
            return true;
        }
        foreach ($this->queue as $k => $v) {
            $type = strtolower(trim($v['type']));
            $item = $v['item'];
            switch ($type) {
                case 'goods_store':
                    //加回预扣的库存
                    $this->CI->m_goods->increase($item['iid'], $item['num']);
                    break;
                case 'goods_user_buy':
                    //减去预加的用户购买量
                    $this->CI->m_order->decrease_goods_user_buy($item['user_id'], $item['iid'], $item['num']);
                    break;
                case 'phone_month_limit':
                    //减去预加的用户每月充值限额
                    $this->CI->m_phone_recharge->user_month_limit_decrease($item['user_id'], intval($item['face_price']) * $item['num']);
                    break;
            }
        }
        return true;
    }

}
