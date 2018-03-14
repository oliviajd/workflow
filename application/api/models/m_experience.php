<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_experience
 *
 * @author win7
 */
class m_experience extends CI_Model implements ObjInterface {

    const STATUS_EXPERIENCE_INIT = 3;
    const STATUS_EXPERIENCE_ON = 1;
    const STATUS_EXPERIENCE_OFF = 2;
    const STATUS_USER_IN_USE = 1;
    const STATUS_USER_USED = 2;
    const STATUS_USER_EXPIRE = 3;
    const STATUS_USER_CLOSED = 4;
    const STATUS_USER_LOCKED = 5;
    const STATUS_USER_INIT = 6;
    const TYPE_EXPERIENCE_IN = 1;
    const TYPE_EXPERIENCE_OUT = 2;

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['title'] = trim($data['title']);
        $param['remark'] = trim($data['remark']);
//        $param['limit_on_time'] = intval($data['limit_on_time']);
//        $param['limit_off_time'] = intval($data['limit_off_time']);
        $param['limit_upper_money'] = intval($data['limit_upper_money']);
        $param['rate'] = intval($data['rate']);
        $param['days'] = intval($data['days']);
        $param['expire'] = intval($data['expire']);
        $param['status'] = intval($data['status']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_EXPERIENCE, $param);
        return $this->db->insert_id();
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
                case 'days':
                    $param['days'] = intval($data['days']);
                    break;
                case 'rate':
                    $param['rate'] = intval($data['rate']);
                    break;
                case 'expire':
                    $param['expire'] = intval($data['expire']);
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
                case 'status':
                    if (in_array(intval($data['status']), array(self::STATUS_EXPERIENCE_INIT, self::STATUS_EXPERIENCE_ON, self::STATUS_EXPERIENCE_OFF))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_EXPERIENCE, $param, array('id' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_experience_status($detail['status']);
            return new obj_experience($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_EXPERIENCE . '.*');
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_EXPERIENCE)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_experience_status($v['status']);
            $rows[$k] = new obj_experience($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_EXPERIENCE)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_EXPERIENCE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function send_to_user($id, $money, $user_id, $remark = false) {
        //查看用户体验金账户是否建立
        $account = $this->_account_detail($user_id);
        if (empty($account)) {
            $this->account_add(array('user_id' => $user_id));
        }
        $detail = $this->detail($id);
        $expire_time = array(time() + $detail->expire);
        if ($detail->limit_off_time) {
            $expire_time[] = $detail->limit_off_time;
        }
        $this->db->insert(TABLE_EXPERIENCE_USER, array(
            'user_id' => $user_id,
            'experience_id' => $id,
            'money' => $money,
            'rate' => $detail->rate,
            'days' => $detail->days,
            'expire_time' => min($expire_time),
            'receive_time' => time(),
            'profit' => $this->profit($money, $detail->rate, $detail->days * 3600 * 24), //计算收益
            'profit_unget' => $this->profit($money, $detail->rate, $detail->days * 3600 * 24), //计算收益
            'status' => self::STATUS_USER_INIT,
            'remark' => $remark === false ? $detail->remark : trim($remark),
        ));
        return $this->db->insert_id();
    }

    public function user_detail($id) {
        $detail = $this->_user_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_use_status($detail['status']);
            return new obj_experience_user($detail);
        }
    }

    public function user_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_EXPERIENCE_USER . '.*');
        $this->_user_condition($condition);
        $rows = $this->db->get_where(TABLE_EXPERIENCE_USER)->result_array();
        $this->load->model('m_user');
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_use_status($v['status']);
            $v['user'] = $this->m_user->detail($v['user_id']);
            $rows[$k] = new obj_experience_user($v);
        }
        return $rows;
    }

    public function user_count($condition) {
        $this->db->select('count(1) as count');
        $this->_user_condition($condition);
        return $this->db->get_where(TABLE_EXPERIENCE_USER)->row(0)->count;
    }

    public function user_delete($condition) {
        
    }

    private function _user_condition($condition) {
        if ($condition['experience_id']) {
            $this->db->where('experience_id', $condition['experience_id']);
        }
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

    private function _user_detail($id) {
        $detail = $this->db->get_where(TABLE_EXPERIENCE_USER, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    //加锁处理
    public function increase($id, $money) {
        $sql = "SELECT * FROM " . TABLE_EXPERIENCE . " WHERE id = " . intval($id) . ' FOR UPDATE';
        $detail = $this->db->query($sql)->row_array(0);
        if ($detail['limit_upper_money'] > 0 && $detail['has_sent_money'] + $money > $detail['limit_upper_money']) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->set('has_sent_money', 'has_sent_money + ' . $money, false);
        $this->db->update(TABLE_EXPERIENCE, array(), array(
            'id' => $id
        ));
        $this->db->trans_commit();
        return true;
    }

    public function user_activate($id) {
        $detail = $this->_user_detail($id);
        $now = time();
        $this->db->update(TABLE_EXPERIENCE_USER, array(
            'status' => self::STATUS_USER_IN_USE,
            'start_time' => $now,
            'end_time' => $detail['days'] * 3600 * 24 + $now,
                ), array('id' => $id, 'status' => self::STATUS_USER_INIT));
        return $this->db->affected_rows();
    }

    public function user_close($id) {
        $this->db->update(TABLE_EXPERIENCE_USER, array('status' => self::STATUS_USER_CLOSED), array('id' => $id, 'status' => self::STATUS_USER_INIT));
        return $this->db->affected_rows();
    }

    public function user_open($id) {
        $this->db->update(TABLE_EXPERIENCE_USER, array('status' => self::STATUS_USER_INIT), array('id' => $id, 'status' => self::STATUS_USER_CLOSED));
        return $this->db->affected_rows();
    }

    public function account_add($data) {
        $param['user_id'] = intval($data['user_id']);
        $this->db->insert(TABLE_EXPERIENCE_ACCOUNT, $param);
        return $this->db->insert_id();
    }

    public function account_detail($id) {
        $detail = $this->_account_detail($id);
        if (empty($detail)) {
            return new obj_experience_account(array('user_id' => $id, 'money' => 0.00, 'moeny_total' => 0.00, 'money_out' => 0.00, 'money_real_time' => 0.00, 'experience_real_time' => 0.00));
        } else {
            $detail['money_real_time'] = $detail['money'];
            $detail['experience_real_time'] = 0;
            $now = time();
            $in_use = $this->user_lists(array('user_id' => $id, 'status' => self::STATUS_USER_IN_USE), false, false);
            foreach ($in_use as $k => $v) {
                $seconds = min($now, $v->end_time) - max($v->start_time, $v->profit_last_time);
                $profit = $this->profit($v->money, $v->rate, $seconds);
                $detail['money_real_time'] += $profit;
                $detail['experience_real_time'] += $v->money;
            }
            $detail['real_time'] = $now;
            $detail['in_use'] = $in_use;
            return new obj_experience_account($detail);
        }
    }

    public function account_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_EXPERIENCE_ACCOUNT . '.*');
        $this->_account_condition($condition);
        $rows = $this->db->get_where(TABLE_EXPERIENCE_ACCOUNT)->result_array();
        $this->load->model('m_user');
        foreach ($rows as $k => $v) {
            $v['user'] = $this->m_user->detail($v['account_id']);
            $rows[$k] = new obj_experience_account($v);
        }
        return $rows;
    }

    public function account_count($condition) {
        $this->db->select('count(1) as count');
        $this->_account_condition($condition);
        return $this->db->get_where(TABLE_EXPERIENCE_ACCOUNT)->row(0)->count;
    }

    public function account_delete($condition) {
        
    }

    private function _account_condition($condition) {
        if ($condition['experience_id']) {
            $this->db->where('experience_id', $condition['experience_id']);
        }
        if (isset($condition['account_id'])) {
            is_array($condition['account_id']) ? $this->db->where_in('account_id', $condition['account_id']) : $this->db->where('account_id', $condition['account_id']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

    private function _account_detail($id) {
        $detail = $this->db->get_where(TABLE_EXPERIENCE_ACCOUNT, array('user_id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function log_add($data) {
        $param['user_id'] = intval($data['user_id']);
        $param['experience_id'] = intval($data['experience_id']);
        $param['experience_user_id'] = intval($data['experience_user_id']);
        $param['money'] = $data['money'];
        $param['type'] = intval($data['type']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_EXPERIENCE_LOG, $param);
        return $this->db->insert_id();
    }

    public function log_lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_EXPERIENCE_LOG . '.*');
        $this->_log_condition($condition);
        $rows = $this->db->get_where(TABLE_EXPERIENCE_LOG)->result_array();
        $this->load->model('m_user');
        foreach ($rows as $k => $v) {
            $v['user'] = $this->m_user->detail_static($v['user_id']);
            $rows[$k] = new obj_experience_log($v);
        }
        return $rows;
    }

    public function log_count($condition) {
        $this->db->select('count(1) as count');
        $this->_log_condition($condition);
        return $this->db->get_where(TABLE_EXPERIENCE_LOG)->row(0)->count;
    }
    
    public function log_sum($condition) {
        $r = array();
        $condition['type'] = 1;
        $this->db->select('sum(money) as sum');
        $this->_log_condition($condition);
        $r['in_sum'] = $this->db->get_where(TABLE_EXPERIENCE_LOG)->row(0)->sum;
        $condition['type'] = 2;
        $this->db->select('sum(money) as sum');
        $this->_log_condition($condition);
        $r['out_sum'] = $this->db->get_where(TABLE_EXPERIENCE_LOG)->row(0)->sum;
        return $r;
    }

    private function _log_condition($condition) {
        if ($condition['experience_id']) {
            $this->db->where('experience_id', $condition['experience_id']);
        }
        if (isset($condition['log_id'])) {
            is_array($condition['log_id']) ? $this->db->where_in('log_id', $condition['log_id']) : $this->db->where('log_id', $condition['log_id']);
        }
        if (isset($condition['user_id'])) {
            is_array($condition['user_id']) ? $this->db->where_in('user_id', $condition['user_id']) : $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['type']) {
            is_array($condition['type']) ? $this->db->where_in('type', $condition['type']) : $this->db->where('type', $condition['type']);
        }
    }

    public function user_settle($user_id, $time) {
        $in_use = $this->user_lists(array('user_id' => $user_id, 'status' => self::STATUS_USER_IN_USE), false, false);
        $this->db->trans_start();
        foreach ($in_use as $k => $v) {
            $this->_settle($v, $time);
        }
        $this->db->trans_complete();
    }

    //$v obj_experience_user 对象
    public function _settle($v, $time) {
        $end_time = min($time, $v->end_time);
        $seconds = $end_time - max(array($v->start_time, $v->profit_last_time));
        $profit = 0;
        if ($v->end_time <= $time) {//已完成体验
            $profit = $v->profit_unget;
            $this->db->set('status', self::STATUS_USER_USED);
            $this->db->set('profit_unget', 0);
        } else {
            $profit = min($v->profit_unget, $this->profit($v->money, $v->rate, $seconds));
            $this->db->set('profit_unget', 'profit_unget - ' . $profit, false);
        }
        $this->db->update(TABLE_EXPERIENCE_USER, array(
            'profit_last_time' => $time
                ), array('id' => $v->experience_user_id, 'status' => self::STATUS_USER_IN_USE));
        if ($this->db->affected_rows() < 1) {
            return false;
        }
        $this->log_add(array(
            'user_id' => $v->user_id,
            'experience_id' => $v->experience_id,
            'experience_user_id' => $v->experience_user_id,
            'money' => $profit,
            'type' => self::TYPE_EXPERIENCE_IN
        ));
        $this->db->set('money', 'money +' . $profit, false);
        $this->db->set('money_total', 'money_total +' . $profit, false);
        $this->db->update(TABLE_EXPERIENCE_ACCOUNT, array(), array('user_id' => $v->user_id));
        return true;
    }

    public function transfer($user_id, $money) {
        $this->load->model('m_account');
        $this->m_account->lock($user_id);
        $handle = $this->db->query("SELECT * FROM " . TABLE_EXPERIENCE_ACCOUNT . " WHERE user_id = {$user_id} FOR UPDATE");
        if ($handle === false) {//加锁失败
            $this->db->query('rollback');
            $this->m_account->unlock($user_id);
            return false;
        }
        $account = $handle->row_array();
        if (empty($account)) {
            $this->account_add(array('user_id' => $user_id));
        }
        if (floatval($money) > floatval($account['money'])) {
            do_log(array(
                '体验金转出异常',
                'out_money' => $money,
                'account_money' => $account['money']
            ));
            $this->m_account->unlock($user_id);
            return false;
        }
        $param = array(
            'income' => $money,
            'expend' => 0,
            'balance' => $money,
            'balance_cash' => $money,
            'balance_frost' => 0,
            'frost' => 0,
            'await' => 0,
        );
        $param['user_id'] = intval($user_id);
        $param['type'] = 'experience_transfer';
        $param['money'] = $money;
        $param['remark'] = '收到体验金收益';
        $param['borrow_id'] = 0;
        $param['tender_id'] = 0;
        $param['to_userid'] = 0;
        $this->m_account->add_log($param);
        $this->log_add(array(
            'user_id' => $user_id,
            'experience_id' => 0,
            'experience_user_id' => 0,
            'money' => $money,
            'type' => self::TYPE_EXPERIENCE_OUT
        ));
        $this->db->set('money', 'money - ' . $money, false);
        $this->db->set('money_out', 'money_out + ' . $money, false);
        $this->db->update(TABLE_EXPERIENCE_ACCOUNT, array(), array('user_id' => $user_id));
        $this->m_account->unlock($user_id);
        $this->db->query('commit');
        return true;
    }

    public function get_experience_status($key = false) {
        $data = array(
            1 => '开放',
            2 => '关闭',
            3 => '未开放',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'EXPERIENCE_ERROR', 'text' => '体验金状态错误');
    }

    public function get_use_status($key = false) {
        $data = array(
            self::STATUS_USER_IN_USE => '生效中',
            self::STATUS_USER_USED => '已失效',
            self::STATUS_USER_EXPIRE => '已过期',
            self::STATUS_USER_CLOSED => '已关闭',
            self::STATUS_USER_LOCKED => '已锁定',
            self::STATUS_USER_INIT => '未激活',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'EXPERIENCE_ERROR', 'text' => '体验金状态错误');
    }

    public function profit($money, $rate, $seconds) {
        $year = $seconds / 3600 / 24 / 360; //一年按360天计算
        $rate /= 10000;
        return floatval(sprintf('%.6f', $money * $year * $rate));
    }

}
