<?php

class wechat_lib {

    public function __construct() {
        $this->CI = get_instance();
        $this->CI->load->config('myconfig');
    }
    
    public function getAccessToken($appid,$secret) {
        $config = $this->CI->config->item('wechat');
        $url = $config['api_url'].'cgi-bin/token';
        $result = curl_upload_https($url, array(
            'appid' => $appid,
            'secret' => $secret,
            'grant_type' => 'client_credential'
         )); 
        $obj = json_decode($result);
        return $obj;
    }
    
    public function getOauthAccessToken($appid,$secret,$code){
        $config = $this->CI->config->item('wechat');
        $url = $config['api_url'].'sns/oauth2/access_token';
        $result = curl_upload_https($url, array(
             'appid' => $appid,
             'secret' => $secret,
             'code' => $code,
             'grant_type' => 'authorization_code'
         ));
        $obj = json_decode($result);
        return $obj;
    }
    
    public function getOauthUserInfo($access_token,$openid){
        $config = $this->CI->config->item('wechat');
        $url = $config['api_url'].'sns/userinfo';
        $result = curl_upload_https($url, array(
            'access_token' => $access_token,
            'openid' => $openid,
        ));

        $obj = json_decode($result);
        return $obj;
    }
    
    public function getUserList($access_token){
        $config = $this->CI->config->item('wechat');
        $url = $config['api_url'].'cgi-bin/user/get';
        //$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token;
        $result = curl_upload_https($url, array(
            'access_token' => $access_token
        ));
        
        $obj = json_decode($result);
        return $obj;
    }
    
    public function getUserInfo($openid,$access_token){
        $config = $this->CI->config->item('wechat');
        $url = $config['api_url'].'cgi-bin/user/info';
        //$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token;
        $result = curl_upload_https($url, array(
            'access_token' => $access_token,
            'openid' => $openid
        ));
        
        $obj = json_decode($result);
        return $obj;
    }
    
    public function get_token_by_unionid($data) {
        $this->CI->load->model('m_wechat');
        //TODO  输入参数设置
        if (isset($data['code']) && isset($data['state'])){ //code存在
            $code = $data['code'];
            $state = $data['state'];
            if($state){
               $config = $this->CI->config->item('wechat');
               $access_token_obj = $this->getOauthAccessToken($config['mp_appid'],$config['mp_secret'],$code);
               $access_token = $access_token_obj -> access_token;
               $openid = $access_token_obj -> openid;

               $userinfo_obj = $this->getOauthUserInfo($access_token,$openid);
               $param['wx_unionid'] = $userinfo_obj->unionid;
               $userinfo_array = $this->CI->m_wechat->detail($param);
               //用户登录
               if($userinfo_obj->unionid && $userinfo_array['wx_unionid']){     //用户已绑定微信,登陆获取token  TODO,其实重做接口不需要token，使用user_id查询也可以吧？
                   $data = array(
                       'loginname' => $userinfo_array['username'],
                       'password_md5' => $userinfo_array['password'],
                       'from' => 'wechat',
                       'device' => ''
                   );
                   $r_login = $this->login($data);
                   do_log('wechat_token');
                   do_log($userinfo_array['user_id']);
                   do_log($r_login);
                   do_log($r_login['token']->token);
                   //setcookie('token',$r_login['token']->token,0,'/');
                   
                   return $r_login['token']->token;
                   exit;
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
                $this->CI->api->output(false, ERR_FILED_NECESSARY_NO, ERR_FILED_NECESSARY_MSG);
            }

        }else{  //没获取到code
            $_url = WECHAT_HOST;
            echo '<script>window.location.href="'.$_url.'";</script>';
            exit;
        }
    }
    
    // 登录
    private function login($data) {
        $this->CI->load->model('m_user');
        if ($this->CI->m_user->is_loginname_exists($data['loginname'])) {
            $user_id = $this->CI->m_user->check($data['loginname'], $data['password_md5']);
            if (!$user_id) {
                $this->CI->api->output(false, ERR_WRONG_PASSWORD_NO, ERR_WRONG_PASSWORD_MSG);
            }
            if (!$this->CI->m_user->check_from($user_id, $data['from'])) {
                $this->CI->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
            }
            $user = $this->CI->m_user->detail($user_id);
            if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                $this->CI->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
            }
            $token = $this->CI->m_user->login($user_id, $data['from'], $data['device']);
            $result = array(
                'user' => $user,
                'token' => $token
                    );
            return $result;
        } else {
            $this->CI->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }

}

?>
