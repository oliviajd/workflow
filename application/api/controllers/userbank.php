<?php

/**
 * 用户银行卡类
 *
 */
class Userbank extends CI_Controller {
    
    const STATUS_MODIFY_SUCCESS = 1;
    const STATUS_MODIFY_FAILED = 2;
    const STATUS_MODIFY_PROCESSING = 3;
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_userbank');
        session_start();
    }

    // 获取银行卡详情
    public function get() {
        $r = $this->m_userbank->detail($this->api->user()->user_id);
        $this->api->output($r);
    }
    
    //判断用户是否绑定银行卡
    public function is_bind() {
        $r = $this->m_userbank->detail($this->api->user()->user_id);
        if(!$r || !$r->account || $r->status != 2 ){
            $this->api->output(false, ERR_USERBANK_NOT_EXISTS_NO, ERR_USERBANK_NOT_EXISTS_MSG);
        }
        else{
            $this->api->output(true, ERR_USERBANK_EXISTS_NO, ERR_USERBANK_EXISTS_MSG);
        }
    }
    
    //删除银行卡
    public function delete() {
        //判断手机验证码是否正确
        $mobile = $this->api->in['mobile'];
        $this->load->model('m_user');
        $user_result = $this->m_user->detail($this->api->user()->user_id);
        if($user_result->loginname != $mobile){ //判断该验证手机号是否和用户token对应
            $this->api->output(false, ERR_PHONE_ERROR_NO, ERR_PHONE_ERROR_MSG);
            exit;
        }
        $mobilecode = $this->api->in['mobilecode'];
        $_mobilecode = md5($mobilecode.date('Ymdh'));
        if($mobile != $_SESSION['mobile'] || $_mobilecode != $_SESSION['mobilecode']){
                $this->api->output(false, ERR_MOBILE_CODE_ERROR_NO, ERR_MOBILE_CODE_ERROR_MSG);
                exit;
        }
        
        $this->load->model('m_account');
        $r = $this->m_account->detail($this->api->user()->user_id);
        if(!$r){    //资金账户不存在
            $this->api->output(false, ERR_ACCOUNT_NOT_EXISTS_NO, ERR_ACCOUNT_NOT_EXISTS_MSG);
        }
        else{
            if($r->total != 0){ //账户总资产不为0，无法删除
                $this->api->output(false, ERR_ACCOUNT_NOT_EMPTY_NO, ERR_ACCOUNT_NOT_EMPTY_MSG);
            }
            else{   //账户总资产不为0，进行删除，做记录，记录到另一张表
                $userbank = $this->m_userbank->_detail($this->api->user()->user_id);
                
                if(!$userbank || !$userbank['account']){
                    $this->api->output(false, ERR_USERBANK_NOT_EXISTS_NO, ERR_USERBANK_NOT_EXISTS_MSG);
                    
                }
                else{
                    if($userbank['status'] != 2){   //原银行卡绑定完成
                       $log_result = $this->m_userbank->add_delete_log($userbank);    //记录到另一张表
                        if($log_result){
                            $delete_result = $this->m_userbank->delete($userbank['id']);
                            $this->api->output($delete_result);
                        }
                        else{
                            $this->api->output(false, ERR_USERBANK_DELETE_LOG_ERROR_NO, ERR_USERBANK_DELETE_LOG_ERROR_MSG);
                        } 
                    }
                    else{   //原银行卡尚未绑定完成
                        $this->api->output(false, ERR_USERBANK_NOT_BIND_NO, ERR_USERBANK_NOT_BIND_MSG);
                    }
                }
                
            }
        }
        
        exit;
    }
    
    //账户总资产不为0时,可以提交修改银行卡的申请
    public function modify() {
        do_log($this->api->in);
        $this->load->model('m_account');
        $r = $this->m_account->detail($this->api->user()->user_id);
        if(!$r){    //资金账户不存在
            $this->api->output(false, ERR_ACCOUNT_NOT_EXISTS_NO, ERR_ACCOUNT_NOT_EXISTS_MSG);
        }
        else{
            if($r->total == 0){ //账户总资产为0，无法修改
                $this->api->output(false, ERR_ACCOUNT_EMPTY_NO, ERR_ACCOUNT_EMPTY_MSG);
            }
            else{
                $userbank = $this->m_userbank->detail($this->api->user()->user_id);
                $this->load->model('m_cash');
                $cash = $this->m_cash->uncheck_item($this->api->user()->user_id);
                if(!$userbank || !$userbank->account ){      //银行卡不存在
                    $this->api->output(false, ERR_USERBANK_NOT_EXISTS_NO, ERR_USERBANK_NOT_EXISTS_MSG);
                }
                elseif($cash){  //有待审核提现
                    $this->api->output(false, ERR_UNCHECK_CASH_EXISTS_NO, ERR_UNCHECK_CASH_EXISTS_MSG);
                }
                else{
                    if($userbank->status != 2){   //原银行卡绑定完成
                        $condition['is_delete'] = STATUS_NOT_DELETE;
                        $condition['status'] = self::STATUS_MODIFY_PROCESSING;
                        $modify_log = $this->m_userbank->modify_log_detail_by_userid($this->api->user()->user_id,$condition);    //获取正在审核中未删除的修改记录表详情
                        if($modify_log){    //仍有未审核的修改记录
                            $this->api->output(false, ERR_CHECK_STATUS_PROCESSING_NO, ERR_CHECK_STATUS_PROCESSING_MSG);
                        }
                        else{
                            $this->load->model('m_user');
                            $user_detail = $this->m_user->detail($this->api->user()->user_id,true);
                            do_log($this->api->in);
                            if($this->api->in['newbank_pic'] == 'undefined' || $this->api->in['hand_pic'] == 'undefined' || $this->api->in['behind_pic'] == 'undefined' || $this->api->in['front_pic'] == 'undefined'){
                                $this->api->output(false, ERR_USERBANK_MODIFY_PIC_NOT_EXISTS_NO, ERR_USERBANK_MODIFY_PIC_NOT_EXISTS_MSG);
                                exit;
                            }
                            $param = array(
                                'mobile' => $this->api->user()->mobile,
                                'user_id' => $this->api->user()->user_id,
                                'realname' => $user_detail->realname,
                                'status' => self::STATUS_MODIFY_PROCESSING,
                                'account' => $this->api->in['account'],
                                'bank' => $this->api->in['bank'],
                                'province' => $this->api->in['province'],
                                'city' => $this->api->in['city'],
                                'area' => $this->api->in['area'],
                                'reason' => $this->api->in['reason'],
                                'front_pic' => $this->api->in['front_pic'],
                                'behind_pic' => $this->api->in['behind_pic'],
                                'hand_pic' => $this->api->in['hand_pic'],
                                'newbank_pic' => $this->api->in['newbank_pic'],
                                'old_account' => $userbank->account,
                                'old_bank' => $userbank->bank,
                                'old_province' => $userbank->province,
                                'old_city' => $userbank->city,
                                'old_area' => $userbank->area
                            );
                            do_log($param);
                            $log_result = $this->m_userbank->add_modify_log($param);    //记录到修改表
                            
                            if($log_result){
                                //更新银行卡状态为审核中
                                $update_param = array(
                                    'check_status' => self::STATUS_MODIFY_PROCESSING,
                                    'update_time' => time()
                                );
                                $update_result = $this->m_userbank->update($userbank->id,$update_param);
                                $this->api->output($update_result);
                            }
                            else{
                                $this->api->output(false, ERR_USERBANK_DELETE_LOG_ERROR_NO, ERR_USERBANK_DELETE_LOG_ERROR_MSG);
                            }
                        }
                    }
                    else{   //原银行卡尚未绑定完成
                        $this->api->output(false, ERR_USERBANK_NOT_BIND_NO, ERR_USERBANK_NOT_BIND_MSG);
                    }
                }
                
            }
        }
    }
    
    //账户总资产不为0时,审核通过可以修改银行卡
    public function check() {
        $modify_log = $this->m_userbank->modify_log_detail($this->api->in['id'],$condition); 
        if($modify_log->status['id'] != 3){
            $this->api->output(false, ERR_USERBANK_MODIFY_LOG_EXISTS_NO, ERR_USERBANK_MODIFY_LOG_EXISTS_MSG);
        }
        $user_id = $modify_log->user_id;
        
        $this->load->model('m_account');
        $r = $this->m_account->detail($user_id);
        if(!$r){    //资金账户不存在
            $this->api->output(false, ERR_ACCOUNT_NOT_EXISTS_NO, ERR_ACCOUNT_NOT_EXISTS_MSG);
        }
        else{
            if($r->total == 0){ //账户总资产为0，无法修改
                $this->api->output(false, ERR_ACCOUNT_EMPTY_NO, ERR_ACCOUNT_EMPTY_MSG);
            }
            else{
                $userbank = $this->m_userbank->detail($user_id);
                if(!$userbank || !$userbank->account ){      //银行卡不存在
                    $this->api->output(false, ERR_USERBANK_NOT_EXISTS_NO, ERR_USERBANK_NOT_EXISTS_MSG);
                }
                else{
                    if($userbank->status != 2){   //原银行卡绑定完成
                        if($this->api->in['status'] == self::STATUS_MODIFY_SUCCESS){
                            $param = array(
                                'status' => $this->api->in['status']
                            );
                            $log_result = $this->m_userbank->update_modify_log($this->api->in['id'],$param);    //更新到修改表
                            if($log_result){    //更新银行卡状态为审核通过并更新其他信息
                                $condition['is_delete'] = STATUS_NOT_DELETE;
                                $modify_log = $this->m_userbank->modify_log_detail($this->api->in['id'],$condition);    //获取修改记录表详情
                                $update_param = array(
                                    'account' => $modify_log->account,
                                    'bank' => $modify_log->bank['id'],
                                    'province' => $modify_log->province['id'],
                                    'city' => $modify_log->city['id'],
                                    'area' => $modify_log->area['id'],
                                    'check_status' => self::STATUS_MODIFY_SUCCESS,
                                    'update_time' => time()
                                );
                                $update_result = $this->m_userbank->update($userbank->id,$update_param);
                                if($update_result){ //修改成功发送短信提醒
                                    $this->load->model('m_user');
                                    $user = $this->m_user->detail($user_id);
                                    $mobile = $user->mobile;
                                    $content = '您的银行卡信息变更已成功。';
                                    $this->m_userbank->check_mobile_code($mobile,$content,$user_id);
                                }
                                $this->api->output($update_result);
                            }
                        }
                        else if($this->api->in['status'] == self::STATUS_MODIFY_FAILED){
                            $param = array(
                                'status' => $this->api->in['status'],
                                'fail_reason' => $this->api->in['fail_reason'],
                                'advice' => $this->api->in['advice']
                            );
                            $log_result = $this->m_userbank->update_modify_log($this->api->in['id'],$param);    //更新到修改表
                            if($log_result){    //更新银行卡状态为审核失败并更新其他信息
                                $condition['is_delete'] = STATUS_NOT_DELETE;
                                $modify_log = $this->m_userbank->modify_log_detail($this->api->in['id'],$condition);    //获取修改记录表详情
                                $update_param = array(
                                    'check_status' => self::STATUS_MODIFY_FAILED,
                                    'update_time' => time()
                                );
                                $update_result = $this->m_userbank->update($userbank->id,$update_param);
                                if($update_result){ //修改失败发送短信提醒
                                    $this->load->model('m_user');
                                    $user = $this->m_user->detail($user_id);
                                    $mobile = $user->mobile;
                                    $content = '您的银行卡信息变更审核未通过，未通过原因为：'.$this->api->in['fail_reason'].'，银行卡信息不做变更，原银行卡仍可用。若仍需变更银行卡，可重新提交信息。';
                                    $this->m_userbank->check_mobile_code($mobile,$content,$user_id);
                                }
                                $this->api->output($update_result);
                            }
                        }
                        else{   //设置审核状态错误
                            $this->api->output(false, ERR_CHECK_STATUS_ERROR_NO, ERR_CHECK_STATUS_ERROR_MSG);
                        }
                    }
                    else{   //原银行卡尚未绑定完成
                        $this->api->output(false, ERR_USERBANK_NOT_BIND_NO, ERR_USERBANK_NOT_BIND_MSG);
                    }
                }
            }
        }
    }
    
    public function cancel_modify() {
        $condition['is_delete'] = STATUS_NOT_DELETE;
        $condition['status'] = self::STATUS_MODIFY_FAILED;
        $modify_log = $this->m_userbank->modify_log_detail_by_userid($this->api->user()->user_id,$condition);
        if($modify_log){
            $r = $this->m_userbank->cancel_modify($this->api->user()->user_id);
            $this->api->output($r);
        }
        else{
            $this->api->output(false, ERR_USERBANK_MODIFY_LOG_NOT_EXISTS_NO, ERR_USERBANK_MODIFY_LOG_NOT_EXISTS_MSG);
        }
        
    }
    
    public function modify_log_detail() {//用户编辑信息界面获取信息需要未删除的记录
        $condition['is_delete'] = intval($this->api->in['is_delete']) ? intval($this->api->in['is_delete']) : STATUS_NOT_DELETE;
        $detail = $this->m_userbank->modify_log_detail_by_userid($this->api->user()->user_id,$condition);
        $this->api->output($detail);
    }
    
    public function modify_log_detail_admin() {
        $detail = $this->m_userbank->modify_log_detail($this->api->in['id'],$condition);
        $this->api->output($detail);
    }
    
    
    // 银行卡修改列表
    public function modify_log_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_userbank->modify_log_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_userbank->modify_log_count($condition);
        $this->api->output($r);
    }
    
}
