<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_bouns_user
 *
 * @author win7
 */
class m_bouns_user extends CI_Model implements ObjInterface {

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
        $param['title'] = trim($data['title']);
        $param['limit_upper_times'] = intval($data['limit_upper_times']);
        $param['has_sent_times'] = intval($data['has_sent_times']);
        $param['ratio'] = intval($data['ratio']);

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
        $detail['use_channel'] = $this->_transform_use_channel($detail['status']);
        $detail['use_type'] = $detail['usetype'];
        return empty($detail) ? false : new obj_bouns($detail);
    }

    public function lists($condition, $page, $size, $order) {
        if($page && $size){
            $this->db->limit(intval($size),intval(($page - 1) * $size));
        }
        if($order){
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

    public function send_to_user($id, $user_id) {
        $detail = $this->detail($id);
        $this->db->insert(TABLE_BOUNS_USER, array(
            'uid' => $user_id,
            'hid' => $id,
            'endtime' => $detail->start_time + $detail->expire,
            'addtime' => time(),
            'status' => 1,
            'remarks' => $detail->remark,
        ));
        return $this->db->insert_id();
    }

    public function merge_start($user_id, $ids) {
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
            return empty($bouns_info) ? false : array('bouns_user_id' => $id, 'money' => $bouns_info['money']);
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
        foreach ($bouns as $k => $v) {
            if ($v['status'] != 1) {//1 表示可用
                $enable = false;
            }
            $sum += $bouns_info_obj[$v['hid']]['money'];
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
        $this->db->trans_commit();
        return array('bouns_user_id' => $huid, 'money' => $sum);
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

}
