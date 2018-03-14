<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_wechat_mp
 *
 * @author win7
 */
class m_wechat_mp extends CI_Model implements ObjInterface {

    const STATUS_SEND_SUCCESS = 1;
    const STATUS_SEND_FAILED = 2;
    const STATUS_SEND_ING = 3;
    const STATUS_SEND_INIT = 5;

    public function add($data) {
        $param = array();
        $param['wx_unionid'] = trim($data['wx_unionid']);
        $param['wx_openid_mp'] = trim($data['wx_openid_mp']);

        $param['create_time'] = time();
        $this->db->insert(TABLE_WECHAT_MP, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }
    
    private function _detail($condition) {
        $this->_condition($condition);
        $detail = $this->db->get(TABLE_WECHAT_MP)->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function detail($condition) {
        $detail = $this->_detail($condition);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_wechat_mp($detail);
        }
    }
    
    private function _condition($condition) {
        if ($condition['wx_openid_mp']) {
            $this->db->where('wx_openid_mp', $condition['wx_openid_mp']);
        }
        if ($condition['wx_unionid']) {
            $this->db->where('wx_unionid', $condition['wx_unionid']);
        }
    }

    public function lists($condition, $page, $size, $order) {
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    
}
