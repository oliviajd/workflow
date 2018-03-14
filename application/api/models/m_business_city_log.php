<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_business_city_log
 *
 * @author win7
 */
class m_business_city_log extends CI_Model implements ObjInterface {

    const STATUS_LOG_ENABLE = 1;

    public function add($data) {
        $param['ymd'] = trim($data['date']);
        $ymd = explode('-', $param['ymd']);
        $param['year'] = $ymd[0];
        $param['month'] = $ymd[1];
        $param['day'] = $ymd[2];
        $param['ym'] = "{$ymd[0]}-{$ymd[1]}";
        $param['city_id'] = intval($data['city_id']);
        $param['data_credit_investigation'] = intval($data['data_credit_investigation']);
        $param['data_home_visits'] = intval($data['data_home_visits']);
        $param['data_refuse'] = intval($data['data_refuse']);
        $param['data_paid'] = intval($data['data_paid']);
        $param['data_paid_nums'] = intval($data['data_paid_nums']);
        $param['data_bank_repay'] = intval($data['data_bank_repay']);
        $param['data_bank_repay_nums'] = intval($data['data_bank_repay_nums']);
        $param['data_mortgage'] = intval($data['data_mortgage']);
        $param['data_mortgage_nums'] = intval($data['data_mortgage_nums']);
        $param['data_overdue'] = intval($data['data_overdue']);
        $param['data_overdue_nums'] = intval($data['data_overdue_nums']);
        $param['status'] = intval($data['status']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_BUSINESS_CITY_LOG, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'data_credit_investigation':
                    $param['data_credit_investigation'] = intval($data['data_credit_investigation']);
                    break;
                case 'data_home_visits':
                    $param['data_home_visits'] = intval($data['data_home_visits']);
                    break;
                case 'data_refuse':
                    $param['data_refuse'] = intval($data['data_refuse']);
                    break;
                case 'data_paid':
                    $param['data_paid'] = intval($data['data_paid']);
                    break;
                case 'data_paid_nums':
                    $param['data_paid_nums'] = intval($data['data_paid_nums']);
                    break;
                case 'data_bank_repay':
                    $param['data_bank_repay'] = intval($data['data_bank_repay']);
                    break;
                case 'data_bank_repay_nums':
                    $param['data_bank_repay_nums'] = intval($data['data_bank_repay_nums']);
                    break;
                case 'data_mortgage':
                    $param['data_mortgage'] = intval($data['data_mortgage']);
                    break;
                case 'data_mortgage_nums':
                    $param['data_mortgage_nums'] = intval($data['data_mortgage_nums']);
                    break;
                case 'data_overdue':
                    $param['data_overdue'] = intval($data['data_overdue']);
                    break;
                case 'data_overdue_nums':
                    $param['data_overdue_nums'] = intval($data['data_overdue_nums']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_BUSINESS_CITY_LOG, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $this->load->model('m_address');
            $city = $this->m_address->get_area($detail['city_id']);
            $detail['city'] = array('id' => $city['id'], 'text' => $city['name']);
            $detail['status'] = $this->get_log_status($detail['status']);
            return new obj_business_city_log($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_BUSINESS_CITY_LOG . '.*');
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->result_array();
        $this->load->model('m_address');
        foreach ($rows as $k => $v) {
            $city = $this->m_address->get_area($v['city_id']);
            $v['city'] = array('id' => $city['id'], 'text' => $city['name']);
            $v['status'] = $this->get_log_status($v['status']);
            $rows[$k] = new obj_business_city_log($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['city_id']) {
            $this->db->where('city_id', $condition['city_id']);
        }
        if ($condition['date']) {
            $this->db->where('ymd', $condition['date']);
        }
        if ($condition['year_month']) {
            $this->db->where('ym', $condition['year_month']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['m_start']) {
            $this->db->where('ym >= ' . intval($condition['m_start']), false, false);
        }
        if ($condition['m_end']) {
            $this->db->where('ym <= ' . intval($condition['m_end']), false, false);
        }
        if ($condition['with_out']) {
            $this->db->where_not_in('city_id', $condition['with_out']);
        }
        $this->db->where(TABLE_BUSINESS_CITY_LOG . '.is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_BUSINESS_CITY_LOG, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function get_log_status($key = false) {
        $data = array(
            1 => '开放',
            2 => '关闭',
            3 => '未开放',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'LOG_ERROR', 'text' => '日志状态错误');
    }

}
