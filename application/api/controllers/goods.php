<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of goods
 *
 * @author win7
 */
class goods extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_goods');
    }

    public function add() {
        //todo 验证cid是否有效

        $iid = $this->m_goods->add($this->api->in);
        $r = $this->m_goods->detail($iid);
        $this->api->output($r);
    }

    public function update() {
        //todo 如果传入了cid，判断cid是否有效
        $iid = $this->api->in['iid'];
        if (!$this->m_goods->detail($iid)) {
            $this->api->output(false, ERR_GOODS_NOT_EXISTS_NO, ERR_GOODS_NOT_EXISTS_MSG);
        }
        $this->m_goods->update($iid, $this->api->in);
        $r = $this->m_goods->detail($iid);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_goods->detail($this->api->in['iid']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_GOODS_NOT_EXISTS_NO, ERR_GOODS_NOT_EXISTS_MSG);
        }
    }

    public function delete() {
        $r = $this->m_goods->detail($this->api->in['iid']);
        if ($r) {
            $r2 = $this->m_goods->delete($this->api->in['iid']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_GOODS_NOT_EXISTS_NO, ERR_GOODS_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['cid']) {
            //查询子类ID
        }
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order']) {
            $order = 'is_rec desc,iid desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_goods->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_goods->count($condition);
        $this->api->output($r);
    }

    public function off() {
        $iid = $this->api->in['iid'];
        $detail = $this->m_goods->detail($iid);
        if (!$detail) {
            $this->api->output(false, ERR_GOODS_NOT_EXISTS_NO, ERR_GOODS_NOT_EXISTS_MSG);
        }
        $r = $this->m_goods->off($iid);
        $this->api->output($r);
    }

    public function on() {
        $iid = $this->api->in['iid'];
        $detail = $this->m_goods->detail($iid);
        if (!$detail) {
            $this->api->output(false, ERR_GOODS_NOT_EXISTS_NO, ERR_GOODS_NOT_EXISTS_MSG);
        }
        $r = $this->m_goods->on($iid);
        $this->api->output($r);
    }

    public function category_lists() {
        
    }

    public function get_zones() {
        $r['rows'] = $this->m_goods->get_zones($this->api->in['iid']);
        $this->api->output($r);
    }

    public function set_zones() {
        $this->load->model('m_goods_zone');
        $iid = $this->api->in['iid'];
        $exists = $this->m_goods->get_zones($iid);
        $new = explode(',', $this->api->in['zone_ids']);
        $add = array();
        foreach ($exists as $k => $v) {
            if (!in_array($v->zone_id, $new)) {
                $this->m_goods_zone->item_delete($v->zone_id, array($iid));
            } else {
                //todo 不重复set
            }
        }
        if (count($new) > 0) {
            $this->m_goods->set_zones($iid, $new);
        }
        $this->api->output(count($add));
    }

    public function zone_get() {
        $this->load->model('m_goods_zone');
        $r = $this->m_goods_zone->detail($this->api->in['zone_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function zone_add() {
        //重复名称的判断
        $this->load->model('m_goods_zone');
        if ($this->m_goods_zone->is_title_exists($this->api->in['title'])) {
            $this->api->output(false, ERR_ITEM_REPEAT_NO, "[title]" . ERR_ITEM_REPEAT_MSG);
        }
        $id = $this->m_goods_zone->add($this->api->in);
        $r = $this->m_goods_zone->detail($id);
        $this->api->output($r);
    }

    public function zone_update() {
        $this->load->model('m_goods_zone');
        $id = $this->api->in['zone_id'];
        if (!$this->m_goods_zone->detail($id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //重复名称的判断
        if ($this->api->in['title']) {
            $exists = $this->m_goods_zone->is_title_exists($this->api->in['title']);
            if (!empty($exists) && $id != $exists) {
                $this->api->output(false, ERR_ITEM_REPEAT_NO, "[title]" . ERR_ITEM_REPEAT_MSG);
            }
        }
        $this->m_goods_zone->update($id, $this->api->in);
        $r = $this->m_goods_zone->detail($id);
        $this->api->output($r);
    }

    public function zone_lists() {
        $this->load->model('m_goods_zone');
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'is_rec desc,id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $condition['status'] = m_goods_zone::STATUS_ZONE_OPEN;
        $r['rows'] = $this->m_goods_zone->lists($condition, $page = false, $size = false, $order);
        $r['total'] = $this->m_goods_zone->count($condition);
        $this->api->output($r);
    }

    public function zone_lists_admin() {
        $this->load->model('m_goods_zone');
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'is_rec desc,id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_goods_zone->lists($condition, $page = false, $size = false, $order);
        $r['total'] = $this->m_goods_zone->count($condition);
        $this->api->output($r);
    }

    public function zone_delete() {
        $this->load->model('m_goods_zone');
        $r = $this->m_goods_zone->detail($this->api->in['zone_id']);
        if ($r) {
            $r2 = $this->m_goods_zone->delete($this->api->in['zone_id']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function zone_item_add() {
        $this->load->model('m_goods_zone');
        if (!$this->m_goods_zone->detail($this->api->in['zone_id'])) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, "[zone:{$this->api->in['zone_id']}]" . ERR_ITEM_NOT_EXISTS_MSG);
        }
        $iids = explode(',', $this->api->in['iids']);
        foreach ($iids as $k => $v) {
            $goods = $this->m_goods->detail($v);
            if (!$goods) {
                $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, "[goods:{$v}]" . ERR_ITEM_NOT_EXISTS_MSG);
            }
        }
        $r = $this->m_goods_zone->item_add($this->api->in['zone_id'], $iids);
        $this->api->output($r);
    }

    public function zone_item_delete() {
        $this->load->model('m_goods_zone');
        if (!$this->m_goods_zone->detail($this->api->in['zone_id'])) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $r = $this->m_goods_zone->item_delete($this->api->in['zone_id'], explode(',', $this->api->in['iids']));
        $this->api->output($r);
    }

}
