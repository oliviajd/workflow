<?php

/**
 * 奖品模型
 *
 *
 */
class m_prize_chance extends CI_Model{

    const STATUS_PRIZE_ON = 1;
    const STATUS_PRIZE_OFF = 2;
    const STATUS_PRIZE_INIT = 3;

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['user_id'] = $data['user_id'];
        $param['chance'] = $data['chance'];
        $param['activity_id'] = $data['activity_id'];
        $param['create_time'] = time();
        $this->db->insert(TABLE_PRIZE_CHANCE, $param);
        $pid = $this->db->insert_id();
        return $pid;
    }

    public function update($user_id,$activity_id,$data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'chance':
                    $param['chance'] = trim($data['chance']);
                    break;
                case 'flag':
                    $param['flag'] = trim($data['flag']);
                    break;
                case 'num':
                    $param['num'] = trim($data['num']);
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->where('user_id', $user_id);
        $this->db->where('activity_id', $activity_id);
        $this->db->update(TABLE_PRIZE_CHANCE,$param);
        return $this->db->affected_rows() > 0;

    }

    public function detail($user_id,$activity_id) {
        $detail = $this->_detail($user_id,$activity_id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_prize_chance($detail);
        }
    }

    public function lists($condition) {
        $this->_condition($condition);
        $rows = $this->db->get(TABLE_PRIZE_CHANCE)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_prize_chance($v);
        }
        return $rows;
    }

    public function _condition($condition) {
        if ($condition['activity_id']) {
            $this->db->where('activity_id', $condition['activity_id']);
        }
    }

    private function _detail($user_id,$activity_id) {
        $this->db->where('activity_id', $activity_id);
        $detail = $this->db->get_where(TABLE_PRIZE_CHANCE, array('user_id' => $user_id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function is_user_id_exists($user_id,$activity_id) {
        $detail = $this->db->get_where(TABLE_PRIZE_CHANCE, array('user_id' => trim($user_id),'activity_id' => trim($activity_id)))->row(0)->id;
        return empty($detail) ? false : $detail;
    }


    public function decrease($user_id,$activity_id, $num) {
        $this->load->model('m_prize_chance');
        $this->_create_info($user_id,$activity_id);
        //开始事务
        $this->db->trans_start();
        $prize_chance = $this->db->query("SELECT chance FROM " . TABLE_PRIZE_CHANCE . " WHERE user_id = " . intval($user_id) . " AND  activity_id = " . intval($activity_id) . " LIMIT 1 FOR UPDATE")->row(0)->chance;

        if (intval($prize_chance) < abs($num)) {
            $this->db->trans_rollback();
            return false;
        }
        if (intval($prize_chance) <= 0) {
            $this->db->set('flag', 2, false);
        }

        $this->db->set('chance', 'chance - ' . intval($num), false);
        $this->db->update(TABLE_PRIZE_CHANCE, array(), array('user_id' => $user_id,'activity_id' => $activity_id));

        $this->db->trans_complete();
        return true;

    }

    public function increase($user_id,$activity_id, $num) {

        $this->_create_info($user_id,$activity_id);
        //开始事务
        $this->db->trans_start();
        //$prize_chance = $this->db->query("SELECT chance FROM " . TABLE_PRIZE_CHANCE . " WHERE user_id = " . intval($user_id) . " LIMIT 1 FOR UPDATE")->row(0)->chance;
        $this->db->set('flag', 1, false);
        $this->db->set('chance', 'chance + ' . intval($num), false);
        $this->db->update(TABLE_PRIZE_CHANCE, array(), array('user_id' => $user_id , 'activity_id' => $activity_id));

        $this->db->trans_complete();
        return true;

    }

    public function _create_info($user_id,$activity_id) {
        $r = $this->db->get_where(TABLE_PRIZE_CHANCE, array('user_id' => $user_id,'activity_id' => $activity_id))->row_array(0);
        if (empty($r)) {
            $this->db->insert(TABLE_PRIZE_CHANCE, array(
                'user_id' => $user_id,
                'activity_id' => $activity_id
            ));
        } else {
            return true;
        }
    }
    
    public function num_increase($user_id,$activity_id, $num) {

        $this->_create_info($user_id,$activity_id);
        //开始事务
        $this->db->trans_start();
        //$prize_chance = $this->db->query("SELECT chance FROM " . TABLE_PRIZE_CHANCE . " WHERE user_id = " . intval($user_id) . " LIMIT 1 FOR UPDATE")->row(0)->chance;
        $this->db->set('flag', 1, false);
        $this->db->set('num', 'num + ' . intval($num), false);
        $this->db->update(TABLE_PRIZE_CHANCE, array(), array('user_id' => $user_id , 'activity_id' => $activity_id));

        $this->db->trans_complete();
        return true;

    }


}
?>