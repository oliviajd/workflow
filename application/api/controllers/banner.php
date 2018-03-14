<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of banner
 *
 * @author win7
 */
class banner extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_banner');
    }

    public function add() {
        $id = $this->m_banner->add($this->api->in);
        $r = $this->m_banner->detail($id);
        $this->api->output($r);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'order desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_banner->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_banner->count($condition);
        $this->api->output($r);
    }
    
    public function update() {
        $id = $this->api->in['id'];
        if (!$this->m_banner->detail($id)) {
            $this->api->output(false, ERR_BANNER_NOT_EXISTS_NO, ERR_BANNER_NOT_EXISTS_MSG);
        }
        $this->m_banner->update($id, $this->api->in);
        $r = $this->m_banner->detail($id);
        $this->api->output($r);
    }

    public function delete() {
        $r = $this->m_banner->detail($this->api->in['id']);
        if ($r) {
            $r2 = $this->m_banner->delete($this->api->in['id']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_BANNER_NOT_EXISTS_NO, ERR_BANNER_NOT_EXISTS_MSG);
        }
    }
    
    public function get() {
        $r = $this->m_banner->detail($this->api->in['id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_BANNER_NOT_EXISTS_NO, ERR_BANNER_NOT_EXISTS_NO);
        }
    }
}
