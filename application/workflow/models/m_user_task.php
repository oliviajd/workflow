<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_user_task
 *
 * @author win7
 */
class m_user_task extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        
    }

    public function count($condition) {
        
    }

    public function delete($task_instance_id) {
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('is_delete' => STATUS_HAS_DELETE), array('id' => $task_instance_id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        
    }

    //用户占用资源
    public function lock($ids, $user_id) {
        if (is_array($ids)) {
            $ids = implode(',', $ids);
            $count = count($ids);
        } else {
            $ids = intval($ids);
            $count = 1;
        }
        $this->db->query('begin');
        $handle = $this->db->query("SELECT * FROM " . TABLE_PROCESS_ITEM_INSTANCE . " WHERE id in ({$ids}) AND is_locked = 0 FOR UPDATE");
        if ($handle === false) {//mysql加锁失败
            $this->db->query('rollback');
            return false;
        }
        $rows = $handle->result_array();
        if (count($rows) == $count) {
            $this->db->query("UPDATE " . TABLE_PROCESS_ITEM_INSTANCE . " SET is_locked = {$user_id} WHERE id in ({$ids}) AND is_locked = 0");
            $this->db->query('commit');
            return $count;
        } else {
            $this->db->query('rollback');
            return false;
        }
    }

    //用户释放资源
    public function unlock($ids, $user_id) {
        if (is_array($ids)) {
            $ids = implode(',', $ids);
            $count = count($ids);
        } else {
            $ids = intval($ids);
            $count = 1;
        }
        $this->db->query('begin');
        $handle = $this->db->query("SELECT * FROM " . TABLE_PROCESS_ITEM_INSTANCE . " WHERE id in ({$ids}) AND is_locked = {$user_id} FOR UPDATE");
        if ($handle === false) {//mysql加锁失败
            $this->db->query('rollback');
            return false;
        }
        $rows = $handle->result_array();
        if (count($rows) == $count) {
            $this->db->query("UPDATE " . TABLE_PROCESS_ITEM_INSTANCE . " SET is_locked = 0 WHERE id in ({$ids}) AND is_locked = {$user_id}");
            $this->db->query('commit');
            return $count;
        } else {
            $this->db->query('rollback');
            return false;
        }
    }

    //只能拾取user_id是0的任务，需lock成功后才能pickup，pickup成功后释放lock
    public function pickup($task_instance_id, $user_id) {
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('user_id' => $user_id, 'lock_time' => time()), array('id' => $task_instance_id, 'user_id' => 0, 'is_locked' => $user_id));
        return $this->db->affected_rows() > 0;
    }

    public function giveup($task_instance_id, $user_id) {
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('user_id' => 0, 'is_locked' => 0), array('id' => $task_instance_id, 'user_id' => $user_id));
        return $this->db->affected_rows() > 0;
    }

    public function assign($task_instance_id, $user_id, $to_user_id) {
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('user_id' => $to_user_id, 'is_locked' => $to_user_id), array('id' => $task_instance_id, 'user_id' => $user_id));
        return $this->db->affected_rows() > 0;
    }

    //锁定后续任务，并删除，才能成功取回
    public function fetch($task_instance_id) {
        $this->load->model('m_item_instance');
        $detail = $this->detail($task_instance_id);
        $nexts = explode(',', $detail->next);
        $lock = $this->lock($nexts, $detail->user->user_id);
        if (!$lock) {
            return false;
        }
        foreach ($nexts as $k => $v) {
            $this->delete($v);
        }
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('status' => m_item_instance::STATUS_ITEM_INSTANCE_START), array('id' => $task_instance_id));
        return $this->db->affected_rows() > 0;
    }

    //完成任务，此时是否要推动下一步任务
    public function complete($task_instance_id) {
        $this->db->update(TABLE_PROCESS_ITEM_INSTANCE, array('status' => m_item_instance::STATUS_ITEM_INSTANCE_COMPLETE, 'has_completed' => 1, 'complete_time' => time()), array('id' => $task_instance_id));
        return $this->db->affected_rows() > 0;
    }

}
