<?php

/**
 * 极验验证模型
 *
 *
 */
class m_geetest extends CI_Model {

    public function __construct() {
        parent::__construct();
        require_once APPPATH . 'libraries/class.geetestlib.php';
        session_start();
    }
    
    public function start_captcha($type){
        $this->load->config('myconfig');
        $geetest_config = $this->config->item('geetest');
        if($type == 'pc'){
            $GtSdk = new GeetestLib($geetest_config['captcha_id'], $geetest_config['private_key']);
        }elseif ($type == 'mobile') {
            $GtSdk = new GeetestLib($geetest_config['mobile_captcha_id'], $geetest_config['mobile_private_key']);
        }
        $user_id = "test";
        $status = $GtSdk->pre_process($user_id);
        
        $_SESSION['gtserver'] = $status;
        $_SESSION['user_id'] = $user_id;
        return json_decode($GtSdk->get_response_str());
    }
    
    public function verify_login($type,$geetest_challenge,$geetest_validate,$geetest_seccode){
        $this->load->config('myconfig');
        $geetest_config = $this->config->item('geetest');
        if($type == 'pc'){
            $GtSdk = new GeetestLib($geetest_config['captcha_id'], $geetest_config['private_key']);
        }elseif ($type == 'mobile') {
            $GtSdk = new GeetestLib($geetest_config['mobile_captcha_id'], $geetest_config['mobile_private_key']);
        }
        
        $user_id = $_SESSION['user_id'];
        if ($_SESSION['gtserver'] == 1) {   //服务器正常
            
            $result = $GtSdk->success_validate($geetest_challenge, $geetest_validate, $geetest_seccode, $user_id);
            if ($result) {
                return true;
            } else{
                return false;
            }
        }else{  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($geetest_challenge,$geetest_validate,$geetest_seccode)) {
                return true;
            }else{
                return false;
            }
        }
    }
    
    
}
