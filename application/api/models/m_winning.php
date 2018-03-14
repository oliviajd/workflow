<?php

/**
 * 用户积分模型
 *
 *
 */
class m_winning extends CI_Model implements ObjInterface {

    CONST STATUS_SHIPPING_SUCCESS = 1;
    CONST STATUS_SHIPPING_PREPARE = 2;

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['pid'] = $data['pid'];
        $param['iid'] = $data['iid'];
        $param['pname'] = $data['pname'];
        $param['order_sn'] = $data['order_sn'];
        $param['create_time'] = time();
        $param['shipping_status'] = $data['shipping_status'];
        $param['user_id'] = $data['user_id'];
        $this->load->model('m_user');
        $detail['user'] = $this->m_user->detail($data['user_id']);
        $param['username'] = $detail['user']->loginname;
        $param['activity_id'] = $data['activity_id'];
        $param['decrease_type'] = $data['decrease_type'];
        $this->db->insert(TABLE_WINNING, $param);
        $wid = $this->db->insert_id();
        return $wid;
    }

    public function update($wid, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'pid':
                    $param['pid'] = intval($data['pid']);
                    break;
                case 'iid':
                    $param['iid'] = intval($data['iid']);
                    break;
                case 'pname':
                    $param['pname'] = trim($data['pname']);
                    break;
                case 'order_sn':
                    $param['order_sn'] = trim($data['order_sn']);
                    break;
                case 'shipping_status':
                    $param['shipping_status'] = intval($data['shipping_status']);
                    break;
                case 'user_id':
                    $param['user_id'] = intval($data['user_id']);
                    $this->load->model('m_user');
                    $detail['user'] = $this->m_user->detail($data['user_id']);
                    $param['username'] = $detail['user']->loginname;
                    break;
                case 'activity_id':
                    $param['activity_id'] = intval($data['activity_id']);
                    break;
                default:
                    break;
            }
        }
        $param['create_time'] = time();
        $this->db->update(TABLE_WINNING, $param, array('wid' => $wid));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $this->load->model('m_user');
        $this->load->model('m_address');
        $this->load->model('m_goods');
        $this->load->model('m_goods_category');
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        }
        $detail['user'] = $this->m_user->detail($detail['user_id']);
        $detail['shipping_status'] = new obj_item($this->get_shipping_status($detail['shipping_status']));
        return new obj_winning($detail);
    }

    public function detail_by_sn($sn) {
        $this->load->model('m_user');
        $this->load->model('m_address');
        $detail = $this->_detail_by_sn($sn);
        if (empty($detail)) {
            return false;
        }
        return $this->detail($detail['oid']);
    }

    public function lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get(TABLE_WINNING)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = $this->detail($v['wid']);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_WINNING)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['activity_id']) {
            $this->db->where('activity_id', $condition['activity_id']);
        }
        if ($condition['shipping_status']) {
            $this->db->where('shipping_status', $condition['shipping_status']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_WINNING, array('wid' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    private function _detail_by_sn($sn) {
        $detail = $this->db->get_where(TABLE_ORDER, array('order_sn' => $sn))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function get_shipping_status($key = false) {
        $data = array(
            1 => '已发货',
            2 => '未发货',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'ORDER_SHIPPING_ERROR', 'text' => '订单发货信息错误');
    }

}
