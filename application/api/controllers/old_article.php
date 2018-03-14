<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of old_article
 *
 * @author win7
 */
class old_article extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_old_article');
    }
    
    public function get() {
        $r = $this->m_old_article->detail($this->api->in['article_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'addtime desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_old_article->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_old_article->count($condition);
        $this->api->output($r);
    }
    
    
}
