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
class m_finance_account_sub extends CI_Model implements ObjInterface {

    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 2;

    public function add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['status'] = self::STATUS_ENABLE;
        $param['money'] = 0;
        $param['company'] = trim($data['company']);
        $param['name'] = trim($data['name']);
        $param['bank'] = trim($data['bank']);
        $param['bank_card'] = trim($data['bank_card']);
        $param['id_card'] = trim($data['id_card']);
        $param['mobile'] = trim($data['mobile']);

        $param['version'] = 1;
        $param['create_time'] = time();
        $this->db->insert(TABLE_FINANCE_ACCOUNT_SUB, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $detail = $this->_detail($id);
        unset($detail['id']);
        $detail['create_time'] = time();
        //记录日志
        $this->db->insert(TABLE_FINANCE_ACCOUNT_SUB_HISTORY, $detail);
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'company':
                    $param['company'] = trim($data['company']);
                    break;
                case 'name':
                    $param['name'] = trim($data['name']);
                    break;
                case 'bank':
                    $param['bank'] = trim($data['bank']);
                    break;
                case 'bank_card':
                    $param['bank_card'] = trim($data['bank_card']);
                    break;
                case 'id_card':
                    $param['id_card'] = trim($data['id_card']);
                    break;
                case 'mobile':
                    $param['mobile'] = trim($data['mobile']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->set('version', 'version + 1', false);
        $this->db->update(TABLE_FINANCE_ACCOUNT_SUB, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $this->load->model('m_user');
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['user'] = $this->m_user->detail($detail['user_id']);
            return new obj_finance_account_sub($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        $this->load->model('m_user');
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_FINANCE_ACCOUNT_SUB)->result_array();
        foreach ($rows as $k => $v) {
            $v['user'] = $this->m_user->detail($v['user_id']);
            $rows[$k] = new obj_finance_account_sub($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_FINANCE_ACCOUNT_SUB)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_FINANCE_ACCOUNT_SUB, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows();
    }

    private function _condition($condition) {
        if ($condition['user_id']) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_FINANCE_ACCOUNT_SUB, array('id' => $id, 'is_delete' => STATUS_NOT_DELETE))->row_array(0);
        return empty($detail) ? false : $detail;
    }

}
