<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of prize
 *
 * @author win7
 */
class prize extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_prize');

    }

    public function add() {
        if (intval($this->api->in['iid']) == 0) {
            $this->api->output(false, ERR_PRIZE_IID_IS_NULL_NO, ERR_PRIZE_IID_IS_NULL_MSG);
        }
        $pid = $this->m_prize->add($this->api->in);
        $r = $this->m_prize->detail($pid);
        $this->api->output($r);
    }

    public function lists(){
        $condition = $this->api->in;
        $r['rows'] = $this->m_prize->lists($condition);
        $r['total'] = $this->m_prize->count($condition);
        $this->api->output($r);
    }

    public function update() {
        //todo 如果传入了pid，判断pid是否有效
        if (intval($this->api->in['iid']) == 0) {
            $this->api->output(false, ERR_PRIZE_IID_IS_NULL_NO, ERR_PRIZE_IID_IS_NULL_MSG);
        }
        $pid = $this->api->in['pid'];
        if (!$this->m_prize->detail($pid)) {
            $this->api->output(false, ERR_PRIZE_NOT_EXISTS_NO, ERR_PRIZE_NOT_EXISTS_MSG);
        }
        $this->m_prize->update($pid, $this->api->in);
        $r = $this->m_prize->detail($pid);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_prize->detail($this->api->in['pid']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function delete(){
        $r = $this->m_prize->detail($this->api->in['pid']);
        if ($r) {
            $r2 = $this->m_prize->delete($this->api->in['pid']);
            $this->api->output($r2);
        }
        else{
            $this->api->output(false, ERR_PRIZE_NOT_EXISTS_NO, ERR_prize_NOT_EXISTS_MSG);
        }
    }

    public function start(){
        $this->load->model('m_activity');
        $this->load->model('m_prize_chance');
        if (!$this->m_activity->detail($this->api->in['activity_id'])) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $r_activity = $this->m_activity->detail($this->api->in['activity_id']);

        if ($r_activity->status->id != 1) {
            $this->api->output(false, ERR_ACTIVITY_NOT_START_NO, ERR_ACTIVITY_NOT_START_MSG);
        }

        if ($r_activity->limit_on_time > 0 && $r_activity->limit_on_time > time()) {
            $this->api->output(false, ERR_ACTIVITY_NOT_START_NO, ERR_ACTIVITY_NOT_START_MSG);
        }

        if ($r_activity->limit_off_time>0 && $r_activity->limit_off_time < time()) {
            $this->api->output(false, ERR_ACTIVITY_END_NO, ERR_ACTIVITY_END_MSG);
        }

        //判断是消耗积分还是次数，用于中奖纪录
        /*$r_chance = $this->m_prize_chance->detail($this->api->user()->user_id,$this->api->in['activity_id']);
        if ($r_chance->result->prize_chance->chance <= 0) {
            $chance_type = 'credit';
        }
        else{
            $chance_type = 'prize_chance';
        }*/

        //减抽奖机会
        $r_decrease = $this->chance_decrease($this->api->user()->user_id,$this->api->in['activity_id'], 1,100);
        if(!$r_decrease){
            $this->api->output(false, ERR_PRIZE_CHANCE_NOT_ENOUGH_NO, ERR_PRIZE_CHANCE_NOT_ENOUGH_MSG);
        }
        
        //加抽奖次数
        $r_increase = $this->num_increase($this->api->user()->user_id,$this->api->in['activity_id'], 1);
        
        $r_prize_chance = $this->m_prize_chance->detail($this->api->user()->user_id,$this->api->in['activity_id']);
        

        $r = $this->m_prize->lists(array('activity_id'=>$r_activity->activity_id));
        if(!$r){
            $this->api->output(false, ERR_PRIZE_NOT_EXISTS_NO, ERR_PRIZE_NOT_EXISTS_MSG);
        }
        $rate_arr = '';
        foreach ($r as $k => $v) {
            $rate_arr[$k] = $v->rate;
        }
        if($r_prize_chance->num >= 0 && $r_prize_chance->num < 10){
            $array = array(3785,2500,1200,1000,800,300,200,200,10,5);
        }
        elseif($r_prize_chance->num >= 10 && $r_prize_chance->num < 50){
            $array = array(3785,2500,1200,1000,800,300,200,200,10,5);
        }
        else{
            $array = array(3785,2500,1200,1000,800,300,200,200,10,5);
        }
        unset($rate_arr);
        $rate_arr = $array;
        
        foreach ($array as $k => $v) {
            $r[$k]->rate = $v;
        }
        

        $max_key = array_search(max($rate_arr), $rate_arr);
        
        foreach ($r as $k => $v) {
            if($v->store == 0){
                $r[$max_key]->rate += $v->rate;
                $v->rate = 0;
                $prize_arr[$max_key] = array('pid' => $r[$max_key]->pid,'prize' => $r[$max_key]->title,'v' => floatval($r[$max_key]->rate));
            }
            $prize_arr[] = array('pid' => $v->pid,'prize' => $v->title,'v' => floatval($v->rate));
        }
        foreach ($prize_arr as $key => $val) {
            $arr[$val['pid']] = $val['v'];
        }
        $pid = $this->get_rand($arr);
        $r = $this->m_prize->detail($pid);

        //减奖品数量
        if (!$this->m_prize->decrease($r->pid, 1)) {
            $this->api->output(false, ERR_PRIZE_NOT_ENOUGH_NO, ERR_PRIZE_NOT_ENOUGH_MSG);
        }

        //添加中奖纪录
        $this->load->model('m_winning');

        $win['pid'] = $r->pid;
        $win['iid'] = $r->iid;
        $win['pname'] = $r->title;
        $win['order_sn'] = '';
        $win['shipping_status'] = 2;
        $win['user_id'] = $this->api->user()->user_id;
        $win['activity_id'] = $r_activity->activity_id;
        $win['decrease_type'] = $r_decrease->decrease_type ? $r_decrease->decrease_type : 'prize_chance';

        $wid = $this->m_winning->add($win);


        //下订单
        $this->load->library('admin_runner');
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/order/add/prize';

        $result = curl_upload($url, array(
            'address_id' => 0,
            'user_id' => $this->api->user()->user_id,
            'wid' => $wid,
            'order_goods' => json_encode(array(array('iid' => $r->iid, 'num' => 1))),
            'token' => $this->admin_runner->get_token()
        ));
        $obj = json_decode($result);

        if ($obj->error_no == 200) {
            //下单成功
            $win['order_sn'] = $obj->result->order_sn;
            $win['shipping_status'] = 1;
            //update
            $this->m_winning->update($wid, $win);
        } else {
            //没操作
        }

        $this->api->output($win);
    }
    
    public function send() {
        $this->load->model('m_winning');
        $wid = $this->api->in['wid'];
        $detail = $this->m_winning->detail($wid);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->shipping_status->id != 2) {
            $this->api->output(false, ERR_PRIZE_HAS_SENT_NO, ERR_PRIZE_HAS_SENT_MSG); //已下单
        }
        //下订单
        $this->load->library('admin_runner');
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/order/add/prize';

        $result = curl_upload($url, array(
            'address_id' => 0,
            'user_id' => $detail->user->user_id,
            'wid' => $wid,
            'order_goods' => json_encode(array(array('iid' => $detail->iid, 'num' => 1))),
            'token' => $this->admin_runner->get_token()
        ));
        $obj = json_decode($result);

        if ($obj->error_no == 200) {
            //下单成功
            $win['order_sn'] = $obj->result->order_sn;
            $win['shipping_status'] = 1;
            //update
            $this->m_winning->update($wid, $win);
            $this->api->output(array('order_sn' => $win['order_sn']));
        } else {
            //没操作
            $this->api->output($obj, ERR_PRIZE_FAILED_TO_SEND_NO, ERR_PRIZE_FAILED_TO_SEND_MSG);
        }
    }

    public function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);

        return $result;
    }

    public function chance_get() {
        $this->load->model('m_prize_chance');
        $this->load->model('m_credit');
        $user_id = $this->api->user()->user_id;
        $credit = $this->m_credit->detail($user_id);
        if (!$credit) {
            $this->m_credit->_create_info($user_id);
            $r['credit'] = $this->m_credit->detail($user_id);
        } else {
            $r['credit'] = $credit;
        }
        $prize_chance = $this->m_prize_chance->detail($user_id,$this->api->in['activity_id']);
        if (!$prize_chance) {
            $this->m_prize_chance->_create_info($user_id,$this->api->in['activity_id']);
            $r['prize_chance'] = $this->m_prize_chance->detail($user_id,$this->api->in['activity_id']);
        } else {
            $r['prize_chance'] = $prize_chance;
        }
        $r['flag'] = $r['prize_chance']->flag;
        $this->api->output($r);
    }

    public function chance_increase() {
        $user_id = $this->api->in['user_id'];
        $activity_id = $this->api->in['activity_id'];
        $chance = $this->api->in['chance'];

        $this->load->model('m_prize_chance');

        if (!$this->m_prize_chance->detail($user_id,$activity_id)) {
            $date['user_id'] = $user_id;
            $date['chance'] = $chance;
            $date['activity_id'] = $activity_id;
            $this->m_prize_chance->add($date);
        }
        else{
            $this->m_prize_chance->increase($user_id,$activity_id,$chance);
        }

        $r = $this->m_prize_chance->detail($user_id,$activity_id);
        $this->api->output($r);
    }

    public function chance_update($user_id,$activity_id,$chance) {
        $this->load->model('m_prize_chance');
        if (!$this->m_prize_chance->is_user_id_exists($user_id,$activity_id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO,ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($chance < 0) {
            $this->api->output(false, ERR_PRIZE_CHANCE_NOT_ENOUGH_NO, ERR_PRIZE_CHANCE_NOT_ENOUGH_MSG);
        }
        $date['chance'] = $chance;
        $this->m_prize_chance->update($user_id,$activity_id,$date);
        $r = $this->m_prize_chance->detail($user_id,$activity_id);
        $this->api->output($r);
    }

    public function chance_credit_decrease($user_id,$activity_id,$chance_decrease,$credit_decrease){
        $this->load->model('m_prize_chance');

        $p_r = $this->m_prize_chance->decrease($user_id,$activity_id, $chance_decrease);

        if ($p_r) {
            $r = $this->m_prize_chance->detail($user_id,$activity_id);
            $r->decrease_type = 'prize_chance';
            return $r;
        }
        else{
            $this->load->model('m_credit');
            $credit = $this->m_credit->detail($user_id);
            $flag['flag'] = 2;
            $this->m_prize_chance->update($user_id,$activity_id,$flag);
            if ($credit->current < $credit_decrease) {
                $this->api->output(false, ERR_PRIZE_CREDIT_NOT_ENOUGH_NO,ERR_PRIZE_CREDIT_NOT_ENOUGH_MSG);
            }
            else{
                $c_r = $this->m_credit->decrease($user_id, $credit_decrease, array(
                    'type' => 'prize',
                    'item_id' => $user_id,
                    'remark' => '抽奖兑换',
                ));

                if ($c_r) {

                    $r = $this->m_credit->detail($user_id);
                    $r->decrease_type = 'credit';
                    return $r;
                }
            }
        }

    }
    
    public function chance_decrease($user_id,$activity_id,$chance_decrease,$credit_decrease){
        $this->load->model('m_prize_chance');

        $p_r = $this->m_prize_chance->decrease($user_id,$activity_id, $chance_decrease);

        if ($p_r) {
            $r = $this->m_prize_chance->detail($user_id,$activity_id);
            $r->decrease_type = 'prize_chance';
            return $r;
        }
        else{
            return false;
        }

    }
    
    public function num_increase($user_id,$activity_id) {

        $this->load->model('m_prize_chance');
        $num = 1;
        $this->m_prize_chance->num_increase($user_id,$activity_id,$num);

        $r = $this->m_prize_chance->detail($user_id,$activity_id);
        return $r;
    }

}
