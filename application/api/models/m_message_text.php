<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_message_text
 *
 * @author win7
 */
class m_message_text extends CI_Model implements ObjInterface {

    public function add($data) {
        $param['sender_id'] = intval($data['sender_id']);
        $param['title'] = trim($data['title']);
        $param['text'] = trim($data['text']);
        $param['send_time'] = trim($data['send_time']);
        $param['is_delete'] = STATUS_NOT_DELETE;
        $this->db->insert(TABLE_MESSAGE_TEXT, $param);
        $id = $this->db->insert_id();
        return $id;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'sender_id':
                    $param['sender_id'] = intval($data['sender_id']);
                    break;
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'text':
                    $param['text'] = trim($data['text']);
                    break;
                    break;
                default:
                    break;
            }
        }
        $detail = $this->_detail($id);
        $param['modify_time'] = time();
        $this->db->update(TABLE_MESSAGE_TEXT, $param, array('id' => $id , 'is_delete' => STATUS_NOT_DELETE));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_message_text($detail);
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
        $rows = $this->db->get_where(TABLE_MESSAGE_TEXT)->result_array();
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_MESSAGE_TEXT)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_MESSAGE_TEXT, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id,'is_delete' => STATUS_NOT_DELETE));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['sender_id']) {
            $this->db->where('sender_id', $condition['sender_id']);
        }
        if ($condition['receiver_id']) {
            $this->db->where('receiver_id', $condition['receiver_id']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_MESSAGE_TEXT, array('id' => $id,'is_delete' => STATUS_NOT_DELETE))->row_array();
        return empty($detail) ? false : $detail;
    }

}
