<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_coupon
 *
 * @author win7
 */
class m_coupon extends CI_Model implements ObjInterface {

    const STATUS_ON = 1;
    const STATUS_OFF = 2;
    const STATUS_USER_NOT_USE = 1;
    const STATUS_USER_USED = 2;
    const STATUS_USER_EXPIRE = 3;
    const STATUS_USER_CLOSED = 4;
    const STATUS_USER_LOCKED = 5;

    public function add($data) {
        $param['is_delete'] = STATUS_NOT_DELETE;
        $param['rate'] = intval($data['rate']);
        $param['admin_user_id'] = intval($data['admin_user_id']); //2.00% 记为200
        $param['type'] = strtolower(trim($data['type']));
        $param['status'] = in_array(intval($data['status']), array(self::STATUS_ON, self::STATUS_OFF)) ? intval($data['status']) : self::STATUS_OFF;
        $param['num_sent'] = 0;
        $param['store'] = intval($data['store']);
        $param['check_store'] = intval($data['check_store']);
        $param['expire'] = intval($data['expire']);
        $param['level'] = intval($data['level']);
        $param['limit_lower_money'] = intval($data['limit_lower_money']);
        $param['limit_upper_money'] = intval($data['limit_upper_money']);
        $param['limit_on_time'] = intval($data['limit_on_time']);
        $param['limit_off_time'] = intval($data['limit_off_time']);
        $param['use_times'] = intval($data['use_times']);
        $param['limit_use_for'] = intval($data['limit_use_for']);
        $param['use_for'] = trim($data['use_for']) ? trim($data['use_for']) : '';
        $param['is_force_use'] = intval($data['is_force_use']);
        $param['start_time'] = intval($data['start_time']);
        $param['end_time'] = intval($data['end_time']);
        $param['title'] = trim($data['title']);
        $param['remark'] = trim($data['remark']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_COUPON, $param);
        $id = $this->db->insert_id();
        if ($param['level'] > 0) {
            $this->_after_set_level($id, $param['level']);
        }
        return $id;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'remark':
                    $param['remark'] = trim($data['remark']);
                    break;
                case 'use_for':
                    $param['use_for'] = trim($data['use_for']) ? trim($data['use_for']) : '';
                    break;
                case 'rate':
                    $param['rate'] = intval($data['rate']);
                    break;
                case 'type':
                    $param['type'] = strtolower(trim($data['type']));
                    break;
                case 'status':
                    if (in_array(intval($data['status']), array(self::STATUS_ON, self::STATUS_OFF))) {
                        $param['status'] = intval($data['status']);
                    } else {
                        
                    }
                    break;
                case 'store':
                    $param['store'] = intval($data['store']);
                    break;
                case 'check_store':
                    $param['check_store'] = intval($data['check_store']);
                    break;
                case 'expire':
                    $param['expire'] = intval($data['expire']);
                    break;
                case 'level':
                    $param['level'] = intval($data['level']);
                    break;
                case 'limit_lower_money':
                    $param['limit_lower_money'] = intval($data['limit_lower_money']);
                    break;
                case 'limit_upper_money':
                    $param['limit_upper_money'] = intval($data['limit_upper_money']);
                    break;
                case 'limit_on_time':
                    $param['limit_on_time'] = intval($data['limit_on_time']);
                    break;
                case 'limit_off_time':
                    $param['limit_off_time'] = intval($data['limit_off_time']);
                    break;
                case 'use_times':
                    $param['use_times'] = intval($data['use_times']);
                    break;
                case 'limit_use_for':
                    $param['limit_use_for'] = intval($data['limit_use_for']);
                    break;
                case 'use_for':
                    $param['use_for'] = trim($data['use_for']);
                    break;
                case 'is_force_use':
                    $param['is_force_use'] = intval($data['is_force_use']);
                    break;
                case 'start_time':
                    $param['start_time'] = intval($data['start_time']);
                    break;
                case 'end_time':
                    $param['end_time'] = intval($data['end_time']);
                    break;
                default:
                    break;
            }
        }
        $detail = $this->_detail($id);
        $param['modify_time'] = time();
        $this->db->update(TABLE_COUPON, $param, array('id' => $id));
        if ($param['level'] != $detail['level'] && $param['level'] > 0) {
            $this->_after_set_level($id, $param['level']);
        }
        return true;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_coupon_status($detail['status']);
            return new obj_coupon($detail);
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
        $rows = $this->db->get_where(TABLE_COUPON)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_coupon_status($v['status']);
            $rows[$k] = new obj_coupon($rows[$k]);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_COUPON)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_COUPON, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['admin_user_id']) {
            $this->db->where('admin_user_id', $condition['admin_user_id']);
        }
        if ($condition['status']) {
            $this->db->where('status', $condition['status']);
        }
        if ($condition['q']) {
            $this->db->where('( id = \'' . intval($condition['q']) . '\' or title like \'%' . $this->db->escape_str($condition['q'], true) . '%\')', false, false);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_COUPON, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    private function _after_set_level($id, $level) {
        $this->db->where('id <> ' . intval($id));
        $ids = $this->db->get_where(TABLE_COUPON, array('level' => intval($level)))->result_array();
        if (count($ids) > 0) {
            $this->db->set('level', 'level + 1', false);
            $this->db->where('level >= ' . intval($level));
            $this->db->where('id <> ' . intval($id));
            $this->db->update(TABLE_COUPON);
            return $this->db->affected_rows() > 0;
        } else {
            return false;
        }
    }

    public function get_coupon_status($key = false) {
        $data = array(
            1 => '开放',
            2 => '关闭',
            3 => '未开放',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'COUPON_ERROR', 'text' => '加息券状态错误');
    }

    public function get_use_status($key = false) {
        $data = array(
            self::STATUS_USER_NOT_USE => '未使用',
            self::STATUS_USER_USED => '已使用',
            self::STATUS_USER_EXPIRE => '已过期',
            self::STATUS_USER_CLOSED => '已关闭',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'COUPON_USER_ERROR', 'text' => '加息券用户状态错误');
    }

    public function user_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_user_condition($condition);
        $rows = $this->db->get_where(TABLE_COUPON_USER)->result_array();
        $this->load->model('m_user');
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_use_status($v['status']);
            $rows[$k]['user'] = $this->m_user->detail($v['user_id']);
            $rows[$k] = new obj_coupon_user($rows[$k]);
        }
        return $rows;
    }

    public function user_count($condition) {
        $this->db->select('count(1) as count');
        $this->_user_condition($condition);
        return $this->db->get_where(TABLE_COUPON_USER)->row(0)->count;
    }

    private function _user_condition($condition) {
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['coupon_id']) {
            $this->db->where('coupon_id', $condition['coupon_id']);
        }
        if ($condition['status']) {
            $this->db->where('status', $condition['status']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }

    //加锁处理
    public function decrease($id) {
        $sql = "SELECT * FROM " . TABLE_COUPON . " WHERE id = " . intval($id) . ' FOR UPDATE';
        $detail = $this->db->query($sql)->row_array(0);
        if ($detail['store'] < 1) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->set('store', 'store - 1', false);
        $this->db->set('num_sent', 'num_sent + 1', false);
        $this->db->update(TABLE_COUPON, array(), array(
            'id' => $id
        ));
        $this->db->trans_commit();
        return true;
    }

    public function is_sent($id, $user_id) {
        $this->db->limit(1);
        $r = $this->db->get_where(TABLE_COUPON_USER, array('user_id' => $user_id, 'coupon_id' => $id))->row_array(0);
        return !empty($r);
    }

    public function send_to_user($id, $user_id, $remark = false) {
        $detail = $this->detail($id);
        $expire_time = array(time() + $detail->expire);
        if ($detail->limit_off_time) {
            $expire_time[] = $detail->limit_off_time;
        }
        $this->db->insert(TABLE_COUPON_USER, array(
            'user_id' => $user_id,
            'coupon_id' => $id,
            'type' => $detail->type,
            'title' => $detail->title,
            'limit_use_for' => $detail->limit_use_for,
            'use_for' => $detail->use_for,
            'is_force_use' => $detail->is_force_use,
            'rate' => $detail->rate,
            'enable_time' => $detail->limit_on_time,
            'disable_time' => $detail->limit_off_time,
            'expire_time' => min($expire_time),
            'receive_time' => time(),
            'limit_lower_money' => $detail->limit_lower_money,
            'limit_upper_money' => $detail->limit_upper_money,
            'use_times' => $detail->use_times,
            'unuse_times' => $detail->use_times,
            'status' => self::STATUS_USER_NOT_USE,
            'remark' => $remark === false ? $detail->remark : $remark,
        ));
        //todo 发送次数+1
        return $this->db->insert_id();
    }

    public function user_close($id) {
        $this->db->update(TABLE_COUPON_USER, array('status' => self::STATUS_USER_CLOSED), array('id' => $id, 'status' => self::STATUS_USER_NOT_USE));
        return $this->db->affected_rows();
    }

    public function user_open($id) {
        $this->db->update(TABLE_COUPON_USER, array('status' => self::STATUS_USER_NOT_USE), array('id' => $id, 'status' => self::STATUS_USER_CLOSED));
        return $this->db->affected_rows();
    }

    public function user_auto_use($id) {
        $detail = $this->user_detail($id);
        $this->db->update(TABLE_COUPON_USER, array('is_auto_use' => 1), array('id' => $id));
        $this->db->where('id <>' . intval($id), false, false);
        $this->db->update(TABLE_COUPON_USER, array('is_auto_use' => 2), array('user_id' => $detail->user_id));
        return true;
    }
    
    public function user_auto_use_cancel($id) {
        $this->db->update(TABLE_COUPON_USER, array('is_auto_use' => 2), array('id' => $id));
        return $this->db->affected_rows();
    }

    //锁定加息券等待使用
    public function use_lock($coupon_user_id) {
        $this->db->trans_begin();
        $sql = "SELECT * FROM " . TABLE_COUPON_USER . " WHERE id = " . intval($coupon_user_id) . ' FOR UPDATE';
        $detail = $this->db->query($sql)->row_array(0);
        if ($detail['use_times'] > 0 && $detail['unuse_times'] < 1) {
            $this->db->trans_rollback();
            return false;
        }
        $time = time();
        if ($detail['use_times'] > 0) {
            $this->db->set('unuse_times', 'unuse_times - 1', false);
            if ($detail['unuse_times'] == 1) {
                $this->db->set('status', self::STATUS_USER_USED);
            }
            $this->db->update(TABLE_COUPON_USER, array(), array(
                'id' => $coupon_user_id
            ));
        }
        //插入使用记录
        $this->use_add(array(
            'user_id' => $detail['user_id'],
            'coupon_id' => $detail['coupon_id'],
            'coupon_user_id' => $coupon_user_id,
            'rate' => $detail['rate'],
            'status' => self::STATUS_USER_LOCKED,
            'use_time' => $time,
            'use_for' => '',
            'use_times' => $detail['use_times'] - $detail['unuse_times'] + 1,
            'remark' => $detail['remark'],
        ));
        $lock_id = $this->db->insert_id();
        $this->db->trans_commit();
        return $lock_id;
    }

    //解锁未使用的加息券
    public function use_unlock($lock_id) {
        $sql = "SELECT * FROM " . TABLE_COUPON_USER_USE . " WHERE id = " . intval($lock_id) . ' FOR UPDATE';
        $detail = $this->db->query($sql)->row_array(0);
        if ($detail['status'] != self::STATUS_USER_LOCKED) {
            $this->db->trans_rollback();
            return false;
        }
        $coupon_user_id = $detail['coupon_user_id'];
        $this->db->set('unuse_times', 'unuse_times + 1', false);
        $this->db->set('status', self::STATUS_USER_NOT_USE);
        $this->db->where_in('status', array(self::STATUS_USER_NOT_USE, self::STATUS_USER_USED));
        $this->db->update(TABLE_COUPON_USER, array(), array(
            'id' => $coupon_user_id
        ));
        //删除使用记录
        $this->use_delete($lock_id);
        $this->db->trans_commit();
        return true;
    }

    //成功使用加息券
    public function make_use($lock_id, $use_for, $use_for_title) {
        $sql = "SELECT * FROM " . TABLE_COUPON_USER_USE . " WHERE id = " . intval($lock_id) . ' FOR UPDATE';
        $detail = $this->db->query($sql)->row_array(0);
        if ($detail['status'] != self::STATUS_USER_LOCKED) {
            $this->db->trans_rollback();
            return false;
        }
        //插入使用记录
        $this->use_update($lock_id, array(
            'status' => self::STATUS_USER_USED,
            'use_for' => $use_for,
            'use_for_title' => $use_for_title,
        ));
        //冗余tender表相关字段
        $this->load->model('m_borrow');
        $this->m_borrow->use_coupon($use_for, array(
            'coupon_user_use_id' => $detail['id'],
            'coupon_rate' => $detail['rate'],
        ));
        $this->db->trans_commit();
        return true;
    }

    //加锁处理
    public function use_decrease($id) {
        $sql = "SELECT * FROM " . TABLE_COUPON_USER . " WHERE id = " . intval($id) . ' FOR UPDATE';
        $detail = $this->db->query($sql)->row_array(0);
        if ($detail['unuse_times'] < 1) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->set('unuse_times', 'unuse_times - 1', false);
        $this->db->set('num_sent', 'num_sent + 1', false);
        $this->db->update(TABLE_COUPON_USER, array(), array(
            'id' => $id
        ));
        $this->db->trans_commit();
        return true;
    }

    public function user_detail($id) {
        $detail = $this->_user_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_use_status($detail['status']);
            return new obj_coupon_user($detail);
        }
    }

    private function _user_detail($id) {
        $detail = $this->db->get_where(TABLE_COUPON_USER, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function use_detail($id) {
        $detail = $this->_use_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_use_status($detail['status']);
            $coupon_user = $this->_user_detail($detail['coupon_user_id']);
            $detail['receive_time'] = $coupon_user['receive_time'];
            return new obj_coupon_user_used($detail);
        }
    }

    private function _use_detail($id) {
        $detail = $this->db->get_where(TABLE_COUPON_USER_USE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function use_add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['coupon_id'] = intval($data['coupon_id']);
        $param['coupon_user_id'] = intval($data['coupon_user_id']);
        $param['rate'] = intval($data['rate']);
        $param['status'] = intval($data['status']);
        $param['use_time'] = intval($data['use_time']);
        $param['use_for'] = trim($data['use_for']);
        $param['use_times'] = intval($data['use_times']);
        $param['remark'] = trim($data['remark']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_COUPON_USER_USE, $param);
        return $this->db->insert_id();
    }

    public function use_delete($id) {
        $this->db->update(TABLE_COUPON_USER_USE, array('is_delete' => STATUS_HAS_DELETE), array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function use_update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'status':
                    $param['status'] = intval($data['status']);
                    break;
                case 'use_for':
                    $param['use_for'] = trim($data['use_for']);
                    break;
                case 'use_for_title':
                    $param['use_for_title'] = trim($data['use_for_title']);
                    break;
                default:
                    break;
            }
        }
        $this->db->update(TABLE_COUPON_USER_USE, $param, array('id' => $id));
        return true;
    }

    public function use_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_use_condition($condition);
        $rows = $this->db->get_where(TABLE_COUPON_USER_USE)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_use_status($v['status']);
            $coupon_user = $this->_user_detail($v['coupon_user_id']);
            $rows[$k]['receive_time'] = $coupon_user['receive_time'];
            $rows[$k] = new obj_coupon_user_used($rows[$k]);
        }
        return $rows;
    }

    public function use_count($condition) {
        $this->db->select('count(1) as count');
        $this->_use_condition($condition);
        return $this->db->get_where(TABLE_COUPON_USER_USE)->row(0)->count;
    }

    private function _use_condition($condition) {
        if ($condition['user_id']) {
            $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['status']) {
            $this->db->where('status', $condition['status']);
        }
        $this->db->where('is_delete', STATUS_NOT_DELETE);
    }

}
