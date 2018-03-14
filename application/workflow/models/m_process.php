<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_process
 *
 * @author win7
 */
class m_process extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_process($detail);
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
        $detail = $this->db->get_where(TABLE_PROCESS, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function get_extends_keys($id) {
        $r = $this->db->get_where('INFORMATION_SCHEMA.COLUMNS', array(
                    'table_name' => TABLE_PROCESS_EXTENDS . intval($id),
                ))->result_array();
        $result = array();
        foreach ($r as $k => $v) {
            $result[$v['COLUMN_NAME']] = $v['DATA_TYPE'];
        }
        return $result;
    }

    public function get_extends_value($id, $process_instance_id) {
        $r = $this->db->get_where(TABLE_PROCESS_EXTENDS . intval($id), array(
                    'process_instance_id' => $process_instance_id
                ))->row_array(0);
        return empty($r) ? false : $r;
    }

    public function set_extends_value($id, $process_instance_id, $data) {
        $exists = $this->get_extends_value($id, $process_instance_id);
        if (!$exists) {
            $data['process_id'] = $id;
            $data['process_instance_id'] = $process_instance_id;
            $this->db->insert(TABLE_PROCESS_EXTENDS . intval($id), $data);
        } else {
            $data['process_id'] = $id;
            $data['process_instance_id'] = $process_instance_id;
            $this->db->update(TABLE_PROCESS_EXTENDS . intval($id), $data, array('process_instance_id' => $process_instance_id));
        }
    }

}
