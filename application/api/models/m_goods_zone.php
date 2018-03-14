<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_goods_zone
 *
 * @author win7
 */
class m_goods_zone extends CI_Model implements ObjInterface {

    const STATUS_ZONE_OPEN = 1;
    const STATUS_ZONE_CLOSE = 2;
    const STATUS_ZONE_HIDE = 3;

    public function add($data) {
        $param['title'] = trim($data['title']);
        $param['is_delete'] = STATUS_NOT_DELETE;
        $param['goods_num'] = 0;
        $param['desc'] = trim($data['desc']);
        $param['is_rec'] = intval($data['is_rec']);
        $param['status'] = in_array(intval($data['status']), array(self::STATUS_ZONE_OPEN, self::STATUS_ZONE_CLOSE, self::STATUS_ZONE_HIDE)) ? intval($data['status']) : self::STATUS_ZONE_CLOSE;
        $param['create_time'] = time();
        $this->db->insert(TABLE_GOODS_ZONE, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'desc':
                    $param['desc'] = trim($data['desc']);
                    break;
                case 'status':
                    $param['status'] = in_array(intval($data['status']), array(self::STATUS_ZONE_OPEN, self::STATUS_ZONE_CLOSE, self::STATUS_ZONE_HIDE)) ? intval($data['status']) : self::STATUS_ZONE_CLOSE;
                    break;
                case 'is_rec':
                    $param['is_rec'] = intval($data['is_rec']);
                    break;
                default:
                    break;
            }
        }
        $this->db->update(TABLE_GOODS_ZONE, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = new obj_item($this->get_zone_status($detail['status']));
            return new obj_goods_zone($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
//        $page = intval($page) > 0 ? intval($page) : 1;
//        $size = intval($size) ? intval($size) : 20;
//        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_GOODS_ZONE)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = new obj_item($this->get_zone_status($v['status']));
            $rows[$k] = new obj_goods_zone($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_GOODS_ZONE)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_GOODS_ZONE, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['status']) {
            $this->db->where('status', $condition['status']);
        }
        if ($condition['use_for']) {
            $this->db->where('use_for', $condition['use_for']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_GOODS_ZONE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function get_zone_status($key) {
        $data = array(
            1 => '开放',
            2 => '关闭',
            3 => '隐藏',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'ZONE_STATUS_ERROR', 'text' => '商品专区状态错误');
    }

    public function is_title_exists($title) {
        $detail = $this->db->get_where(TABLE_GOODS_ZONE, array('title' => trim($title), 'is_delete' => STATUS_NOT_DELETE))->row(0)->id;
        return empty($detail) ? false : $detail;
    }

    // $iids array
    public function item_add($id, $iids) {
        if (empty($iids)) {
            return false;
        }
        $param = array();
        foreach ($iids as $k => $v) {
            if ($this->db->get_where(TABLE_GOODS_ZONE_ITEM, array(
                        'zone_id' => intval($id),
                        'iid' => intval($v),
                        'is_delete' => STATUS_NOT_DELETE,
                    ))->row(0)->id) {
                continue;
            }
            $param[] = array(
                'zone_id' => intval($id),
                'iid' => intval($v),
                'create_time' => time(),
                'is_delete' => STATUS_NOT_DELETE,
            );
        }
        if (empty($param)) {
            return 0;
        }
        $this->db->insert_batch(TABLE_GOODS_ZONE_ITEM, $param);
        $num = count($param);
        if ($num > 0) {
            $this->db->set('goods_num', 'goods_num + ' . $num, false);
            $this->db->update(TABLE_GOODS_ZONE, array(), array('id' => intval($id)));
        }
        return $num;
    }

    // $iids array
    public function item_delete($id, $iids) {
        if (empty($iids)) {
            return false;
        }
        $this->db->where('zone_id', intval($id));
        $this->db->where_in('iid', $iids);
        $this->db->update(TABLE_GOODS_ZONE_ITEM, array('is_delete' => STATUS_HAS_DELETE, 'modify_time' => time()), array('is_delete' => STATUS_NOT_DELETE));
        $num = $this->db->affected_rows();
        if ($num > 0) {
            $this->db->set('goods_num', 'goods_num - ' . $num, false);
            $this->db->update(TABLE_GOODS_ZONE, array(), array('id' => intval($id)));
        }
        return $num;
    }
    

}
