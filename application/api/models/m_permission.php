<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author win7
 */
class m_permission extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }
    
    public function detail_method($id) {
        $detail = $this->_detail_method($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_permission_method($detail);
        }
    }
    
    private function _detail_method($id) {
        $detail = $this->db->get_where(TABLE_API2_METHOD, array('method_id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }
    
    public function detail_module($id) {
        $detail = $this->_detail_module($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_permission_module($detail);
        }
    }
    
    private function _detail_module($id) {
        $detail = $this->db->get_where(TABLE_API2_CATEGORY, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function lists_user_method($condition, $page = false, $size = false, $order = false) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        if (empty($condition['role_id'])) {
            return false;
        }
        if (in_array(1, $condition['role_id']) || $condition['role_id'] == 1) {
            $rows = $this->lists_method($condition);
            foreach ($rows as $k => $v) {
                $rows[$k] = new obj_role_method(array('method_id' => $v->method_id,'method_name_cn'=>$v->titile));
            }
        } else {
            $this->db->group_by('method_id');
            $this->_condition_user_method($condition);
            $rows = $this->db->get_where(TABLE_ROLE_PRIVILEGE)->result_array();
            foreach ($rows as $k => $v) {
                $rows[$k] = new obj_role_method($v);
            }
        }
        return $rows;
    }

    private function _condition_user_method($condition) {
        if ($condition['module_id']) {
            is_array($condition['module_id']) ? $this->db->where_in('module_id', $condition['module_id']) : $this->db->where('module_id', $condition['module_id']);
        }
        if ($condition['role_id']) {
            is_array($condition['role_id']) ? $this->db->where_in('role_id', $condition['role_id']) : $this->db->where('role_id', $condition['role_id']);
        }
    }
    
    public function lists_user_modules($condition, $page = false, $size = false, $order = false) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        if (empty($condition['role_id'])) {
            return false;
        }
        if (in_array(1, $condition['role_id']) || $condition['role_id'] == 1) {
            $rows = $this->lists_modules($condition);
            foreach ($rows as $k => $v) {
                $rows[$k] = new obj_role_module(array('module_id' => $v->module_id));
            }
        } else {
            $this->db->group_by('module_id');
            $this->_condition_user_modules($condition);
            $rows = $this->db->get_where(TABLE_ROLE_PRIVILEGE)->result_array();
            foreach ($rows as $k => $v) {
                $rows[$k] = new obj_role_module($v);
            }
        }
        return $rows;
    }

    private function _condition_user_modules($condition) {
        if ($condition['role_id']) {
            is_array($condition['role_id']) ? $this->db->where_in('role_id', $condition['role_id']) : $this->db->where('role_id', $condition['role_id']);
        }
    }

    public function lists_modules($condition, $page = false, $size = false, $order = false) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition_modules($condition);
        $rows = $this->db->get_where(TABLE_API2_CATEGORY)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_permission_module($v);
        }
        return $rows;
    }

    private function _condition_modules($condition) {
        $this->db->where('cate_type', 1);
        $this->db->where('is_menu', 1);
    }
    
    public function lists_method($condition, $page = false, $size = false, $order = false) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition_method($condition);
        $rows = $this->db->get_where(TABLE_API2_METHOD)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_permission_method($v);
        }
        return $rows;
    }

    private function _condition_method($condition) {
        if ($condition['module_id']) {
            is_array($condition['module_id']) ? $this->db->where_in('cid', $condition['module_id']) : $this->db->where('cid', $condition['module_id']);
        }
        $this->db->where('check_permission', 1);
    }

}
