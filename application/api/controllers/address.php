<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of address
 *
 * @author win7
 */
class address extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_address');
    }

    public function add() {
        $param = $this->api->in;
        $param['user_id'] = $this->api->user()->user_id;

        //地址数量超出限制
        if ($this->m_address->count(array('user_id' => $param['user_id'])) >= 10) {
            $this->api->output(false, ERR_OVER_ADDRESS_LIMIT_NO, ERR_OVER_ADDRESS_LIMIT_MSG);
        }

        //判断是否为中文姓名
        if (!is_chinese_name($param['name'])) {
            $this->api->output(false, ERR_NOT_CHINESE_NAME_NO, ERR_NOT_CHINESE_NAME_MSG);
        }

        //判断手机号码是否有效
        if (!is_mobile($param['mobile'])) {
            $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
        }

        //判断地区信息是否有效
        if ($param['province_id']) {
            $province = $this->m_address->get_area($param['province_id'], 0);
            if (!$province) {
                $this->api->output(false, ERR_AREA_NOT_MATCH_NO, ERR_AREA_NOT_MATCH_MSG);
            }
            $param['province'] = $province['name'];

            $city = $this->m_address->get_area($param['city_id'], $param['province_id']);
            if (!$city) {
                $this->api->output(false, ERR_AREA_NOT_MATCH_NO, ERR_AREA_NOT_MATCH_MSG);
            }
            $param['city'] = $city['name'];

            $country = $this->m_address->get_area($param['country_id'], $param['city_id']);
            if (!$country) {
                $this->api->output(false, ERR_AREA_NOT_MATCH_NO, ERR_AREA_NOT_MATCH_MSG);
            }
            $param['country'] = $country['name'];
        }

        $address_id = $this->m_address->add($param);
        $detail = $this->m_address->detail($address_id);
        $this->api->output($detail);
    }

    public function update() {
        $address_id = $this->api->in['address_id'];
        $param = $this->api->in;

        $old = $this->m_address->detail($address_id);
        if (empty($old)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($old->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }

        if ($param['name']) {
            //判断是否为中文姓名
            if (!is_chinese_name($param['name'])) {
                $this->api->output(false, ERR_NOT_CHINESE_NAME_NO, ERR_NOT_CHINESE_NAME_MSG);
            }
        }

        if ($param['mobile']) {
            //判断手机号码是否有效
            if (!is_mobile($param['mobile'])) {
                $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
            }
        }

        if ($param['province_id']) {
            //判断地区信息是否有效
            $province = $this->m_address->get_area($param['province_id'], 0);
            if (!$province) {
                $this->api->output(false, ERR_AREA_NOT_MATCH_NO, ERR_AREA_NOT_MATCH_MSG);
            }
            $param['province'] = $province['name'];

            $city = $this->m_address->get_area($param['city_id'], $param['province_id']);
            if (!$city) {
                $this->api->output(false, ERR_AREA_NOT_MATCH_NO, ERR_AREA_NOT_MATCH_MSG);
            }
            $param['city'] = $city['name'];

            $country = $this->m_address->get_area($param['country_id'], $param['city_id']);
            if (!$country) {
                $this->api->output(false, ERR_AREA_NOT_MATCH_NO, ERR_AREA_NOT_MATCH_MSG);
            }
            $param['country'] = $country['name'];
        }

        $this->m_address->update($address_id, $param);
        $detail = $this->m_address->detail($address_id);
        $this->api->output($detail);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'is_default asc,id asc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_address->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_address->count($condition);
        $this->api->output($r);
    }

    public function delete() {
        $detail = $this->m_address->detail($this->api->in['address_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        $r = $this->m_address->delete($this->api->in['address_id']);
        $this->api->output($r);
    }

}
