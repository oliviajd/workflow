<?php

/**
 * 反馈模型
 *
 *
 */
class m_feedback extends CI_Model{
    
    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['content'] = trim($data['content']);
        $param['pics'] = trim($data['pics']);
        $param['contact'] = trim($data['contact']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_FEEDBACK, $param);
        $pid = $this->db->insert_id();
        return $pid;
    }

    public function update($id,$data) {
        /*if ($data['store']) {
            $this->_set_memcache_store($id, 0);
        }*/
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'activity_id':
                    $param['activity_id'] = intval($data['activity_id']);
                    break;
                case 'cid':
                    $param['cid'] = intval($data['cid']);
                    break;
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'rate':
                    $param['rate'] = intval($data['rate']);
                    break;
                case 'background'://这参数有个'#'号开头，URL中无法直接传递，需要前端在传递参数前转码，例如encodeURIComponent('#123')
                    $param['background'] = trim($data['background']);
                    break;
                case 'rate':
                    $param['rate'] = intval($data['rate']);
                    break;
                case 'store':
                    $param['store'] = intval($data['store']);
                    break;
                case 'iid':
                    $param['iid'] = intval($data['iid']);
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->where('pid', $id);
        $this->db->update(TABLE_PRIZE,$param);
        /*if ($param['store']) {
            //将库存设为正常值，开放售卖
            $this->_set_memcache_store($id, $param['store']);
        }*/
        return $this->db->affected_rows() > 0;

    }

    public function decrease($pid, $num) {
        $this->load->model('m_prize');
        //开始事务
        $this->db->trans_start();
        $prize_store = $this->db->query("SELECT store FROM " . TABLE_PRIZE . " WHERE pid = " . intval($pid) . " LIMIT 1 FOR UPDATE")->row(0)->store;

        if (intval($prize_store) < abs($num)) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->set('store', 'store - ' . intval($num), false);
        $this->db->update(TABLE_PRIZE, array(), array('pid' => $pid));

        $this->db->trans_complete();
        return true;

    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_feedback($detail);
        }
        //
    }

    public function lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get(TABLE_FEEDBACK)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = new obj_feedback($v);
        }
        return $rows;
    }

    public function _condition($condition) {
        if ($condition['id']) {
            $this->db->where('id', $condition['id']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_FEEDBACK, array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function delete($id) {
        $this->db->update(TABLE_PRIZE, array('is_delete' => STATUS_HAS_DELETE), array('pid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_FEEDBACK)->row(0)->count;
    }


}
?>