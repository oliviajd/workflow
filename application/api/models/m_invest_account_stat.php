<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_invest_account_stat
 *
 * @author win7
 */
class m_invest_account_stat extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        if ($page !== false && $size !== false) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $this->db->select('owner_user_id,ymd,sum(avg) as avg,sum(avg_add) as avg_add');
        $this->db->group_by('owner_user_id,ymd');
        $rows = $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array();
        return $rows;
    }

    public function count($condition) {
        $this->db->select('id');
        $this->_condition($condition);
        $this->db->group_by('owner_user_id,ymd');
        return count($this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array());
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['date']) {
            is_array($condition['date']) ? $this->db->where_in('ymd', $condition['date']) : $this->db->where('ymd', $condition['date']);
        }
        if (isset($condition['manager_user_id'])) {
            is_array($condition['manager_user_id']) ? $this->db->where_in('owner_user_id', $condition['manager_user_id']) : $this->db->where('owner_user_id', $condition['manager_user_id']);
        }
    }
    
    public function log_lists($condition, $page, $size, $order) {
        if ($page !== false && $size !== false) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_log_condition($condition);
        $rows = $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array();
        return $rows;
    }

    public function log_count($condition) {
        $this->db->select('count(1) as count');
        $this->_log_condition($condition);
        return $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->row(0)->count;
    }

    public function log_delete($condition) {
        
    }

    private function _log_condition($condition) {
        if ($condition['date']) {
            is_array($condition['date']) ? $this->db->where_in('ymd', $condition['date']) : $this->db->where('ymd', $condition['date']);
        }
        if (isset($condition['manager_user_id'])) {
            is_array($condition['manager_user_id']) ? $this->db->where_in('owner_user_id', $condition['manager_user_id']) : $this->db->where('owner_user_id', $condition['manager_user_id']);
        }
    }
    
    public function month_lists($condition, $page, $size, $order) {
        if ($page !== false && $size !== false) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_month_condition($condition);
        $this->db->select('user_id,owner_user_id,ym,sum(avg) as avg,sum(avg_achievement) as avg_achievement');
        $this->db->group_by('user_id,ym');
        $rows = $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array();
        return $rows;
    }

    public function month_count($condition) {
        $this->db->select('id');
        $this->_month_condition($condition);
        $this->db->group_by('user_id,ym');
        return count($this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array());
    }
    
    public function month_sum($condition) {
        $this->db->select('sum(avg) as sum,sum(avg_achievement) as sum_achievement');
        $this->_month_condition($condition);
        return $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->row_array();
    }

    private function _month_condition($condition) {
        if ($condition['date']) {
            is_array($condition['date']) ? $this->db->where_in('ymd', $condition['date']) : $this->db->where('ymd', $condition['date']);
        }
        if ($condition['ym']) {
            is_array($condition['ym']) ? $this->db->where_in('ym', $condition['ym']) : $this->db->where('ym', $condition['ym']);
        }
        if (isset($condition['manager_user_id'])) {
            is_array($condition['manager_user_id']) ? $this->db->where_in('owner_user_id', $condition['manager_user_id']) : $this->db->where('owner_user_id', $condition['manager_user_id']);
        }
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
    }
    
    public function month_log_lists($condition, $page, $size, $order) {
        if ($page !== false && $size !== false) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_month_log_condition($condition);
        $this->db->select('user_id,owner_user_id,ym,sum(avg) as avg_sum,sum(avg_achievement) as avg_achievement,amount,borrow_nid,days,avg,invest_time,borrow_title');
        $this->db->group_by('user_id,ym,borrow_tender_id');
        $rows = $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array();
        return $rows;
    }

    public function month_log_count($condition) {
        $this->db->select('id');
        $this->_month_log_condition($condition);
        $this->db->group_by('user_id,ym,borrow_tender_id');
        return count($this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array());
    }

    private function _month_log_condition($condition) {
        if ($condition['date']) {
            is_array($condition['date']) ? $this->db->where_in('ymd', $condition['date']) : $this->db->where('ymd', $condition['date']);
        }
        if ($condition['ym']) {
            is_array($condition['ym']) ? $this->db->where_in('ym', $condition['ym']) : $this->db->where('ym', $condition['ym']);
        }
        if (isset($condition['manager_user_id'])) {
            is_array($condition['manager_user_id']) ? $this->db->where_in('owner_user_id', $condition['manager_user_id']) : $this->db->where('owner_user_id', $condition['manager_user_id']);
        }
        if ($condition['borrow_max']) {
            $this->db->where('borrow_nid <= ' . $condition['borrow_max'], false, false);
        }
        if ($condition['borrow_min']) {
            $this->db->where('borrow_nid >= ' . $condition['borrow_min'], false, false);
        }
    }
    
    public function month_owner_lists($condition, $page, $size, $order) {
        if ($page !== false && $size !== false) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_month_owner_condition($condition);
        $this->db->select('owner_user_id,ym,sum(avg) as avg,sum(avg_achievement) as avg_achievement');
        $this->db->group_by('owner_user_id,ym');
        $rows = $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array();
        return $rows;
    }

    public function month_owner_count($condition) {
        $this->db->select('id');
        $this->_month_owner_condition($condition);
        $this->db->group_by('owner_user_id,ym');
        return count($this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->result_array());
    }
    
    public function month_owner_sum($condition) {
        $this->db->select('sum(avg) as sum,sum(avg_achievement) as sum_achievement');
        $this->_month_owner_condition($condition);
        return $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->row_array();
    }

    private function _month_owner_condition($condition) {
        if ($condition['date']) {
            is_array($condition['date']) ? $this->db->where_in('ymd', $condition['date']) : $this->db->where('ymd', $condition['date']);
        }
        if ($condition['ym']) {
            is_array($condition['ym']) ? $this->db->where_in('ym', $condition['ym']) : $this->db->where('ym', $condition['ym']);
        }
        if (isset($condition['manager_user_id'])) {
            is_array($condition['manager_user_id']) ? $this->db->where_in('owner_user_id', $condition['manager_user_id']) : $this->db->where('owner_user_id', $condition['manager_user_id']);
        }
    }
    
    public function get_last_ymd() {
        $this->db->select('max(ymd) as max');
        $this->db->limit(1);
        $max = $this->db->get_where(TABLE_INVEST_ACCOUNT_STAT_DAILY)->row(0)->max;
        return $max ? $max : false ;
    }

}
