<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_api_obj
 *
 * @author wangyuan
 */
class m_api_obj extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		$database = $_COOKIE['database'] ? $_COOKIE['database'] : 'localhost';
        $this->db = $this->load->database($database, true);
        // $this->load->library('cache');
    }

    /**
     * [api2] obj detail
     */
    public function api_obj_field_detail($condition)
    {
        return $this->db->from(TABLE_API2_FIELD)->where('field_id', $condition['field_id'])->get()->row_array(0);
    }

    /**
     * [apiv2] obj detail list
     */
    public function api_obj_detail_list($condition)
    {
        if(!$condition['select'])
        {
            $condition['select'] = array('field_id', 'item_id', 'obj_id', 'field_name', 'is_necessary', 'example', 'default_value', 'description', TABLE_API2_FIELD.'.sort');
        }
        $this->db->select($condition['select']);

        if($condition['where'])
        {
            foreach($condition['where'] as $k=>$v)
            {
                $this->db->where($k, $v);
            }
        }

        if($condition['order_by'])
        {
            foreach($condition['order_by'] as $k=>$v)
            {
                $this->db->order_by(TABLE_API2_FIELD.'.'.$k, $v);
            }
        }

        // dump($condition);
        if($condition['join'])
        {
            if(in_array('cate_name', $condition['join']))
            {
                $this->db->join(TABLE_API2_CATEGORY, TABLE_API2_FIELD.'.obj_id = '.TABLE_API2_CATEGORY.'.id', 'INNER');
                $this->db->select('cate_type, cate_name');
            }
        }

        return $this->db->get_where(TABLE_API2_FIELD)->result_array();
        // echo $this->db->last_query();exit;
    }

    /**
     * [apiv2] obj field update
     */
    public function api_obj_field_update($d)
    {
        if(isset($d['field_id']) && $d['field_id'] != '')
        { // 存在, update
            // $field_id = $d['field_id'];
            $r = $this->db->update(TABLE_API2_FIELD, $d, array('field_id'=>$d['field_id']));
            // var_dump($r);exit;
        }
        else
        { // 不存在 field_id save
            $this->db->insert(TABLE_API2_FIELD, $d);
            $r = $this->db->insert_id();
        }
        return $r;
    }

    /**
     * 删除 field 
     */
    public function api_obj_field_delete($d)
    {
        $this->db->where_in('field_id', $d['field_id']);
        $r = $this->db->delete(TABLE_API2_FIELD);
        return $r;
    }

}

?>
