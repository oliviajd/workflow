<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_bouns
 *
 * @author win7
 */
class m_bouns extends CI_Model implements ObjInterface {

    const STATUS_USER_NOT_USE = 1;
    const STATUS_USER_USED = 2;
    const STATUS_USER_EXPIRE = 3;
    const STATUS_USER_CLOSED = 4;
    const STATUS_USER_LOCKED = 5;

    public function add($data) {
        $param['money'] = trim($data['money']);
        $param['username'] = trim($data['creator']);
        $param['nums'] = intval($data['num_current']);
        $param['totalnum'] = intval($data['num_total']);
        $param['starttime'] = intval($data['start_time']);
        $param['ytime'] = intval($data['expire']);
        $param['status'] = $this->_check_use_channel(trim($data['use_channel']));
        $param['usetype'] = intval($data['use_type']);
        $param['addttime'] = time();
        $param['whyadd'] = trim($data['remark']);

        $this->db->insert(TABLE_BOUNS, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'money':
                    $param['money'] = intval($data['money']);
                    break;
                case 'creator':
                    $param['username'] = trim($data['creator']);
                    break;
                case 'num_current':
                    $param['nums'] = intval($data['num_current']);
                    break;
                case 'num_total':
                    $param['totalnum'] = intval($data['num_total']);
                    break;
                case 'expire':
                    $param['ytime'] = intval($data['expire']);
                    break;
                case 'start_time':
                    $param['starttime'] = intval($data['start_time']);
                    break;
                case 'use_channel':
                    $param['status'] = $this->_check_use_channel(trim($data['use_channel']));
                    break;
                case 'use_type':
                    $param['usetype'] = intval($data['use_type']);
                    break;
                case 'remark':
                    $param['whyadd'] = trim($data['remark']);
                    break;
                case 'create_time':
                    $param['addttime'] = intval($data['create_time']);
                    break;
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'limit_upper_times':
                    $param['limit_upper_times'] = intval($data['limit_upper_times']);
                    break;
                case 'has_sent_times':
                    $param['has_sent_times'] = intval($data['has_sent_times']);
                    break;
                case 'ratio':
                    $param['ratio'] = intval($data['ratio']);
                    break;
                default:
                    break;
            }
        }
        $param['edittime'] = time();
        $this->db->update(TABLE_BOUNS, $param, array('hid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['use_channel'] = $this->_transform_use_channel($detail['status']);
            $detail['use_type'] = $detail['usetype'];
            return new obj_bouns($detail);
        }
    }

    public function find($user_id, $remark) {
        $this->db->like(TABLE_BOUNS . '.whyadd', $remark);
        $this->db->join(TABLE_BOUNS_USER, TABLE_BOUNS_USER . '.hid = ' . TABLE_BOUNS . '.hid');
        $r = $this->db->get_where(TABLE_BOUNS, array(TABLE_BOUNS_USER . '.uid' => $user_id))->row_array(0);
        return empty($r) ? false : $r;
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get(TABLE_BOUNS)->result_array();
        foreach ($rows as $k => $v) {
            $v['use_channel'] = $this->_transform_use_channel($v['status']);
            $v['use_type'] = $v['usetype'];
            $rows[$k] = new obj_bouns($v);
        }
        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_BOUNS, array('hid' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    private function _check_use_channel($channel) {
        $r = 0;
        switch (strtolower($channel)) {
            case 'all':
                $r = 0;
                break;
            case 'web':
                $r = 1;
                break;
            case 'phone':
                $r = 2;
                break;
            case 'reg':
                $r = 3;
                break;
            case 'none':
                $r = 4;
                break;
        }
        return $r;
    }

    private function _transform_use_channel($channel) {
        $r = 'all';
        switch (intval($channel)) {
            case 0:
                $r = 'all';
                break;
            case 1:
                $r = 'web';
                break;
            case 2:
                $r = 'phone';
                break;
            case 3:
                $r = 'reg';
                break;
            case 4:
                $r = 'none';
                break;
        }
        return $r;
    }

    public function send_to_user($id, $user_id, $status = 1) {
        $detail = $this->detail($id);
        $this->db->insert(TABLE_BOUNS_USER, array(
            'uid' => $user_id,
            'hid' => $id,
            'money' => $detail->money,
            'endtime' => $detail->limit_off_time > 0 ? min(time() + $detail->expire, $detail->limit_off_time) : time() + $detail->expire,
            'addtime' => time(),
            'status' => $status,
            'remarks' => $detail->remark,
            'enable_time' => $detail->limit_on_time,
            'disable_time' => $detail->limit_off_time,
            'limit_lower_money' => $detail->limit_lower_money,
            'limit_upper_money' => $detail->limit_upper_money,
        ));
        return $this->db->insert_id();
    }

    //判断红包是否可用，可用返回总金额
    public function is_available($user_id, $ids) {
        $ids_format = array_map('intval', $ids);
        $this->db->where_in('id', $ids_format);
        $bouns = $this->db->get_where(TABLE_BOUNS_USER, array(
                    'uid' => $user_id,
                    'is_merged' => 2,
                    'status' => 1,
                ))->result_array();
        if (count($bouns) == count($ids_format)) {
            return array('money' => array_sum(array_column($bouns, 'money')));
        } else {
            return false;
        }
    }

    public function merge_start($user_id, $ids, $tender_money) {
        if (empty($ids)) {
            return false;
        }
        $this->db->trans_begin();
        if (count($ids) == 1) {
            $id = intval(current($ids));
            $sql = "SELECT * FROM " . TABLE_BOUNS_USER . " WHERE uid = {$user_id} AND id = '{$id}' AND is_merged = 2 AND status = 1 FOR UPDATE";
            $handle = $this->db->query($sql);
            if ($handle === false) {
                $this->db->query('rollback');
                return false;
            }
            $bouns = $handle->row_array();
            $bouns_info = $this->_detail($bouns['hid']);
            $limit_lower_money = $bouns['limit_lower_money'];
            $limit_upper_money = $bouns['limit_upper_money'];
            $org_limit_lower_money = $bouns['limit_lower_money'] ? 0 : $this->limit_lower_money($bouns_info['money']);
            return empty($bouns_info) ? false : array('bouns_user_id' => $id, 'money' => $bouns_info['money'], 'limit_lower_money' => $limit_lower_money, 'limit_upper_money' => $limit_upper_money, 'org_limit_lower_money' => $org_limit_lower_money);
        }
        $ids_format = array_map('intval', $ids);
        $id_str = implode(',', $ids_format);
        $sql = "SELECT * FROM " . TABLE_BOUNS_USER . " WHERE uid = {$user_id} AND id in ({$id_str}) AND is_merged = 2 AND status = 1 FOR UPDATE";
        $handle = $this->db->query($sql);
        if ($handle === false) {
            $this->db->query('rollback');
            return false;
        }
        $bouns = $handle->result_array();
        if (count($bouns) != count($ids)) {//红包数量不匹配，存在使用他人红包的可能
            $this->db->trans_rollback();
            return false;
        }
        $hids = array_column($bouns, 'hid');
        $this->db->where_in('hid', $hids);
        $bouns_info_array = $this->db->get_where(TABLE_BOUNS)->result_array();
        $bouns_info_obj = array();
        foreach ($bouns_info_array as $k => $v) {
            $bouns_info_obj[$v['hid']] = $v;
        }
        $enable = true;
        $sum = 0;
        $limit_lower_money = 0;
        $limit_upper_money = 0;
        foreach ($bouns as $k => $v) {
            if ($v['status'] != 1) {//1 表示可用
                $enable = false;
            }
            $sum += $bouns_info_obj[$v['hid']]['money'];
            $limit_lower_money += $bouns_info_obj[$v['hid']]['limit_lower_money'];
            $limit_upper_money += $bouns_info_obj[$v['hid']]['limit_upper_money'];
            $org_limit_lower_money += $bouns_info_obj[$v['hid']]['limit_lower_money'] ? 0 : $this->limit_lower_money($bouns_info_obj[$v['hid']]['money']);
        }
        //超过200无法使用
        if ($sum > 200) {
            $this->db->trans_rollback();
            return false;
        }
        if (!$enable) {//传入的ID中 存在不可用的红包
            $this->db->trans_rollback();
            return false;
        }
        $this->db->where_in('id', $ids_format);
        $this->db->update(TABLE_BOUNS_USER, array('is_merged' => 3, 'status' => self::STATUS_USER_CLOSED), array('uid' => $user_id, 'is_merged' => 2, 'status' => 1));
        $bouns_id = $this->add(array(
            'creator' => 'admin',
            'money' => $sum,
            'num_current' => 1,
            'num_total' => 1,
            'start_time' => time(),
            'expire' => 3600 * 24 * 1,
            'use_channel' => 'all',
            'use_type' => 0,
            'remark' => '红包合并'
        ));
        $this->db->insert(TABLE_BOUNS_USER, array(
            'uid' => $user_id,
            'hid' => $bouns_id,
            'addtime' => time(),
            'endtime' => time() + 3600,
            'merge_from' => $id_str,
            'is_merged' => 1,
            'remarks' => '红包合并'
        ));
        $huid = $this->db->insert_id();
        $this->db->where_in('id', $ids_format);
        $this->db->update(TABLE_BOUNS_USER, array('merge_to' => $huid), array('uid' => $user_id, 'is_merged' => 3, 'status' => self::STATUS_USER_CLOSED)); //记录合并结果
        $this->db->query('commit');
        return array('bouns_user_id' => $huid, 'money' => $sum, 'limit_lower_money' => $limit_lower_money, 'limit_upper_money' => $limit_upper_money, 'org_limit_lower_money' => $org_limit_lower_money);
    }

    public function merge_success($user_id, $bouns_user_id, $borrow) {
        //将合并后的红包记为已使用
        $this->db->update(TABLE_BOUNS_USER, array(
            'status' => 2,
            'usetime' => time(),
            'pname' => $borrow->title,
                ), array(
            'id' => $bouns_user_id,
            'uid' => $user_id
        ));
        //将待合并的红包标记为合并，并记录合并结果
        $this->db->update(TABLE_BOUNS_USER, array('is_merged' => 1, 'status' => 4), array(
            'uid' => $user_id,
            'merge_to' => $bouns_user_id,
            'is_merged' => 3
        ));
        $this->db->query('commit');
        return true;
    }

    public function merge_failed($user_id, $bouns_user_id) {
        //删除新增的红包
        $bouns = $this->db->get_where(TABLE_BOUNS_USER, array('id' => $bouns_user_id, 'is_merged' => 1))->row_array(0);
        if (empty($bouns)) {
            return false;
        }
        $ids = explode(',', $bouns['merge_from']);
        //将待合并的红包标记为未合并，并记录合并结果
        $this->db->where_in('id', $ids);
        $this->db->update(TABLE_BOUNS_USER, array('is_merged' => 2, 'merge_to' => 0, 'status' => self::STATUS_USER_NOT_USE), array(
            'uid' => $user_id,
            'merge_to' => $bouns_user_id,
            'is_merged' => 3
        ));
        $this->db->delete(TABLE_BOUNS, array('hid' => $bouns['hid']));
        $this->db->delete(TABLE_BOUNS_USER, array('id' => $bouns_user_id));
        $this->db->query('commit');
        return true;
    }

    //旧计算公式，计算红包最低使用限制
    public function limit_lower_money($money) {
        if (intval($money) < 20) {
            return $money * 100;
        } else {
            return $money * 200;
        }
    }

    public function user_detail($bouns_user_id) {
        $detail = $this->_user_detail($bouns_user_id);
        if (!empty($detail)) {
            $detail['status'] = $this->get_use_status($detail['status']);
            return new obj_bouns_user($detail);
        } else {
            return false;
        }
    }

    public function user_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_user_condition($condition);
        $rows = $this->db->get_where(TABLE_BOUNS_USER)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['status'] = $this->get_use_status($v['status']);
            $rows[$k] = new obj_bouns_user($rows[$k]);
        }
        return $rows;
    }

    public function user_count($condition) {
        $this->db->select('count(1) as count');
        $this->_user_condition($condition);
        return $this->db->get_where(TABLE_BOUNS_USER)->row(0)->count;
    }

    private function _user_condition($condition) {
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('uid', $condition['user_id']) : $this->db->where('uid', $condition['user_id']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['bouns_id']) {
            $this->db->where('hid', $condition['bouns_id']);
        }
        if ($condition['tender_money']) {
            $this->db->where('limit_lower_money <= ' . intval($condition['tender_money']), false, false);
        }
        //$this->db->where('is_delete', STATUS_NOT_DELETE);
    }

    public function get_use_status($key = false) {
        $data = array(
            1 => '可用',
            2 => '已使用',
            3 => '过期',
            4 => '关闭',
            5 => '待激活',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'GOODS_ERROR', 'text' => '商品状态错误');
    }

    private function _user_detail($bouns_user_id) {
        $detail = $this->db->get_where(TABLE_BOUNS_USER, array('id' => $bouns_user_id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    //开始银行存管的红包划款
    public function start_pay($bouns_user_id) {
        $this->db->update(TABLE_BOUNS_USER, array('has_paid' => 3), array('id' => $bouns_user_id));
        return $this->db->affected_rows() > 0;
    }

    //银行存管的红包划款完成
    public function success_pay($bouns_user_id, $result) {
        $this->db->update(TABLE_BOUNS_USER, array('has_paid' => 1, 'paid_result' => json_encode($result)), array('id' => $bouns_user_id, 'has_paid' => 3));
        return $this->db->affected_rows() > 0;
    }

    //银行存管的红包划款失败
    public function failed_pay($bouns_user_id, $result) {
        $this->db->update(TABLE_BOUNS_USER, array('has_paid' => 4, 'paid_result' => json_encode($result)), array('id' => $bouns_user_id, 'has_paid' => 3));
        return $this->db->affected_rows() > 0;
    }

}
