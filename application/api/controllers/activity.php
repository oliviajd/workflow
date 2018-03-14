<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of activity
 *
 * @author win7
 */
class activity extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_activity');
    }

    public function add() {
        $iid = $this->m_activity->add($this->api->in);
        $r = $this->m_activity->detail($iid);
        $this->api->output($r);
    }

    public function update() {
        $id = $this->api->in['activity_id'];
        if (!$this->m_activity->detail($id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_activity->update($id, $this->api->in);
        $r = $this->m_activity->detail($id);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_activity->detail($this->api->in['activity_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function delete() {
        $r = $this->m_activity->detail($this->api->in['iid']);
        if ($r) {
            $r2 = $this->m_activity->delete($this->api->in['iid']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
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
        $r['rows'] = $this->m_activity->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_activity->count($condition);
        $this->api->output($r);
    }
    
    public function wish_sum() {
        $r = $this->m_activity->wish_sum();
        $this->api->output($r);
    }
    
    public function wish_add() {
        if(time() < 1495209600){
            $this->api->output(false, ERR_ACTIVITY_NOT_START_NO, '活动于5月20日 00:00开放，敬请期待');
        }
        else{
            $param['user_id'] = trim($this->api->user()->user_id);
            $param['mobile'] = trim($this->api->user()->loginname);
            $param['content'] = trim($this->api->in['content']);
            $r = $this->m_activity->wish_add($param);
            $this->api->output($r);
        }
    }
    
    public function wish_list() {
        $param['limit'] = trim($this->api->in['limit']);
        $r = $this->m_activity->wish_list($param);
        $this->api->output($r);
    }
    
}
