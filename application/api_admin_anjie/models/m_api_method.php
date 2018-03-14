<?php

/**
 * Description of m_api_method
 * 方法接口 model
 * @author wangyuan
 */
class m_api_method extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $database = $_COOKIE['database'] ? $_COOKIE['database'] : 'localhost';
        $this->db = $this->load->database($database, true);
        // $this->load->library('cache');
    }

    /**
     * [apiv2] method detail
     */
    public function api_method_detail($arr)
    {
        $this->db->where('method_id', $arr['method_id']);
        $r = $this->db->get_where(TABLE_API2_METHOD)->result_array();
        
        return $r[0];
    }

    /**
     * [apiv2] 类别
     */
    public function api_method_list($config)
    {
        // select
        if (isset($config['select'])) {
            $this->db->select(implode(',', $config['select']));
        }
        
        // where
        if (isset($config['where']) && ! empty($config['where'])) {
            foreach ($config['where'] as $k => $v) {
                $this->db->where($k, $v);
            }
        }
        
        // where_in
        if (isset($config['where_in']) && ! empty($config['where_in'])) {
            foreach ($config['where_in'] as $k => $v) {
                $this->db->where_in($k, $v);
            }
        }
        $this->db->order_by('method_name_en', 'ASC');
        // order_by
        if (isset($config['order_by']) && ! empty($config['order_by'])) {
            foreach ($config['order_by'] as $k => $v) {
                $this->db->order_by($k, $v);
            }
        }
        
        $r = $this->db->get_where(TABLE_API2_METHOD)->result_array();
        // echo $this->db->last_query();exit;
        return $r;
    }

    /**
     * api method delete
     */
    public function api_method_delete($arr)
    {
        if (! empty($arr)) {
            if (isset($arr['method_id']))
                $this->db->where_in('method_id', $arr);
            
            $this->db->delete(TABLE_API2_METHOD);
            
            return $this->db->affected_rows() ? 1 : 0;
        }
        return 0;
    }

    /**
     * api method update
     */
    public function api_method_update($arr)
    {
        if (! empty($arr)) {
            if (isset($arr['method_id'])) {
                $method_id = $arr['method_id'];
                unset($arr['method_id']);
                $arr['modify_time'] = time();
                $r = $this->db->update(TABLE_API2_METHOD, $arr, array(
                    'method_id' => $method_id
                ));
            } else {
                $arr['create_time'] = time();
                $this->db->insert(TABLE_API2_METHOD, $arr);
                $r = $this->db->insert_id();
            }
            
            return $r;
        }
        
        return 0;
    }

    /**
     * api method is exists
     */
    public function api_method_is_exists($arr)
    {
        $this->db->select('method_id');
        $this->db->where('cate_name', $arr['cate_name']);
        $this->db->where('cate_type', $arr['cate_type']);
        $this->db->get_where(TABLE_API2_METHOD);
        
        return $this->db->affected_rows() ? 1 : 0;
    }
}

?>
