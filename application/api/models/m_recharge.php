<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_recharge
 *
 * @author win7
 */
class m_recharge extends CI_Model implements ObjInterface {

    const STATUS_RECHARGE_SUCCESS = 1;
    const STATUS_RECHARGE_UNFINISH = 2;

    public function add($data) {
        $param['user_id'] = $data['user_id'];
        $param['status'] = self::STATUS_RECHARGE_UNFINISH; //未成功
        $param['money'] = $data['money'];
        $param['balance'] = $data['money'];
        $param['payment'] = intval($data['payment']);
        $param['type'] = 1;
        $param['remark'] = trim($data['remark']);
        $param['addtime'] = time();
        $param['addip'] = get_ip();
        $this->db->insert(TABLE_ACCOUNT_RECHARGE, $param);
        $id = $this->db->insert_id();

        //更新订单号，订单价格
        $order_sn = create_order_sn($id);
        $this->db->update(TABLE_ACCOUNT_RECHARGE, array('nid' => $order_sn), array('id' => $id));
        return $id;
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_recharge_status($detail['status']);
            return new obj_recharge($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_ACCOUNT_RECHARGE, array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function detail_by_sn($sn) {
        $detail = $this->_detail_by_sn($sn);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_recharge_status($detail['status']);
            return new obj_recharge($detail);
        }
    }

    private function _detail_by_sn($sn) {
        $detail = $this->db->get_where(TABLE_ACCOUNT_RECHARGE, array('nid' => $sn))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function get_recharge_status($key = false) {
        $data = array(
            self::STATUS_RECHARGE_SUCCESS => '成功',
            self::STATUS_RECHARGE_UNFINISH => '未成功',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'RECHARGE_ERROR', 'text' => '充值状态错误');
    }

    //充值成功
    public function success($id, $admin, $remark) {
        $this->db->update(TABLE_ACCOUNT_RECHARGE, array(
            'verify_time' => time(),
            'verify_userid' => $admin->user_id,
            'verify_remark' => $remark,
            'status' => self::STATUS_RECHARGE_SUCCESS
                ), array('id' => $id, 'status' => self::STATUS_RECHARGE_UNFINISH));
        return $this->db->affected_rows() > 0;
    }

}
