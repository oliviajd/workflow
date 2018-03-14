<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_message_user
 *
 * @author win7
 */
class m_message_user extends CI_Model implements ObjInterface {
    
    const FLAG_HAS_READ = 1;
    const FLAG_NOT_READ = 2;
    
    public function add($data) {
            $param['sender_id'] = intval($data['sender_id']);
            $param['user_id'] = intval($data['user_id']);
            $param['read_flag'] = $data['read_flag'] ? intval($data['read_flag']) : self::FLAG_NOT_READ;
            $param['source_message_id'] = intval($data['source_message_id']);
            $param['message_text_id'] = intval($data['message_text_id']);
            $param['send_time'] = trim($data['send_time']);
            $param['is_delete'] = STATUS_NOT_DELETE;
            $this->db->insert(TABLE_MESSAGE_USER, $param);
            $id = $this->db->insert_id();
            return $id;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'read_flag':
                    $param['read_flag'] = intval($data['read_flag']);
                    break;
                case 'source_message_id':
                    $param['source_message_id'] = intval($data['source_message_id']);
                    break;
                case 'message_text_id':
                    $param['message_text_id'] = intval($data['message_text_id']);
                    break;
                default:
                    break;
            }
        }
        $detail = $this->_detail($id);
        $param['modify_time'] = time();
        $this->db->update(TABLE_MESSAGE_USER, $param, array('id' => $id,'is_delete' => STATUS_NOT_DELETE));
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
            return new obj_message_user($detail);
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
        $rows = $this->db->get_where(TABLE_MESSAGE_USER)->result_array();
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
        return $this->db->get_where(TABLE_MESSAGE_USER)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_MESSAGE_USER, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id,'is_delete' => STATUS_NOT_DELETE));
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
            $this->db->where('user_id', $condition['user_id']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_MESSAGE_USER, array('id' => $id,'is_delete' => STATUS_NOT_DELETE))->row_array();
        return empty($detail) ? false : $detail;
    }
    
    public function sync($user_id) {
        $user_id = intval($user_id);
        $this->db->where('(receiver_id = 1 OR receiver_id = '.$user_id.') AND is_delete = '.STATUS_NOT_DELETE,false,false);
        $detail_1 = $this->db->get(TABLE_MESSAGE)->result_array();
        
        $this->db->like('receiver_id', $user_id,'both');
        $this->db->where('is_delete',STATUS_NOT_DELETE);
        $detail_2 = $this->db->get(TABLE_MESSAGE)->result_array();
        $detail = array_merge($detail_1,$detail_2);
        
        if($detail){
            foreach($detail as $k => $v){
                /*$text = $this->db->get_where(TABLE_MESSAGE_TEXT,array('id' => $v['message_text_id']))->row_array();
                $detail[$k]['title'] = $text['title'];
                $detail[$k]['text'] = $text['text'];*/
                $r_user = $this->db->get_where(TABLE_MESSAGE_USER, array('user_id' => $user_id,'source_message_id' => $v['id'],'is_delete' => STATUS_NOT_DELETE))->row_array();
                if(!$r_user){
                   $param = array(
                        'sender_id' => $v['sender_id'],
                        'user_id' => $user_id,
                        'source_message_id' => $v['id'],
                        'message_text_id' => $v['message_text_id'],
                        'send_time' => $v['send_time']
                    ); 
                   $this->add($param);
                }
            }
        }
        
        return empty($detail) ? false : $detail;
    }
    
    public function read_all($user_id) {
        $this->db->update(TABLE_MESSAGE_USER, array('read_flag' => self::FLAG_HAS_READ,'modify_time' => time()), array('user_id' => $user_id,'read_flag' => self::FLAG_NOT_READ,'is_delete' => STATUS_NOT_DELETE));
        return $this->db->affected_rows() > 0;
    }
    
    public function unread_count($user_id) {
        $user_id = intval($user_id);
        //$r_sync = $this->sync($user_id);
        $this->db->select('count(1) as count');
        $detail = $this->db->get_where(TABLE_MESSAGE_USER, array('read_flag' => self::FLAG_NOT_READ,'user_id' => $user_id))->row()->count;
        return $detail;
    }
}
