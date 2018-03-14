<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of recover
 *
 * @author win7
 */
class recover extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_recover');
    }
    
    public function get() {
        $r = $this->m_recover->detail($this->api->in['id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_RECOVER_NOT_EXISTS_NO, ERR_RECOVER_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_recover->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_recover->count($condition);
        $r['sum'] = $this->m_recover->sum($condition);
        $r['range_sum'] = $this->m_recover->range_sum($condition);
        $this->api->output($r);
    }

}
