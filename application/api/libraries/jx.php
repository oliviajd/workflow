<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of jx
 *
 * @author win7
 */

namespace JiXin;

//require_once('./Retcodes.php');
//require_once(APPPATH.'libraries/Retcodes.php');



class config {

    const password = 'juchejinrong_uat@2016';
    const BASE_URL = 'https://access.credit2go.cn';
    const VERSION = '10';
    const instCode = '00970001';
    const bankCode = '30050000';
    const forgotPwdUrl = 'http://test.ifcar99.com';
    const retUrl = 'http://apitest.ifcar99.com/ba.return.url';
//    const notifyUrl = 'http://122.224.68.82:8002/ba.notify';//jhm本地
//    const notifyUrl = 'http://122.224.68.82:8003/ba.notify';//太子爷本地
    const notifyUrl = 'http://apitest.ifcar99.com/ba.notify'; //测试环境
    const file_bankcode ='3005';
    const file_productcode ='0087'; //SIT
    //const file_productcode ='0087'; //UAT
    
//      const idNo = '410303198704057397';
//      const name = '红包';
//      const mobile = '11101000300';
//      const cardNo = '3005988812340300';
      
      const idNo = '410303198704056239';
      const name = '手续费';
      const mobile = '11101000400';
      const cardNo = '3005988812340400';
      //const accountId = '6212461960000000044';
      
    const bouns_accountId = '6212461960000000036';
    const CHANNEL_APP = '000001';
    const CHANNEL_WEB = '000002';
    const CHANNEL_WECHAT = '000003';
    const CHANNEL_COUNTER = '000004';

    static function privateKeyFilePath() {
        return APPPATH . 'libraries/jx/key/juchejinrong_uat.p12';
    }

    static function PublicKeyFilePath() {
        return APPPATH . 'libraries/jx/key/juchejinrong_uat.crt';
    }

    static function serverPublicKeyFilePath() {
        return APPPATH . 'libraries/jx/key/fdep.crt';
    }

}

class api {

    public function __construct() {
        $this->header_param = array(
            ''
        );
    }

    private function sign($params) {
        
        ksort($params);
        $data = implode($params, '');
        openssl_pkcs12_read(file_get_contents(config::privateKeyFilePath()), $privateKey, config::password);
        $privateKey = $privateKey['pkey'];
        openssl_sign($data, $sign, $privateKey);
        return base64_encode($sign);
    }

    // 验证签名
    public function sign_verify($result) {
        $sign = base64_decode($result['sign']);
        unset($result['sign']);
        $sign_rude = '';
        $this->paramsToString($sign_rude, $result);
        $publicKey = openssl_x509_read(file_get_contents(config::serverPublicKeyFilePath()));
        $this->sign_rude = $sign_rude;
        return openssl_verify($sign_rude, $sign, $publicKey);
    }

    // 递归按照字典顺序
    private function paramsToString(&$sign_rude, $params) {
        ksort($params);
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                $params[$key] = $this->paramsToString($sign_rude, $val);
            } else {
                $sign_rude .= $val;
            }
        }
        return $params;
    }

    public function accountOpen() {
        $url = '/escrow/p2p/page/mobile';
        $param = array(
            'version' => config::VERSION,
            'txCode' => 'accountOpen',
            'instCode' => config::instCode,
            'bankCode' => config::bankCode,
            'txDate' => date('Ymd'),
            'txTime' => date('His'),
            'seqNo' => counter::auto_id(),
            'channel' => '000002',
            'idType' => '01',
            'idNo' => config::idNo,
            'name' => config::name,
            'mobile' => config::mobile,
            'cardNo' => config::cardNo,
            'email' => '',
            'acctUse' => '00000',
            'retUrl' => config::retUrl,
            'notifyUrl' => config::notifyUrl,
            'acqRes' => '123',
            'userIP' => '',
        );
        $param['sign'] = $this->sign($param);
        //$r = curl_upload('https://test.credit2go.cn/escrow/p2p/page/mobile', $param, 10);
        $r = $this->request_form(config::BASE_URL . $url, $param);
        echo $r;
        do_log(array(
            'param' => $param,
            'result' => $r
        ));
    }

    public function accountOpenPlus($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION,
            'txCode' => 'accountOpenPlus',
            'instCode' => config::instCode,
            'bankCode' => config::bankCode,
            'txDate' => date('Ymd'),
            'txTime' => date('His'),
            'seqNo' => counter::auto_id(),
            'channel' => '000002',
            'idType' => '01',
            'idNo' => $data['idNo'],
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'cardNo' => $data['cardNo'],
            'email' => '',
            'acctUse' => '00000',
            'lastSrvAuthCode' => $data['lastSrvAuthCode'],
            'smsCode' => $data['smsCode'],
            'userIP' => '127.0.0.1',
            'acqRes' => '123',
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    public function cardBind() {  //解绑银行卡
        $url = '/escrow/p2p/page/mobile';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'cardBind', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'idType' => '01', //证件类型01-身份证（18位）
            'idNo' => config::idNo, //证件号码
            'name' => config::name, //姓名
            'mobile' => config::mobile, //手机号
            'cardNo' => config::cardNo, //银行卡号
            'retUrl' => config::retUrl, //前台跳转链接
            'notifyUrl' => config::notifyUrl, //后台通知地址(需指定到php文件)
            'userIP' => '', //客户IP,选填
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request_form(config::BASE_URL . $url, $param);
    }

    public function cardBindPlus($data) {//个人开户增强（无页面）
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'cardBindPlus', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'idType' => '01', //证件类型01-身份证（18位）
            'idNo' => $data['idNo'], //证件号码
            'name' => $data['name'], //姓名
            'mobile' => $data['mobile'], //手机号
            'cardNo' => $data['cardNo'], //银行卡号
            'lastSrvAuthCode' => $data['lastSrvAuthCode'], //前导业务授权码，通过请求发送短信验证码接口获取
            'smsCode' => $data['smsCode'], //手机接收到短信验证码
            'acqRes' => '', //请求方保留，选填
            'userIP' => '', //客户IP,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    public function cardUnbind($data) {  //解绑银行卡(无页面)
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'cardUnbind', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'idType' => '01', //证件类型01-身份证（18位）
            'idNo' => $data['idNo'], //证件号码
            'name' => $data['name'], //姓名
            'mobile' => $data['mobile'], //手机号
            'cardNo' => $data['cardNo'], //银行卡号
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    public function passwordSet($data) {  //密码设置
        $url = '/escrow/p2p/page/passwordset';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'passwordSet', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'idType' => '01', //证件类型01-身份证（18位）
            'idNo' => $data['idNo'], //证件号码
            'name' => $data['name'], //姓名
            'mobile' => $data['mobile'], //手机号
            'retUrl' => config::retUrl . '?txCode=passwordSet' . ($data['is_new'] ? 'New' : ''), //前台跳转链接
            'notifyUrl' => config::notifyUrl, //后台通知地址(需指定到php文件)
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request_form(config::BASE_URL . $url, $param);
    }

    public function passwordReset($data) {  //密码重置
        $url = '/escrow/p2p/page/mobile';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'passwordReset', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'idType' => '01', //证件类型01-身份证（18位）
            'idNo' => $data['idNo'], //证件号码
            'name' => $data['name'], //姓名
            'mobile' => $data['mobile'], //手机号
            'retUrl' => config::retUrl, //前台跳转链接
            'notifyUrl' => config::notifyUrl, //后台通知地址(需指定到php文件)
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request_form(config::BASE_URL . $url, $param);
    }

    public function passwordResetPlus() {//电子账户手机号修改增强（无页面）
        $url = '/escrow/p2p/page/mobile/plus';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'passwordResetPlus', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'idType' => '01', //证件类型01-身份证（18位）
            'idNo' => config::idNo, //证件号码
            'name' => config::name, //姓名
            'mobile' => config::mobile, //手机号
            'retUrl' => config::retUrl, //前台跳转链接
            'notifyUrl' => config::notifyUrl, //后台通知地址(需指定到php文件)
            'lastSrvAuthCode' => '1102709002574561220', //前导业务授权码，通过请求发送短信验证码接口获取
            'smsCode' => '111111', //手机接收到短信验证码
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        $r = $this->request_form(config::BASE_URL . $url, $param);
        var_dump(array(
            'param' => $param,
            'result' => $r
        ));
    }

    public function mobileModify() {  //电子账户手机号修改
        $url = '/escrow/p2p/page/mobileModify';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'mobileModify', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'option' => '1',
            'mobile' => '15699990001', //手机号
            'retUrl' => config::retUrl, //前台跳转链接
            'notifyUrl' => config::notifyUrl, //后台通知地址(需指定到php文件)
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        $this->request_form(config::BASE_URL . $url, $param);
    }

    public function smsCodeApply($data) {  //请求发送短信验证码（无页面）
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'smsCodeApply', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'mobile' => $data['mobile'], //手机号
            'srvTxCode' => $data['srvTxCode'],
            'acqRes' => '', //请求方保留，选填
        );
        if($data['srvTxCode'] == 'directRechargeOnline'){
            $param['reqType'] = $data['reqType'];
            $param['cardNo'] = $data['cardNo'];
        }
        
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    public function mobileModifyPlus() {//电子账户手机号修改增强（无页面）
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'mobileModifyPlus', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'option' => '1', //选项，1-修改
            'mobile' => config::mobile, //新手机号,TODO只能修改为原手机?此接口是否存在问题
            //'mobile' => '15005715101',  //新手机号
            'lastSrvAuthCode' => '1829833081230597635', //前导业务授权码，通过请求发送短信验证码接口获取
            'smsCode' => '111111', //手机接收到短信验证码
            'acqRes' => '', //请求方保留，选填
            'userIP' => '', //客户IP,选填
        );
        $param['sign'] = $this->sign($param);
        $r = $this->request(config::BASE_URL . $url, $param);
        var_dump(array(
            'param' => $param,
            'result' => $r
        ));
    }

    //投资人投标申请
    public function bidapply($data) {
        $url = '/escrow/p2p/page/bidapply';
        $param = array(
            'version' => config::VERSION,
            'txCode' => 'bidApply',
            'instCode' => config::instCode,
            'bankCode' => config::bankCode,
            'txDate' => date('Ymd'),
            'txTime' => date('His'),
            'seqNo' => counter::auto_id(),
            'channel' => $data['channel'],
            'accountId' => $data['accountId'],
            'orderId' => $data['orderId'],
            'txAmount' => $data['txAmount'],
            'productId' => $data['productId'],
            'bonusFlag' => 0,
            'bonusAmount' => 0,
            'frzFlag' => '1',
            'forgotPwdUrl' => 'http://jd.com',
            'retUrl' => config::retUrl, //前台跳转链接
            'notifyUrl' => config::notifyUrl, //后台通知地址(需指定到php文件)
            'acqRes' => rand(1000, 9999),
        );
        $param['sign'] = $this->sign($param);
        $r = $this->request_form(config::BASE_URL . $url, $param);
        return $r;
    }

    public function directrecharge() {
        $url = '/escrow/p2p/page/mobile';
        $param = array(
            'version' => config::VERSION,
            'txCode' => 'directRecharge',
            'instCode' => config::instCode,
            'bankCode' => config::bankCode,
            'txDate' => date('Ymd'),
            'txTime' => date('His'),
            'seqNo' => counter::auto_id(),
            'channel' => '000002',
            'accountId' => '6212461270000160610',
            'idType' => '01',
            'idNo' => '120107197810191363',
            'name' => '蒋丹',
            'mobile' => '13762059876',
            'cardNo' => '6217001210024455220',
            'txAmount' => 90000,
            'currency' => '156',
            'cardBankCode' => '',
            'cardBankNameCn' => '',
            'cardBankNameEn' => '',
            'cardBankProvince' => '',
            'cardBankCity' => '',
            'retUrl' => config::retUrl,
            'notifyUrl' => config::notifyUrl,
            'acqRes' => '123',
            'userIP' => '',
        );
        $param['sign'] = $this->sign($param);
        //r = $this->request(config::BASE_URL.$url, $param);
        $this->request_form(config::BASE_URL . $url, $param);
        exit();
    }

    //充值增强
    public function directRechargePlus($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION,
            'txCode' => 'directRechargeOnline',
            'instCode' => config::instCode,
            'bankCode' => config::bankCode,
            'txDate' => $data['txDate'],
            'txTime' => $data['txTime'],
            'seqNo' => $data['seqNo'],
            'channel' => $data['channel'],
            'accountId' => $data['accountId'],
            'idType' => '01',
            'idNo' => $data['idNo'],
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'cardNo' => $data['cardNo'],
            'txAmount' => $data['txAmount'],
            'currency' => '156',
            'cardBankCode' => '',
            'cardBankNameCn' => '',
            'cardBankNameEn' => '',
            'cardBankProvince' => '',
            'cardBankCity' => '',
            'smsSeq' => $data['smsSeq'],
            'smsCode' => $data['smsCode'],
            'callBackAdrress' => config::notifyUrl,
            'notifyUrl' => config::notifyUrl,
            'userIP' => '',
            'acqRes' => '123',
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //提现
    public function withdraw($data) {
        $url = '/escrow/p2p/page/withdraw';
        $param = array(
            'version' => config::VERSION,
            'txCode' => 'withdraw',
            'instCode' => config::instCode,
            'bankCode' => config::bankCode,
            'txDate' => $data['txDate'],
            'txTime' => $data['txTime'],
            'seqNo' => $data['seqNo'],
            'channel' => $data['channel'],
            'accountId' => $data['accountId'],
            'idType' => '01',
            'idNo' => $data['idNo'],
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'cardNo' => $data['cardNo'],
            'txAmount' => $data['txAmount'],
            'txFee' => $data['txFee'],
            'routeCode' => $data['routeCode'],
            'cardBankCnaps' => $data['cardBankCnaps'],
            'cardBankCode' => '',
            'cardBankNameCn' => '',
            'cardBankNameEn' => '',
            'cardBankProvince' => '',
            'cardBankCity' => '',
            'forgotPwdUrl' => config::forgotPwdUrl,
            'retUrl' => config::retUrl . '?txCode=withdraw',
            'notifyUrl' => config::notifyUrl,
            'acqRes' => '123',
        );
        $param['sign'] = $this->sign($param);
        return $this->request_form(config::BASE_URL . $url, $param);
    }

    public function debtRegister($data) {  //借款人标的登记
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'debtRegister', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'productId' => $data['productId'], //标的号
            'productDesc' => $data['productDesc'], //标的描述
            'raiseDate' => $data['raiseDate'], //开始募集日期
            'raiseEndDate' => $data['raiseEndDate'], //结束募集日期
            'intType' => '0', //0-到期与本金一起归还,1-每月固定日期支付,2-每月不确定日期支付
            'intPayDay' => '01', //利息每月支付日付息方式为1时必填;若设置日期大于月份最后一天时，则为该月最后一天支付,平台仅记录
            'duration' => $data['duration'], //借款期限 天数，从满标日期开始计算
            'txAmount' => $data['txAmount'], //借款金额
            'rate' => $data['rate'], //年化利率
            'txFee' => '0.00', //平台手续费
            'bailAccountId' => '', //担保账户
            'nominalAccountId' => '', //名义借款人电子帐号
            'acqRes' => '', //
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    public function debtRegisterCancel($data) {//借款人标的撤销
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'debtRegisterCancel', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'productId' => $data['productId'], //标的号
            'raiseDate' => $data['raiseDate'], //开始募集日期
            'acqRes' => '', //
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    public function autoBidAuth() {//自动投标签约
        $url = '/escrow/p2p/page/mobile';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'autoBidAuth', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'orderId' => time(), //订单ID
            'totAmount' => '10000.00', //自动投标金额上限
            'forgotPwdUrl' => 'http://jd.com',
            'retUrl' => config::retUrl,
            'notifyUrl' => config::notifyUrl,
            'acqRes' => '', //
        );
        $param['sign'] = $this->sign($param);
        $this->request_form(config::BASE_URL . $url, $param);
    }

    public function autoBidAuthPlus($data) {//自动投标签约增强
        $url = '/escrow/p2p/page/mobile/plus';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'autoBidAuthPlus', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'orderId' => $data['orderId'], //订单ID
            'txAmount' => $data['txAmount'], //自动投标单次金额上限
            'totAmount' => $data['totAmount'], //自动投标累计金额上限
            'forgotPwdUrl' => 'http://jd.com',
            'retUrl' => config::retUrl . '?txCode=autoBidAuthPlus',
            'notifyUrl' => config::notifyUrl,
            'lastSrvAuthCode' => $data['lastSrvAuthCode'],
            'smsCode' => $data['smsCode'],
            'acqRes' => '', //
        );
        $param['sign'] = $this->sign($param);
        return $this->request_form(config::BASE_URL . $url, $param);
    }

    public function autoBidAuthCancel($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'autoBidAuthCancel', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'orderId' => $data['orderId'], //订单ID
            'orgOrderId' => $data['orgOrderId'], //原签约订单号
            'acqRes' => '', //
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //自动投标申请
    public function bidAutoApply($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'bidAutoApply', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'orderId' => $data['orderId'], //订单ID
            'txAmount' => $data['txAmount'], //交易金额
            'productId' => $data['productId'], //标的号
            'contOrderId' => $data['contOrderId'], //自动投标签约订单号
            'frzFlag' => '1', //是否冻结
            'acqRes' => '', //
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //按证件号查询电子账号
    public function accountIdQuery() {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'accountIdQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'idType' => '01', //证件类型
            'idNo' => '512501196512305186', //证件号码
        );
        $param['sign'] = $this->sign($param);
        $this->request(config::BASE_URL . $url, $param);
    }

    //绑卡关系查询
    public function cardBindDetailsQuery() {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'cardBindDetailsQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => '6212461270000460929', //证件号码
        );
        $param['sign'] = $this->sign($param);
        $this->request(config::BASE_URL . $url, $param);
    }

    //余额查询
    public function balanceQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'balanceQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //证件号码
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //银行存管资金明细查询
    public function accountDetailsQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'accountDetailsQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //证件号码
            'startDate' => "{$data['startDate']}", //
            'endDate' => "{$data['endDate']}", //
            'type' => "{$data['type']}", //
            'tranType' => "{$data['tranType']}", //
            'pageNum' => "{$data['pageNum']}", //
            'pageSize' => "{$data['pageSize']}", //
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //投资人债权明细查询
    public function creditDetailsQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'creditDetailsQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'productId' => $data['productId'], //查询标的号，选填，为空查询所有名下所有债权
            'state' => $data['state'], //查询记录状态,0-所有债权,1-有效债权（投标成功，且本息尚未返还完成）
            'startDate' => "{$data['startDate']}", //起始日期
            'endDate' => "{$data['endDate']}", //结束日期
            'pageNum' => "{$data['pageNum']}", //查询页数
            'pageSize' => "{$data['pageSize']}", //每页笔数
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //借款人标的信息查询
    public function debtDetailsQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'debtDetailsQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //证件号码
            'productId' => $data['productId'], //查询标的号，选填，为空查询所有名下所有债权
            'state' => "{$data['state']}", //查询记录状态,0-所有债权,1-有效债权（投标成功，且本息尚未返还完成）
            'startDate' => $data['startDate'], //起始日期
            'endDate' => $data['endDate'], //结束日期
            'pageNum' => "{$data['pageNum']}", //查询页数
            'pageSize' => "{$data['pageSize']}", //每页笔数
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //电子账户手机号查询（查询电子账户的手机号，P2P平台应在调用前校验手机验证码）
    public function mobileMaintainace($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'mobileMaintainace', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //证件号码
            'option' => '0', //选项,0-查询
            'mobile' => '', //手机号
            'acqRes' => ''//请求方保留
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //按手机号查询电子账号信息
    public function accountQueryByMobile($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'accountQueryByMobile', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'mobile' => config::mobile //手机号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //查询交易状态
    /* 根据交易流水号或者订单号查询交易的状态
     * （可以查询放款、还款、融资人还担保账户垫款、结束债权、批次放款、批次还款、
     * 批次融资人还担保账户垫款、批次结束债权交易、批次投资人购买债权、批次担保账户代偿）
     */
    public function transactionStatusQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'transactionStatusQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号,选填，若原交易有电子账号，必填
            //'accountId' =>  config::accountId,  //电子账号,选填，若原交易有电子账号，必填
            'reqType' => '2', //查询类别
            'reqTxCode' => '1231', //查询交易代码
            'reqTxDate' => '', //查询交易日期
            'reqTxTime' => '', //查询交易时间
            'reqSeqNo' => '', //查询交易流水号
            'reqOrderId' => '124124'  //查询订单号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //查询批次状态
    /* 查询批次的状态，包括批次放款、批次还款、批次融资人还担保账户垫款接口、
     * 批次结束债权、批次投资人购买债权、批次担保账户代偿。
     */
    public function batchQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'batchQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'batchTxDate' => '156', //批次交易日期
            'batchNo' => '456' //批次号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //查询批次交易明细状态
    /* 查询批次交易明细的状态，包括批次放款、批次还款、批次融资人还担保账户垫款接口、
     * 批次结束债权、批次投资人购买债权、批次担保账户代偿。
     */
    public function batchDetailsQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'batchDetailsQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'batchTxDate' => '20170613', //批次交易日期
            'batchNo' => '000393', //批次号
            'type' => '9', //交易种类
            'pageNum' => '1', //页数
            'pageSize' => '20'    //页长
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //投资人购买债权查询
    /* 查询单笔投资人购买债权。
     */
    public function creditInvestQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'creditInvestQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'orgOrderId' => '123124', //原订单号
            'acqRes' => '', //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //投资人投标申请查询
    /* 查询单笔投资人投标申请。
     */
    public function bidApplyQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'bidApplyQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'),    //交易日期
            'txTime' => date('His'),    //交易时间
            'seqNo' => counter::auto_id(),  //交易流水号定长6位
            'channel' => $data['channel'],  //交易渠道
            'accountId' => $data['accountId'],   //电子账号
            'orgOrderId' => $data['orgOrderId'],   //原订单号
            'acqRes' => '',    //请求方保留，选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //投资人签约状态查询
    /* 查询投资人自动投标签约或自动债转签约状态。
     */
    public function creditAuthQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'creditAuthQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'type' => '1', //查询类型 1 自动投标签约2 自动债转签约
            'accountId' => config::accountId   //电子账号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //企业账户查询
    /* 如果电子账户绑定的是企业账户，使用本接口查询企业账户信息
     */
    public function corprationQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'corprationQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId   //电子账号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //账户资金冻结明细查询
    /* 查询电子账号下资金冻结的交易明细
     */
    public function freezeDetailsQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'freezeDetailsQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'state' => '0', //查询记录状态0-所有冻结 1-有效冻结（尚未解冻）
            'startDate' => '20100520', //起始日期
            'endDate' => '20180910', //结束日期
            'pageNum' => '1', //页数
            'pageSize' => '20'  //页长
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //电子账户密码是否设置查询
    /* 查询电子账户是否设置过密码
     */
    public function passwordSetQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'passwordSetQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId   //电子账号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //单笔还款申请冻结查询
    /* 查询单笔还款冻结申请记录
     */
    public function balanceFreezeQuery($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'balanceFreezeQuery', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'orgOrderId' => '12346' //原订单号
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //扣款类接口
    //放款
    /* 投资人投标以后，P2P平台通过本交易申请将资金从投资人电子账户划转到融资人电子账户，
     * 实际生效的时间视银行处理情况而定。
     */
    public function lendPay($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'lendPay', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //投资人电子账号
            'orderId' => date('Ymd') . date('His'), //订单号
            'txAmount' => '200', //交易金额
            'bidFee' => '10', //投资手续费
            'debtFee' => '20', //融资手续费
            'forAccountId' => '6212461270000910733', //融资人电子账号
            'productId' => '123124', //投资人投标成功的标的号
            'authCode' => '20160910163147617647', //投资人投标成功的授权号(投标后返回数据中的)
            'acqRes' => ''  //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //还款
    /* 融资人向投资人还款，P2P平台通过本交易申请将资金从融资人电子账户划转到投资人电子账户，
     * 实际生效的时间视银行处理情况而定。
     */
    public function repay($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'repay', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => '6212461270000910733', //融资人电子账号
            'orderId' => date('Ymd') . date('His'), //订单号，由P2P生成，必须保证唯一
            'txAmount' => '210', //交易金额，融资人实际付出金额=交易金额+交易利息+还款手续费
            'intAmount' => '10', //交易利息
            'txFeeOut' => '3', //还款手续费,向融资人收取的手续费
            'txFeeIn' => '4', //收款手续费,向投资人收取的手续费
            'forAccountId' => config::accountId, //投资人电子账号
            'productId' => '123124', //投资人投标成功的标的号
            'authCode' => '20160910140107615560', //投资人投标成功的授权号(投标后返回数据中的)
            'freezeFlag' => '1', //冻结资金开关,0-不冻结资金（如果在调用还款交易之前，已经使用了“资金冻结”，则无需再冻结）1-冻结资金
            'acqRes' => ''  //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //投资人购买债权
    /* 投资人从其他投资人名下购买债权，资金会实时从债权的购买方电子账户转到卖出方电子账户。
     */
    public function creditInvest($data) {
        
    }

    //红包发放
    /* P2P平台可以从红包电子账户向其他电子账户以发红包的方式转移资金。
     */
    public function voucherPay($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'voucherPay', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => $data['channel'], //交易渠道
            'accountId' => $data['accountId'], //红包账号
            'txAmount' => $data['txAmount'], //红包金额
            'forAccountId' => $data['forAccountId'], //接收方账号
            'desLineFlag' => $data['desLineFlag'], //是否使用交易描述1-使用0-不使用
            'desLine' => $data['desLine'], //交易描述,选填
            'acqRes' => ''  //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //红包发放撤销
    /* P2P平台撤销红包发放（仅银行系统的当日有效），资金从发放目标账户回到红包账户
     */
    public function voucherPayCancel($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'voucherPayCancel', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::bouns_accountId, //原交易的红包账号
            'txAmount' => '100', //原交易的红包金额
            'forAccountId' => config::accountId, //接收方账号
            'orgTxDate' => '20170522', //原交易日期
            'orgTxTime' => '152536', //原交易时间
            'orgSeqNo' => counter::auto_id(), //原交易流水号
            'acqRes' => ''  //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //融资人还担保账户垫款
    /* 融资人向担保账户还款，P2P平台通过本交易申请将资金从融资人电子账户划转到担保账户，实际生效的时间视银行处理情况而定。
     */
    public function repayBail($data) {
        
    }

    //结束债权
    /* 结束某笔债权，P2P平台通过本交易申请结束一笔投资人持有的债权，实际生效的时间视银行处理情况而定
     */
    public function creditEnd($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'creditEnd', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => '6212461270000910733', //融资人电子账号
            'orderId' => date('Ymd') . date('His'), //订单号,由P2P生成，必须保证唯一
            'forAccountId' => config::accountId, //投资人账号
            'productId' => '123124', //投资人投标成功的标的号
            'authCode' => '20160910140107615560', //投资人投标成功的授权号
            'acqRes' => ''  //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //放款或还款撤销
    /* P2P平台在放款、还款、融资人还担保账户垫款未被集中处理前可以撤销该交易。
     */
    public function payCancel($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'payCancel', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //原交易的转出账号
            'txAmount' => '200', //原交易的金额
            'forAccountId' => '6212461270000110854', //原交易的转入账号
            'orgTxDate' => '20170522', //投资人投标成功的标的号
            'orgTxTime' => '163331', //投资人投标成功的授权号
            'orgSeqNo' => counter::auto_id(), //原交易流水号
            'orgTxCode' => 'lendPay', //原交易代码
            'acqRes' => ''  //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //批次放款
    /* 投资人投标以后，P2P平台通过本交易申请将资金从投资人电子账户划转到融资人
     * 电子账户，实际生效的时间视银行处理情况而定，支持多笔交易，同一个批次号
     * 的交易一起处理，但是可能仅部分交易成功。后台收到请求以后，同步回应接收
     * 结果，异步通知请求方报文收取和合法性判断的结果（P2P平台收到后回应
     * success表示收到异步通知），业务处理也异步通知到相应的URL（P2P平台收
     * 到后回应success表示收到异步通知），或者请求方可以主动查询。
     * 超时时间：5分钟
     * 报文大小：小于10000笔
     */
    public function batchLendPay($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'batchLendPay', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => $data['txDate'] ? $data['txDate'] : date('Ymd'), //交易日期
            'txTime' => $data['txTime'] ? $data['txTime'] : date('His'), //交易时间
            'seqNo' => $data['seqNo'] ? $data['seqNo'] : counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'batchNo' => $data['batchNo'], //当日相同的批次号交易后台在一个批量中处理,批次号当日必须唯一
            'txAmount' => $data['txAmount'], //原交易的金额
            'txCounts' => "{$data['txCounts']}", //本批次所有交易笔数
            'notifyURL' => config::notifyUrl, //后台通知连接,后台通知URL，“响应参数”返回到该URL，收到后返回“success”,对数据合法性检查等的通知
            'retNotifyURL' => config::notifyUrl, //业务结果通知,后台通知URL，“响应参数”返回到该URL，收到后返回“success”,对请求业务处理的结果通知
            'acqRes' => '', //请求方保留,选填
            'subPacks' => json_encode($data['subPacks'])   //请求数组,JSON数组，内容解释见下文,选填
                /* subPacks:(
                  'accountId' => '',  //投资人电子账号
                  'orderId' => date('Ymd').date('His'),    //订单号
                  'txAmount' => '',   //投资人实际付出金额=交易金额+投资手续费
                  'bidFee' => '',  //投资手续费
                  'debtFee' => '',  //融资手续费
                  'forAccountId' => '6212461270000110854',   //原交易的转入账号
                  'productId' => '',  //投资人投标成功的标的号
                  'authCode' => config::accountId   //投资人投标成功的授权号) */
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //批次还款
    /* 融资人向投资人还款，P2P平台通过本交易申请将资金从融资人电子账户划转到
     * 投资人电子账户，实际生效的时间视银行处理情况而定，支持多笔交易，同一
     * 个批次号的交易一起处理，但是可能仅部分交易成功。后台收到请求以后，同
     * 步回应接收结果，异步通知请求方报文收取和合法性判断的结果（P2P平台收
     * 到后回应success表示收到异步通知），业务处理也异步通知到相应的URL
     * （P2P平台收到后回应success表示收到异步通知），或者请求方可以主动查
     * 询。本交易不会主动冻结融资人资金，如需要先冻结，请调用“资金冻结”接口。
     * 超时时间：5分钟
     * 报文大小：小于10000笔
     */
    public function batchRepay($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'batchRepay', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => $data['txDate'] ? $data['txDate'] : date('Ymd'), //交易日期
            'txTime' => $data['txTime'] ? $data['txTime'] : date('His'), //交易时间
            'seqNo' => $data['seqNo'] ? $data['seqNo'] : counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'batchNo' => $data['batchNo'], //当日相同的批次号交易后台在一个批量中处理,批次号当日必须唯一
            'txAmount' => $data['txAmount'], //原交易的金额
            'txCounts' => "{$data['txCounts']}", //本批次所有交易笔数
            'notifyURL' => config::notifyUrl, //后台通知连接,后台通知URL，“响应参数”返回到该URL，收到后返回“success”,对数据合法性检查等的通知
            'retNotifyURL' => config::notifyUrl, //业务结果通知,后台通知URL，“响应参数”返回到该URL，收到后返回“success”,对请求业务处理的结果通知
            'acqRes' => '', //请求方保留,选填
            'subPacks' => json_encode($data['subPacks'])   //请求数组,JSON数组，内容解释见下文,选填
                /* subPacks:(
                  'accountId' => '',  //投资人电子账号
                  'orderId' => date('Ymd').date('His'),    //订单号
                  'txAmount' => '',   //投资人实际付出金额=交易金额+投资手续费
                  'txFeeOut' => '',  //还款手续费
                  'txFeeIn' => '',  //收款手续费
                  'forAccountId' => '6212461270000110854',   //原交易的转入账号
                  'productId' => '',  //投资人投标成功的标的号
                  'authCode' => config::accountId   //投资人投标成功的授权号) */
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //批次结束债权
    /* 结束债权，P2P平台通过本交易申请结束一笔投资人持有的债权，实际生效的时间
     * 视银行处理情况而定，支持多笔交易，同一个批次号的交易一起处理，但是可能
     * 仅部分交易成功。后台收到请求以后，异步通知请求方报文收取和合法性判断的
     * 结果，业务处理也异步通知到相应的URL，或者请求方可以主动查询。
     * 超时时间：5分钟
     * 报文大小：小于10000笔
     */
    public function batchCreditEnd($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'batchCreditEnd', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => $data['txDate'] ? $data['txDate'] : date('Ymd'), //交易日期
            'txTime' => $data['txTime'] ? $data['txTime'] : date('His'), //交易时间
            'seqNo' => $data['seqNo'] ? $data['seqNo'] : counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'batchNo' => $data['batchNo'], //当日相同的批次号交易后台在一个批量中处理,批次号当日必须唯一
            'txCounts' => "{$data['txCounts']}", //本批次所有交易笔数
            'notifyURL' => config::notifyUrl, //后台通知连接,后台通知URL，“响应参数”返回到该URL，收到后返回“success”,对数据合法性检查等的通知
            'retNotifyURL' => config::notifyUrl, //业务结果通知,后台通知URL，“响应参数”返回到该URL，收到后返回“success”,对请求业务处理的结果通知
            'acqRes' => '', //请求方保留,选填
            'subPacks' => json_encode($data['subPacks'])   //请求数组,JSON数组，内容解释见下文,选填
                /* subPacks:(
                  'accountId' => '',  //投资人电子账号
                  'orderId' => date('Ymd').date('His'),    //订单号
                  'txAmount' => '',   //投资人实际付出金额=交易金额+投资手续费
                  'bidFee' => '',  //投资手续费
                  'debtFee' => '',  //融资手续费
                  'forAccountId' => '6212461270000110854',   //原交易的转入账号
                  'productId' => '',  //投资人投标成功的标的号
                  'authCode' => config::accountId   //投资人投标成功的授权号) */
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //批次撤销
    /* 在批次尚未进行业务处理时，可以进行撤销操作。
     * 超时时间：5分钟
     * 报文大小：小于10000笔
     */
    public function batchCancel($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'batchCancel', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'batchNo' => date('His'), //当日相同的批次号交易后台在一个批量中处理,批次号当日必须唯一
            'txAmount' => '200', //原交易的金额
            'txCounts' => '2', //本批次所有交易笔数
            'acqRes' => '', //请求方保留,选填
                /* subPacks:(
                  'accountId' => '',  //投资人电子账号
                  'orderId' => date('Ymd').date('His'),    //订单号
                  'txAmount' => '',   //投资人实际付出金额=交易金额+投资手续费
                  'bidFee' => '',  //投资手续费
                  'debtFee' => '',  //融资手续费
                  'forAccountId' => '6212461270000110854',   //原交易的转入账号
                  'productId' => '',  //投资人投标成功的标的号
                  'authCode' => config::accountId   //投资人投标成功的授权号) */
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //冻结类接口
    //还款申请冻结资金
    /* 冻结用户电子账户中指定金额。
     */
    public function balanceFreeze($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'balanceFreeze', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => $data['accountId'], //电子账号
            'orderId' => $data['orderId'], //订单号
            'txAmount' => sprintf('%.2f', $data['txAmount']), //冻结金额
            'acqRes' => $data['acqRes'] ? $data['acqRes'] : '', //请求方保留,选填
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    //还款申请撤销资金解冻
    /* 撤销原来冻结的电子账户资金。
     */
    public function balanceUnfreeze($data) {
        $url = '/escrow/p2p/online';
        $param = array(
            'version' => config::VERSION, //版本号
            'txCode' => 'balanceUnfreeze', //交易代码
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => date('Ymd'), //交易日期
            'txTime' => date('His'), //交易时间
            'seqNo' => counter::auto_id(), //交易流水号定长6位
            'channel' => '000002', //交易渠道
            'accountId' => config::accountId, //电子账号
            'orderId' => date('Ymd') . date('His'), //订单号
            'txAmount' => '10', //冻结金额
            'orgOrderId' => '20170523105050', //原冻结订单号
            'acqRes' => '', //请求方保留,选填
                /* subPacks:(
                  'accountId' => '',  //投资人电子账号
                  'orderId' => date('Ymd').date('His'),    //订单号
                  'txAmount' => '',   //投资人实际付出金额=交易金额+投资手续费
                  'bidFee' => '',  //投资手续费
                  'debtFee' => '',  //融资手续费
                  'forAccountId' => '6212461270000110854',   //原交易的转入账号
                  'productId' => '',  //投资人投标成功的标的号
                  'authCode' => config::accountId   //投资人投标成功的授权号) */
        );
        $param['sign'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }
    
    /* 渠道获取交易确认文件
     */
    public function fileDownload($data) {
        $url = '/escrow/file/download';
        $param = array(
            'instCode' => config::instCode, //机构代码
            'bankCode' => config::bankCode, //银行代码
            'txDate' => $data['txDate'], //日期
            'fileName' => config::file_bankcode.'-EVE'.config::file_productcode.'-'.$data['txDate'], //文件名
        );
        $param['SIGN'] = $this->sign($param);
        return $this->request(config::BASE_URL . $url, $param);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     */
    public function request_form($url, $params) {
        api_log::set(array(
            'request_jx' => $params,
            'version' => $params['version'],
            'txCode' => $params['txCode'],
            'channel' => $params['channel'],
            'seqNo' => $params['txDate'] . $params['txTime'] . $params['seqNo'],
        ));
        $html_text = $this->buildRequestForm($url, $params, "post", "确认");
        return $html_text;
    }

    // 请求接口
    public function request($url, $params) {
        api_log::set(array(
            'request_jx' => $params,
            'version' => $params['version'],
            'txCode' => $params['txCode'],
            'channel' => $params['channel'],
            'seqNo' => $params['txDate'] . $params['txTime'] . $params['seqNo'],
        ));
        //echo '<pre>';
        // 参数签名
        //$params['SIGN'] = $this->sign($params);
        $params_json = json_encode_ex($params);
//        echo $params_json;
        //$result = TyqLib_Curl::postjson($url, $params);
        $result_org = $this->curl_upload_https($url, $params_json);

        //$html_text = $this->buildRequestForm($params, "post", "确认");
        api_log::set('response', $params);
        $result = json_decode($result_org, TRUE);
        //var_dump($retcodes[$result['retCode']]);
        !isset(TyqLib_Credit2go_Retcodes::$retcodes[$result['retCode']]) ? $msg = $result['retMsg'] : $msg = TyqLib_Credit2go_Retcodes::$retcodes[$result['retCode']];
        $retcode = $result['retCode'];
        $status = 1;
        // 请求失败
        if (empty($result['sign'])) {
            $status = 2;
            $msg = '请求失败：参数错误';
//            unset($result);
            do_log(array('$url'=>$url, '$params'=>$params, '$result'=>$result, '$msg'=>$msg,'$result_org'=>$result_org));
        }
        // 验证签名
        elseif (!$this->sign_verify($result)) {
            $status = 3;
            $msg = '验证签名失败';
//            unset($result);
            do_log(array(
                'sign_rude' => $this->sign_rude,
                'url' => $url,
                'params' => $params,
                'result' => $result,
                'msg' => $msg
            ));
        }
        //return $result;
        return compact('status', 'retcode', 'msg', 'result');
    }

    /**
     * 生成要请求给连连支付的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function buildRequestPara($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = argSort($para_filter);
        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);
        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(trim($this->llpay_config['sign_type']));
        foreach ($para_sort as $key => $value) {
            $para_sort[$key] = urlencode($value);
        }
        return urldecode(json_encode($para_sort));
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    function buildRequestForm($url, $para_temp, $method, $button_name) {
        //待请求参数数组
        //$para = $this->buildRequestPara($para_temp);
        $sHtml = "<form id='jxpaysubmit' name='jxpaysubmit' action='" . $url . "' method='" . $method . "' style='display:none;'>";
        foreach ($para_temp as $k => $v) {
            $sHtml .= "<input type='hidden' name='" . $k . "' value='" . $v . "'/>";
        }

        //$sHtml .= "<input type='hidden' name='req_data' value='" . $para . "'/>";
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='" . $button_name . "'></form>";
        $sHtml = $sHtml . "<script>document.forms['jxpaysubmit'].submit();</script>";
        return $sHtml;
    }

    function curl_upload_https($url, $data) { // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8"));
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            return 'error.' . curl_error($curl); // 捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    function curl_upload($post_url, $post_data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8"));
        curl_setopt($curl, CURLOPT_URL, $post_url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        return $error ? $error : $result;
    }

}

class api_log {

    private static $api_log = array();

    public static function set($k, $v = false) {
        if (is_array($k)) {
            foreach ($k as $k1 => $v1) {
                self::$api_log[$k1] = $v1;
            }
        } else {
            self::$api_log[$k] = $v;
        }
        return self;
    }

    public static function write() {
        $CI = get_instance();
        $CI->db->insert('v1_ba_api_log', array(
            'api_name_cn' => self::$api_log['api_name_cn'],
            'api_name_en' => self::$api_log['api_name_en'],
            'user_id' => self::$api_log['user_id'],
            'request_jc' => json_encode(self::$api_log['request_jc']),
            'request_jx' => json_encode(self::$api_log['request_jx']),
            'response' => json_encode(self::$api_log['response']),
            'sqls' => print_r($CI->db->all_query(), true),
            'version' => self::$api_log['version'],
            'txCode' => self::$api_log['txCode'],
            'seqNo' => self::$api_log['seqNo'],
            'channel' => self::$api_log['channel'],
            'ip' => get_ip(),
            'request_time' => date('Y-m-d H:i:s', self::$api_log['request_time']),
            'run_time' => microtime(1) - self::$api_log['start_time'],
            'respones_time' => date('Y-m-d H:i:s', time()),
        ));
        self::$api_log = array();
        return $CI->db->insert_id();
    }

    public static function get_userId_by_seqNo($seqNo) {
        $CI = get_instance();
        $CI->db->limit(1);
        $CI->db->where('user_id > 0', false, false);
        $user_id = $CI->db->get_where('v1_ba_api_log', array('seqNo' => $seqNo))->row(0)->user_id;
        return $user_id ? $user_id : 0;
    }

}

class counter {

    public static function auto_id() {
        $CI = get_instance();
        $CI->load->library('cache_memcache');
        $id = $CI->cache_memcache->increment('JX_SEQNO_COUNTER', 1);
        if (!$id) {
            $CI->cache_memcache->add('JX_SEQNO_COUNTER', 1);
            $id = $CI->cache_memcache->increment('JX_SEQNO_COUNTER', 1);
        }
        return str_pad($id % 100000, 6, '0', STR_PAD_LEFT);
    }

}

/**
 * 对变量进行 JSON 编码
 * @param mixed value 待编码的 value ，除了resource 类型之外，可以为任何数据类型，该函数只能接受 UTF-8 编码的数据
 * @return string 返回 value 值的 JSON 形式
 */
function json_encode_ex($value) {
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        $str = json_encode($value);
        $str = preg_replace_callback(
                "#\\\u([0-9a-f]{4})#i", function($matchs) {
            return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
        }, $str
        );
        return $str;
    } else {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}

Class TyqLib_Credit2go_Retcodes {

    public static $retcodes = array(
        '00000000' => '成功',
        'CE999999' => '系统错误',
        'CE999001' => '功能受限，交易失败',
        'CE999002' => '实名认证失败',
        'CE999003' => '绑定卡不存在',
        'CE999004' => '卡片已解绑',
        'CE999005' => '电子账户不存在',
        'CE999006' => '卡片已经绑定',
        'CE999007' => '数据库操作错',
        'CE999008' => '缴费金额超限',
        'CE999009' => '数据格式校验错',
        'CE999010' => '代扣日期非法',
        'CE999011' => '代扣金额超出限额',
        'CE999012' => '代扣金额非法',
        'CE999013' => '系统错误',
        'CE999014' => '已签约',
        'CE999015' => '格式校验错误(必要上送字段不能为空)',
        'CE999016' => '翻页查询,卡号不能为空',
        'CE999017' => '该签约已解约,无需重复解约',
        'CE999018' => '不存在该签约关系',
        'CE999019' => '签约关系已失效,请重新签约',
        'CE999020' => '未找到记录',
        'CE999021' => '核验绑定卡失败',
        'N/A' => ' 其他错误',
        'CI01' => '交易失败。详情请咨询95516',
        'CI02' => '系统未未开发',
        'CI03' => '交易通讯超时，请查询绑定银行卡余额',
        'CI04' => '互联网渠道转账失败',
        'CI05' => '交易已受理，请稍后查询交易结果',
        'CI10' => '请联系发卡行',
        'CI11' => '请联系发卡行',
        'CI12' => '重复交易',
        'CI13' => '请联系发卡行',
        'CI14' => '请联系发卡行',
        'CI30' => '交易未通过，请尝试使用其他银联卡支付或联系95516',
        'CI31' => '请联系发卡行',
        'CI32' => '请联系发卡行',
        'CI33' => '交易金额超限',
        'CI34' => '请联系发卡行',
        'CI35' => '请联系发卡行',
        'CI36' => '请联系发卡行',
        'CI37' => '请联系发卡行',
        'CI38' => '风险受限',
        'CI39' => '交易不在受理时间范围内',
        'CI40' => '请联系发卡行',
        'CI41' => '请联系发卡行',
        'CI42' => '请联系发卡行',
        'CI60' => '交易失败，详情请咨询您的发卡行',
        'CI61' => '请联系发卡行',
        'CI62' => '请联系发卡行',
        'CI63' => '卡状态不正确',
        'CI64' => '余额不足',
        'CI65' => '请联系发卡行',
        'CI66' => '核验身份信息失败，请联系发卡行',
        'CI67' => '密码输入次数超限',
        'CI68' => '您的银行卡暂不支持该业务，请向您的银行或95516咨询',
        'CI69' => '请联系发卡行',
        'CI70' => '请联系发卡行',
        'CI71' => '动态口令或短信验证码校验失败',
        'CI72' => '您尚未在银行网点柜面或个人网银签约加办银联无卡支付业务，请去柜面或网银开通或拨打发卡行客户电话',
        'CI73' => '请联系发卡行',
        'CI74' => '请联系发卡行',
        'CI75' => '请联系发卡行',
        'CI76' => '需要验密开通',
        'CI77' => '银行卡未开通认证支付',
        'CA01' => '请联系发卡行',
        'CA03' => '请联系发卡行',
        'CA05' => '原交易状态不正确',
        'CA12' => '日期非法',
        'CA13' => '交易金额与原交易不匹配',
        'CA14' => '卡号不存在',
        'CA22' => '原交易状态不正确',
        'CA25' => '找不到原交易',
        'CA30' => '报文格式错误',
        'CA36' => '卡片已注销',
        'CA38' => '超过允许的PIN最大试输入次数',
        'CA40' => '不支持的功能',
        'CA41' => '卡片挂失',
        'CA42' => '账户已注销',
        'CA44' => '姓名校验失败',
        'CA45' => '证件号码验证失败',
        'CA51' => '账户已冻结',
        'CA54' => '卡片已过期',
        'CA55' => '密码错',
        'CA57' => '账户已冻结',
        'CA61' => '消费金额超限',
        'CA62' => '卡片状态不正常',
        'CA64' => '预授权完成金额超过预授权金额',
        'CA65' => '取现次数超限',
        'CA75' => '超过当日允许的PIN最大试输入次数',
        'CA90' => '正在进行日切',
        'CA94' => '重复交易',
        'CAA0' => 'MAC校验失败或MAC密钥产生失败',
        'CAA5' => '存款确认和转入确认失败',
        'CA000014' => '未找到此卡号对应的账户记录',
        'CA000050' => '卡号非法',
        'CA000054' => '卡号不存在',
        'CA000063' => '未找到该持卡人的客户记录',
        'CA000063' => '未找到该持卡人的客户记录',
        'CA000102' => '客户记录已经存在',
        'CA000143' => '未找到该卡号对应的记录',
        'CA000150' => '未找到银行记录',
        'CA000294' => '卡号不能为空',
        'CA000408' => '地址类型非法',
        'CA000642' => 'PIN/PVV/CVV/CVV2调用失败',
        'CA001324' => 'L034-AUDIT-CREATE错误',
        'CA002141' => '请输入详细地址',
        'CA002643' => '系统错误－未找到卡片所属的产品记录',
        'CA002644' => '系统错误－未找到卡片所属的产品处理参数记录',
        'CA002894' => '账单地址标志无效',
        'CA003403' => '不能存取其他银行的数据',
        'CA003811' => 'PIN校验错误',
        'CA009004' => '证件类型必须输入',
        'CA009005' => '证件号码必须输入',
        'CA100146' => '证件号码长度非法',
        'CA100147' => '证件号码不正确',
        'CA100149' => '请输入证件类型',
        'CA100150' => '证件类型无效',
        'CA100152' => '证件类型不正确',
        'CA100457' => '身份核查未通过',
        'CA100458' => '需进行身份核查，但客户信息未找到',
        'CA100459' => '留存身份证复印件标志不合法',
        'CA100460' => '身份核查结果标志取值不合法',
        'CA100461' => '身份核查标志取值为空',
        'CA100463' => '留存身份证复印件标志为空',
        'CA100478' => '该产品的卡不支持预约理财功能',
        'CA100484' => '起始日期结束日期间隔须在3个月之内',
        'CA100509' => '转出卡已销户',
        'CA100623' => '请输入处理流水号',
        'CA100658' => '该账户费用类型未在PRMFE里定义',
        'CA100675' => '请输入18位身份证号',
        'CA100676' => '请输入15位身份证号',
        'CA100677' => '18位身份证号长度必须为18位',
        'CA100678' => '15位身份证号长度必须为15位',
        'CA100679' => '计算身份证校验位出错',
        'CA100762' => '请输入银行号',
        'CA100763' => '请输入交易来源',
        'CA100764' => '请输入操作员号',
        'CA100765' => '请输入交易流水号',
        'CA100766' => '身份证(18位)校验位错误',
        'CA100767' => 'L057-COMPR-NAME错误',
        'CA100769' => '请输入地址行1',
        'CA100770' => '请输入地址行2',
        'CA100771' => '请输入邮政编码',
        'CA100773' => '请输入产品代码',
        'CA100774' => '产品代码无效',
        'CA100776' => '系统中未定义缺省申请书来源代码',
        'CA100777' => '凸字错误 ',
        'CA100779' => 'L031-ACCT-CREATE错误',
        'CA100781' => '开通短信标志必须为0或1',
        'CA100792' => '卡片已止付',
        'CA100793' => '卡片已销卡',
        'CA100794' => '卡片已停用',
        'CA100803' => '卡片已冻结或活期子帐户已冻结',
        'CA100804' => '附卡权限级别不够',
        'CA100805' => '检查密码标志不合法',
        'CA100808' => '卡片已正式挂失',
        'CA100809' => '定期储蓄子账户不存在',
        'CA100810' => '定期储蓄子账户已销户',
        'CA100836' => '银行编号不存在',
        'CA100838' => '修改/查询标志不合法',
        'CA100845' => '交易金额有误',
        'CA100870' => '账户余额不足',
        'CA100878' => '起始日期非法',
        'CA100879' => '结束日期非法',
        'CA100880' => '上次末笔交易日期非法',
        'CA100881' => '起始日期不得晚于当前日期',
        'CA100882' => '结束日期不得早于开户日期',
        'CA100901' => '操作选项错误',
        'CA100902' => '请输入客户资料或账单地址信息',
        'CA100903' => '邮政编码必须是数字',
        'CA100906' => '请输入账单地址类型',
        'CA100907' => '系统错误-未找到当前工作日期记录',
        'CA100911' => '卡片已注销',
        'CA100912' => '卡片未制卡或未申领',
        'CA100913' => '请输入证件号码',
        'CA100914' => '请输入中文姓名',
        'CA100916' => '请输入性别',
        'CA100917' => '请输入账户费用类型',
        'CA100919' => '请输入分行编号',
        'CA100920' => '分行编号无效',
        'CA100922' => '身份证(18位)校验位错误',
        'CA100923' => '性别代码非法',
        'CA100924' => '账户费用类型非法',
        'CA100925' => '生成客户的NAME-KEY错误',
        'CA100934' => '该账户已销户',
        'CA100952' => '手机号码不正确',
        'CA100960' => '未登记手机号码,无法开通短信通知',
        'CA100967' => '卡片不属于该持卡人',
        'CA100968' => '卡片不属于该发卡行',
        'CA100976' => '今日密码连续错误次数超限',
        'CA100977' => '累计密码连续错误次数超限',
        'CA100979' => '卡片已口头挂失',
        'CA100980' => '密码已挂失',
        'CA100982' => '未知的卡片状态',
        'CA100985' => '请输入网点号',
        'CA100991' => '该交易必须到原开户网点进行',
        'CA100992' => '翻页标志不合法',
        'CA101006' => '交易网点未定义',
        'CA101007' => '该交易不允许跨行进行',
        'CA101012' => '请输入员工所在分行',
        'CA101013' => '核实结果非法',
        'CA101014' => '核实结果不允许修改',
        'CA101015' => '核实结果修改错误',
        'CA101017' => '无法核实原因非法',
        'CA101018' => '处置方式非法',
        'CA101019' => '无法核实原因与核实结果不匹配',
        'CA101020' => '处置方式与核实结果不匹配',
        'CA101021' => '核实结果为空',
        'CA101022' => '无法核实原因为空',
        'CA101023' => '处置方式为空',
        'CA101028' => '卡片未激活',
        'CA101047' => 'CVN2未送',
        'CA101048' => 'CVN2错误',
        'CA101050' => '系统已停止对房产地址维护，请联系银联数据',
        'CA101059' => '密码已重置',
        'CA101061' => '该卡不是理财卡',
        'CA101063' => '请输入是否更新末次风险评估日期（0/1）',
        'CA101066' => '只能申领理财卡',
        'CA101067' => '申卡必须先风险评估',
        'CA101068' => '客户已申领过该理财卡产品',
        'CA101072' => '未找到该基金的当前基本信息',
        'CA101073' => '查询范围不得超过3个月',
        'CA101074' => '查询时间跨度不得超过10日',
        'CA101075' => '此银行没有开通相应基金公司',
        'CA101077' => '银行未开通理财卡功能',
        'CA101082' => '未找到银行与基金公司的对应信息',
        'CA101083' => '指定交易类型不存在',
        'CA101084' => '申请标志非法，必须为0或1',
        'CA101098' => '签约最高金额为空',
        'CA101099' => '签约交易流水号为空',
        'CA101100' => '存在未取消的自动投标签约记录',
        'CA101101' => '签约流水号已存在',
        'CA101101' => '申请流水号已存在',
        'CA101102' => '签约取消交易流水号为空',
        'CA101103' => '原签约交易流水号为空',
        'CA101105' => '存在相同的解约流水号',
        'CA101106' => '不存在自动投标签约记录',
        'CA101107' => '自动投标签约记录已取消',
        'CA101108' => '冻结金额标志不合法',
        'CA101109' => '请输入交易流水号',
        'CA101109' => '请输申请流水号',
        'CA101110' => '起息日不能为空',
        'CA101111' => '付息方式有误',
        'CA101112' => '利息每月支付日为空',
        'CA101113' => '到期日为空',
        'CA101114' => '年化收益率为空',
        'CA101115' => '起息日期不得早于当前日期',
        'CA101116' => '产品发行方错误',
        'CA101117' => '签约流水号不存在',
        'CA101118' => '签约类型非自动投标',
        'CA101119' => '签约状态为未签约',
        'CA101120' => '电子账户不匹配',
        'CA101121' => '账户余额不足以达到此次自动投标',
        'CA101122' => '查询的记录状态非法',
        'CA101124' => '请输原交易申请流水号',
        'CA101125' => '产品发行方与原投标记录不符',
        'CA101126' => '产品编号与原投标记录不符',
        'CA101127' => '投标金额与原投标记录不符',
        'CA101128' => '只有状态是申购中的记录才能撤销',
        'CA101129' => '请输入绑定卡号',
        'CA101131' => '电子账号已与该卡号绑定',
        'CA101132' => '电子账号已存在签约关系',
        'CA101133' => '未找到此卡号对应的电子账户绑定记录',
        'CA101134' => '账户余额不为0，不能解除绑定',
        'CA101135' => '存在本息未返还的第三方理财产品的记录',
        'CA101136' => '理财类型非法',
        'CA101137' => '此账户已经是此账户类型',
        'CA101138' => '电子账户转换日期与登记表不一致',
        'CA101139' => '卡片和签约记录不一致',
        'CA101140' => '翻页标志为1，上送的产品编号不能为空',
        'CA101141' => '投标记录不存在',
        'CA101142' => '未成年（18岁）控制',
        'CA101152' => '转让申请流水号为空',
        'CA101154' => '转让登记表中转让申请流水号已存在 ',
        'CA101155' => '总共可转让金额为空',
        'CA101156' => '转让金额为空',
        'CA101158' => '转让价格为空',
        'CA101159' => '转让后预期年化收益率为空',
        'CA101160' => '转让方卡号不存在',
        'CA101161' => '转让方卡片不属于该发卡行',
        'CA101162' => '未找到转让方的客户记录',
        'CA101163' => '未找到转让方的账户记录',
        'CA101164' => '转让方账户已销户',
        'CA101165' => 'CUFPI表中原交易申请流水号不存在',
        'CA101166' => '原交易申请流水号的电子账号错误',
        'CA101168' => '承接方电子账号错误',
        'CA101170' => '手续费扣款方式非法',
        'CA101171' => '转出交易类型不存在',
        'CA101172' => '资金转出方余额不足',
        'CA101173' => '转入交易类型不存在',
        'CA101174' => '转入手续费交易未配置',
        'CA101175' => 'L084手续费计算失败(资金转入方)',
        'CA101176' => '资金转入方余额不足',
        'CA101177' => 'L065更新失败(资金转出方)',
        'CA101178' => 'L090更新失败（资金转出卡）',
        'CA101179' => 'L065更新失败(资金转入方)',
        'CA101180' => 'L090更新失败(资金转入方)',
        'CA101181' => 'L084更新失败(资金转入方)',
        'CA101182' => '转让方卡号为空',
        'CA101184' => '累计已转让金额超过总共可转让金额',
        'CA101195' => '总共可转让金额必须小于等于原债权金额',
        'CA101200' => '第三方平台账户不存在',
        'CA101201' => '第三方平台账户非法',
        'CA101202' => '手续费转入机构账户交易类型不存在',
        'CA101203' => 'L065更新失败(手续费转入方)',
        'CA101204' => 'L090更新失败(手续费转入方)',
        'CA101206' => '找不到合作平台编号记录',
        'CA101206' => '找不到合作平台编号记录',
        'CA101207' => '平台交易验证未通过',
        'CA101228' => '不支持开立靠档账户或活期账户',
        'CA101229' => '不支持开立基金账户或活期账户',
        'CA101230' => '不支持开立基金账户或靠档账户',
        'CA103742' => '请输入手机号',
        'CA110070' => '掌钱产品只支持申领虚拟卡',
        'CA110071' => '该手机号已申领过掌钱账户',
        'CA110083' => '已开通智能通知存款则不能开通约定定期',
        'CA110092' => '账户类型错误',
        'CA110093' => '基金公司代码不能为空',
        'CA110094' => '基金公司代码非法',
        'CA110095' => '同一天不能进行两次账户类型转换',
        'CA110096' => '电子账户类型非法不允许做更换',
        'CA110097' => '不存在该银行与基金公司的对应记录',
        'CA110100' => '未找到基金切换时间节点',
        'CA110102' => '电子账户类型未定义',
        'CA110103' => '电子账户变动日期错误',
        'CA110104' => '请选择查询交易种类',
        'CA110106' => '未设置约定存期',
        'CA110107' => '体验金类型标志不合法',
        'CA110108' => '靠档计息账户',
        'CA110109' => '体验金功能未开通',
        'CA110110' => '理财产品发行方未填写',
        'CA110111' => '申购信息缺失：检查起息日，到期日，付息方式，利率等是否填写',
        'CA110112' => '原交易不存在',
        'CA110113' => '原交易已存在',
        'CA110114' => '付息方式有误',
        'CA110115' => '起息日期不得早于当前日期',
        'CA110116' => '原交易数据不符',
        'CA110117' => '原交易不可撤销',
        'CA110118' => '撤销金额超过冻结金额',
        'CA110120' => '不支持基金账户转靠档账户',
        'CA110121' => '不支持靠档账户转基金账户',
        'CA6460' => '支持理财预约',
        'CA800202' => '邮件地址无效',
        'CA800300' => '起始日期不能晚于结束日期',
        'CP0001' => '请联系发卡行',
        'CP1111' => '请联系发卡行',
        'CP2222' => '请联系发卡行',
        'CP9999' => '认证失败,请稍后重试',
        'CP9900' => '认证失败,请联系发卡行',
        'CP9901' => '交易失败',
        'CP9902' => '交易失败',
        'CP9903' => '交易失败',
        'CP9904' => '交易失败',
        'CP9905' => '客户取消交易',
        'CP9906' => '交易失败',
        'CP9907' => '此卡已过期',
        'CP9908' => '密码错误',
        'CP9909' => '余额不足',
        'CP9910' => '未开通此功能',
        'CP9911' => '交易异常,请联系发卡行',
        'CP9912' => '超出金额限制',
        'CP9913' => '此卡受限制,请联系发卡行',
        'CP9914' => '超出取款次数限制',
        'CP9915' => '超出最大输入密码次数,请联系发卡行',
        'CP9916' => '交易超时,请稍后查询交易结果',
        'CP9917' => '交易重复,请稍后查询结果',
        'CP9918' => '密码格式错误',
        'CP9919' => '银行卡与姓名不符',
        'CP9920' => '银行卡与证件不符',
        'CP45200' => '交易正在处理，请稍后查询结果',
        'CP2000' => '交易正在处理，请稍后查询结果',
        'CP452045' => '交易正在处理，请稍后查询结果',
        'CP092009' => '交易正在处理，请稍后查询结果',
        'CPE220E2' => '数字签名或证书错',
        'CP012001' => '查开户方原因',
        'CP032003' => '无效商户',
        'CP052005' => '未开通业务',
        'CP062006' => '系统处理失败',
        'CP132013' => '货币错误',
        'CP142014' => '无效卡号',
        'CP222022' => '交易失败',
        'CP302030' => '报文错误',
        'CP312031' => '超过支付额度',
        'CP412041' => '挂失卡',
        'CP512051' => '余额不足',
        'CP612061' => '超出提款限额',
        'CP942094' => '重复业务',
        'CPEC20EC' => '商户状态不合法',
        'CPF320F3' => '累计退货金额大于原交易金额',
        'CPFF20FF' => '非白名单卡号',
        'CPP920P9' => '账户已冻结',
        'CPPD20PD' => '账户未加办代收付标志',
        'CPPS20PS' => '户名不符',
        'CPPU20PU' => '订单号错误',
        'CPPZ20PZ' => '原交易信息不存在',
        'CPQ320Q3' => '日期错误',
        'CPQB20QB' => '商户审核不通过',
        'CPQS20QS' => '系统忙，请稍后再提交',
        'CPST20ST' => '已撤销',
        'CPT420T4' => '未签约账户',
        'CPTY20TY' => '交易失败',
        'CPEL20EL' => '交易失败',
        'CP010001' => '交易失败',
        'CPCT9901' => '交易失败',
        'CPCT9902' => '交易失败,请核实资金',
        'CPCT9903' => '交易失败,请核实资金',
        'B3CA0001' => '账户不存在',
        'B3CA0002' => '账户状态异常',
        'B3CA0003' => '账户功能受限',
        'B3CA0004' => '账户功能受限',
        'B3CA0005' => '账户功能受限',
        'B3CA1011' => '账户不存在',
        'B3CA1012' => '账户不存在',
        'B3CA1013' => '余额不足',
        'B3CA1015' => '账户状态异常',
        'B3CA1017' => '账户状态异常',
        'B3CA1018' => '证件号不符合',
        'B3CA1020' => '余额不足',
        'B3CA1021' => '账户状态异常',
        'B3CA1022' => '账户异常',
        'B3CA1029' => '账户异常',
        'B3CA1033' => '证件号不符合',
        'B3CAC002' => '账户不存在',
        'B3CAC003' => '账户不存在',
        'B3CAC004' => '账户不存在',
        'B3CAC005' => '余额不足',
        'B3CAC017' => '账户不存在',
        'B3CAC023' => '账户状态异常',
        'B3CAC043' => '发卡行异常,咨询发卡行',
        'B3CB1001' => '发卡行异常,咨询发卡行',
        'B3CB1002' => '发卡行异常,咨询发卡行',
        'B3CCPR01' => '发卡行异常,咨询发卡行',
        'B3CCPR02' => '发卡行异常,咨询发卡行',
        'B3CCPR03' => '发卡行异常,咨询发卡行',
        'B3CCPR04' => '发卡行异常,咨询发卡行',
        'B3CCPR05' => '发卡行异常,咨询发卡行',
        'B3CCPR07' => '重复交易',
        'B3CCPR09' => '发卡行异常,咨询发卡行',
        'B3CCPR10' => '发卡行异常,咨询发卡行',
        'B3CCPR11' => '发卡行异常,咨询发卡行',
        'B3CCPR12' => '发卡行异常,咨询发卡行',
        'B3CCPR13' => '发卡行异常,咨询发卡行',
        'B3CCPR14' => '发卡行异常,咨询发卡行',
        'B3CCPR15' => '发卡行异常,咨询发卡行',
        'B3CCPR16' => '无对应币种的账户',
        'B3CCPR17' => '客户不存在',
        'B3CT0001' => '发卡行异常,咨询发卡行',
        'B3CT0003' => '发卡行异常,咨询发卡行',
        'B3CT1001' => '发卡行异常,咨询发卡行',
        'B3CT1003' => '发卡行异常,咨询发卡行',
        'B3CT1007' => '发卡行异常,咨询发卡行',
        'B3CT1008' => '发卡行异常,咨询发卡行',
        'B3CT1009' => '发卡行异常,咨询发卡行',
        'B3CT1010' => '发卡行异常,咨询发卡行',
        'B3CT1011' => '发卡行异常,咨询发卡行',
        'B3CT1012' => '发卡行异常,咨询发卡行',
        'B3CT1014' => '证件号为空',
        'B3CT1015' => '发卡行异常,咨询发卡行',
        'B3CT1016' => '发卡行异常,咨询发卡行',
        'B3CT1017' => '发卡行异常,咨询发卡行',
        'B3CT1101' => '交易卡号为空',
        'B3CT1102' => '发卡行异常,咨询发卡行',
        'B3CT1103' => '发卡行异常,咨询发卡行',
        'B3CT1104' => '发卡行异常,咨询发卡行',
        'B3CT1105' => '发卡行异常,咨询发卡行',
        'B3CT1106' => '交易金额为零',
        'B3CT1107' => '发卡行异常,咨询发卡行',
        'B3CT1108' => '发卡行异常,咨询发卡行',
        'B3CT1109' => '账户状态异常',
        'B3CT1110' => '发卡行异常,咨询发卡行',
        'B3CT1111' => '发卡行异常,咨询发卡行',
        'B3CT1112' => '发卡行异常,咨询发卡行',
        'B3CT1113' => '账户不存在',
        'B3CT1114' => '账户状态异常',
        'B3CT1119' => '发卡行异常,咨询发卡行',
        'B3CT1120' => '发卡行异常,咨询发卡行',
        'B3CT1121' => '发卡行异常,咨询发卡行',
        'B3CT1122' => '余额不足',
        'B3CT1123' => '余额不足',
        'B3CT1124' => '发卡行异常,咨询发卡行',
        'B3CT1126' => '发卡行异常,咨询发卡行',
        'B3CT1127' => '发卡行异常,咨询发卡行',
        'B3CT1128' => '发卡行异常,咨询发卡行',
        'B3CT1129' => '账户不存在',
        'B3CT1130' => '账户状态异常',
        'B3CT1131' => '账户异常',
        'B3CT1132' => '账户异常',
        'B3CT1133' => '账户异常',
        'B3CT1134' => '发卡行异常,咨询发卡行',
        'B3CT1135' => '余额不足',
        'B3CT1136' => '发卡行异常,咨询发卡行',
        'B3CT1137' => '发卡行异常,咨询发卡行',
        'B3CT1138' => '发卡行异常,咨询发卡行',
        'B3CT1139' => '发卡行异常,咨询发卡行',
        'B3CT1141' => '发卡行异常,咨询发卡行',
        'B3CT1142' => '发卡行异常,咨询发卡行',
        'B3CT1144' => '发卡行异常,咨询发卡行',
        'B3CT1148' => '发卡行异常,咨询发卡行',
        'B3CT1149' => '发卡行异常,咨询发卡行',
        'B3CW1001' => '发卡行异常,咨询发卡行',
        'B3CW1002' => '发卡行异常,咨询发卡行',
        'B3CW1023' => '发卡行异常,咨询发卡行',
        'B3CW1024' => '发卡行异常,咨询发卡行',
        'B3CW1025' => '发卡行异常,咨询发卡行',
        'CT000001' => '商户数据验签失败',
        'CT000002' => '商户数据解密失败',
        'CT000003' => '响应商户数据签名失败',
        'CT000004' => '响应商户数据加密失败',
        'CT000005' => '交易超时,请稍后查询交易结果',
        'CT250101' => '交易超时,请稍后查询交易结果',
        'CT250102' => '交易重复,请稍后查询结果',
        'CT562801' => '密码设置超时',
        'CT760501' => '网关支付请求数据签名失败',
        'CT760502' => '网关支付响应数据验签失败',
    );

}
