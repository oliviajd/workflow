<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of wechat
 *
 * @author win7
 */
class m_wechat extends CI_Model implements ObjInterface {
    
    const STATUS_SEND_SUCCESS = 1;
    const STATUS_SEND_FAILED = 2;
    const STATUS_SEND_ING = 3;
    const STATUS_SEND_INIT = 5;
    
    public function lists($condition, $page, $size, $order) {
        
    }
    
    public function count($condition) {
        
    }
    
    public function delete($id) {
        
    }
    
    /**
     * 微信信息详情
     */
    public function detail($condition) {
        if ($condition['wx_unionid']) {
            $this->db->where('wx_unionid', $condition['wx_unionid']);
        }
        if ($condition['username']) {
            $this->db->where('username', $condition['username']);
        }
        if ($condition['user_id']) {
            $this->db->where(TABLE_USER_INFO.'.user_id', $condition['user_id']);
        }
        $this->db->limit(1);
        $this->db->from(TABLE_USER_INFO);
        $this->db->select('yyd_users_info.*,'.TABLE_USER.'.username,'.TABLE_USER.'.mobile,'.TABLE_USER.'.password,');
        $this->db->join(TABLE_USER, TABLE_USER.'.user_id = '.TABLE_USER_INFO.'.user_id');
        $r = $this->db->get()->row_array(0);
        return $r;
    }

    public function add($data) {
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'wx_unionid':
                    $param['wx_unionid'] = trim($data['wx_unionid']);
                    break;
                case 'wx_openid':
                    $param['wx_openid'] = trim($data['wx_openid']);
                    break;
                case 'wx_openid_app':
                    $param['wx_openid_app'] = trim($data['wx_openid_app']);
                    break;
                case 'wx_openid_mp':
                    $param['wx_openid_mp'] = trim($data['wx_openid_mp']);
                    break;
                case 'wx_nickname':
                    $param['wx_nickname'] = trim($data['wx_nickname']);
                    break;
                case 'wx_headimgurl':
                    $param['wx_headimgurl'] = trim($data['wx_headimgurl']);
                    break;
                default:
                    break;
            }
        }
        $this->db->update(TABLE_USER_INFO, $param, array('user_id' => $id));
        
        return $this->db->affected_rows() > 0;
    }
}
