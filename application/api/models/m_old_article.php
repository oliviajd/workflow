<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_old_article
 *
 * @author win7
 */
class m_old_article extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_old_article($detail);
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
        $rows = $this->db->get_where(TABLE_OLD_ARTICLE)->result_array();
        foreach ($rows as $k => $v) {
            if($v['type_id'] == 42){
                $v['url'] = '/?huodong&nid='.$v['nid'].'&type=42&article_id='.$v['id'];
            }
            if($v['type_id'] == 43){
                $v['url'] = '/?qcjr&nid='.$v['nid'].'&type=43&article_id='.$v['id'];
            }
            
            if($v['type_id'] == 47){
                $v['url'] = '/?hyxw&nid='.$v['nid'].'&type=44&article_id='.$v['id'];
            }
            $rows[$k] = new obj_old_article($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_OLD_ARTICLE)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['news_center'] == 1) {
            $this->db->where_in('type_id', array(43,47));
        }
        if ($condition['type_id']) {
            is_array($condition['type_id']) ? $this->db->where_in('type_id', $condition['type_id']) : $this->db->where('type_id', $condition['type_id']);
        }
        
    }
    
    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_ARTICLE, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

}
