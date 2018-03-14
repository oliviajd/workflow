<?php

/**
 * 奖品模型
 *
 *
 */
class m_prize extends CI_Model{

    const STATUS_PRIZE_ON = 1;
    const STATUS_PRIZE_OFF = 2;
    const STATUS_PRIZE_INIT = 3;

    private $_memcache_store_prepare = 1000000; //用memcache做库存数增减时的预备量，memcache 超减时会返回0，无法判断库存是否足够

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['activity_id'] = $data['activity_id'];
        $param['title'] = $data['title'];
        $param['rate'] = intval($data['rate']);
        $param['background'] = $data['background'];
        $param['store'] = intval($data['store']);
        $param['cid'] = intval($data['cid']);
        $param['iid'] = intval($data['iid']);
        $param['activity_id'] = intval($data['activity_id']);
        $param['create_time'] = time();
        $this->db->insert(TABLE_PRIZE, $param);
        $pid = $this->db->insert_id();
        //$this->_set_memcache_store($pid,$param['store']);
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
        $this->load->model('m_goods_category');
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['category'] = $this->m_goods_category->detail($detail['cid']);
            return new obj_prize($detail);
        }
    }

    public function lists($condition) {
        $this->load->model('m_goods_category');
        $this->_condition($condition);
        $rows = $this->db->get(TABLE_PRIZE)->result_array();
        foreach ($rows as $k => $v) {
            $v['category'] = $this->m_goods_category->detail($v['cid']);
            $rows[$k] = new obj_prize($v);
        }
        return $rows;
    }

    public function _condition($condition) {
        if ($condition['activity_id']) {
            $this->db->where('activity_id', $condition['activity_id']);
        }
        $this->db->where(TABLE_PRIZE . '.is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_PRIZE, array('pid' => $id, 'is_delete' => STATUS_NOT_DELETE))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function delete($id) {
        $this->db->update(TABLE_PRIZE, array('is_delete' => STATUS_HAS_DELETE), array('pid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_PRIZE)->row(0)->count;
    }


}
?>