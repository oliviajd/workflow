<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of business_city
 *
 * @author win7
 */
class business_city extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_business_city');
        $this->load->model('m_business_city_log');
    }
    
    public function log_get() {
        $r = $this->m_business_city_log->detail($this->api->in['log_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function log_add() {
        // 判断城市是否存在
        $city_id = $this->api->in['city_id'];
        $detail = $this->m_business_city->detail($city_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        // 检查date格式
        if (!strtotime($this->api->in['date'])) {
            $this->api->output(false, ERR_WRONG_FORMAT_NO, "[date]" . ERR_WRONG_FORMAT_MSG);
        }
        //检查city_id + date是否重复
        if ($this->m_business_city_log->count(array(
                    'city_id' => $city_id,
                    'date' => $this->api->in['date']
                )) > 0) {
            $this->api->output(false, ERR_ITEM_REPEAT_NO, "[city_id=>{$city_id},date=>{$this->api->in['date']}]" . ERR_ITEM_REPEAT_MSG);
        }
        $iid = $this->m_business_city_log->add($this->api->in);
        $r = $this->m_business_city_log->detail($iid);
        $this->api->output($r);
    }

    public function log_update() {
        $id = $this->api->in['log_id'];
        if (!$this->m_business_city_log->detail($id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_business_city_log->update($id, $this->api->in);
        $r = $this->m_business_city_log->detail($id);
        $this->api->output($r);
    }
    
    public function log_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['status'] = m_business_city_log::STATUS_LOG_ENABLE;
        if (!$this->api->in['order']) {
            $order = 'ymd desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_business_city_log->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_business_city_log->count($condition);
        $this->api->output($r);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 100;
        $condition = $this->api->in;
        $condition['status'] = m_business_city::STATUS_CITY_ENABLE;
        if (!$this->api->in['order']) {
            $order = 'is_rec desc,id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_business_city->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_business_city->count($condition);
        $this->api->output($r);
    }

}
