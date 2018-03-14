<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of article
 *
 * @author win7
 */
class article extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_article');
    }
    
    public function get() {
        $r = $this->m_article->detail($this->api->in['article_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
}
