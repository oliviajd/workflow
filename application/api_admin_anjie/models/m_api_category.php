<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_api_category
 *
 * @author wangyuan
 */
class m_api_category extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		$database = $_COOKIE['database'] ? $_COOKIE['database'] : 'localhost';
        $this->db = $this->load->database($database, true);
        // $this->load->library('cache');
    }

    /**
     * [apiv2] category detail
     */
    public function api_category_detail($arr)
    {
        $this->db->where('id', $arr['id']);
        $r = $this->db->get_where(TABLE_API2_CATEGORY)->result_array();

        return $r[0];
    }

    /**
     * [apiv2] 类别
     */
    public function api_category_list($config)
    {
        // dump($this->db);exit;
        $this->db->select('id, cate_type, cate_name, desc, sort');

        if($config['cate_type']) {
            $this->db->where('cate_type', $config['cate_type']);
        }
        $this->db->order_by('sort', 'desc');
        $this->db->order_by('cate_name', 'asc');

        $r = $this->db->get_where(TABLE_API2_CATEGORY)->result_array();
        foreach($r as $k=>$v) {
            $this->db->select('count(1) as count');
            $r[$k]['children'] = $v['cate_type'] != 1 ? $this->db->get_where(TABLE_API2_FIELD,array('item_id'=>$v['id']))->row(0)->count : $this->db->get_where(TABLE_API2_METHOD,array('cid'=>$v['id']))->row(0)->count;
        }
        return $r;
    }

    /**
     * api category delete
     */
    public function api_category_delete($arr)
    {
        if(!empty($arr))
        {
            $this->db->where_in('id', $arr);
            $this->db->delete(TABLE_API2_CATEGORY);

            return $this->db->affected_rows() ? 1 : 0;
        }
        return 0;
    }

    /**
     * api category update 
     */
    public function api_category_update($arr)
    {
        if(!empty($arr))
        {
            if(isset($arr['id']))
            {
                $id = $arr['id'];
                unset($arr['id']);

                $r = $this->db->update(TABLE_API2_CATEGORY, $arr, array('id'=>$id));
            }
            else
            {
                $r = $this->db->insert(TABLE_API2_CATEGORY, $arr);
            }

            return $r ? 1 : 0;
        }

        return 0;
    }

    /**
     * api category is exists
     */
    public function api_category_is_exists($arr)
    {
        $this->db->select('id');
        $this->db->where('cate_name', $arr['cate_name']);
        $this->db->where('cate_type', $arr['cate_type']);
        $this->db->get_where(TABLE_API2_CATEGORY);

        return $this->db->affected_rows() ? 1 : 0;
    }

}

?>
