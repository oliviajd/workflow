<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of wechat
 *
 * @author win7
 */
class wechat extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_wechat');
        $this->load->config('myconfig');
        $this->load->model('m_user');
        $this->load->library('wechat_lib');
    }

    public function web_oauth() {
        //TODO  输入参数设置
        if (isset($this->api->in['code']) && isset($this->api->in['state'])){ //code存在
            $code = $this->api->in['code'];
            $state = $this->api->in['state'];
            if($state){
               $config = $this->config->item('wechat');
               $access_token_obj = $this->wechat_lib->getOauthAccessToken($config['pc_appid'],$config['pc_secret'],$code);
               $access_token = $access_token_obj -> access_token;
               $openid = $access_token_obj -> openid;

               $userinfo_obj = $this->wechat_lib->getOauthUserInfo($access_token,$openid);
               
               $param['wx_unionid'] = $userinfo_obj->unionid;
               $userinfo_array = $this->m_wechat->detail($param);
               
               //用户登录
               if($userinfo_obj->unionid && $userinfo_array['wx_unionid']){     //用户已绑定微信
                   //记录COOKIE
                    $data['user_id'] = $userinfo_array['user_id'];
                    $data['email'] = $userinfo_array['mobile'];
                    $data['username'] = $userinfo_array['mobile'];
                    $data['password'] = $userinfo_array['password'];
                    $data['mobile'] = $userinfo_array['mobile'];
                    $data['cookie_status'] = 0;

                    $result = $this->initial_login($data);
               }
               else{
                   if($userinfo_obj->unionid){      //用户未绑定微信
                       /*setcookie('wx_unionid',$userinfo_obj->unionid,0,'/','.ifcar99.com');
                       setcookie('wx_openid',$userinfo_obj->openid,0,'/','.ifcar99.com');
                       setcookie('wx_nickname',$userinfo_obj->nickname,0,'/','.ifcar99.com');
                       setcookie('wx_headimgurl',$userinfo_obj->headimgurl,0,'/','.ifcar99.com');*/
                       setcookie('wx_unionid',$userinfo_obj->unionid,0,'/');
                       setcookie('wx_openid',$userinfo_obj->openid,0,'/');
                       setcookie('wx_nickname',$userinfo_obj->nickname,0,'/');
                       setcookie('wx_headimgurl',$userinfo_obj->headimgurl,0,'/');
                        $_url = WECHAT_HOST.'/wechat/bind.html';
                        echo '<script>window.location.href="'.$_url.'";</script>';
                        exit;
                   }
               }
                exit;
            }

        }else{  //没获取到code
            $_url = WECHAT_HOST;
            echo '<script>window.location.href="'.$_url.'";</script>';
            exit;
        }
    }
    
    

    
    public function get() {
        $condition = $this->api->in;
        //$unionid = $condition['unionid'] ? $condition['unionid'] : $unionid;
        $unionid = $condition['unionid'];
        //var_dump($this->api->in);
        $r = $this->m_wechat->detail($unionid);
        $this->api->output($r);
    }
    
    public function initial_login($data) {
        
        
        $user_id = isset($data['user_id'])?$data['user_id']:"";
        $username = isset($data['mobile'])?$data['mobile']:"";
        $password = isset($data['password'])?$data['password']:"";
        $email = isset($data['email'])?$data['email']:"";
        $mobile = isset($data['mobile'])?trim($data['mobile']):'';
        
       
        // 登录
        if ($this->m_user->is_loginname_exists($username)) {
            $db_user_id = $this->m_user->check($username, $password);
            if (!$db_user_id || $db_user_id != $user_id) {
                //登陆失败，记录日志
                $config = $this->config->item('msg');
                //加入用户操作记录
                $user_log["user_id"] = $user_id;
                $user_log["code"] = "users";
                $user_log["type"] = "action";
                $user_log["operating"] = "login";
                $user_log["article_id"] = $user_id;
                $user_log["result"] = 0;
                $user_log["content"] =  str_replace(array('#keywords#'), array($data['mobile']), $config["users_wechat_login_error_msg"]);
                $this->m_user->add_users_log($user_log);
            
                $this->api->output(false, ERR_WRONG_PASSWORD_NO, ERR_WRONG_PASSWORD_MSG);
            }
            /*if (!$this->m_user->check_from($user_id, $this->api->in['from'])) {
                $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
            }*/
            
            $user = $this->m_user->detail($user_id);
            if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
            }
            
            //登陆成功，记录日志
            //更新用户的登录信息
            
            $this->m_user->update_login_time($user_id);

            //加入用户操作记录
            $config = $this->config->item('msg');
            
            $user_log["user_id"] = $user_id;
            $user_log["code"] = "users";
            $user_log["type"] = "action";
            $user_log["operating"] = "login";
            $user_log["article_id"] = $user_id;
            $user_log["result"] = 1;
            $user_log["content"] =  $config["users_wechat_login_success_msg"];
            $result = $this->m_user->add_users_log($user_log);
            
            $token = $this->m_user->login($user_id, 'pc', '');
            
            /*$this->api->output(array(
                'user' => $user,
                'token' => $token
                    ), ERR_SUCCESS_NO, ERR_SUCCESS_MSG);*/
            if ($result>0){
                    //加入cookie
                    $data['user_id'] = $user_id;
                    $config = $this->config->item('cookie');
                    $data['cookie_status'] = $config['status']; 
                    $config = $this->config->item('td');
                    if ($config['status'] == 1){
                        $info = array(
                            "event_id" => "login_professional_web",
                            "token_id" => rand(1000000, 9999999),//此处填写设备指纹服务的会话标识，和部署设备脚本的token一致
                            "account_mobile"=>$data['mobile'],
                            "account_login" => $data['mobile'],
                            "ip_address" => ip_address(),
                            "state" => '1'
                        );
                        $td = $this->m_user->invoke_fraud_api($info);
                    }
                    session_start();
                    DelCookies($data);  //清除登陆的COOKIE或SESSION信息
                    SetCookies($data);  //记录登陆的COOKIE或SESSION信息
                    
                    /*setcookie('token',$token->token,0,'/','.ifcar99.com');
                    setcookie('uid',$user_id,0,'/','.ifcar99.com');*/
                    setcookie('token',$token->token,0,'/');
                    setcookie('uid',$user_id,0,'/');
                    //$_G["user_id"] = GetCookies(array("cookie_status"=>0));
                    
                    /*var_dump('test:');
                    var_dump($_G['user_id']);
                    var_dump($_SESSION);
                    exit;*/
                    if($_SESSION){
                        $_url = WECHAT_HOST.'/?user';
                        //设置前端cookie
                        echo '<script>
                        window.localStorage.setItem("user_nick", "'.$mobile.'");
                        window.localStorage.setItem("user_id", "'.$user_id.'");
                        window.location.href="'.$_url.'"; 
                        </script>';
                        //header("Location: ".$_url);
                        exit;
                    }
                    else{
                        $_url = WECHAT_HOST.'/?user';
                        echo '<script>
                        window.location.href="'.$_url.'"; 
                        </script>';
                        //header("Location: ".$_url);
                        exit;
                    }
                    
                    exit;
            }
            else{
                $config = $this->config->item('td');
                if ($config['status'] == 1){
                    $info = array(
                        "event_id" => "login_professional_web",
                        "token_id" => rand(1000000, 9999999),//此处填写设备指纹服务的会话标识，和部署设备脚本的token一致
                        "account_mobile"=>$data['mobile'],
                        "account_login" => $data['mobile'],
                        "ip_address" => ip_address(),
                        "state" => '0'
                    );
                    $td = $this->m_user->invoke_fraud_api($info);
                }
            }
            
        } else {
            $_url = WECHAT_HOST;
            echo '<script>
            window.location.href="'.$_url.'"; 
            </script>';
            //header("Location: ".$_url);
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }
    
    public function bind(){
        session_start();
        unset($condition);
        $condition['wx_unionid'] = $this->api->in['wx_unionid'];
        /*$condition['wx_openid'] = $this->api->in['wx_openid'];
        $condition['wx_nickname'] = $this->api->in['wx_nickname'];
        $condition['wx_headimgurl'] = $this->api->in['wx_headimgurl'];*/
        
        $r = $this->m_wechat->detail($condition);
        if(!$r){
            //判断验证码是否正确
            $mobile = $this->api->in['phone'];
            $mobilecode = $this->api->in['mobilecode'];
            $_mobilecode = md5($mobilecode.date('Ymdh'));
            if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                    $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                    exit;
            }
            
            unset($condition);
            $condition['username'] = $this->api->in['phone'];
            $r = $this->m_wechat->detail($condition);
            if(!$r){    //用户名不存在
                $this->api->output(false, ERR_WECHAT_MOBILE_NOT_EXISTS_NO, ERR_WECHAT_MOBILE_NOT_EXISTS_MSG);
            }
            else if(md5 ($this->api->in['password']) != $r['password']){  //登陆密码错误
                $this->api->output(false, ERR_WECHAT_PASSWORD_ERROR_NO, ERR_WECHAT_PASSWORD_ERROR_MSG);
            }
            else if(empty($r['wx_unionid'])){  //手机号未绑定
                $param['wx_unionid'] = $this->api->in['wx_unionid'];
                $param['wx_openid'] = $this->api->in['wx_openid'];
                $param['wx_nickname'] = $this->api->in['wx_nickname'];
                $param['wx_headimgurl'] = $this->api->in['wx_headimgurl'];
                $user_id = $r['user_id'];
                $r = $this->m_wechat->update($user_id,$param);
                if($r){
                    session_start();
                    
                    $cookie_config = $this->config->item('cookie');
                    $cookie_data['cookie_status'] = $cookie_config['status']; 
                    $cookie_data['user_id'] = $user_id; 
                    
                    DelCookies($cookie_data);  //清除登陆的COOKIE或SESSION信息
                    SetCookies($cookie_data);  //记录登陆的COOKIE或SESSION信息
                    //登陆，获取token
                    $user = $this->m_user->detail($user_id);
                    if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                        $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
                    }

                    //登陆成功，记录日志
                    //更新用户的登录信息

                    $this->m_user->update_login_time($user_id);

                    //加入用户操作记录
                    $config = $this->config->item('msg');

                    $user_log["user_id"] = $user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "action";
                    $user_log["operating"] = "login";
                    $user_log["article_id"] = $user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  $config["users_wechat_login_success_msg"];
                    $this->m_user->add_users_log($user_log);
                    
                    $token = $this->m_user->login($user_id, 'pc', 'pc');
                   
                    /*setcookie('token',$token->token,0,'/','.ifcar99.com');
                    setcookie('uid',$user_id,0,'/','.ifcar99.com');*/
                    setcookie('token',$token->token,0,'/');
                    setcookie('uid',$user_id,0,'/');
                    
                    $json_result->user_id = $user_id;
                    $json_result->username = $this->api->in['phone'];
                    //站内信提醒
                    $this->load->model('m_message');
                    $send_message = $this->m_message->send_admin(array(
                        'receiver_id' => $user_id,
                        'title' => '微信绑定成功',
                        'text' => '您于 '.date('Y-m-d H:i:s',time()).' 成功绑定微信',
                    ));
                    $this->api->output($json_result, ERR_WECHAT_BIND_SUCCESS_NO, ERR_WECHAT_BIND_SUCCESS_MSG);
                }
                else{
                    $this->api->output(false, ERR_WECHAT_BIND_FAIL_NO, ERR_WECHAT_BIND_FAIL_MSG);
                }
            }
            else{   //手机号已绑定
                $this->api->output(false, ERR_WECHAT_MOBILE_BINDED_NO, ERR_WECHAT_MOBILE_BINDED_MSG);
            }
        }
        else{
            $this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
        }
    }
    
    public function reg(){
        $this->load->model('m_user');
        session_start();
        $condition['wx_unionid'] = $this->api->in['wx_unionid'];
        $r = $this->m_wechat->detail($condition);
        if(!$r){    //该微信号还没有绑定
            if (!$this->m_user->is_loginname_exists($this->api->in['phone'])) {    //用户不存在
                //判断验证码是否正确
                $mobile = $this->api->in['phone'];
                $mobilecode = $this->api->in['mobilecode'];
                $_mobilecode = md5($mobilecode.date('Ymdh'));
                if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                        $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                }

                $param = array(
                    'mobile' => $this->api->in['phone'],
                    'password' => $this->api->in['password'],
                    'confirm_password' => $this->api->in['confirm_password'],
                    'invite_userid' => $this->api->in['invite_userid'],
                    'mobilecode' => $this->api->in['mobilecode'],
                    'wx_unionid' => $this->api->in['wx_unionid'],
                    'wx_openid' => $this->api->in['wx_openid'],
                    'wx_nickname' => $this->api->in['wx_nickname'],
                    'wx_headimgurl' => $this->api->in['wx_headimgurl']
                );
                //注册用户
                $user_id = $this->m_user->reg($param);
                if($user_id){   //注册并绑定微信成功，进行登录
                    $cookie_config = $this->config->item('cookie');
                    $cookie_data['cookie_status'] = $cookie_config['status']; 
                    $cookie_data['user_id'] = $user_id; 
                    
                    DelCookies($cookie_data);  //清除登陆的COOKIE或SESSION信息
                    SetCookies($cookie_data);  //记录登陆的COOKIE或SESSION信息
                    //登陆，获取token
                    $user = $this->m_user->detail($user_id);
                    if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                        $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
                    }
                    //登陆成功，记录日志
                    //更新用户的登录信息

                    $this->m_user->update_login_time($user_id);

                    //加入用户操作记录
                    $config = $this->config->item('msg');

                    $user_log["user_id"] = $user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "action";
                    $user_log["operating"] = "login";
                    $user_log["article_id"] = $user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  $config["users_wechat_login_success_msg"];
                    $this->m_user->add_users_log($user_log);
                    
                    $token = $this->m_user->login($user_id, 'pc', 'pc');
                   
                    /*setcookie('token',$token->token,0,'/','.ifcar99.com');
                    setcookie('uid',$user_id,0,'/','.ifcar99.com');*/
                    setcookie('token',$token->token,0,'/');
                    setcookie('uid',$user_id,0,'/');
                    //setcookie('token', $token, time() + 3600 * 24 * 7, '/');
                    
                    $json_result->user_id = $user_id;
                    $json_result->username = $this->api->in['phone'];
                    //站内信提醒
                    $this->load->model('m_message');
                    $send_message = $this->m_message->send_admin(array(
                        'receiver_id' => $user_id,
                        'title' => '微信绑定成功',
                        'text' => '您于 '.date('Y-m-d H:i:s',time()).' 成功绑定微信',
                    ));
                    $this->api->output($json_result, ERR_WECHAT_REG_BIND_SUCCESS_NO, ERR_WECHAT_REG_BIND_SUCCESS_MSG);
                }
            } else {    //用户已存在
                $this->api->output(false, ERR_LOGINNAME_EXISTS_NO, ERR_LOGINNAME_EXISTS_MSG);
            }
        
        }
        else{
            $this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
        }
    }
    
    public function mobile_oauth() {
        $param['wx_unionid'] = $this->api->in['unionid'];
        $userinfo_array = $this->m_wechat->detail($param);
        //用户登录
        if($this->api->in['unionid'] && $userinfo_array['wx_unionid']){ //用户已绑定微信
            $data['user_id'] = $userinfo_array['user_id'];
            $data['username'] = $userinfo_array['mobile'];
            $data['password'] = $userinfo_array['password'];
            $data['mobile'] = $userinfo_array['mobile'];
            $data['device'] = $this->api->in['device'];
            $data['cookie_status'] = 0;
            $result = $this->mobile_login($data);  //登陆
            
        }
        else{
            if($this->api->in['unionid']){      //用户未绑定微信
                $this->api->output(false, ERR_WECHAT_INFO_NOT_EXISTS_NO, ERR_WECHAT_INFO_NOT_EXISTS_MSG);
            }else{
                $this->api->output(false, ERR_FILED_NECESSARY_NO, ERR_FILED_NECESSARY_MSG);
            }
        }
        exit;
    }
    
    public function mobile_login($data) {
        
        
        $user_id = isset($data['user_id'])?$data['user_id']:"";
        $username = isset($data['mobile'])?$data['mobile']:"";
        $password = isset($data['password'])?$data['password']:"";
        $email = isset($data['email'])?$data['email']:"";
        $mobile = isset($data['mobile'])?trim($data['mobile']):'';
        $from = isset($data['from'])?trim($data['from']):'';
        $device = isset($data['device'])?trim($data['device']):'';
       
        // 登录
        if ($this->m_user->is_loginname_exists($username)) {    //该登陆用户名存在
            $db_user_id = $this->m_user->check($username, $password);
            if (!$db_user_id || $db_user_id != $user_id) {
                //登陆失败，记录日志
                $config = $this->config->item('msg');
                //加入用户操作记录
                $user_log["user_id"] = $user_id;
                $user_log["code"] = "users";
                $user_log["type"] = "action";
                $user_log["operating"] = "login";
                $user_log["article_id"] = $user_id;
                $user_log["result"] = 0;
                $user_log["content"] =  str_replace(array('#keywords#'), array($data['mobile']), $config["users_wechat_login_error_msg"]);
                $this->m_user->add_users_log($user_log);
            
                $this->api->output(false, ERR_WRONG_PASSWORD_NO, ERR_WRONG_PASSWORD_MSG);
            }
            /*if (!$this->m_user->check_from($user_id, $this->api->in['from'])) {
                $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
            }*/
            
            $user = $this->m_user->detail($user_id);
            if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
            }
            
            //登陆成功，记录日志
            //更新用户的登录信息
            
            $this->m_user->update_login_time($user_id);

            //加入用户操作记录
            $config = $this->config->item('msg');
            
            $user_log["user_id"] = $user_id;
            $user_log["code"] = "users";
            $user_log["type"] = "action";
            $user_log["operating"] = "login";
            $user_log["article_id"] = $user_id;
            $user_log["result"] = 1;
            $user_log["content"] =  $config["users_wechat_login_success_msg"];
            $result = $this->m_user->add_users_log($user_log);
            
            $token = $this->m_user->login($user_id, $from, $device);
            
            
            $this->api->output(array(
                'user' => $user,
                'token' => $token
                    ), ERR_SUCCESS_NO, ERR_SUCCESS_MSG);
            
        } else {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }
    
    public function mobile_bind(){
        session_start();
        unset($condition);
        $condition['wx_unionid'] = $this->api->in['wx_unionid'];
        $device = $this->api->in['device'];
        $from = $this->api->in['from'];
        /*$condition['wx_openid'] = $this->api->in['wx_openid'];
        $condition['wx_nickname'] = $this->api->in['wx_nickname'];
        $condition['wx_headimgurl'] = $this->api->in['wx_headimgurl'];*/
        
        $r = $this->m_wechat->detail($condition);
        if(!$r){
            //判断验证码是否正确
            $mobile = $this->api->in['phone'];
            $mobilecode = $this->api->in['mobilecode'];
            $_mobilecode = md5($mobilecode.date('Ymdh'));
            if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                    $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                    exit;
            }
            
            unset($condition);
            $condition['username'] = $this->api->in['phone'];
            $r = $this->m_wechat->detail($condition);
            if(!$r){    //用户名不存在
                $this->api->output(false, ERR_WECHAT_MOBILE_NOT_EXISTS_NO, ERR_WECHAT_MOBILE_NOT_EXISTS_MSG);
            }
            else if(md5 ($this->api->in['password']) != $r['password']){  //登陆密码错误
                $this->api->output(false, ERR_WECHAT_PASSWORD_ERROR_NO, ERR_WECHAT_PASSWORD_ERROR_MSG);
            }
            else if(empty($r['wx_unionid'])){  //手机号未绑定
                $param['wx_unionid'] = $this->api->in['wx_unionid'];
                $param['wx_openid_app'] = $this->api->in['wx_openid'];
                $param['wx_nickname'] = $this->api->in['wx_nickname'];
                $param['wx_headimgurl'] = $this->api->in['wx_headimgurl'];
                $user_id = $r['user_id'];
                $r = $this->m_wechat->update($user_id,$param);
                if($r){
                    //登陆，获取token
                    $user = $this->m_user->detail($user_id);
                    if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                        $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
                    }
                    
                    //登陆成功，记录日志
                    //更新用户的登录信息

                    $this->m_user->update_login_time($user_id);

                    //加入用户操作记录
                    $config = $this->config->item('msg');

                    $user_log["user_id"] = $user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "action";
                    $user_log["operating"] = "login";
                    $user_log["article_id"] = $user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  $config["users_wechat_login_success_msg"];
                    $this->m_user->add_users_log($user_log);
                    
                    //登陆，获取token
                    $token = $this->m_user->login($user_id, $from, $device);
                    
                    //站内信提醒
                    $this->load->model('m_message');
                    $send_message = $this->m_message->send_admin(array(
                        'receiver_id' => $user_id,
                        'title' => '微信绑定成功',
                        'text' => '您于 '.date('Y-m-d H:i:s',time()).' 成功绑定微信',
                    ));
                    
                    $this->api->output(array(
                        'user' => $user,
                        'token' => $token
                    ), ERR_WECHAT_BIND_SUCCESS_NO, ERR_WECHAT_BIND_SUCCESS_MSG);
                }
                else{
                    $this->api->output(false, ERR_WECHAT_BIND_FAIL_NO, ERR_WECHAT_BIND_FAIL_MSG);
                }
            }
            else{   //手机号已绑定
                $this->api->output(false, ERR_WECHAT_MOBILE_BINDED_NO, ERR_WECHAT_MOBILE_BINDED_MSG);
            }
        }
        else{
            $this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
        }
    }
    
    public function mobile_reg(){
        $this->load->model('m_user');
        session_start();
        $condition['wx_unionid'] = $this->api->in['wx_unionid'];
        $device = $this->api->in['device'];
        $from = $this->api->in['from'];
        $r = $this->m_wechat->detail($condition);
        if(!$r){    //该微信号还没有绑定
            if (!$this->m_user->is_loginname_exists($this->api->in['phone'])) {    //用户不存在
                //判断验证码是否正确
                $mobile = $this->api->in['phone'];
                $mobilecode = $this->api->in['mobilecode'];
                $_mobilecode = md5($mobilecode.date('Ymdh'));
                if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                        $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                }

                $param = array(
                    'mobile' => $this->api->in['phone'],
                    'password' => $this->api->in['password'],
                    'confirm_password' => $this->api->in['confirm_password'],
                    'invite_userid' => $this->api->in['invite_userid'],
                    'mobilecode' => $this->api->in['mobilecode'],
                    'wx_unionid' => $this->api->in['wx_unionid'],
                    'wx_openid_app' => $this->api->in['wx_openid'],
                    'wx_nickname' => $this->api->in['wx_nickname'],
                    'wx_headimgurl' => $this->api->in['wx_headimgurl']
                );
                //注册用户
                $user_id = $this->m_user->reg($param);
                if($user_id){   //注册并绑定微信成功，进行登录
                    //站内信提醒
                    $this->load->model('m_message');
                    $send_message = $this->m_message->send_admin(array(
                        'receiver_id' => $user_id,
                        'title' => '微信绑定成功',
                        'text' => '您于 '.date('Y-m-d H:i:s',time()).' 成功绑定微信',
                    ));
                    //登陆，获取token
                    $user = $this->m_user->detail($user_id);
                    if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                        $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
                    }
                    //登陆成功，记录日志
                    //更新用户的登录信息

                    $this->m_user->update_login_time($user_id);

                    //加入用户操作记录
                    $config = $this->config->item('msg');

                    $user_log["user_id"] = $user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "action";
                    $user_log["operating"] = "login";
                    $user_log["article_id"] = $user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  $config["users_wechat_login_success_msg"];
                    $this->m_user->add_users_log($user_log);
                    
                    //登陆，获取token
                    $token = $this->m_user->login($user_id, $from, $device);
                    
                    $this->api->output(array(
                        'user' => $user,
                        'token' => $token
                    ), ERR_WECHAT_REG_BIND_SUCCESS_NO, ERR_WECHAT_REG_BIND_SUCCESS_MSG);
                }
            } else {    //用户已存在
                $this->api->output(false, ERR_LOGINNAME_EXISTS_NO, ERR_LOGINNAME_EXISTS_MSG);
            }
        
        }
        else{
            $this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
        }
    }
    
    public function mp_bind(){
        session_start();
        do_log($this->api->in);
        unset($condition);
        $condition['wx_unionid'] = $this->api->in['wx_unionid'];
        /*$condition['wx_openid'] = $this->api->in['wx_openid'];
        $condition['wx_nickname'] = $this->api->in['wx_nickname'];
        $condition['wx_headimgurl'] = $this->api->in['wx_headimgurl'];*/
        
        $r = $this->m_wechat->detail($condition);
        if(!$r){
            //判断验证码是否正确
            $mobile = $this->api->in['phone'];
            $mobilecode = $this->api->in['mobilecode'];
            $_mobilecode = md5($mobilecode.date('Ymdh'));
            if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                    $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                    exit;
            }
            
            unset($condition);
            $condition['username'] = $this->api->in['phone'];
            $r = $this->m_wechat->detail($condition);
            if(!$r){    //用户名不存在
                $this->api->output(false, ERR_WECHAT_MOBILE_NOT_EXISTS_NO, ERR_WECHAT_MOBILE_NOT_EXISTS_MSG);
            }
            else if(md5 ($this->api->in['password']) != $r['password']){  //登陆密码错误
                $this->api->output(false, ERR_WECHAT_PASSWORD_ERROR_NO, ERR_WECHAT_PASSWORD_ERROR_MSG);
            }
            else if(empty($r['wx_unionid'])){  //手机号未绑定
                
                $param['wx_unionid'] = $this->api->in['wx_unionid'];
                $param['wx_openid_mp'] = $this->api->in['wx_openid_mp'];
                $param['wx_nickname'] = $this->api->in['wx_nickname'];
                $param['wx_headimgurl'] = $this->api->in['wx_headimgurl'];
                $user_id = $r['user_id'];
                $r = $this->m_wechat->update($user_id,$param);
                if($r){
                    session_start();
                    
                    $cookie_config = $this->config->item('cookie');
                    $cookie_data['cookie_status'] = $cookie_config['status']; 
                    $cookie_data['user_id'] = $user_id; 
                    
                    DelCookies($cookie_data);  //清除登陆的COOKIE或SESSION信息
                    SetCookies($cookie_data);  //记录登陆的COOKIE或SESSION信息
                    //登陆，获取token
                    $user = $this->m_user->detail($user_id);
                    if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                        $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
                    }

                    //登陆成功，记录日志
                    //更新用户的登录信息

                    $this->m_user->update_login_time($user_id);

                    //加入用户操作记录
                    $config = $this->config->item('msg');

                    $user_log["user_id"] = $user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "action";
                    $user_log["operating"] = "login";
                    $user_log["article_id"] = $user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  $config["users_wechat_login_success_msg"];
                    $this->m_user->add_users_log($user_log);
                    
                    $token = $this->m_user->login($user_id, 'pc', 'pc');
                   
                    /*setcookie('token',$token->token,0,'/','.ifcar99.com');
                    setcookie('uid',$user_id,0,'/','.ifcar99.com');*/
                    setcookie('token',$token->token,0,'/');
                    setcookie('uid',$user_id,0,'/');
                    
                    $json_result->user_id = $user_id;
                    $json_result->username = $this->api->in['phone'];
                    //站内信提醒
                    $this->load->model('m_message');
                    $send_message = $this->m_message->send_admin(array(
                        'receiver_id' => $user_id,
                        'title' => '微信绑定成功',
                        'text' => '您于 '.date('Y-m-d H:i:s',time()).' 成功绑定微信',
                    ));
                    $this->api->output($json_result, ERR_WECHAT_BIND_SUCCESS_NO, ERR_WECHAT_BIND_SUCCESS_MSG);
                }
                else{
                    $this->api->output(false, ERR_WECHAT_BIND_FAIL_NO, ERR_WECHAT_BIND_FAIL_MSG);
                }
            }
            else{   //手机号已绑定
                $this->api->output(false, ERR_WECHAT_MOBILE_BINDED_NO, ERR_WECHAT_MOBILE_BINDED_MSG);
            }
        }
        else{
            $this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
        }
    }
    
    public function mp_reg(){
        $this->load->model('m_user');
        session_start();
        $condition['wx_unionid'] = $this->api->in['wx_unionid'];
        $r = $this->m_wechat->detail($condition);
        if(!$r){    //该微信号还没有绑定
            if (!$this->m_user->is_loginname_exists($this->api->in['phone'])) {    //用户不存在
                //判断验证码是否正确
                $mobile = $this->api->in['phone'];
                $mobilecode = $this->api->in['mobilecode'];
                $_mobilecode = md5($mobilecode.date('Ymdh'));
                if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                        $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                }

                $param = array(
                    'mobile' => $this->api->in['phone'],
                    'password' => $this->api->in['password'],
                    'confirm_password' => $this->api->in['confirm_password'],
                    'invite_userid' => $this->api->in['invite_userid'],
                    'mobilecode' => $this->api->in['mobilecode'],
                    'wx_unionid' => $this->api->in['wx_unionid'],
                    'wx_openid_mp' => $this->api->in['wx_openid_mp'],
                    'wx_nickname' => $this->api->in['wx_nickname'],
                    'wx_headimgurl' => $this->api->in['wx_headimgurl']
                );
                //注册用户
                $user_id = $this->m_user->reg($param);
                if($user_id){   //注册并绑定微信成功，进行登录
                    $cookie_config = $this->config->item('cookie');
                    $cookie_data['cookie_status'] = $cookie_config['status']; 
                    $cookie_data['user_id'] = $user_id; 
                    
                    DelCookies($cookie_data);  //清除登陆的COOKIE或SESSION信息
                    SetCookies($cookie_data);  //记录登陆的COOKIE或SESSION信息
                    //登陆，获取token
                    $user = $this->m_user->detail($user_id);
                    if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                        $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
                    }
                    //登陆成功，记录日志
                    //更新用户的登录信息

                    $this->m_user->update_login_time($user_id);

                    //加入用户操作记录
                    $config = $this->config->item('msg');

                    $user_log["user_id"] = $user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "action";
                    $user_log["operating"] = "login";
                    $user_log["article_id"] = $user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  $config["users_wechat_login_success_msg"];
                    $this->m_user->add_users_log($user_log);
                    
                    $token = $this->m_user->login($user_id, 'pc', 'pc');
                   
                    /*setcookie('token',$token->token,0,'/','.ifcar99.com');
                    setcookie('uid',$user_id,0,'/','.ifcar99.com');*/
                    setcookie('token',$token->token,0,'/');
                    setcookie('uid',$user_id,0,'/');
                    //setcookie('token', $token, time() + 3600 * 24 * 7, '/');
                    
                    $json_result->user_id = $user_id;
                    $json_result->username = $this->api->in['phone'];
                    //站内信提醒
                    $this->load->model('m_message');
                    $send_message = $this->m_message->send_admin(array(
                        'receiver_id' => $user_id,
                        'title' => '微信绑定成功',
                        'text' => '您于 '.date('Y-m-d H:i:s',time()).' 成功绑定微信',
                    ));
                    $this->api->output($json_result, ERR_WECHAT_REG_BIND_SUCCESS_NO, ERR_WECHAT_REG_BIND_SUCCESS_MSG);
                }
            } else {    //用户已存在
                $this->api->output(false, ERR_LOGINNAME_EXISTS_NO, ERR_LOGINNAME_EXISTS_MSG);
            }
        
        }
        else{
            $this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
        }
    }
    
    public function mp_oauth() {
        
        //TODO  输入参数设置
        if (isset($this->api->in['code']) && isset($this->api->in['state'])){ //code存在
            $code = $this->api->in['code'];
            $state = $this->api->in['state'];
            if($state){
               $config = $this->config->item('wechat');
               $access_token_obj = $this->wechat_lib->getOauthAccessToken($config['mp_appid'],$config['mp_secret'],$code);
               $access_token = $access_token_obj -> access_token;
               $openid = $access_token_obj -> openid;

               $userinfo_obj = $this->wechat_lib->getOauthUserInfo($access_token,$openid);
               $param['wx_unionid'] = $userinfo_obj->unionid;
               $userinfo_array = $this->m_wechat->detail($param);
               
               //用户登录
               if($userinfo_obj->unionid && $userinfo_array['wx_unionid']){     //用户已绑定微信,跳转到已绑定页面
                   //$this->api->output(false, ERR_WECHAT_INFO_BINDED_NO, ERR_WECHAT_INFO_BINDED_MSG);
                   $_url = WECHAT_HOST.'/wechat/bindinfo.html';
                   echo '<script>window.location.href="'.$_url.'";</script>';
               }
               else{
                   
                   if($userinfo_obj->unionid){      //用户未绑定微信
                       //var_dump($userinfo_obj->unionid);
                       /*setcookie('wx_unionid',$userinfo_obj->unionid,0,'/','.ifcar99.com');
                       setcookie('wx_openid',$userinfo_obj->openid,0,'/','.ifcar99.com');
                       setcookie('wx_nickname',$userinfo_obj->nickname,0,'/','.ifcar99.com');
                       setcookie('wx_headimgurl',$userinfo_obj->headimgurl,0,'/','.ifcar99.com');*/
                       setcookie('wx_unionid',$userinfo_obj->unionid,0,'/');
                       setcookie('wx_openid_mp',$userinfo_obj->openid,0,'/');
                       setcookie('wx_nickname',$userinfo_obj->nickname,0,'/');
                       setcookie('wx_headimgurl',$userinfo_obj->headimgurl,0,'/');
                       
                        $_url = WECHAT_HOST.'/wechat/mp/bind.html';
                        echo '<script>window.location.href="'.$_url.'";</script>';
                        exit;
                   }
               }
                exit;
            }
            else{
                $this->api->output(false, ERR_FILED_NECESSARY_NO, ERR_FILED_NECESSARY_MSG);
            }

        }else{  //没获取到code
            $_url = WECHAT_HOST;
            echo '<script>window.location.href="'.$_url.'";</script>';
            exit;
        }
    }
    
    /*public function mp_add_msg($template_id,$url,$data) {       //添加消息到队列
        $config = $this->config->item('wechat');
        $access_token_obj = $this->wechat_lib->getAccessToken($config['mp_appid'],$config['mp_secret']);
        $access_token = $access_token_obj -> access_token;
        $userlist_obj = $this->wechat_lib->getUserList($access_token);
        if($userlist_obj -> data ->openid){
            foreach ($userlist_obj -> data ->openid as $k => $v) {
                $userinfo_obj = $this->wechat_lib->getUserInfo($v,$access_token);
                $param['wx_unionid'] = $userinfo_obj->unionid;
                $userinfo_array = $this->m_wechat->detail($param);
                
                //添加消息到队列
               if($userinfo_obj->unionid && $userinfo_array['wx_unionid']){     //用户已绑定微信
                   $this->load->model('m_wechat_msg');
                   $param = array(
                       'user_id' => $userinfo_array['user_id'],
                       'mobile' => $userinfo_array['phone'],
                       'wx_unionid' => $userinfo_array['wx_unionid'],
                       'wx_openid' => $v,   //发送消息使用公众号里的openid
                       'template_id' => $template_id,
                       'url' => $url,
                       'data' => $data
                   );
                   $r = $this->m_wechat_msg->add($param);
                   
                   //var_dump($r);
               }
                //var_dump($userinfo_array);
            }
        }
        
        exit;
    }*/
    
    public function mp_template_send($user_id,$template_id,$url,$data) {       //添加消息到队列
        //$template_id = 'UGLhFF4Jye4tiKrghkGH3wP8Pvut-AbK9lTKEibNoJU';
        $user_id = 8786;
        $template_id = 'FByvcMBznrOyCZKwheCJs2WEJsBshTc4z3gnWGFa1nQ';
        $url = 'http://www.ifcar99.com/';
        $data = array(
                'first' => array(
                    'value' => '您投资的项目有还款，请注意查收',
                    'color' => '#173177'
                ),
                'keyword1' => array(
                    'value' => 'XCH20170223007A',
                    'color' => '#173177'
                ),
                'keyword2' => array(
                    'value' => '10000元',
                    'color' => '#173177'
                ),
                'keyword3' => array(
                    'value' => '100元',
                    'color' => '#173177'
                ),
                'keyword4' => array(
                    'value' => '2017年4月14日 14:06',
                    'color' => '#173177'
                ),
                'keyword5' => array(
                    'value' => '到期还款还息',
                    'color' => '#173177'
                ),
                'remark' => array(
                    'value' => '感谢您的使用，欢迎再次投资！',
                    'color' => '#173177'
                )
            );
        $this->load->model('m_wechat_msg');
        $r = $this->m_wechat_msg->mp_add_msg($user_id,$template_id,$url,json_encode($data));
        exit;
    }
    
}
