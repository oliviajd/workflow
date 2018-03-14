<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_risk_test
 *
 * @author lsk
 */
class m_risk_test extends CI_Model implements ObjInterface{
    
    public function add($data) {
        $param['user_id'] = trim($data['user_id']);
        $param['score'] = trim($data['score']);
        $param['times'] = 1;
        $param['create_time'] = time();
        $this->db->insert(TABLE_RISK_TEST, $param);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'score':
                    $param['score'] = trim($data['score']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->set('times', 'times+1',FALSE);
        $this->db->update(TABLE_RISK_TEST, $param, array('user_id' => $id));
        return $this->db->affected_rows() > 0;
    }
    
    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_risk_test($detail);
        }
    }
    
    public function detail_by_userid($id) {
        $detail = $this->_detai_by_userid($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_risk_test($detail);
        }
    }
    
    public function lists($condition='', $page='', $size='', $order='') {
    }
    
    public function count($condition) {
    }
    
    public function delete($id) {
    }
    
    private function _condition($condition) {
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_RISK_TEST, array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }
    
    private function _detai_by_userid($id) {
        $detail = $this->db->get_where(TABLE_RISK_TEST, array('user_id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }
}
