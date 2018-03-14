<?php

/**
 * 极验验证类
 *
 */
class Geetest extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_geetest');
    }

    public function start_captcha() {
        $r = $this->m_geetest->start_captcha($this->api->in['type']);
        $this->api->output($r);
    }
    
    public function verify_login() {
        $r = $this->m_geetest->verify_login($this->api->in['type'],$this->api->in['geetest_challenge'],$this->api->in['geetest_validate'],$this->api->in['geetest_seccode']);
        $this->api->output($r);
    }
    
    
}
