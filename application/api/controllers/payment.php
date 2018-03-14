<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of payment
 *
 * @author win7
 */
class payment extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_payment');
    }

    public function bank_lists() {
        $condition = array();
        $condition['status'] = m_payment::STATUS_BANK_ENABLE;
        $condition['payment'] = strtolower($this->api->in['payment']);
        $r['rows'] = $this->m_payment->bank_lists($condition, false, false, 'sort asc');
        $r['total'] = $this->m_payment->bank_count($condition);
        $this->api->output($r);
    }

}
