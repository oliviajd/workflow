<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_message
 *
 * @author win7
 */
class m_message extends CI_Model implements ObjInterface {

    public function add($data) {
        $param['sender_id'] = intval($data['sender_id']);
        $param['receiver_id'] = trim($data['receiver_id']);
        $param['message_text_id'] = intval($data['message_text_id']);
        $param['send_time'] = trim($data['send_time']);
        $param['is_delete'] = STATUS_NOT_DELETE;
        $this->db->insert(TABLE_MESSAGE, $param);
        $id = $this->db->insert_id();
        return $id;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'receiver_id':
                    $param['receiver_id'] = trim($data['receiver_id']);
                    break;
                default:
                    break;
            }
        }
        $detail = $this->_detail($id);
        $param['modify_time'] = time();
        $this->db->update(TABLE_MESSAGE, $param, array('id' => $id,'is_delete' => STATUS_NOT_DELETE));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $text = $this->db->get_where(TABLE_MESSAGE_TEXT,array('id' => $detail['message_text_id']))->row_array();
            $detail['title'] = $text['title'];
            $detail['text'] = $text['text'];
            return new obj_message($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_MESSAGE)->result_array();
        //do_log($this->db->last_query());
        foreach ($rows as $k => $v) {
            $text = $this->db->get_where(TABLE_MESSAGE_TEXT,array('id' => $v['message_text_id']))->row_array();
            $rows[$k]['title'] = $text['title'];
            $rows[$k]['text'] = $text['text'];
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_MESSAGE)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_MESSAGE, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id , 'is_delete' => STATUS_NOT_DELETE));
        $this->db->update(TABLE_MESSAGE_USER, array('is_delete' => STATUS_HAS_DELETE), array('source_message_id' => $id , 'is_delete' => STATUS_NOT_DELETE));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['sender_id']) {
            $this->db->where('sender_id', $condition['sender_id']);
        }
        if ($condition['receiver_id']) {
            $this->db->where('receiver_id', $condition['receiver_id']);
        }
        if ($condition['user_id']) {
            $this->db->where('receiver_id', $condition['receiver_id']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_MESSAGE, array('id' => $id , 'is_delete' => STATUS_NOT_DELETE))->row_array();
        return empty($detail) ? false : $detail;
    }
    
    public function send_admin($data) {
        $this->load->model('m_message_text');
        if(empty($data['receiver_id'])){
            //$this->api->output(false, ERR_FILED_NECESSARY_NO, ERR_FILED_NECESSARY_MSG);
            do_log(ERR_FILED_NECESSARY_MSG);
            return false;
        }
        if(empty($data['title'])){
            //$this->api->output(false, ERR_FILED_NECESSARY_NO, ERR_FILED_NECESSARY_MSG);
            do_log(ERR_FILED_NECESSARY_MSG);
            return false;
        }
        if(empty($data['text'])){
            //$this->api->output(false, ERR_FILED_NECESSARY_NO, ERR_FILED_NECESSARY_MSG);
            do_log(ERR_FILED_NECESSARY_MSG);
            return false;
        }
        $text_param['sender_id'] = 0;
        $text_param['send_time'] = time();
        $text_param['title'] = $data['title'];
        $text_param['text'] = $data['text'];
        //添加站内信内容
        $text_id = $this->m_message_text->add($text_param);
        
        if($text_id){
            //添加站内信发送表
            $param['sender_id'] = 0;
            $param['receiver_id'] = trim($data['receiver_id']);
            $param['message_text_id'] = $text_id; 
            $param['send_time'] = $text_param['send_time'];
        
            $message_id = $this->m_message->add($param);

            //$detail = $this->m_message->detail($message_id);
            $detail = $this->m_message->detail($message_id);
            return true;
        }
        else{
            //$this->api->output(false, ERR_MESSAGE_TEXT_NOT_EXISTS_NO, ERR_MESSAGE_TEXT_NOT_EXISTS_MSG);
            do_log(ERR_MESSAGE_TEXT_NOT_EXISTS_MSG);
            return false;
        }
    }

}
