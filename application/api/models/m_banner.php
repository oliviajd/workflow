<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_banner
 *
 * @author lsk
 */
class m_banner extends CI_Model implements ObjInterface{
    
    public function add($data) {
        $param['title'] = trim($data['title']);
        $param['pic'] = trim(parse_url($data['pic'], PHP_URL_PATH));
        $param['mobile_pic'] = trim(parse_url($data['mobile_pic'], PHP_URL_PATH));
        $param['url'] = trim($data['url']);
        $param['mobile_url'] = trim($data['mobile_url']);
        $param['share_title'] = trim($data['share_title']);
        $param['share_content'] = trim($data['share_content']);
        $param['share_url'] = trim($data['share_url']);
        $param['order'] = intval($data['order']);
        $param['starttime'] = intval($data['starttime']);
        $param['endtime'] = intval($data['endtime']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_BANNER, $param);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'pic':
                    $param['pic'] = trim(parse_url($data['pic'], PHP_URL_PATH));
                    break;
                case 'mobile_pic':
                    $param['mobile_pic'] = trim(parse_url($data['mobile_pic'], PHP_URL_PATH));
                    break;
                case 'url':
                    $param['url'] = trim(parse_url($data['url'], PHP_URL_PATH));
                    break;
                case 'mobile_url':
                    $param['mobile_url'] = trim(parse_url($data['mobile_url'], PHP_URL_PATH));
                    break;
                case 'share_title':
                    $param['share_title'] = trim($data['share_title']);
                    break;
                case 'share_content':
                    $param['share_content'] = trim($data['share_content']);
                    break;
                case 'share_url':
                    $param['share_url'] = trim($data['share_url']);
                    break;
                case 'status':
                    $param['status'] = trim($data['status']);
                    break;
                case 'starttime':
                    $param['starttime'] = trim($data['starttime']);
                    break;
                case 'endtime':
                    $param['endtime'] = trim($data['endtime']);
                    break;
                case 'is_delete':
                    $param['is_delete'] = trim($data['is_delete']);
                    break;
                case 'order':
                    $param['order'] = trim($data['order']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_BANNER, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }
    
    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_banner_status($detail['status']);
            return new obj_banner($detail);
        }
    }
    
    public function lists($condition='', $page='', $size='', $order='') {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BANNER)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_banner_status($v['status']);
            $rows[$k] = new obj_banner($v);
        }
        return $rows;
    }
    
    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BANNER)->row(0)->count;
    }
    
    public function delete($id) {
        $this->db->update(TABLE_BANNER, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        do_log($this->db->last_query());
        return $this->db->affected_rows() > 0;
    }
    
    private function _condition($condition) {
        if ($condition['borrow_id']) {
            is_array($condition['borrow_id']) ? $this->db->where_in('borrow_nid', $condition['borrow_id']) : $this->db->where('borrow_nid', $condition['borrow_id']);
        }
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['status']) {
            $this->db->where('status', $condition['status']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_BANNER, array('id' => $id, 'is_delete' => STATUS_NOT_DELETE))->row_array(0);
        return empty($detail) ? false : $detail;
    }
    
    public function get_banner_status($key = false) {
        $data = array(
            1 => '显示',
            2 => '隐藏',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'BANNER_ERROR', 'text' => '横幅状态错误');
    }
}
