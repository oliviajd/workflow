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
class winning extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_winning');
    }
    
    public function add() {
        $wid = $this->m_winning->add($this->api->in);
        $r = $this->m_winning->detail($wid);
        $this->api->output($r);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'wid desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_winning->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_winning->count($condition);
        $this->api->output($r);
    }
    
    public function lists_admin() {
        $this->load->model('m_user');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($this->api->in['user']) {
            $user_ids = $this->m_user->find($this->api->in['user']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'wid desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_winning->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_winning->count($condition);
        $this->api->output($r);
    }



}
