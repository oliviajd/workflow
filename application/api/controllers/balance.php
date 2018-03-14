<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of balance_account
 *
 * @author lsk
 */
class balance extends CI_Controller{
    
    const STATUS_BALANCE_INIT = 0;
    const STATUS_BALANCE_CORRECT = 1;
    const STATUS_BALANCE_PROCESS = 2;
    const STATUS_BALANCE_ERROR = 3;
    
    public function __construct() {
        parent::__construct();
        $this->load->model('m_balance_account');
        $this->load->model('m_balance_log');
        $this->load->model('m_balance_borrow');
        $this->load->model('m_balance_error');
    }
    public function account_init() {
        $this->load->model('m_account');
        $this->load->model('m_script');
        
        //读取上次记录的最后新增的borrow的ID
        $key_last_account_id = 'LAST_ACCOUNT_ID';
        if (!$this->m_script->isset_key(__FUNCTION__, $key_last_account_id)) {
            $this->m_script->set_value(__FUNCTION__, $key_last_account_id, 0);
        }
        $last_account_id = $this->m_script->get_value(__FUNCTION__, $key_last_account_id);
        
        $this->db->where('id > ' . $last_account_id, false, false);
        $this->db->limit(10);
        $account_list = $this->m_account->lists();
        
        
        foreach($account_list as $k =>$v){
            $balance_detail = $this->m_balance_account->detail($v->user_id);
             
            if(!$balance_detail){
                $param['user_id'] = intval($v->user_id);
                $param['create_time'] = time();
                $insert_r = $this->m_balance_account->add($param);
            }
            
        }
        if($insert_r){
            $this->m_script->set_value(__FUNCTION__,$key_last_account_id,$v->user_account_id);
        }
        
        
        //exit;
    }
    
    public function account_check() {
        //ini_set('display_errors', 1);
        //error_reporting(E_WARNING);
        set_time_limit(0);
        $this->load->model('m_account');
        $this->load->model('m_script');
        $condition = '';
        
        
        $key_last_balance_account_id = 'LAST_BALANCE_ACCOUNT_ID';
        $key_last_account_id = 'LAST_ACCOUNT_ID';
        
        
        if($last_account_id <= $last_balance_account_id){
            
            if($this->m_account->count($condition) > $this->m_balance_account->count($condition)){
                $this->account_init();
            }
        }
        
        
        //从上次检查的ID开始继续检查
        if (!$this->m_script->isset_key(__FUNCTION__, $key_last_balance_account_id)) {
            $this->m_script->set_value(__FUNCTION__, $key_last_balance_account_id, 0);
        }
        $last_balance_account_id = $this->m_script->get_value(__FUNCTION__, $key_last_balance_account_id);
        $this->db->where('id > ',$last_balance_account_id);
        $balance_list = $this->m_balance_account->lists('','','','');
        
        foreach($balance_list as $k => $v){
            $account_detail = $this->m_account->detail($v->user_id);
            
            if($account_detail->modify_time >=$v->modify_time){
                $r = $this->account_detail_check($account_detail,$v);
            }
        }
        $condition['status'] = self::STATUS_BALANCE_ERROR;
        $error_list = $this->m_balance_account->lists($condition,'','','');
        $error_list['total'] = $this->m_balance_account->count($condition,'','','');
        //var_dump($error_list);
        echo "<script>window.location.reload();</script>";
        $this->api->output(true);
    }
    
    public function account_detail_check($account_detail,$balance_detail) {
        //检查每个account账户是否有负值(balance_frost字段除外)
        
        //记录本次检查的借款标的nid
        $this->load->model('m_script');
        $key_last_balance_account_id = 'LAST_BALANCE_ACCOUNT_ID';
        $this->m_script->set_value('account_check', $key_last_balance_account_id,  $balance_detail->id );
            
        if($account_detail->total == 0){
            $param['status'] = self::STATUS_BALANCE_CORRECT;
            $param['modify_time'] = time();
            $param['account_log_id'] = $v['id'];
            $param['remark'] = '';
            $r = $this->m_balance_account->update($balance_detail->id, $param);
            return $r;
        }
        foreach ($account_detail as $k => $v){
            if($k != 'balance_frost' && intval($v)<0){
                //TODO 记录异常状态，记录异常报警表
                $param['status'] = self::STATUS_BALANCE_ERROR;
                $param['remark'] = $k.'字段为负值';
                $param['modify_time'] = time();
                $r = $this->m_balance_account->update($balance_detail->id, $param);
                $this->add_error_log($account_detail->user_id,'',self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                return $r;
            }
        }
        if(bccomp($account_detail->total , $account_detail->balance + $account_detail->frost + $account_detail->await)){
            $param['status'] = self::STATUS_BALANCE_ERROR;
            $param['remark'] = '资产总额(total):('.$account_detail->total.')不等于可用金额+冻结金额+待收金额(balance+frost+await):'.'('.$account_detail->balance.')('.$account_detail->frost.')('.$account_detail->await.')';
            $param['modify_time'] = time();
            $r = $this->m_balance_account->update($balance_detail->id, $param);
            $this->add_error_log($account_detail->user_id,'',self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
            return $r;
        }
        if($balance_detail->status != self::STATUS_BALANCE_ERROR){
            $r = $this->account_log_check($account_detail->user_id);
            return $r;
        }
        
    }
    
    public function account_log_check($user_id){
        $balance_account = $this->m_balance_account->detail($user_id);
        if($balance_account->status != self::STATUS_BALANCE_ERROR){
            $last_log_id = $balance_account->account_log_id;
            $this->db->limit(200);
            $this->db->order_by('id', 'ASC');
            $this->db->where('id > '.$last_log_id);
            $this->db->where('user_id', $user_id);
            $this->db->where('addtime > ', '1483200000');  //筛选2017年之后的记录
            $rows = $this->db->get_where(TABLE_ACCOUNT_LOG)->result_array();
            
            foreach ($rows as $k => $v) {
                $param['status'] = self::STATUS_BALANCE_ERROR;
                $param['modify_time'] = time();
                $param['account_log_id'] = $v['id'];
                if(bccomp($v['total'] , $v['balance'] + $v['frost'] + $v['await'])){
                    $param['remark'] = '资金记录ID:'.$v['id'].',资产总额(total):('.$v['total'].')不等于可用金额+冻结金额+待收金额(balance+frost+await):'.'('.$v['balance'].')+('.$v['frost'].')+('.$v['await'].')=('.($v['balance'] + $v['frost'] + $v['await']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                /*if(bccomp($v['balance'] , $v['balance_cash'] + $v['balance_frost'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',可用金额(balance):('.$v['balance'].')不等于可提现金额+不可提现金额(balance_cash+balance_frost):'.'('.$v['balance_cash'].')+('.$v['balance_frost'].')=('.($v['balance_cash'] + $v['balance_frost']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }*/
                if(bccomp($v['income'] , $v['income_old'] + $v['income_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',收入(income):('.$v['income'].')不等于原有收入+新增收入(income_old+income_new):'.'('.$v['income_old'].')+('.$v['income_new'].')=('.($v['income_old'] + $v['income_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['expend'] , $v['expend_old'] + $v['expend_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',支出(expend):('.$v['expend'].')不等于原有支出+新增支出(expend_old+expend_new):'.'('.$v['expend_old'].')+('.$v['expend_new'].')=('.($v['expend_old'] + $v['expend_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['balance'] , $v['balance_old'] + $v['balance_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',可用余额(balance):('.$v['balance'].')不等于原有可用余额+新增可用余额(balance_old+balance_new):'.'('.$v['balance_old'].')+('.$v['balance_new'].')=('.($v['balance_old'] + $v['balance_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['balance_cash'] , $v['balance_cash_old'] + $v['balance_cash_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',提现金额(balance_cash):('.$v['balance_cash'].')不等于原有提现金额+新增提现金额(balance_cash_old+balance_cash_new):'.'('.$v['balance_cash_old'].')+('.$v['balance_cash_new'].')=('.($v['balance_cash_old'] + $v['balance_cash_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['balance_frost'] , $v['balance_frost_old'] + $v['balance_frost_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',不可提现金额(balance_frost):('.$v['balance_frost'].')不等于原有不可提现金额+新增不可提现金额(balance_frost_old+balance_frost_new):'.'('.$v['balance_frost_old'].')+('.$v['balance_frost_new'].')=('.($v['balance_frost_old'] + $v['balance_frost_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['frost'] , $v['frost_old'] + $v['frost_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',冻结金额(frost):('.$v['frost'].')不等于原有冻结金额+新增冻结金额(frost_old+frost_new):'.'('.$v['frost_old'].')+('.$v['frost_new'].')=('.($v['frost_old'] + $v['frost_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['await'] , $v['await_old'] + $v['await_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',待收金额(await):('.$v['await'].')不等于原有待收金额+新增待收金额(await_old+await_new):'.'('.$v['await_old'].')+('.$v['await_new'].')=('.($v['await_old'] + $v['await_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if(bccomp($v['hbag'] , $v['hbag_old'] + $v['hbag_new'] )){
                    $param['remark'] = '资金记录ID:'.$v['id'].',红包金额(hbag):('.$v['hbag'].')不等于原有红包额+新增红包金额(hbag_old+hbag_new):'.'('.$v['hbag_old'].')+('.$v['hbag_new'].')=('.($v['hbag_old'] + $v['hbag_new']).')';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    $this->add_error_log($user_id,$v['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                    return $r;
                }
                if($k != count($rows) - 1){//除了最后一个
                    if(bccomp($v['balance_cash'] , $rows[$k+1]['balance_cash_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录提现(balance_cash):('.$v['balance_cash'].')不等于本条记录原有提现(balance_cash_old):'.'('.$rows[$k+1]['balance_cash_old'].')';
                    }
                    if(bccomp($v['balance_frost'] , $rows[$k+1]['balance_frost_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录不可提现金额(balance_frost):('.$v['balance_frost'].')不等于本条记录原有不可提现金额(balance_frost_old):'.'('.$rows[$k+1]['balance_frost_old'].')';
                    }
                    if(bccomp($v['balance'] , $rows[$k+1]['balance_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录可用金额(balance):('.$v['balance'].')不等于本条记录原有可用金额(balance_old):'.'('.$rows[$k+1]['balance_old'].')';
                    }
                    
                    if(bccomp($v['income'] , $rows[$k+1]['income_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录收入(income):('.$v['income'].')不等于本条记录原有收入(income_old):'.'('.$rows[$k+1]['income_old'].')';
                    }
                    if(bccomp($v['expend'] , $rows[$k+1]['expend_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录支出(expend):('.$v['expend'].')不等于本条记录原有支出(expend_old):'.'('.$rows[$k+1]['expend_old'].')';
                    }
                    if(bccomp($v['await'] , $rows[$k+1]['await_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录待收金额(await):('.$v['income'].')不等于本条记录代收金额(await_old):'.'('.$rows[$k+1]['await_old'].')';
                    }
                    if(bccomp($v['frost'] , $rows[$k+1]['frost_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录冻结金额(frost):('.$v['frost'].')不等于本条记录原有冻结金额(frost_old):'.'('.$rows[$k+1]['frost_old'].')';
                    }
                    if(bccomp($v['total'] , $rows[$k+1]['total_old'])){
                        $param['remark'] = '资金记录ID:'.$rows[$k+1]['id'].',上条记录总资产(total):('.$v['total'].')不等于本条记录原有总资产(total_old):'.'('.$rows[$k+1]['total_old'].')';
                    }
                    
                    if($param['remark']){
                        $param['status'] = self::STATUS_BALANCE_ERROR;
                        $param['modify_time'] = time();
                        $param['account_log_id'] = $rows[$k+1]['id'];
                        $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                        $this->add_error_log($user_id,$rows[$k+1]['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),1,$param['remark']);
                        return $r;
                    }
                    else{
                        $param['status'] = self::STATUS_BALANCE_PROCESS;
                        $param['modify_time'] = time();
                        $param['account_log_id'] = $rows[$k+1]['id'];
                        $param['remark'] = '';
                        $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    }
                    
                }
                else{
                    $param['status'] = self::STATUS_BALANCE_CORRECT;
                    $param['modify_time'] = time();
                    $param['account_log_id'] = $v['id'];
                    $param['remark'] = '';
                    $r = $this->db->update(TABLE_BALANCE_ACCOUNT, $param, array('user_id' => $user_id));
                    return $r;
                }
            }
        }
    }

    public function account_add() {
        $id = $this->m_balance_account->add($this->api->in);
        $r = $this->m_balance_account->detail($id);
        $this->api->output($r);
    }

    public function account_update() {
        $id = $this->api->in['id'];
        if (!$this->m_balance_account->detail($id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_balance_account->update($id, $this->api->in);
        $r = $this->m_balance_account->detail($id);
        $this->api->output($r);
    }

    public function account_get() {
        $r = $this->m_balance_account->detail($this->api->in['id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function account_delete() {
        $r = $this->m_balance_account->detail($this->api->in['id']);
        if ($r) {
            $r2 = $this->m_balance_accounty->delete($this->api->in['id']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    
    public function log_check(){
        $this->load->model('m_account_log');
        $this->load->model('m_script');
        $condition = '';
        $order = 'id DESC';
        //查找最后查询log的记录
        $balance_log = $this->m_balance_log->get($condition,$order);
        //查找ID最大的log的记录
        $account_log = $this->m_account_log->get($condition,$order);
        
        $key_last_log_id = 'LAST_BALANCE_LOG_ID';
        if (!$this->m_script->isset_key(__FUNCTION__,$key_last_log_id)) {
            $this->m_script->set_value(__FUNCTION__,$key_last_log_id,0);
        }
        
        $last_log_id = $this->m_script->get_value(__FUNCTION__,$key_last_log_id);
        
        
        if(empty($balance_log) || $last_log_id < $account_log['id']){
            $last_log_id = $last_log_id ? $last_log_id : 0;
            
            $this->db->limit(100);
            $this->db->order_by('id', 'ASC');
            $this->db->where('id > '.$last_log_id);
            $this->db->where('addtime > ', '1483200000');  //筛选2017年之后的记录
            $log_list = $this->db->get_where(TABLE_ACCOUNT_LOG)->result_array();
            
            foreach($log_list as $k => $v){
                
                $r = $this->log_detail_check($v);
                
                //查询所有log的对账错误列表
                /*$condition['status'] = self::STATUS_BALANCE_ERROR;
                $error_list = $this->m_balance_log->lists($condition);
                $error_list['total'] = $this->m_balance_log->count($condition);*/
                $this->m_script->set_value(__FUNCTION__,$key_last_log_id,$v['id']);
            }
            //var_dump($error_list);
            
        }
        echo "<script>window.location.reload();</script>";
        $this->api->output(true);
    }
    
    public function log_detail_check($log_detail){
        //检查每条log是否有负值(balance_frost字段除外)
        foreach ($log_detail as $k => $v){
            $block_array = array('balance_frost','balance_new','balance_frost_old','frost_new','balance_frost_new','balance_cash_new','await_new');
            if(!in_array($k,$block_array) && intval($v)<0){
                //TODO 记录异常状态，记录异常报警表
                $param['status'] = self::STATUS_BALANCE_ERROR;
                $param['remark'] = '资金记录ID:'.$log_detail['id'].'的'.$k.'字段为负值';
                $param['user_id'] = intval($log_detail['user_id']);
                $param['create_time'] = time();
                $param['account_log_id'] = intval($log_detail['id']);
                $r = $this->m_balance_log->add( $param);
                $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
                return $r;
            }
            
        }
        $param['status'] = self::STATUS_BALANCE_ERROR;
        $param['user_id'] = intval($log_detail['user_id']);
        $param['create_time'] = time();
        $param['account_log_id'] = intval($log_detail['id']);
        
        if(bccomp($log_detail['total'] , $log_detail['balance'] + $log_detail['frost'] + $log_detail['await'])){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',资产总额(total):('.$log_detail['total'].')不等于可用金额+冻结金额+待收金额(balance+frost+await):'.'('.$log_detail['balance'].')+('.$log_detail['frost'].')+('.$log_detail['await'].')=('.($log_detail['balance'] + $log_detail['frost'] + $log_detail['await']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        /*if(bccomp($log_detail['balance'] , $log_detail['balance_cash'] + $log_detail['balance_frost'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',可用金额(balance):('.$log_detail['balance'].')不等于可提现金额+不可提现金额(balance_cash+balance_frost):'.'('.$log_detail['balance_cash'].')+('.$log_detail['balance_frost'].')=('.($log_detail['balance_cash'] + $log_detail['balance_frost']).')';
            $r = $this->m_balance_log->add( $param);   
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }*/
        if(bccomp($log_detail['income'] , $log_detail['income_old'] + $log_detail['income_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',收入(income):('.$log_detail['income'].')不等于原有收入+新增收入(income_old+income_new):'.'('.$log_detail['income_old'].')+('.$log_detail['income_new'].')=('.($log_detail['income_old'] + $log_detail['income_new']).')';
            $r = $this->m_balance_log->add( $param); 
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['expend'] , $log_detail['expend_old'] + $log_detail['expend_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',支出(expend):('.$log_detail['expend'].')不等于原有支出+新增支出(expend_old+expend_new):'.'('.$log_detail['expend_old'].')+('.$log_detail['expend_new'].')=('.($log_detail['expend_old'] + $log_detail['expend_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['balance'] , $log_detail['balance_old'] + $log_detail['balance_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',可用余额(balance):('.$log_detail['balance'].')不等于原有可用余额+新增可用余额(balance_old+balance_new):'.'('.$log_detail['balance_old'].')+('.$log_detail['balance_new'].')=('.($log_detail['balance_old'] + $log_detail['balance_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['balance_cash'] , $log_detail['balance_cash_old'] + $log_detail['balance_cash_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',提现金额(balance_cash):('.$log_detail['balance_cash'].')不等于原有提现金额+新增提现金额(balance_cash_old+balance_cash_new):'.'('.$log_detail['balance_cash_old'].')+('.$log_detail['balance_cash_new'].')=('.($log_detail['balance_cash_old'] + $log_detail['balance_cash_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['balance_frost'] , $log_detail['balance_frost_old'] + $log_detail['balance_frost_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',不可提现金额(balance_frost):('.$log_detail['balance_frost'].')不等于原有不可提现金额+新增不可提现金额(balance_frost_old+balance_frost_new):'.'('.$log_detail['balance_frost_old'].')+('.$log_detail['balance_frost_new'].')=('.($log_detail['balance_frost_old'] + $log_detail['balance_frost_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['frost'] , $log_detail['frost_old'] + $log_detail['frost_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',冻结金额(frost):('.$log_detail['frost'].')不等于原有冻结金额+新增冻结金额(frost_old+frost_new):'.'('.$log_detail['frost_old'].')+('.$log_detail['frost_new'].')=('.($log_detail['frost_old'] + $log_detail['frost_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['await'] , $log_detail['await_old'] + $log_detail['await_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',待收金额(await):('.$log_detail['await'].')不等于原有待收金额+新增待收金额(await_old+await_new):'.'('.$log_detail['await_old'].')+('.$log_detail['await_new'].')=('.($log_detail['await_old'] + $log_detail['await_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        if(bccomp($log_detail['hbag'] , $log_detail['hbag_old'] + $log_detail['hbag_new'] )){
            $param['remark'] = '资金记录ID:'.$log_detail['id'].',红包金额(hbag):('.$log_detail['hbag'].')不等于原有红包额+新增红包金额(hbag_old+hbag_new):'.'('.$log_detail['hbag_old'].')+('.$log_detail['hbag_new'].')=('.($log_detail['hbag_old'] + $log_detail['hbag_new']).')';
            $r = $this->m_balance_log->add( $param);
            $this->add_error_log($param['user_id'],$log_detail['borrow_nid'],self::STATUS_BALANCE_ERROR,time(),2,$param['remark']);
            return $r;
        }
        
    }
    
    public function borrow_check() {
        ini_set('display_errors', 1);
        error_reporting(E_WARNING);
        set_time_limit(0);
        
        $this->load->model('m_borrow');
        $this->load->model('m_script');
        $condition = '';
        
        if($this->m_borrow->count($condition) > $this->m_balance_borrow->count($condition)){
            $this->borrow_init();
        }
        //从上次检查的ID开始继续检查
        $key_last_balance_borrow_id = 'LAST_BALANCE_BORROW_ID';
        if (!$this->m_script->isset_key(__FUNCTION__, $key_last_balance_borrow_id)) {
            $this->m_script->set_value(__FUNCTION__, $key_last_balance_borrow_id, 0);
        }
        $last_balance_borrow_id = $this->m_script->get_value(__FUNCTION__, $key_last_balance_borrow_id);
        $this->db->where('id > ',$last_balance_borrow_id);
        $balance_list = $this->m_balance_borrow->lists();
        
        foreach($balance_list as $k => $v){
            $borrow_detail = $this->m_borrow->detail($v->borrow_id);
            if($borrow_detail->create_time >$v->modify_time){
                $r = $this->borrow_detail_check($borrow_detail,$v);
            }
        }
        $condition['status'] = self::STATUS_BALANCE_ERROR;
        $error_list = $this->m_balance_borrow->lists($condition);
        $error_list['total'] = $this->m_balance_borrow->count($condition);
        //var_dump($error_list);
        echo "<script>window.location.reload();</script>";
        $this->api->output(true);
    }
    
    public function borrow_init() {
        $this->load->model('m_script');

        //读取上次记录的最后新增的borrow的ID
        $key_last_borrow_id = 'LAST_BORROW_ID';
        if (!$this->m_script->isset_key(__FUNCTION__, $key_last_borrow_id)) {
            $this->m_script->set_value(__FUNCTION__, $key_last_borrow_id, 0);
        }
        $last_borrow_id = $this->m_script->get_value(__FUNCTION__, $key_last_borrow_id);
        
        $this->db->where('borrow_nid > ' . $last_borrow_id, false, false);
        $this->db->where('addtime > ', '1483200000');  //筛选2017年之后的记录
        $this->db->limit(100);
        $borrow_list = $this->m_borrow->lists('','','','');
        
        foreach($borrow_list as $k =>$v){
            if(!$balance_detail){
                $param['borrow_id'] = $v->borrow_id;
                $param['create_time'] = time();
                $insert_r = $this->m_balance_borrow->add($param);
            }
        }
        if($insert_r){
            //记录本次增加的borrow的ID
            $this->m_script->set_value(__FUNCTION__, $key_last_borrow_id, $v->borrow_id);
        }
        
    }
    
    public function borrow_detail_check($borrow_detail,$balance_detail) {
        //检查每个标
       
        if($balance_detail->status->id != self::STATUS_BALANCE_ERROR && $balance_detail->status->id != self::STATUS_BALANCE_CORRECT){
            
            $this->load->model('m_borrow_tender');
            $this->load->model('m_account_log');
            $condition['borrow_id'] = $balance_detail->borrow_id;
            //TODO:这里有一些标会因为时间太近还没记录完毕资金记录导致报错
            //$condition['borrow_id'] = '20170200255';
            $condition['before_addtime'] = time() - 180;
            $tender_list = $this->m_borrow_tender->lists($condition);
            
            $condition['type'] = 'tender_success';
            $log_list_1 = $this->m_account_log->lists($condition);
            
            $condition['type'] = 'tender_success_frost';
            $log_list_2 = $this->m_account_log->lists($condition);
            
            $condition['type'] = 'tender';
            $log_list_3 = $this->m_account_log->lists($condition);
            
            foreach ($tender_list as $k => $v) {
                $tender_money += $v['account'];
            }
            foreach ($log_list_1 as $k => $v) {
                $log_money_1 += $v['money'];
            }
            foreach ($log_list_2 as $k => $v) {
                $log_money_2 += $v['await_new'];
            }
            foreach ($log_list_3 as $k => $v) {
                $log_money_3 += $v['frost_new'];
            }
            foreach ($log_list_2 as $k => $v) {
                $log_money_4 += $v['money'];
            }
            
            $param['status'] = self::STATUS_BALANCE_ERROR;
            $param['modify_time'] = time();
            
            //记录本次检查的借款标的nid
            $this->load->model('m_script');
            
            $key_last_balance_borrow_id = 'LAST_BALANCE_BORROW_ID';
            
            $this->m_script->set_value('borrow_check', $key_last_balance_borrow_id,  $balance_detail->id );
            
            if(bccomp($borrow_detail->money_get , $tender_money )){
                $param['remark'] = '标的(borrow)ID:'.$borrow_detail->borrow_id.',已投金额(borrow_account_yes):('.$borrow_detail->money_get.')不等于投资金额总和(tender_money):'.'('.$tender_money.')';
                $r = $this->db->update(TABLE_BALANCE_BORROW, $param, array('borrow_id' => $borrow_detail->borrow_id));
                $this->add_error_log('',$borrow_detail->borrow_id,self::STATUS_BALANCE_ERROR,time(),3,$param['remark']);
                return $r;
            }
            if(bccomp($borrow_detail->money_get , $log_money_1 )){
                $param['remark'] = '标的(borrow)ID:'.$borrow_detail->borrow_id.',已投金额(borrow_account_yes):('.$borrow_detail->money_get.')不等于资金记录中的投资成功金额总和(tender_success):'.'('.$log_money_1.')';
                $r = $this->db->update(TABLE_BALANCE_BORROW, $param, array('borrow_id' => $borrow_detail->borrow_id)); 
                $this->add_error_log('',$borrow_detail->borrow_id,self::STATUS_BALANCE_ERROR,time(),3,$param['remark']);
                return $r;
            }
            $tender_success_frost = round($borrow_detail->money_get*(1+($borrow_detail->rate/100)*round($borrow_detail->period*30)/360),1);
            if(bccomp($tender_success_frost , round($log_money_2,1) ) ){
                $param['remark'] = '标的(borrow)ID:'.$borrow_detail->borrow_id.',已投金额(borrow_account_yes):('.$tender_success_frost.')不等于资金记录中的解冻(tender_success_frost)待收金额(await_new)总和:'.'('.round($log_money_2,1).')';
                $r = $this->db->update(TABLE_BALANCE_BORROW, $param, array('borrow_id' => $borrow_detail->borrow_id));
                $this->add_error_log('',$borrow_detail->borrow_id,self::STATUS_BALANCE_ERROR,time(),3,$param['remark']);
                return $r;
            }
            if(bccomp($tender_success_frost , $log_money_4 )){
                $param['remark'] = '标的(borrow)ID:'.$borrow_detail->borrow_id.',已投金额(borrow_account_yes):('.$tender_success_frost.')不等于资金记录中的解冻金额总和(tender_success_frost):'.'('.$log_money_4.')';
                $r = $this->db->update(TABLE_BALANCE_BORROW, $param, array('borrow_id' => $borrow_detail->borrow_id));
                $this->add_error_log('',$borrow_detail->borrow_id,self::STATUS_BALANCE_ERROR,time(),3,$param['remark']);
                return $r;
            }
            
            if(isset($log_money_3) && bccomp($borrow_detail->money_get , $log_money_3 )){
                $param['remark'] = '标的(borrow)ID:'.$borrow_detail->borrow_id.',已投金额(borrow_account_yes):('.$borrow_detail->money_get.')不等于资金记录中的投标冻结金额总和(tender):'.'('.$log_money_3.')';
                $r = $this->db->update(TABLE_BALANCE_BORROW, $param, array('borrow_id' => $borrow_detail->borrow_id));
                $this->add_error_log('',$borrow_detail->borrow_id,self::STATUS_BALANCE_ERROR,time(),3,$param['remark']);
                return $r;
            }
            $param['status'] = self::STATUS_BALANCE_CORRECT;
            $r = $this->db->update(TABLE_BALANCE_BORROW, $param, array('borrow_id' => $borrow_detail->borrow_id));
            return $r;
            
        }
        
    }
    
    public function add_error_log($user_id='',$borrow_id='',$status,$create_time,$category,$remark) {
        $error_log['user_id'] = $user_id;
        $error_log['borrow_id'] = $borrow_id;
        $error_log['status'] = $status;
        $error_log['create_time'] = $create_time;
        $error_log['category'] = $category;
        $error_log['remark'] = $remark;
        $this->m_balance_error->add($error_log);
    }
    
    public function account_list() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['q']) {
            $this->load->model('m_user');
            $users = $this->m_user->find($condition['q']);
            if(!empty($users)){
                $condition['user_id'] = $users;
            }
        }
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_balance_account->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_balance_account->count($condition);
        $this->api->output($r);
    }
    
    public function borrow_list() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['q']) {
            $condition['borrow_id'] = $condition['q'];
        }
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_balance_borrow->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_balance_borrow->count($condition);
        $this->api->output($r);
    }
    
    public function log_list() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['q']) {
            $this->load->model('m_user');
            $users = $this->m_user->find($condition['q']);
            if(!empty($users)){
                $condition['user_id'] = $users;
            }
        }
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_balance_log->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_balance_log->count($condition);
        $this->api->output($r);
    }
    
    public function error_list() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($condition['q']) {
            $this->load->model('m_user');
            $users = $this->m_user->find($condition['q']);
            if(!empty($users)){
                $condition['user_id'] = $users;
            }
            else{
                $condition['borrow_id'] = $condition['q'];
            }
        }
        if ($condition['status']) {
            $condition['status'] = explode(',', $condition['status']);
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_balance_error->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_balance_error->count($condition);
        $this->api->output($r);
    }
}
