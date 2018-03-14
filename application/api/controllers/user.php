<?php

/**
 * 用户控制类
 *
 */
class User extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_user');
    }

    // 获取用户详情
    public function get() {
        $user_id = $this->api->user()->user_id;
        $this->api->output(array('user' => $this->m_user->detail($user_id)));
    }

    // 登录
    public function login() {
        if ($this->m_user->is_loginname_exists($this->api->in['loginname'])) {
            $user_id = $this->m_user->check($this->api->in['loginname'], $this->api->in['password_md5']);
            if (!$user_id) {
                $this->api->output(false, ERR_WRONG_PASSWORD_NO, ERR_WRONG_PASSWORD_MSG);
            }
            if (!$this->m_user->check_from($user_id, $this->api->in['from'])) {
                $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
            }
            $user = $this->m_user->detail($user_id);
            if ($user->status->id != m_user::STATUS_USER_ENABLE) {
                $this->api->output(false, ERR_USER_ACCOUNT_DISABLE_NO, ERR_USER_ACCOUNT_DISABLE_MSG);
            }
            $token = $this->m_user->login($user_id, $this->api->in['from'], $this->api->in['device']);
            $this->api->output(array(
                'user' => $user,
                'token' => $token
                    ), ERR_SUCCESS_NO, ERR_SUCCESS_MSG);
        } else {
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }

    //签到
    public function sign() {
        if (!$this->m_user->has_signed()) {
            if ($this->m_user->sign()) {
                $this->load->model('m_credit');
                $num = $this->m_credit->rand_sign();
                $this->m_credit->add($this->api->user()->id, m_credit::TYPE_CREDIT_SIGN, $num);
                $this->api->output(array(
                    'credit_add' => $num,
                    'credit_serial' => $credit_serial,
                    'serial_days' => $serial_days,
                        ), ERR_SUCCESS_NO, ERR_SUCCESS_MSG);
            } else {
                $this->api->output(false, ERR_FAILED_NO, ERR_FAILED_MSG);
            }
        } else {
            $this->api->output(false, ERR_REPEAT_SIGN_NO, ERR_REPEAT_SIGN_MSG);
        }
    }

    //查找用户
    public function find() {
        $loginames = json_decode($this->api->in['loginnames'], true);
        if (!is_array($loginames)) {
            $loginames = array($loginames);
        }
        $r = array(
            'not_found' => array(),
            'rows' => array(),
            'total' => 0
        );
        foreach ($loginames as $k => $v) {
            $u = $this->m_user->find_by_loginname($v);
            if ($u !== false) {
                $r['rows'][] = $u;
            } else {
                $r['not_found'][] = new obj_user(array('username' => $v));
            }
        }
        $r['total'] = count($r['rows']);
        $this->api->output($r);
    }
   //注册新用户
    public function reg(){
        session_start();
        if (!$this->m_user->is_loginname_exists($this->api->in['mobile'])) {    //用户不存在
            $mobile = $this->api->in['mobile'];
            $mobilecode = $this->api->in['mobilecode'];
            $_mobilecode = md5($mobilecode.date('Ymdh'));
            if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                    $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
            }
            
            $param = array(
                'mobile' => $this->api->in['mobile'],
                'password' => $this->api->in['password'],
                'confirm_password' => $this->api->in['confirm_password'],
                'invite_userid' => $this->api->in['invite_userid'] ? $this->api->in['invite_userid'] : 0,
                'mobilecode' => $this->api->in['mobilecode'],
                'channel' => $this->api->in['channel'],
            );
            //注册用户
            $user_id = $this->m_user->reg($param);
            if($user_id){
                $this->api->output(true);
            }
        } else {
            $this->api->output(false, ERR_LOGINNAME_EXISTS_NO, ERR_LOGINNAME_EXISTS_MSG);
        }
    }
    
    //注册发送验证码
    public function reg_mobile_code() {
        //判断手机号格式是否正确
        if(!is_mobile($this->api->in['mobile'])){
            $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
        }
        if (!$this->m_user->is_loginname_exists($this->api->in['mobile'])) {    //用户不存在
            $r = $this->m_user->reg_mobile_code($this->api->in['mobile']);
            $this->api->output($r);
        }
        else{
            $this->api->output(false, ERR_LOGINNAME_EXISTS_NO, ERR_LOGINNAME_EXISTS_MSG);
        }
    }
    
    //绑定微信发送验证码
    public function bind_mobile_code() {
        //判断手机号格式是否正确
        if(!is_mobile($this->api->in['mobile'])){
            $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
        }
        if ($this->m_user->is_loginname_exists($this->api->in['mobile'])) {    //用户存在
            $r = $this->m_user->reg_mobile_code($this->api->in['mobile']);
            $this->api->output($r);
        }
        else{
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }
    
    //给已有用户发送短信验证码
    public function mobile_code() {
        //判断手机号格式是否正确
        if(!is_mobile($this->api->in['mobile'])){
            $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
        }
        if ($this->m_user->is_loginname_exists($this->api->in['mobile'])) {    //用户存在
            $r = $this->m_user->reg_mobile_code($this->api->in['mobile']);
            $this->api->output($r);
        }
        else{
            $this->api->output(false, ERR_LOGINNAME_NOT_EXISTS_NO, ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
    }
    
    //判断用户名是否存在
    public function is_loginname_exists() {
        if (!$this->m_user->is_loginname_exists($this->api->in['loginname'])) {//用户不存在
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        } else {
            $this->api->output(true);
        }
    }
    
    public function no_tender_user_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = TABLE_USER.'.user_id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_user->no_tender_user_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_user->no_tender_user_count($condition);
        $this->api->output($r);
    }
    
    public function no_tender_user_lists_admin_export() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->model('m_file');
        $this->load->library('PHPExcel');

        if (0) {
            $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
            $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        } else {
            $page = false;
            $size = false;
        }
        $condition = $this->api->in;
        /*if ($this->api->in['user']) {
            $user_ids = $this->m_user->find($this->api->in['user']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }*/
        if (!$this->api->in['order']) {
            $order = TABLE_USER.'.user_id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_user->no_tender_user_lists($condition, $page, $size, $order);
        
        $obj = new PHPExcel();
        // Excel表格式,
        $letter = array(
            'A',
            'B',
            'C',
            'D',
        );
        // 表头数组
        $tableheader = array(
            '用户ID',
            '手机号',
            '真实姓名',
            '注册时间',
        );

        // 填充表头信息
        for ($i = 0; $i < count($tableheader); $i ++) {
            $obj->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }

        $data = array();
        foreach ($r['rows'] as $k => $v) {
            $data[] = array(
                $v['user_id'],
                $v['mobile'],
                $v['realname'],
                date('Y-m-d H:i:s', $v['reg_time']),
            );
        }
        for ($i = 2; $i <= count($data) + 1; $i ++) {
            $j = 0;
            foreach ($data[$i - 2] as $key => $value) {
                $obj->getActiveSheet()->setCellValue("$letter[$j]$i", "$value");
                $j ++;
            }
        }


        $objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        mkdir(FCPATH . 'data/upload/excel/' . date('Ymd') . '/', 0777, true);
        $filepath = FCPATH . 'data/upload/excel/' . date('Ymd') . '/' . md5(microtime(1)) . '.xlsx';
        $objWriter->save($filepath);
        $data = array();
        $data['user_id'] = $this->api->user()->user_id;
        $data['type'] = 'excel';
        $data['suffix'] = 'xlsx';
        $data['size'] = filesize($filepath);
        $path = '/data/upload/' . $data['type'] . '/' . date('Ymd') . '/';
        $data['path'] = $path;
        $fid = $this->m_file->add($data);
        rename($filepath, FCPATH . $path . $fid . '.' . $data['suffix']);
        $file = $this->m_file->detail($fid);
        $this->api->output($file);
    }
    
    //通过验证手机修改密码
    public function set_password() {
        session_start();
        //判断手机号格式是否正确
        if(!is_mobile($this->api->in['mobile'])){
            $this->api->output(false, ERR_NOT_PHONE_NUM_NO, ERR_NOT_PHONE_NUM_MSG);
            exit;
        }
        if ($this->m_user->is_loginname_exists($this->api->in['mobile'])) {    //用户存在
            //第一步
            if($this->api->in['step'] == 1){
                //判断验证码是否正确
                $mobile = $this->api->in['mobile'];
                $mobilecode = $this->api->in['mobilecode'];
                $_mobilecode = md5($mobilecode . date('Ymdh'));
                if ($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']) {
                    $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                    exit;
                }
                $this->api->output(true);
            }
            //第二步
            else if($this->api->in['step'] == 2){
                //判断验证码是否正确
                $mobile = $this->api->in['mobile'];
                $mobilecode = $this->api->in['mobilecode'];
                $_mobilecode = md5($mobilecode . date('Ymdh'));
                if ($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']) {
                    $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                    exit;
                }
                //修改密码
                $password = trim($this->api->in['password']);
                //验证密码位数
                if($password == '' || strlen($password) < 6 || strlen($password) > 15){
                    $this->api->output(false, ERR_PASSWORD_LENGTH_NO, ERR_PASSWORD_LENGTH_MSG);
                    exit;
                }
                $user = $this->m_user->find_by_loginname($mobile);
                $r = $this->m_user->update_password($password,$user->user_id);
                if($r){
                    $user_log["user_id"] = $user->user_id;
                    $user_log["code"] = "users";
                    $user_log["type"] = "user";
                    $user_log["operating"] = "getpwd";
                    $user_log["article_id"] = $user->user_id;
                    $user_log["result"] = 1;
                    $user_log["content"] =  '用户"'.$mobile.'"在'.date('Y-m-d H:i:s',time()).'修改个人密码成功';
                    $this->m_user->add_user_log($user_log);
                }
                
                $this->api->output($r);
            }
            
        }
        
    }
    
    //通过原密码修改密码
    public function modify_password() {
        //判断手机号格式是否正确
        $user_id = $this->api->user()->user_id;
        $user = $this->m_user->password_detail($user_id);
        if($user['password'] != md5($this->api->in['old_password'])){
            $this->api->output(false, ERR_WRONG_PASSWORD_NO, ERR_WRONG_PASSWORD_MSG);
            exit;
        }
        if(empty($this->api->in['new_password'])){
            $this->api->output(false, ERR_PASSWORD_NOT_EXISTS_NO, ERR_PASSWORD_NOT_EXISTS_MSG);
            exit;
        }
        if(empty($this->api->in['confirm_password'])){
            $this->api->output(false, ERR_CONFIRM_PASSWORD_NOT_EXISTS_NO, ERR_CONFIRM_PASSWORD_NOT_EXISTS_MSG);
            exit;
        }
        if($this->api->in['new_password'] != $this->api->in['confirm_password']){
            $this->api->output(false, ERR_CONFIRM_PASSWORD_ERROR_NO, ERR_CONFIRM_PASSWORD_ERROR_MSG);
            exit;
        }
        if(strlen($this->api->in['new_password']) < 6 || strlen($this->api->in['new_password']) > 15){
            $this->api->output(false, ERR_PASSWORD_LENGTH_NO, ERR_PASSWORD_LENGTH_MSG);
            exit;
        }
        
        $r = $this->m_user->update_password($this->api->in['new_password'],$user['user_id']);
        if($r){
            $user_log["user_id"] = $user['user_id'];
            $user_log["code"] = "users";
            $user_log["type"] = "user";
            $user_log["operating"] = "getpwd";
            $user_log["article_id"] = $user['user_id'];
            $user_log["result"] = 1;
            $user_log["content"] =  '用户"'.$mobile.'"在'.date('Y-m-d H:i:s',time()).'修改个人密码成功';
            $this->m_user->add_user_log($user_log);
        }

        $this->api->output($r);
        
    }
    
    public function update_cash_control(){
        $condition = $this->api->in;
        if ($condition['q']) {
            if(strlen($condition['q']) == 11){
                $users = $this->m_user->find_by_loginname($condition['q']);
                $user_id = trim($users->user_id);
            }
            else{
                $user_id = trim($condition['q']);
            }
            $r = $this->m_user->update_cash_control($condition['cash_control'],$user_id);
            $this->api->output($r);
        }
        else{
            $this->api->output(false,ERR_LOGINNAME_NOT_EXISTS_NO,ERR_LOGINNAME_NOT_EXISTS_MSG);
        }
        
    }
    
    /**
     * 提现控制列表
     */
    public function cash_control_lists() {
        $r = $this->m_user->cash_control_lists();
        $this->api->output($r);
    }
}
