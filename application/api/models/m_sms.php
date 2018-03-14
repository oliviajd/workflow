<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author win7
 */
class m_sms extends CI_Model implements ObjInterface {

    const STATUS_SEND_SUCCESS = 1;
    const STATUS_SEND_FAILED = 2;
    const STATUS_SEND_ING = 3;
    const STATUS_SEND_INIT = 5;

    public function add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['mobile'] = trim($data['mobile']);
        $param['content'] = trim($data['content']);
        $param['status'] = self::STATUS_SEND_INIT;

        $param['create_time'] = time();
        $this->db->insert(TABLE_QUEUE_SMS, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_QUEUE_SMS)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_sms_status($v['status']);
            $rows[$k] = new obj_sms($v);
        }
        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_QUEUE_SMS, array('id' => $id))->result_array();
        if (empty($detail)) {
            return false;
        } else {
            return $detail;
        }
    }

    public function send_start($id) {
        $this->db->update(TABLE_QUEUE_SMS, array('status' => self::STATUS_SEND_ING, 'send_time' => time()), array('id' => $id));
        return $this->db->affected_rows();
    }

    public function send($mobile, $content) {
        $this->load->config('myconfig');
        $config = $this->config->item('sms');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $config['url']);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array(
            'account' => $config['account'],
            'password' => $config['password'],
            'mobile' => $mobile,
            'content' => $content,
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // php version >= 5.5 需要 传filesize

        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        $xml = simplexml_load_string($result);
        if ($error) {
            do_log('SEND_SMS_ERROR:', $error);
            return false;
        } else {
            return json_decode(json_encode($xml), true);
        }
    }

    public function send_success($id, $result) {
        $this->db->update(TABLE_QUEUE_SMS, array('status' => self::STATUS_SEND_SUCCESS, 'result_query' => json_encode($result)), array('id' => $id, 'status' => self::STATUS_SEND_ING));
        return $this->db->affected_rows();
    }

    public function send_failed($id, $result) {
        $this->db->update(TABLE_QUEUE_SMS, array('status' => self::STATUS_SEND_FAILED, 'result_query' => json_encode($result)), array('id' => $id, 'status' => self::STATUS_SEND_ING));
        return $this->db->affected_rows();
    }

    public function get_sms_status($key = false) {
        $data = array(
            self::STATUS_SEND_SUCCESS => '成功',
            self::STATUS_SEND_FAILED => '失败',
            self::STATUS_SEND_ING => '发送中',
            self::STATUS_SEND_INIT => '初始化',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'SMS_ERROR', 'text' => '短消息状态错误');
    }

}
