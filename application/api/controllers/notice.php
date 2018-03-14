<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of notice
 *
 * @author win7
 */
class notice extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_notice');
    }

    public function get() {
        $r = $this->m_notice->detail($this->api->in['id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    public function add() {
        $param = $this->api->in;
        $param['admin_user_id'] = $this->api->user()->user_id;

        $notice_id = $this->m_notice->add($param);
        $detail = $this->m_notice->detail($notice_id);
        $this->api->output($detail);
    }

    public function update() {
        $id = $this->api->in['id'];
        $param = $this->api->in;

        $old = $this->m_notice->detail($id);
        if (empty($old)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
            exit;
        }
        if($param['receiver_id']){
            $this->m_notice->update($id, $param);
        }
        if($param['title'] || $param['text']){
            $this->m_notice_text->update($old->notice_text_id, $param);
        }
        
        
        $detail = $this->m_notice->detail($id);
        $this->api->output($detail);
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
        $r['rows'] = $this->m_notice->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_notice->count($condition);
        $this->api->output($r);
    }

    public function lists_admin() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_notice->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_notice->count($condition);
        $this->api->output($r);
    }
    
    public function lists_user() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        $r_sync = $this->m_notice_user->sync($this->api->user()->user_id);
        if (!$this->api->in['order']) {
            $order = 'send_time desc';
        } else {
            $order = $this->api->in['order'];
        }
        //$condition['status'] = m_notice::STATUS_ON;
        $r['rows'] = $this->m_notice_user->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_notice_user->count($condition);
        $this->api->output($r);
    }

    public function delete() {
        $detail = $this->m_notice->detail($this->api->in['id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $r = $this->m_notice->delete($this->api->in['id']);
        $this->api->output($r);
    }

    public function send() {
    }
    
    public function send_admin() {
        $text_param = $this->api->in;
        $text_param['sender_id'] = 0;
        $text_param['send_time'] = time();
        //添加站内信内容
        $text_id = $this->m_notice_text->add($text_param);
        
        if($text_id){
            //添加站内信发送表
            $param['sender_id'] = 0;
            $param['receiver_id'] = trim($this->api->in['receiver_id']);
            $param['notice_text_id'] = $text_id; 
            $param['send_time'] = $text_param['send_time'];
        
            $notice_id = $this->m_notice->add($param);

            //$detail = $this->m_notice->detail($notice_id);
            $detail = $this->m_notice->detail($notice_id);
            $this->api->output($detail);
        }
        else{
            $this->api->output(false, ERR_MESSAGE_TEXT_NOT_EXISTS_NO, ERR_MESSAGE_TEXT_NOT_EXISTS_MSG);
        }
    }
    
    public function send_lists_admin() {
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
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_notice->user_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_notice->user_count($condition);
        $this->api->output($r);
    }

    public function read_all() {
        $this->m_notice_user->read_all($this->api->user()->user_id);
        $this->api->output(true);
    }
    
    public function unread_count() {
        $r =$this->m_notice_user->unread_count($this->api->user()->user_id);
        $this->api->output($r);
    }

}
