<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of role
 *
 * @author win7
 */
class role extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_role');
        $this->load->model('m_permission');
    }

    public function add() {
        if ($this->api->in['parent_id'] < 1) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        //检查是否拥有添加的权限，parent_id为当前角色的role_id 或在它之下
        $roles = $this->m_role->lists_user(array('user_id' => $this->api->user()->user_id));
        $has_permission = false;
        foreach ($roles as $k => $v) {
            if ($v->role->role_id == $this->api->in['parent_id'] || $this->m_role->is_parent($v->role->role_id, $this->api->in['parent_id'])) {
                $has_permission = true;
                break;
            }
        }
        if (!$has_permission) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $role_id = $this->m_role->add(array('title' => $this->api->in['title'], 'parent_id' => $this->api->in['parent_id'], 'desc' => $this->api->in['desc'], 'permission' => json_decode($this->api->in['permission'], true)));
        $r = $this->m_role->detail($role_id);
        $this->api->output($r);
    }

    public function update() {
        $id = $this->api->in['role_id'];
        if (intval($id) == 1) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if (!$this->m_role->detail($id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //检查是否拥有添加的权限，parent_id为当前角色的role_id 或在它之下
        $roles = $this->m_role->lists_user(array('user_id' => $this->api->user()->user_id));
        $has_permission = false;
        foreach ($roles as $k => $v) {
            if ($v->role->role_id == $id || $this->m_role->is_parent($v->role->role_id, $id)) {
                $has_permission = true;
                break;
            }
        }
        if (!$has_permission) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $this->m_role->update($id, array('title' => $this->api->in['title'], 'permission' => json_decode($this->api->in['permission'], true)));
        $r = $this->m_role->detail($id);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_role->detail($this->api->in['role_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function delete() {
        //检查是否拥有删除的权限，role_id在当前角色的role_id之下
        $roles = $this->m_role->lists_user(array('user_id' => $this->api->user()->user_id));
        $has_permission = false;
        foreach ($roles as $k => $v) {
            if ($this->m_role->is_parent($v->role->role_id, $this->api->in['role_id'])) {
                $has_permission = true;
                break;
            }
        }
        if (!$has_permission) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if (intval($this->api->in['role_id']) == 1) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $count = $this->m_role->count_user(array('role_id' => $this->api->in['role_id']));
        if ($count > 0) {
            $this->api->output(false, ERR_ROLE_MEMBER_NOT_EMPTY_NO, "[{$count}]" . ERR_ROLE_MEMBER_NOT_EMPTY_MSG);
        }
        $r = $this->m_role->detail($this->api->in['role_id']);
        if ($r) {
            $r2 = $this->m_role->delete($this->api->in['role_id']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'parent_id asc,id asc';
        } else {
            $order = $this->api->in['order'];
        }
        $user_id = $this->api->user()->user_id;
        $roles = $this->m_role->lists_user(array('user_id' => $user_id));
        $role_ids = array();
        foreach ($roles as $k => $v) {
            $role_ids = array_merge($role_ids, $this->m_role->lists_children(intval($v->role->role_id)), array($v->role->role_id));
        }
        $condition['role_id'] = $role_ids;
        $r['rows'] = $this->m_role->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_role->count($condition);
        foreach ($r['rows'] as $k => $v) {
            $r['rows'][$k]->nums = $this->m_role->count_user(array('role_id' => $v->role_id));
        }
        $this->api->output($r);
    }

    public function lists_user() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        if ($this->api->in['q']) {
            $this->load->model('m_user');
            $user_ids = $this->m_user->find($this->api->in['q']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        $user_id = $this->api->user()->user_id;
        $roles = $this->m_role->lists_user(array('user_id' => $user_id));
        $role_ids = array();
        foreach ($roles as $k => $v) {
            $role_ids = array_merge($role_ids, $this->m_role->lists_children(intval($v->role->role_id)), array($v->role->role_id));
        }
        if (in_array($this->api->in['role_id'], $role_ids)) {
            
        } else {
            $condition['role_id'] = $role_ids;
        }
        $r['rows'] = $this->m_role->lists_user($condition, $page, $size, $order);
        $r['total'] = $this->m_role->count_user($condition);
        $this->api->output($r);
    }

    public function delete_user() {
        //检查是否拥有删除的权限，role_id在当前角色的role_id之下
        $roles = $this->m_role->lists_user(array('user_id' => $this->api->user()->user_id));
        $has_permission = false;
        foreach ($roles as $k => $v) {
            if ($this->m_role->is_parent($v->role->role_id, $this->api->in['role_id'])) {
                $has_permission = true;
                break;
            }
        }
        if (!$has_permission) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $r = $this->m_role->delete_user($this->api->in);
        $this->api->output($r);
    }

    public function add_user() {
        $this->load->model('m_user');
        $role_id = current(explode(',', $this->api->in['role_ids']));
        $user_id = $this->api->in['user_id'];
        $user = $this->m_user->detail($user_id);
        if (empty($user)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $role = $this->m_role->detail($role_id);
        if (empty($role)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($this->m_role->is_exists_user(array('user_id' => $user_id, 'role_id' => $role_id))) {
            $this->api->output(false, ERR_ROLE_USER_REPEAT_NO, ERR_ROLE_USER_REPEAT_MSG . "[user_id={$user_id},role_id={$role_id}]");
        }
        $this->m_role->add_user(array(
            'role_id' => $role_id,
            'user_id' => $user_id
        ));
        $this->api->output(true);
    }

    public function lists_module() {
        $modules = $this->m_permission->lists_user_modules(array('role_id' => $this->api->in['role_id']));
        foreach ($modules as $k => $v) {
            $modules[$k] = $this->m_permission->detail_module($v->module_id);
        }
        $r['rows'] = $modules;
        $this->api->output($r);
    }

    public function lists_method() {
        $methods = $this->m_permission->lists_user_method(array('role_id' => $this->api->in['role_id']), false, false, 'method_id desc');
        foreach ($methods as $k2 => $v2) {
            $methods[$k2] = $this->m_permission->detail_method($v2->method_id);
        }
        $r['rows'] = $methods;
        $this->api->output($r);
    }

    public function lists_user_module() {
        $user_id = $this->api->user()->user_id;
        $roles = $this->m_role->lists_user(array('user_id' => $user_id));
        $role_ids = array();
        foreach ($roles as $k => $v) {
            $role_ids[] = intval($v->role->role_id);
        }
        $r['rows'] = $this->m_permission->lists_user_modules(array('role_id' => $role_ids));
        $this->api->output($r);
    }

    public function lists_user_method() {
        $user_id = $this->api->user()->user_id;
        $roles = $this->m_role->lists_user(array('user_id' => $user_id));
        $role_ids = array();
        foreach ($roles as $k => $v) {
            $role_ids[] = intval($v->role->role_id);
        }
        $r['rows'] = $this->m_permission->lists_user_method(array('role_id' => $role_ids));
        $this->api->output($r);
    }

}
