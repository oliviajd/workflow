<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of credit
 *
 * @author win7
 */
class credit extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_credit');
    }
    
    public function get() {
        $credit = $this->m_credit->detail($this->api->user()->user_id);
        $sign_status = $this->get_sign_status();
        $credit->sign = $sign_status ? $credit->sign : 0;
        $this->api->output($credit);
    }
    
    public function enough() {
        $credit = $this->m_credit->detail($this->api->user()->user_id);
        $this->api->output($credit->current - $this->api->in['num']);
    }
    
    public function log_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $condition['filter_0'] = true;
        $r['rows'] = $this->m_credit->log_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_credit->log_count($condition);
        $this->api->output($r);
    }
    
    public function log_lists_admin() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $condition['filter_0'] = true;
        $r['rows'] = $this->m_credit->log_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_credit->log_count($condition);
        $this->api->output($r);
    }
    
    public function lists() {
        
    }
    
    public function log_increase_admin() {
        
    }
    
    public function log_decrease_admin() {
        
    }
    
    public function get_sign_status() {
        $rows = $this->m_credit->get_sign_status($this->api->user()->user_id);
        return $rows;
    }
}
