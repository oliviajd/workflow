<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of account
 *
 * @author win7
 */
class account extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_account');
    }

    public function add() {
        $iid = $this->m_account->add($this->api->in);
        $r = $this->m_account->detail($iid);
        $this->api->output($r);
    }

    public function update() {
        $id = $this->api->in['account_id'];
        if (!$this->m_account->detail($id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_account->update($id, $this->api->in);
        $r = $this->m_account->detail($id);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_account->detail($this->api->user()->user_id);
        if ($r) {
            $r->income_obj = $this->m_account->get_income($this->api->user()->user_id);
            $r->wait_obj = $this->m_account->get_wait($this->api->user()->user_id);
            //do_log($this->db->last_query());
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function delete() {
        $r = $this->m_account->detail($this->api->in['iid']);
        if ($r) {
            $r2 = $this->m_account->delete($this->api->in['iid']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_account->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_account->count($condition);
        $this->api->output($r);
    }
    
    public function wish_sum() {
        $r = $this->m_account->wish_sum();
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
            $r = $this->m_account->wish_add($param);
            $this->api->output($r);
        }
    }
    
    public function wish_list() {
        $param['limit'] = trim($this->api->in['limit']);
        $r = $this->m_account->wish_list($param);
        $this->api->output($r);
    }
    
    public function get_income() {
        $user_id = $this->api->user()->user_id;
        $r = $this->m_account->get_income($user_id);
        $this->api->output($r);
    }
    
}
