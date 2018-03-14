<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_role
 *
 * @author win7
 */
class m_role extends CI_Model implements ObjInterface {

    public function add($data) {
        $this->load->model('m_permission');
        $param = array();
        $param['title'] = trim($data['title']);
        $param['parent_id'] = intval($data['parent_id']);
        $param['desc'] = trim($data['desc']);
        $param['has_child'] = 2;
        $param['create_time'] = time();
        $this->db->insert(TABLE_ROLE, $param);
        $id = $this->db->insert_id();
        $this->db->update(TABLE_ROLE, array('has_child' => 1), array('id' => $param['parent_id'], 'has_child' => 2));
        $privilege = array();
        foreach ($data['permission']['modules'] as $k => $v) {
            $privilege[] = array(
                'role_id' => $id,
                'module_id' => intval($v),
                'module' => '',
                'method_id' => 0,
                'method' => '',
                'status' => 1,
                'create_time' => time(),
            );
        }
        foreach ($data['permission']['methods'] as $k => $v) {
            $method = $this->m_permission->detail_method(intval($v));
            $privilege[] = array(
                'role_id' => $id,
                'module_id' => $method->module_id,
                'module' => '',
                'method_id' => $method->method_id,
                'method' => '',
                'status' => 1,
                'create_time' => time(),
            );
        }
        if (!empty($privilege)) {
            $this->db->insert_batch(TABLE_ROLE_PRIVILEGE, $privilege);
        }
        return $id;
    }

    public function update($id, $data) {
        $this->load->model('m_permission');
        $param = array();
        $param['title'] = trim($data['title']);
        $param['desc'] = trim($data['desc']);
        $this->db->update(TABLE_ROLE, $param, array('id' => intval($id)));
        $this->db->delete(TABLE_ROLE_PRIVILEGE, array('role_id' => intval($id)));
        $privilege = array();
        foreach ($data['permission']['modules'] as $k => $v) {
            $privilege[] = array(
                'role_id' => $id,
                'module_id' => intval($v),
                'module' => '',
                'method_id' => 0,
                'method' => '',
                'status' => 1,
                'create_time' => time(),
            );
        }
        foreach ($data['permission']['methods'] as $k => $v) {
            $method = $this->m_permission->detail_method(intval($v));
            $privilege[] = array(
                'role_id' => $id,
                'module_id' => $method->module_id,
                'module' => '',
                'method_id' => $method->method_id,
                'method' => '',
                'status' => 1,
                'create_time' => time(),
            );
        }
        if (!empty($privilege)) {
            $this->db->insert_batch(TABLE_ROLE_PRIVILEGE, $privilege);
        }
        return $id;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_role($detail);
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
        $rows = $this->db->get_where(TABLE_ROLE)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_role($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_ROLE)->row(0)->count;
    }

    public function delete($id) {
        $this->db->delete(TABLE_ROLE, array('id' => $id));
        $this->db->delete(TABLE_ROLE_PRIVILEGE, array('role_id' => $id));
        return true;
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_ROLE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    private function _condition($condition) {
        if ($condition['parent_id']) {
            is_array($condition['parent_id']) ? $this->db->where_in('parent_id', $condition['parent_id']) : $this->db->where('parent_id', $condition['parent_id']);
        }
        if ($condition['role_id']) {
            is_array($condition['role_id']) ? $this->db->where_in('id', $condition['role_id']) : $this->db->where('id', $condition['role_id']);
        }
        if ($condition['q']) {
            $this->db->like('title', trim($condition['q']), 'both');
        }
    }

    public function add_user($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['role_id'] = intval($data['role_id']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_USER_ROLE, $param);
        return $this->db->insert_id();
    }

    public function delete_user($condition) {
        $this->db->delete(TABLE_USER_ROLE, array('user_id' => $condition['user_id'], 'role_id' => $condition['role_id']));
        return $this->db->affected_rows() > 0;
    }

    public function is_exists_user($condition) {
        $this->db->limit(1);
        return $this->db->get_where(TABLE_USER_ROLE, array('user_id' => $condition['user_id'], 'role_id' => $condition['role_id']))->row(0)->id > 0;
    }

    public function lists_user($condition, $page = false, $size = false, $order = false) {
        $this->load->model('m_user');
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition_user($condition);
        $r = $this->db->get_where(TABLE_USER_ROLE)->result_array();
        $rows = array();
        foreach ($r as $k => $v) {
            $rows[] = new obj_role_user(array(
                'role' => $this->detail($v['role_id']),
                'user' => $this->m_user->detail($v['user_id']),
                'create_time' => $v['create_time'],
            ));
        }
        return $rows;
    }

    public function count_user($condition) {
        $this->db->select('count(1) as count');
        $this->_condition_user($condition);
        return $this->db->get_where(TABLE_USER_ROLE)->row(0)->count;
    }

    private function _condition_user($condition) {
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['role_id']) {
            is_array($condition['role_id']) ? $this->db->where_in('role_id', $condition['role_id']) : $this->db->where('role_id', $condition['role_id']);
        }
    }

    public function is_parent($parent_id, $children_id) {
        if ($children_id == 0) {
            return false;
        }
        $detail = $this->_detail($children_id);
        if ($detail) {
            if ($detail['parent_id'] == $parent_id) {
                return true;
            } else {
                //往上递归查找
                return $this->is_parent($parent_id, $detail['parent_id']);
            }
        } else {
            return false;
        }
    }

    public function lists_children($id) {
        $r = array();
        $rows = $this->lists(array('parent_id' => $id), false, false);
        foreach ($rows as $k => $v) {
            if (!in_array($v->role_id, $r)) {
                $r[] = $v->role_id;
                if ($v->has_child == 1) {
                    $rows2 = $this->lists_children($v->role_id);
                    foreach ($rows2 as $k2 => $v2) {
                        if (!in_array($v2, $r)) {
                            $r[] = $v2;
                        }
                    }
                }
            }
        }
        return $r;
    }

}
