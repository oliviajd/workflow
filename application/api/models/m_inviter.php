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
class m_inviter extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        return $this->_detail($id);
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
        $detail = $this->db->get_where('v1_manager', array('user_id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    //以找到客户经理 或者邀请人为空
    public function find_parents($user_id, &$parents = array()) {
        $inviter = $this->find_parent($user_id);
        if ($inviter['manager_user_id'] > 0) {//有客户经理的时候，返回客户经理ID
            $parents[] = $inviter['manager_user_id'];
            return $parents;
        } else {
            if ($inviter['inviter_user_id'] > 0) {//没有客户经理，但有邀请人的时候，查找邀请人的父级
                $parents[] = $inviter['inviter_user_id'];
                return $this->find_parents($inviter['inviter_user_id'], $parents);
            } else {//没有邀请人且没有客户经理的时候，返回
                return $parents;
            }
        }
    }

    public function find_parent($user_id) {
        $user = $this->db->get_where(TABLE_USER_INFO, array('user_id' => $user_id))->row_array();
        return array(
            'inviter_user_id' => $user['invite_userid'] == '7088' ? 0 : $user['invite_userid'],
            'manager_user_id' => intval($user['mer_userid']),
            'owner_user_id' => intval($user['mer_userid'] ? $user['mer_userid'] : ($user['invite_userid'] == '7088' ? 0 : $user['invite_userid'])),
        );
    }

    public function is_without($user_id) {
        return intval($this->db->get_where('v1_manager_without', array('user_id' => $user_id))->row(0)->id) > 0;
    }
    
    public function is_manager($user_id) {
        return intval($this->db->get_where('v1_manager', array('user_id' => $user_id))->row(0)->id) > 0;
    }

    public function set_manager($user_id, $manager_user_id) {
        return $this->db->update(TABLE_USER_INFO, array('mer_userid' => $manager_user_id), array('user_id' => $user_id));
    }

    //分配客户经理
    public function assign_manager($user_id) {
        //查看是否需要补人数的客户经理
        $this->db->limit(1);
        $this->db->order_by('id', 'asc');
        $row1 = $this->db->get_where('v1_manager_to_add', array('invited_user_id' => 0))->row(0);
        if (!empty($row1)) {
            //直接分配给缺人的客户经理
            $this->db->update('v1_manager_to_add', array('invited_user_id' => $user_id, 'finish_time' => time()), array('id' => $row1->id, 'invited_user_id' => 0));
            $success_assign = $this->db->affected_rows() > 0;
            if (!$success_assign) {
                return false;
            }
            $manager_user_id = $row1->user_id;
        } else {
            //按顺序分配给客服
            $current = $this->db->get_where('v1_manager', array('is_current' => 1))->row(0);
            if (empty($current)) {
                //其他进程正在处理中
                return false;
            }
            //乐观锁
            $this->db->update('v1_manager', array('is_current' => 2), array('id' => $current->id, 'is_current' => 1));
            $success_locked = $this->db->affected_rows() > 0;
            if (!$success_locked) {
                return false;
            }
            //移动指针
            $this->db->update('v1_manager', array('is_current' => 1), array('id' => $current->next_id, 'is_current' => 2));
            $success_moved = $this->db->affected_rows() > 0;
            if (!$success_moved) {
                return false;
            }
            $manager_user_id = $current->user_id;
        }
        $this->db->update(TABLE_USER_INFO, array('mer_userid' => $manager_user_id), array('user_id' => $user_id, 'mer_userid' => 0));
        return $this->db->affected_rows() > 0 ? $manager_user_id : false;
    }

    //修改客户经理
    public function change_manager($user_id, $manager_user_id) {
        $user = $this->db->get_where(TABLE_USER_INFO, array('user_id' => $user_id))->row(0);
        if ($user->mer_userid > 0 && $this->detail($user->mer_userid)) {
            $this->db->insert('v1_manager_to', array(
                'user_id' => $user->mer_userid,
                'remove_user_id' => $user_id,
                'to_manager_user_id' => $manager_user_id,
            ));
        }
        $this->db->update(TABLE_USER_INFO, array('mer_userid' => $manager_user_id), array('user_id' => $user_id,'mer_userid'=>$user->mer_userid));
        return $this->db->affected_rows() > 0 ? $manager_user_id : false;
    }

}
