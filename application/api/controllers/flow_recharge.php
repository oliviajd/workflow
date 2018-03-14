<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of flow_recharge
 *
 * @author win7
 */
class flow_recharge extends CI_Controller{
    
    const LIMIT_USER_MONTH = 300;
    const STATUS_RECHARGE_INIT = 5;
    const STATUS_RECHARGE_START = 6;
    const STATUS_RECHARGE_SUCCESS = 1;
    const STATUS_RECHARGE_FAILED = 2;
    const STATUS_RECHARGE_PROCESSING = 3;
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_flow_recharge');
    }

    public function add() {
        $id = $this->m_flow_recharge->add($this->api->in);
        //$r = $this->m_flow_recharge->detail($id);
        $data['order_sn'] = create_order_sn($id);
        $this->m_flow_recharge->update($id,$data);
        $this->api->output(true);
    }

    public function update() {
        
    }

    public function get() {
        
    }

    public function delete() {
        
    }

    public function lists() {
        /*$data['user_id']='8786';
        $data['order_sn']=time().'8786';
        $data['mobiles']='15658008231';
        $data['cmcc']='18,19';
        $data['cucc']='10,9';
        $data['ctcc']='20';
        $data['etype']='0';
        $data['version']='2';
                
        $param['user_id'] = intval($data['user_id']);
        $param['oid'] = intval($data['oid']);
        $param['iid'] = intval($data['iid']);
        $param['order_sn'] = trim($data['order_sn']);
        $param['mobiles'] = trim($data['mobiles']);
        $param['cmcc'] = trim($data['cmcc']);
        $param['cucc'] = trim($data['cucc']);
        $param['ctcc'] = trim($data['ctcc']);
        $param['etype'] = intval($data['etype']);
        $param['version'] = intval($data['version']);
        $param['status'] = self::STATUS_RECHARGE_INIT;
        $param['create_time'] = time();
        
        $this->m_flow_recharge->add($param);*/
        
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['cid']) {
            //查询子类ID
        }
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_flow_recharge->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_flow_recharge->count($condition);
        $this->api->output($r);
    }
    
    public function recharge() {
        /*$data['mobiles'] = '15658008231,15655115007';
        $data['ctcc']='1';
        $data['cucc'] = '24';            //移动套餐编号
        $data['cmcc']='11';
        $data['etype'] = 0;            //生效类型，0:立即生效，　1:下月生效
        $data['version'] = 2;          //加密版本必须传2
        $data['order_sn'] = '12478124';*/ //自定义唯一值，必须传唯一
        
        $r = $this->m_flow_recharge->recharge($data);
        $this->api->output($r);
    }
    
    public function callback() {
        $r = $this->m_flow_recharge->callback();
        $this->api->output($r);
    }
    
    public function sendcallback() {
        $r = $this->m_flow_recharge->sendcallback();
        $this->api->output($r);
    }
}
