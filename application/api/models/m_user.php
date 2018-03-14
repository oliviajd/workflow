<?php

/**
 * 用户模型
 *
 *
 */
class m_user extends CI_Model {

    const TYPE_SEX_UNKNOW = 0;
    const TYPE_SEX_MAN = 1;
    const TYPE_SEX_WOMAN = 2;
    const TYPE_USER_TEACHER = 1;
    const TYPE_USER_STUDENT = 2;
    const TYPE_USER_PARENT = 3;
    const STATUS_USER_ENABLE = 1;

    public function __construct() {
        parent::__construct();
    }

    /**
     * 用户详情
     */
    private function _detail($user_id) {
        $this->db->limit(1);
        return $this->db->get_where(TABLE_USER, array('user_id' => $user_id))->row_array(0);
    }

    public function detail($user_id, $fields = false) {
        $detail = $this->_detail($user_id);
        if (!empty($detail)) {
            $this->load->model('m_credit');
            $this->load->model('m_bank_account');
            $this->load->model('m_bank_card');
            $user_credit = $this->m_credit->detail($user_id);
            $detail['credit'] = $user_credit->current;
            $user_info = $this->db->get_where(TABLE_USER_INFO, array('user_id' => $user_id))->row_array(0);
            $detail['realname'] = $user_info['realname'];
            $detail['wx_openid'] = $user_info['wx_openid'];
            $detail['wx_openid_app'] = $user_info['wx_openid_app'];
            $detail['wx_openid_mp'] = $user_info['wx_openid_mp'];
            $detail['wx_nickname'] = $user_info['wx_nickname'];
            $detail['wx_headimgurl'] = $user_info['wx_headimgurl'];
            $detail['wx_unionid'] = $user_info['wx_unionid'];
            $detail['mobile'] = $detail['mobile'];
            $detail['status'] = $this->get_user_status($user_info['status']);
            $detail['ba_status'] = $this->m_bank_account->get_status($detail['ba_status']);
            $detail['ba_auto_bid'] = $this->m_bank_account->get_auto_bid_status($detail['ba_auto_bid']);
            $detail['ba_has_set_password'] = $this->m_bank_account->get_password_status($detail['ba_has_set_password']);
            $detail['ba_has_bind_card'] = $this->m_bank_account->get_bind_card_status($detail['ba_has_bind_card']);
            $detail['ba_card'] = $this->m_bank_card->detail($detail['ba_card_No']);
            if ($fields !== false) {//详细字段
                $user_realname_info = $this->db->get_where(TABLE_USER_REALNAME, array('user_id' => $user_id))->row_array(0);
                $detail['id_card'] = $user_realname_info['card_id'];
                $detail['verify_time'] = $user_realname_info['verify_time'];
                $detail['register_time'] = $detail['reg_time'];
            }
            return new obj_user($detail);
        } else {
            return false;
        }
    }
    
    public function detail_static($user_id) {
        $detail = $this->_detail($user_id);
        if (!empty($detail)) {
            $user_info = $this->db->get_where(TABLE_USER_INFO, array('user_id' => $user_id))->row_array(0);
            $detail['realname'] = $user_info['realname'];
            $detail['mobile'] = $detail['mobile'];
            return new obj_user($detail);
        } else {
            return false;
        }
    }

    public function is_loginname_exists($loginname) {
        $this->db->select('user_id');
        $this->db->limit(1);
        $user_id = $this->db->get_where(TABLE_USER, array('username' => $loginname))->row(0)->user_id;
        return $user_id ? $user_id : false;
    }

    public function check($loginname, $password_md5) {
        $this->db->select('user_id');
        $this->db->limit(1);
        $user_id = $this->db->get_where(TABLE_USER, array('username' => $loginname, 'password' => $password_md5))->row(0)->user_id;
        return $user_id ? $user_id : false;
    }

    public function check_from($user_id, $from) {
        switch (strtolower(trim($from))) {
            case 'admin':
                $this->load->library('privilege');
                return $this->privilege->is_admin($user_id);
                break;
            default:
                break;
        }
        return true;
    }

    public function has_pay_password($user_id) {
        $this->db->select('paypassword');
        $this->db->limit(1);
        return '' == $this->db->get_where(TABLE_USER, array('user_id' => $user_id))->row(0)->paypassword ? false : true;
    }

    public function check_pay_password($user_id, $pay_password_md5) {
        $this->db->select('user_id');
        $this->db->limit(1);
        return $user_id == $this->db->get_where(TABLE_USER, array('user_id' => $user_id, 'paypassword' => $pay_password_md5))->row(0)->user_id ? true : false;
    }

    public function login($user_id, $from, $device) {
        //todo 登录日志
        $user = $this->detail($user_id);
        return $this->token->create(array(
                    'user_id' => $user_id,
                    'from' => $from,
                    'cache' => $user,
                    'device' => $device
        ));
    }

    public function has_signed() {
        return false;
    }

    public function sign() {
        return rand(1, 10);
    }

    public function find($string) {
        $users = array();
        $user_id = $this->db->get_where(TABLE_USER, array('user_id' => intval($string)))->row(0)->user_id;
        if ($user_id) {
            $users[] = $user_id;
        }
        
        $user_id = $this->db->get_where(TABLE_USER, array('mobile' => trim($string)))->row(0)->user_id;
        if ($user_id) {
            $users[] = $user_id;
        }
        $this->db->like('realname', trim($string), 'BOTH');
        $user_ids = $this->db->get_where(TABLE_USER_INFO, array())->result_array();
        foreach ($user_ids as $k => $v) {
            $users[] = $v['user_id'];
        }
        return $users;
    }

    public function find_by_loginname($loginname) {
        if (!trim($loginname)) {
            return false;
        }
        $detail = $this->db->get_where(TABLE_USER, array('username' => trim($loginname)))->row_array(0);
        if (!empty($detail)) {
            $this->load->model('m_credit');
            $user_info = $this->db->get_where(TABLE_USER_INFO, array('user_id' => $detail['user_id']))->row_array(0);
            $detail['realname'] = $user_info['realname'];
            $detail['mobile'] = $detail['mobile'];
            return new obj_user($detail);
        } else {
            return false;
        }
    }

    public function get_user_status($key = false) {
        $data = array(
            1 => '正常',
            2 => '关闭',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'USER_ERROR', 'text' => '用户状态错误');
    }
    
    /**
     * 11，添加用户的操作记录（users_log）
     * @param $param array('user_id' => '用户id','code' => '模块名称','type' => '所属分类,'operating' => '操作类型','article_id' => '操作id','result' => '操作结果','content' => '操作内容')
	 * @return Null
     */
    public function add_users_log($data){
            $data['addtime'] = time();
            $data['addip'] = ip_address();
            $this->db->insert(TABLE_USERS_LOG, $data);
            $id = $this->db->insert_id();
            return $id;
    }
    
    /**
     * 更新用户登陆次数及时间
     */
    public function update_login_time($user_id){
        $this->db->set('logintime', 'logintime+1', FALSE);
        $this->db->set('up_time', 'last_time', FALSE);
        $this->db->set('up_ip', 'last_ip', FALSE);
        $array = array(
            'last_time' => time(),
            'last_ip' => ip_address()
        );
        $this->db->set($array);
        $this->db->where('user_id',$user_id);
        $r = $this->db->update(TABLE_USER);
        return $r;
    }
    
    /**
     * 同盾api函数
    * $params 请求参数
    * $timeout 超时时间
    * $connection_timeout 连接超时时间
    */
    public function invoke_fraud_api(array $params, $timeout = 5000, $connection_timeout = 5000) {
        $params['partner_code'] = 'ifcar99';
        $params['secret_key'] = '16aa9ede7f1142189e39e0c692afd1ed';
        $api_url = "https://api.fraudmetrix.cn/riskService";

        $options = array(
            CURLOPT_POST => 1,            // 请求方式为POST
            CURLOPT_URL => $api_url,      // 请求URL
            CURLOPT_RETURNTRANSFER => 1,  // 获取请求结果
            // -----------请确保启用以下两行配置------------
            CURLOPT_SSL_VERIFYPEER => 0,  // 验证证书
            CURLOPT_SSL_VERIFYHOST =>0,  // 验证主机名
            // -----------否则会存在被窃听的风险------------
            CURLOPT_POSTFIELDS => http_build_query($params) // 注入接口参数
        );
        if (defined(CURLOPT_TIMEOUT_MS)) {
            $options[CURLOPT_NOSIGNAL] = 1;
            $options[CURLOPT_TIMEOUT_MS] = $timeout;
        } else {
            $options[CURLOPT_TIMEOUT] = ceil($timeout / 1000);
        }
        if (defined(CURLOPT_CONNECTTIMEOUT_MS)) {
            $options[CURLOPT_CONNECTTIMEOUT_MS] = $connection_timeout;
        } else {
            $options[CURLOPT_CONNECTTIMEOUT] = ceil($connection_timeout / 1000);
        }
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        if(!($response = curl_exec($ch))) {
            // 错误处理，按照同盾接口格式fake调用结果
            return array(
                "success" => "false",
                "reason_code" => "000:调用API时发生错误[".curl_error($ch)."]"
            );
        }
        curl_close($ch);
        return json_decode($response, true);
    }
    
    /**
     * 用户注册
     */
    public function reg($data){
        //提交的参数是否都存在
        if(!isset($data['mobile'])){
            $this->api->output(false, ERR_PHONE_NOT_EXISTS_NO, ERR_PHONE_NOT_EXISTS_MSG);
        }
        if(!isset($data['password'])){
            $this->api->output(false, ERR_PASSWORD_NOT_EXISTS_NO, ERR_PASSWORD_NOT_EXISTS_MSG);
        }
        if(!isset($data['confirm_password'])){
            $this->api->output(false, ERR_CONFIRM_PASSWORD_NOT_EXISTS_NO, ERR_CONFIRM_PASSWORD_NOT_EXISTS_MSG);
        }
        if($data['invite_userid'] != 0){
            $invite_detail = $this->db->get_where(TABLE_USER, array('user_id' => $data['invite_userid']))->row_array(0);
            if(!$invite_detail){
                $this->api->output(false, ERR_INVITE_ID_NOT_EXISTS_NO, ERR_INVITE_ID_NOT_EXISTS_MSG);
            }
        }
        $data['email'] = $data['email'] ? $data['email'] : '';
        /*$data['wx_unionid'] = $data['wx_unionid'] ? $data['wx_unionid'] : '';
        $data['wx_openid'] = $data['wx_openid'] ? $data['wx_openid'] : '';
        $data['wx_nickname'] = $data['wx_nickname'] ? $data['wx_nickname'] : '';
        $data['wx_headimgurl'] = $data['wx_headimgurl'] ? $data['wx_headimgurl'] : '';*/
        //判断手机号格式是否正确
        if(!is_mobile($data['mobile'])){
            $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
        }
        //判断用户名是否已注册
        if($this->is_loginname_exists($data['mobile'])){
            $this->api->output(false, ERR_LOGINNAME_EXISTS_NO, ERR_LOGINNAME_EXISTS_MSG);
        }
        //判断两次输入密码是否一致
        if($data['password']!=$data['confirm_password']){
            $this->api->output(false, ERR_CONFIRM_PASSWORD_ERROR_NO, ERR_CONFIRM_PASSWORD_ERROR_MSG);
        }
        //判断邀请码格式
        if($data['invite_userid'] != 0){
            if(!preg_match("/^[0-9]{4,11}$/",$data['invite_userid'])){
                $this->api->output(false, ERR_INVITE_ID_FORMAT_ERROR_NO, ERR_INVITE_ID_FORMAT_ERROR_MSG);
            }
        }
        //插入user表
        $insert_user_data = array(
            'reg_time' => time(),
            'reg_ip' => get_ip(),
            'channel' => trim($data['channel']),
            'up_time' => time(),
            'up_ip' => get_ip(),
            'last_time' => time(),
            'last_ip' => get_ip(),
            'username' => $data['mobile'],
            'password' => md5($data['password']),
            'email' => $data['email'],
            'mobile' => $data['mobile']
        );
        $insert_user_id = $this->insert_user($insert_user_data);
        
        
            
        
        if($insert_user_id){
            //插入user_info表
            $this->insert_user_info($insert_user_id,$data['mobile'],$data['invite_userid'],$data['wx_unionid'],$data['wx_openid'],$data['wx_openid_app'],$data['wx_openid_mp'],$data['wx_nickname'],$data['wx_headimgurl']);
            //注册送红包
            //$this->send_reg_bouns($insert_user_id);
            //注册送积分
            $this->send_reg_credit($insert_user_id);
        }
        return $insert_user_id;
    }
    
    public function insert_user($insert_user_data){
        $this->db->insert(TABLE_USER,$insert_user_data);
        $insert_user_id = $this->db->insert_id();
        return $insert_user_id;
    }
    
    public function send_reg_bouns($user_id){
        $this->load->model('m_bouns');
        //判断注册红包开关是否打开
        $bouns_status = $this->db->get_where(TABLE_BOUNS_CONTROL, array('id' => 1))->row_array(0);
        
        if(!empty($bouns_status['status']) && $bouns_status['status'] == 1){
                $this->db->where('nums > ',0);
                $reg_bouns = $this->db->get_where(TABLE_BOUNS, array('status' => 3))->result_array();
                foreach($reg_bouns as $k => $v){
                        $this->m_bouns->send_to_user($v['hid'], $user_id);
                }
                return true;
        }
        else{
            $this->api->output(false, ERR_BOUNS_REG_DISABLE_NO, ERR_BOUNS_REG_DISABLE_MSG);
        }
    }
    
    public function send_reg_credit($user_id){
        $this->load->model('m_credit');
        $reg_credit = $this->m_credit->increase($user_id, 200, array(
            'type' => 'reg',
            'item_id' =>'',
            'remark' => "注册积分",
        ));
    }
    
    public function insert_user_info($user_id,$phone,$invite_userid,$wx_unionid = '',$wx_openid = '',$wx_openid_app = '',$wx_openid_mp = '',$wx_nickname = '',$wx_headimgurl = ''){
        $param_1 = array(
            'user_id' => $user_id,
            'invite_userid' => $invite_userid,
            'status' => 1,
            'type_id' => 2,
            'phone' => $phone,
            'phone_status' => 1,
            'wx_unionid' => $wx_unionid,
            'wx_openid' => $wx_openid,
            'wx_openid_app' => $wx_openid_app,
            'wx_openid_mp' => $wx_openid_mp,
            'wx_nickname' => $wx_nickname,
            'wx_headimgurl' => $wx_headimgurl
        );
        $this->db->insert(TABLE_USER_INFO,$param_1);
        
        $param_2 = array(
            array(
                'user_id' => $user_id,
                'friends_userid' => $invite_userid,
                'status' => 1,
                'type' => 0,
                'addtime' => time(),
                'addip' => get_ip()
            ),
            array(
                'user_id' => $invite_userid,
                'friends_userid' => $user_id,
                'status' => 1,
                'type' => 0,
                'addtime' => time(),
                'addip' => get_ip()
            )
        );
        
        $this->db->insert_batch(TABLE_USER_FRIENDS,$param_2);
        
        $param_3 = array(
            'friends_userid' => $user_id,
            'user_id' => $invite_userid,
            'addtime' => time(),
            'addip' => get_ip,
            'status' => 1,
            'type' => 1
        );
        
        $this->db->insert(TABLE_USER_FRIENDS_INVITE,$param_3);
    }
    
    public function reg_mobile_code($mobile,$user_id = 0){
        session_start();
        $mobile_code = substr(str_shuffle('01234567890'),0,4);
        $randval     = $mobile_code.date('Ymdh');
        // 2-写入SESSION
        $_SESSION['mobilecode'] = md5($randval);
        $_SESSION['mobile'] = $mobile;
        // 3-发送短信
        $content = "您的验证码是：".$mobile_code."。请不要把验证码泄露给其他人。";
        
        $this->load->model('m_sms');
        //短信提醒
        $r = $this->m_sms->add(array(
            'user_id' => $user_id,
            'mobile' => $mobile,
            'content' => $content
        ));
        return $r;
        //sendYY($m, $c);
    }
    
    public function no_tender_user_lists($condition, $page, $size, $order) {
        if ($condition['q']) {
            $users = $this->find($condition['q']);
            if(!empty($users)){
                $condition['user_id'] = $users;
                if ($condition['user_id']) {
                    is_array($condition['user_id']) ? $this->db->where_in(TABLE_USER.'.user_id', $condition['user_id']) : $this->db->where(TABLE_USER.'.user_id', $condition['user_id']);
                }
            }
        }
        
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        
        $this->db->select(TABLE_USER . '.user_id,'.TABLE_USER . '.mobile,'.TABLE_USER_REALNAME . '.realname,'.TABLE_USER. '.reg_time');
        
        $this->db->join(TABLE_USER_REALNAME,TABLE_USER.'.user_id = '.TABLE_USER_REALNAME.'.user_id','left');
        $this->db->join(TABLE_BORROW_TENDER,TABLE_USER.'.user_id = '.TABLE_BORROW_TENDER.'.user_id','left');
        
        $this->db->where(TABLE_BORROW_TENDER.'.account is null');
        $this->db->where(TABLE_USER.'.user_id >',1995);
        $rows = $this->db->get(TABLE_USER)->result_array();
        return $rows;
    }
    
    public function no_tender_user_count($condition) {
        if ($condition['q']) {
            $users = $this->find($condition['q']);
            if(!empty($users)){
                $condition['user_id'] = $users;
                if ($condition['user_id']) {
                    is_array($condition['user_id']) ? $this->db->where_in(TABLE_USER.'.user_id', $condition['user_id']) : $this->db->where(TABLE_USER.'.user_id', $condition['user_id']);
                }
            }
        }
        
        $this->db->select('count(1) as count');
        $this->db->join(TABLE_USER_REALNAME,TABLE_USER.'.user_id = '.TABLE_USER_REALNAME.'.user_id','left');
        $this->db->join(TABLE_BORROW_TENDER,TABLE_USER.'.user_id = '.TABLE_BORROW_TENDER.'.user_id','left');
        
        $this->db->where(TABLE_BORROW_TENDER.'.account is null');
        $this->db->where(TABLE_USER.'.user_id >',1995);
        return $this->db->get(TABLE_USER)->row(0)->count;
    }
    
    /**
     * 更新联行号
     */
    public function update_card_bank_cnaps($card_bank_cnaps,$user_id){
        $array = array(
            'ba_card_bank_cnaps' => $card_bank_cnaps
        );
        $this->db->set($array);
        $this->db->where('user_id',$user_id);
        $r = $this->db->update(TABLE_USER);
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * 修改密码
     */
    public function update_password($password,$user_id = 0){
        $array = array(
            'password' => md5($password),
            'up_time' => time()
        );
        $this->db->set($array);
        $this->db->where('user_id',$user_id);
        $r = $this->db->update(TABLE_USER);
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * 添加用户操作日志
     */
    public function add_user_log($data){
        $param['user_id'] = intval($data['user_id']);
        $param['code'] = trim($data['code']);
        $param['type'] = trim($data['user']);
        $param['operating'] = trim($data['getpwd']);
        $param['article_id'] = trim($data['article_id']);
        $param['article_id'] = trim($data['article_id']);
        $param['result'] = trim($data['result']);
        $param['content'] = trim($data['content']);
        $param['addtime'] = time();
        $this->db->insert(TABLE_USER_LOG, $param);
        $id = $this->db->insert_id();
        return $id;
    }
    
    /**
     * 更新提现开关
     */
    public function update_cash_control($cash_control,$user_id){
        $array = array(
            'ba_cash_control' => $cash_control,
            'up_time' => time()
                
        );
        $this->db->set($array);
        $this->db->where('user_id',$user_id);
        $r = $this->db->update(TABLE_USER);
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * 提现控制列表
     */
    public function cash_control_lists() {
        $this->db->select('user_id,username,ba_cash_control,up_time');
        $this->db->where('ba_cash_control', 2);
        $this->db->order_by('up_time desc');
        $rows = $this->db->get_where(TABLE_USER)->result_array();
        return $rows;
    }
    
    public function password_detail($user_id, $fields = false) {
        $detail = $this->_detail($user_id);
        return $detail;
    }
}
