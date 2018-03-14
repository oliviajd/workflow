<?php

/**
 * 商品模型
 *
 *
 */
class m_goods extends CI_Model {

    const STATUS_GOODS_ON = 1;
    const STATUS_GOODS_OFF = 2;
    const STATUS_GOODS_INIT = 3;

    private $_memcache_store_prepare = 1000000; //用memcache做库存数增减时的预备量，memcache 超减时会返回0，无法判断库存是否足够

    public function __construct() {
        parent::__construct();
    }

    public function add($data) {
        $param['cid'] = intval($data['cid']);
        $param['title'] = trim($data['title']);
        $param['title_small'] = trim($data['title_small']);
        $param['price'] = intval($data['price']);
        $param['price_retail'] = intval($data['price_retail']);
        $param['pic'] = array();
        $pics = explode(',', $data['pic']);
        foreach ($pics as $k2 => $v2) {
            $param['pic'][] = trim(parse_url($v2, PHP_URL_PATH));
        }
        $param['pic'] = implode(',', $param['pic']);
        $param['pic_small'] = trim(parse_url($data['pic_small'], PHP_URL_PATH));
        $param['pic_large'] = trim(parse_url($data['pic_large'], PHP_URL_PATH));

        $param['option'] = trim($data['option']);
        $param['desc'] = trim($data['desc']);
        $param['desc_exchange_process'] = trim($data['desc_exchange_process']);
        $param['desc_important_note'] = trim($data['desc_important_note']);
        $param['store'] = max(intval($data['store']), 0);
        $param['limit'] = max(intval($data['limit']), 0);
        $param['limit_on_time'] = intval($data['limit_on_time']);
        $param['limit_off_time'] = intval($data['limit_off_time']);
        $param['is_rec'] = max(intval($data['is_rec']), 0);

        $param['status'] = $data['status'];

        $param['create_time'] = time();
        $this->db->insert(TABLE_GOODS, $param);
        $iid = $this->db->insert_id();
        $this->_set_memcache_store($iid, $param['store']);
        return $iid;
    }

    public function update($id, $data) {
        if ($data['store']) {
            //将库存设为0，防止在修改库存时产生订单
            $this->_set_memcache_store($id, 0);
        }
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'cid':
                    $param['cid'] = intval($data['cid']);
                    break;
                case 'title':
                    $param['title'] = trim($data['title']);
                    break;
                case 'title_small':
                    $param['title_small'] = trim($data['title_small']);
                    break;
                case 'price':
                    $param['price'] = intval($data['price']);
                    break;
                case 'price_retail':
                    $param['price_retail'] = intval($data['price_retail']);
                    break;
                case 'pic':
                    $param['pic'] = array();
                    $pics = explode(',', $data['pic']);
                    foreach ($pics as $k2 => $v2) {
                        $param['pic'][] = trim(parse_url($v2, PHP_URL_PATH));
                    }
                    $param['pic'] = implode(',', $param['pic']);
                    break;
                case 'pic_small':
                    $param['pic_small'] = trim(parse_url($data['pic_small'], PHP_URL_PATH));
                    break;
                case 'pic_large':
                    $param['pic_large'] = trim(parse_url($data['pic_large'], PHP_URL_PATH));
                    break;
                case 'desc':
                    $param['desc'] = trim($data['desc']);
                    break;
                case 'desc_exchange_process':
                    $param['desc_exchange_process'] = trim($data['desc_exchange_process']);
                    break;
                case 'desc_important_note':
                    $param['desc_important_note'] = trim($data['desc_important_note']);
                    break;
                case 'option':
                    $param['option'] = trim($data['option']);
                    break;
                case 'store':
                    $param['store'] = max(intval($data['store']), 0);
                    break;
                case 'limit':
                    $param['limit'] = max(intval($data['limit']), 0);
                    break;
                case 'limit_on_time':
                    $param['limit_on_time'] = intval($data['limit_on_time']);
                    break;
                case 'limit_off_time':
                    $param['limit_off_time'] = intval($data['limit_off_time']);
                    break;
                case 'is_rec':
                    $param['is_rec'] = max(intval($data['is_rec']), 0);
                    break;
                case 'status':
                    if (in_array(intval($data['status']), array(self::STATUS_GOODS_INIT, self::STATUS_GOODS_OFF, self::STATUS_GOODS_ON))) {
                        $param['status'] = intval($data['status']);
                    }
                    break;
                default:
                    break;
            }
        }
        $param['modify_time'] = time();
        $this->db->update(TABLE_GOODS, $param, array('iid' => $id));
        if ($param['store']) {
            //将库存设为正常值，开放售卖
            $this->_set_memcache_store($id, $param['store']);
        }
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $this->load->model('m_goods_category');
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['status'] = $this->get_goods_status($detail['status']);
            $detail['category'] = $this->m_goods_category->detail($detail['cid']);
            $detail['pic'] = explode(',', $detail['pic']);
            $detail['desc_other'] = new obj_goods_desc_other(array(
                'exchange_process' => $detail['desc_exchange_process'],
                'important_note' => $detail['desc_important_note'],
            ));
            return new obj_goods($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->db->select(TABLE_GOODS . '.*');
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_GOODS)->result_array();
        $this->load->model('m_goods_category');
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_goods_status($v['status']);
            $v['category'] = $this->m_goods_category->detail($v['cid']);
            $v['pic'] = explode(',', $v['pic']);
            $rows[$k] = new obj_goods($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_GOODS)->row(0)->count;
    }

    public function delete($id) {
        $this->db->update(TABLE_GOODS, array('is_delete' => STATUS_HAS_DELETE), array('iid' => $id));
        return $this->db->affected_rows() > 0;
    }

    private function _condition($condition) {
        if ($condition['cid']) {
            is_array($condition['cid']) ? $this->db->where_in('cid', $condition['cid']) : $this->db->where('cid', $condition['cid']);
        }
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
        if ($condition['q']) {
            $q = trim($condition['q']);
            '' . intval($q) . '' === $q ? $this->db->where('iid', $q) : $this->db->like('title', $q, 'both');
        }
        if ($condition['zone_id']) {
            $this->db->join(TABLE_GOODS_ZONE_ITEM, TABLE_GOODS . '.iid = ' . TABLE_GOODS_ZONE_ITEM . '.iid', 'INNER');
            $this->db->where(TABLE_GOODS_ZONE_ITEM . '.zone_id', intval($condition['zone_id']));
            $this->db->where(TABLE_GOODS_ZONE_ITEM . '.is_delete', STATUS_NOT_DELETE);
        }
        $this->db->where(TABLE_GOODS . '.is_delete', STATUS_NOT_DELETE);
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_GOODS, array('iid' => $id, 'is_delete' => STATUS_NOT_DELETE))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    private function _set_memcache_store($id, $value, $cover = true) {//$cover =true 默认使用set方法覆盖
        $this->load->library('cache_memcache');
        $key = 'GOODS_STORE_' . $id;
        if ($cover) {
            return $this->cache_memcache->set($key, intval($value) + $this->_memcache_store_prepare, 0);
        } else {
            return $this->cache_memcache->add($key, intval($value) + $this->_memcache_store_prepare, 0);
        }
    }

    public function on($id) {
        $this->db->update(TABLE_GOODS, array('status' => self::STATUS_GOODS_ON), array('iid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function off($id) {
        $this->db->update(TABLE_GOODS, array('status' => self::STATUS_GOODS_OFF), array('iid' => $id));
        return $this->db->affected_rows() > 0;
    }

    //利用memcache的原子操作减库存
    public function decrease($id, $num) {
        $this->load->library('cache_memcache');
        $key = 'GOODS_STORE_' . $id;
        $store = $this->cache_memcache->get($key);
        //未发现记录，则同步库存到缓存
        if ($store === false) {
            $detail = $this->_detail($id);
            if (intval($detail['store']) <= 0) {
                return false;
            }
            $this->_set_memcache_store($id, $detail['store'], false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->decrement($key, $num);
        if (!$r) {
            return false;
        }
        if ($r >= $this->_memcache_store_prepare) {//减掉用户购买量后库存不小于预备量，则成功减库存
            $this->db->set('store', 'store - ' . intval($num), false);
            $this->db->update(TABLE_GOODS, array(), array('iid' => $id));
            return true;
        } else {
            $this->cache_memcache->increment($key, $num); //将预减的库存数加回来
            return false;
        }
    }

    //利用memcache的原子操作减库存
    public function increase($id, $num) {
        $this->load->library('cache_memcache');
        $key = 'GOODS_STORE_' . $id;
        $store = $this->cache_memcache->get($key);
        //未发现记录，则同步库存到缓存
        if ($store === false) {
            $detail = $this->_detail($id);
//            if (intval($detail['store']) <= 0) {
//                return false;
//            }
            $this->_set_memcache_store($id, $detail['store'], false); //使用add而不用set，避免并发时覆盖
        }
        $r = $this->cache_memcache->increment($key, $num);
        if (!$r) {
            return false;
        }
        $this->db->set('store', 'store + ' . intval($num), false);
        $this->db->update(TABLE_GOODS, array(), array('iid' => $id));
        return true;
    }

    //更新商品售出数量
    public function increase_sold($id, $num) {
        $this->db->set('num_sold', 'num_sold + ' . intval($num), false);
        $this->db->update(TABLE_GOODS, array(), array('iid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function get_goods_status($key = false) {
        $data = array(
            1 => '已上架',
            2 => '已下架',
            3 => '未上架',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'GOODS_ERROR', 'text' => '商品状态错误');
    }

    public function get_zones($id) {
        $this->load->model('m_goods_zone');
        $rows = $this->db->get_where(TABLE_GOODS_ZONE_ITEM, array('iid' => intval($id), 'is_delete' => STATUS_NOT_DELETE))->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k] = $this->m_goods_zone->detail($v['zone_id']);
        }
        return $rows;
    }

    public function set_zones($id, $zone_ids) {
        $this->load->model('m_goods_zone');
        $i = 0;
        foreach ($zone_ids as $k => $v) {
            $this->m_goods_zone->item_add($v, array($id));
            $i++;
        }
        return $i;
    }

}
