<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of borrow_process
 *
 * @author win7
 */
class borrow_process {

    private $queue = array();
    private $CI;

    public function __construct() {
        $this->CI = get_instance();
    }

    /*
     * data = array(
     *      'type'=>''
     *      'item'=>array
     * )
     */

    public function push($data) {
        $this->queue[] = $data;
    }

    public function clean() {
        if (empty($this->queue)) {
            return true;
        }
        foreach ($this->queue as $k => $v) {
            $type = strtolower(trim($v['type']));
            $item = $v['item'];
            switch ($type) {
                case 'borrow_decrease':
                    //加回预扣的项目金额
                    $this->CI->load->model('m_borrow');
                    $this->CI->m_borrow->increase($item['borrow_id'], $item['money']);
                    break;
                case 'account_lock':
                    //加回预扣的用户金额
                    $this->CI->load->model('m_account');
                    $this->CI->m_account->unlock($item['user_id']);
                    break;
                case 'bouns':
                    //还原预使用的红包
                    $this->CI->load->model('m_bouns');
                    $this->CI->m_bouns->merge_failed($item['user_id'], $item['bouns_user_id']);
                    break;
                case 'coupon':
                    //还原预使用的加息券
                    $this->CI->load->model('m_coupon');
                    $this->CI->m_coupon->use_unlock($item['coupon_lock_id']);
                    break;
            }
        }
        $this->CI->db->query('commit');
        return true;
    }

    public function to_string() {
        return json_encode($this->queue);
    }
    
    public function load_string($string) {
        $this->queue = json_decode($string,true);
        return $this;
    }

}
