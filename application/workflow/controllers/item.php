<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of item
 *
 * @author win7
 */
class item extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_item');
        $this->load->model('m_item_instance');
    }
    //这个方法暂时没有用到
    public function start() {
        $process_item_id = $this->api->in['process_item_id'];
        $detail = $this->m_item_instance->detail($process_item_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断是否已开始
        
        $this->m_item_instance->start($process_item_id);
        $this->api->output(true);
    }
    
    public function lists() {
        
    }
    
}
