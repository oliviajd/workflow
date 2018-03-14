<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of permission
 *
 * @author win7
 */
class permission extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_role');
        $this->load->model('m_permission');
    }

    public function lists() {
        
    }

    //获得当前用户角色对应的可设置的权限
    public function tree() {
        $user_id = $this->api->user()->user_id;
        $roles = $this->m_role->lists_user(array('user_id' => $user_id));
        $role_ids = array();
        foreach ($roles as $k => $v) {
            $role_ids[] = intval($v->role->role_id);
        }
        $modules = $this->m_permission->lists_user_modules(array('role_id' => $role_ids));
        $r = array();
        foreach ($modules as $k => $v) {
            $methods = $this->m_permission->lists_user_method(array('role_id' => $role_ids, 'module_id' => $v->module_id), false, false, 'method_id desc');
            foreach ($methods as $k2 => $v2) {
                if ($v2->method_id == 0) {
                    unset($methods[$k2]);
                } else {
                    $methods[$k2] = $this->m_permission->detail_method($v2->method_id);
                }
            }
            $module = $this->m_permission->detail_module($v->module_id);
            $r['rows'][] = array(
                'module' => $module,
                'children' => $methods,
            );
        }
        $this->api->output($r);
    }

}
