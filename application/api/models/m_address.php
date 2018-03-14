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
class m_address extends CI_Model implements ObjInterface {

    const STATUS_DEFAULT = 1;
    const STATUS_NOT_DEFAULT = 2;

    public function add($data) {
        $param['user_id'] = intval($data['user_id']);
        $param['is_delete'] = STATUS_NOT_DELETE;
        $param['is_copy'] = in_array(intval($data['is_copy']), array(1, 2)) ? intval($data['is_copy']) : 2;
        if ($param['is_copy'] == 1) {
            $param['is_default'] = self::STATUS_NOT_DEFAULT;
        } else {
            $param['is_default'] = in_array(intval($data['is_default']), array(self::STATUS_DEFAULT, self::STATUS_NOT_DEFAULT)) ? intval($data['is_default']) : self::STATUS_NOT_DEFAULT;
        }
        $param['name'] = trim($data['name']);
        $param['mobile'] = trim($data['mobile']);
        $param['province'] = trim($data['province']);
        $param['city'] = trim($data['city']);
        $param['country'] = trim($data['country']);
        $param['area'] = trim($data['area']);
        $param['province_id'] = intval($data['province_id']);
        $param['city_id'] = intval($data['city_id']);
        $param['country_id'] = intval($data['country_id']);
        $param['address'] = implode(' ', array($param['province'], $param['city'], $param['country'], $param['area']));
        $param['create_time'] = time();
        $this->db->insert(TABLE_USER_ADDRESS, $param);
        $id = $this->db->insert_id();
        if ($param['is_default'] == self::STATUS_DEFAULT) {
            $this->db->where('id <> ' . $id, false, false);
            $this->db->update(TABLE_USER_ADDRESS, array('is_default' => self::STATUS_NOT_DEFAULT), array('user_id' => $param['user_id'], 'is_copy' => 2));
        }
        return $id;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'name':
                    $param['name'] = trim($data['name']);
                    break;
                case 'mobile':
                    $param['mobile'] = trim($data['mobile']);
                    break;
                case 'province':
                    $param['province'] = trim($data['province']);
                    break;
                case 'city':
                    $param['city'] = trim($data['city']);
                    break;
                case 'country':
                    $param['country'] = trim($data['country']);
                    break;
                case 'area':
                    $param['area'] = trim($data['area']);
                    break;
                case 'province_id':
                    $param['province_id'] = intval($data['province_id']);
                    break;
                case 'city_id':
                    $param['city_id'] = intval($data['city_id']);
                    break;
                case 'country_id':
                    $param['country_id'] = intval($data['country_id']);
                    break;
                case 'is_default':
                    $param['is_default'] = intval($data['is_default']);
                    break;
                default:
                    break;
            }
        }
        $detail = $this->_detail($id);
        $param['province'] = $param['province'] ? $param['province'] : $detail['province'];
        $param['city'] = $param['city'] ? $param['city'] : $detail['city'];
        $param['country'] = $param['country'] ? $param['country'] : $detail['country'];
        $param['area'] = $param['area'] ? $param['area'] : $detail['area'];
        $param['address'] = implode(' ', array($param['province'], $param['city'], $param['country'], $param['area']));
        $param['modify_time'] = time();
        $this->db->update(TABLE_USER_ADDRESS, $param, array('id' => $id));
        if ($param['is_default'] == self::STATUS_DEFAULT && $detail['is_copy'] == 2) {
            $this->db->where('id <> ' . $id, false, false);
            $this->db->update(TABLE_USER_ADDRESS, array('is_default' => self::STATUS_NOT_DEFAULT), array('user_id' => $detail['user_id'], 'is_copy' => 2));
        }
        return true;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $province = $this->get_area($detail['province_id']);
            $city = $this->get_area($detail['city_id']);
            $country = $this->get_area($detail['country_id']);
            $detail['province'] = array('id' => $province['id'], 'text' => $province['name']);
            $detail['city'] = array('id' => $city['id'], 'text' => $city['name']);
            $detail['country'] = array('id' => $country['id'], 'text' => $country['name']);
            return new obj_address($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_USER_ADDRESS)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = $this->detail($v['id']);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_USER_ADDRESS)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_USER_ADDRESS, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['user_id']) {
            $this->db->where('user_id', $condition['user_id']);
        }
        $this->db->where('is_default', 1);
        $this->db->where('is_copy', 2);
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_USER_ADDRESS, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function copy($id) {
        if (!$id) {
            return 0;
        }
        $from = $this->_detail($id);
        $from['is_copy'] = 1;
        $from['is_default'] = self::STATUS_NOT_DEFAULT;
        return $this->add($from);
    }

    public function get_area($area_id, $pid = false) {
        if ($pid !== false) {
            $this->db->where('pid', $pid);
        }
        $detail = $this->db->get_where(TABLE_AREA, array('id' => $area_id))->row_array(0);
        return $detail;
    }

}
