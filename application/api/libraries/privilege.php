<?php

class privilege {

    private $_privilege;

    const ROOT_ROLE_ID = 1;

    public function __construct() {
        $this->CI = & get_instance();
        $this->db = $this->CI->db;
        $this->_privilege = array();
    }

    public function check($user_id, $method_id) {
        $roles = $this->db->get_where(TABLE_USER_ROLE, array('user_id' => intval($user_id)))->result_array();
        if (empty($roles)) {
            return false;
        }
        $role_ids = array_column($roles, 'role_id');
        if (in_array(self::ROOT_ROLE_ID, $role_ids)) {//超级管理员具备所有权限
            return true;
        }
        $this->db->where_in('role_id', $role_ids);
        $r = $this->db->get_where(TABLE_ROLE_PRIVILEGE, array('method_id' => intval($method_id), 'status' => 1))->row_array(0);
        return !empty($r) ? true : false;
    }

    public function is_admin($user_id) {
        $roles = $this->db->get_where(TABLE_USER_ROLE, array('user_id' => intval($user_id)))->result_array();
        if (empty($roles)) {
            return false;
        }
        return array_column($roles, 'role_id');
    }

}
