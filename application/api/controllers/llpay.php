<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of llpay
 *
 * @author win7
 */
class llpay extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /*
     * 功能：连连支付WEB交易接口接入页
     */

    public function add() {
        $this->load->config('myconfig');
        $llpay_config = $this->config->item('llpay');

        require_once (APPPATH . "libraries/webllpay/lib/llpay_submit.class.php");

        /*         * ************************请求参数************************* */

        //商户用户唯一编号
        $user_id = $this->api->user()->user_id;

        //支付类型
        $busi_partner = '101001'; //虚拟商品销售：101001 实物商品销售：109001
        //付款金额
        $money_order = round($this->api->in['money'] / 100, 2);
        //必填
        //商品名称
        $name_goods = '充值';

        //订单地址
//        $url_order = $_POST['url_order'];
        //订单描述
        $info_order = '充值';

        //创建订单
        $this->load->model('m_recharge');
        $id = $this->m_recharge->add(array(
            'user_id' => $user_id,
            'money' => $money_order,
            'payment' => 26,
            'remark' => '网银充值'
        ));
        $recharge = $this->m_recharge->detail($id);

        //商户订单号 商户网站订单系统中唯一订单号，必填
        $no_order = $recharge->order_sn;

        //银行网银编码
        $bank_code = $this->api->in['bank_code'];
        //支付方式
        $pay_type = 1; //1：网银支付（借记卡）8：网银支付（信用卡）9：B2B 企业网银支付
        //卡号
//        $card_no = $_POST['card_no'];
        //姓名
//        $acct_name = $_POST['acct_name'];
        //身份证号
//        $id_no = $_POST['id_no'];
        //协议号
//        $no_agree = $_POST['no_agree'];
        //修改标记
//        $flag_modify = $_POST['flag_modify'];
        //风险控制参数
//        $risk_item = $_POST['risk_item'];
        //分账信息数据
//        $shareing_data = $_POST['shareing_data'];
        //返回修改信息地址
//        $back_url = $_POST['back_url'];
        //订单有效期
//        $valid_order = $_POST['valid_order'];
        //服务器异步通知页面路径
        $notify_url = $llpay_config['notify_url'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数
        //页面跳转同步通知页面路径
        $return_url = $llpay_config['return_url'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

        /*         * ********************************************************* */
        date_default_timezone_set('PRC');
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "version" => trim($llpay_config['version']),
            "oid_partner" => trim($llpay_config['oid_partner']),
            "sign_type" => trim($llpay_config['sign_type']),
            "userreq_ip" => trim($llpay_config['userreq_ip']),
            "id_type" => trim($llpay_config['id_type']),
            "valid_order" => trim($llpay_config['valid_order']),
            "user_id" => $user_id,
            "timestamp" => local_date('YmdHis', time()),
            "busi_partner" => $busi_partner,
            "no_order" => $no_order,
            "dt_order" => local_date('YmdHis', time()),
            "name_goods" => $name_goods,
            "info_order" => $info_order,
            "money_order" => $money_order,
            "notify_url" => $notify_url,
            "url_return" => $return_url,
            "url_order" => $url_order,
            "bank_code" => $bank_code,
            "pay_type" => $pay_type,
            "no_agree" => $no_agree,
            "shareing_data" => $shareing_data,
            "risk_item" => $risk_item,
            "id_no" => $id_no,
            "acct_name" => $acct_name,
            "flag_modify" => $flag_modify,
            "card_no" => $card_no,
            "back_url" => $back_url
        );
        //建立请求
        $llpaySubmit = new LLpaySubmit($llpay_config);
        $html_text = $llpaySubmit->buildRequestForm($parameter, "post", "确认");
        echo $html_text;
    }

    public function notify_url() {
        $this->load->config('myconfig');
        $llpay_config = $this->config->item('llpay');

        require_once (APPPATH . "libraries/webllpay/lib/llpay_cls_json.php");
        require_once (APPPATH . "libraries/webllpay/lib/llpay_notify.class.php");

        //计算得出通知验证结果
        $llpayNotify = new LLpayNotify($llpay_config);
        $verify_result = $llpayNotify->verifyNotify();

        if ($verify_result) { //验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取连连支付的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $is_notify = true;
            $json = new JSON;
            $str = file_get_contents("php://input");
            $val = $json->decode($str);
            $no_order = trim($val->{'no_order'});
            $result_pay = trim($val->{'result_pay'});
            if (strtoupper($result_pay) == 'SUCCESS') {
                $this->load->model('m_recharge');
                $this->load->model('m_account');
                $order = $this->m_recharge->detail_by_sn($no_order);
                //验证订单是否真实存在，状态是否正确
                if (!empty($order) && $order->status->id == m_recharge::STATUS_RECHARGE_UNFINISH) {
                    //交易成功逻辑处理，请不要在这个判断外做业务逻辑
                    $r = $this->m_recharge->success($order->recharge_id, new obj_user(array('user_id' => 0)), '成功充值');
                    if ($r) {
                        //资金操作记录
                        //充值记录
                        $user_id = $order->user_id;
                        $account = $this->m_account->lock($user_id);
                        $param = array(
                            'income' => $order->money,
                            'expend' => 0,
                            'balance' => $order->money,
                            'balance_cash' => $order->money,
                            'balance_frost' => 0,
                            'frost' => 0,
                            'await' => 0,
                        );
                        $param['user_id'] = intval($user_id);
                        $param['type'] = 'online_recharge';
                        $param['money'] = $order->money;
                        $param['remark'] = '在线充值';
                        $param['borrow_id'] = 0;
                        $param['tender_id'] = 0;
                        $param['to_userid'] = 0;
                        $this->m_account->add_log($param);
                        $this->m_account->unlock($user_id);
                        $account = $this->m_account->lock($user_id);
                        //手续费记录
                        $param = array(
                            'income' => 0,
                            'expend' => $order->poundage,
                            'balance' => -$order->poundage,
                            'balance_cash' => -$order->poundage,
                            'balance_frost' => 0,
                            'frost' => 0,
                            'await' => 0,
                        );
                        $param['user_id'] = intval($user_id);
                        $param['type'] = 'recharge_fee';
                        $param['money'] = $order->poundage;
                        $param['remark'] = "充值扣除手续费{$order->poundage}元";
                        $param['borrow_id'] = 0;
                        $param['tender_id'] = 0;
                        $param['to_userid'] = 0;
                        $this->m_account->add_log($param);
                        $this->m_account->unlock($user_id);
                        //ancun 异步处理 script/recharge_third_party
                        $this->db->insert(TABLE_QUEUE_RECHARGE, array(
                            'log_id' => $order->recharge_id,
                            'create_time' => time()
                        ));
                    }
                }
            }
            $this->api->output_string("{'ret_code':'0000','ret_msg':'交易成功'}"); //请不要修改或删除
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            do_log('异步通知 验证失败', $_REQUEST, file_get_contents('php://input'));
            //验证失败
            $this->api->output_string("{'ret_code':'9999','ret_msg':'验签失败'}");
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    public function return_url() {
        $this->load->config('myconfig');
        $llpay_config = $this->config->item('llpay');

        require_once (APPPATH . "libraries/webllpay/lib/llpay_notify.class.php");

        $llpayNotify = new LLpayNotify($llpay_config);
        $verify_result = $llpayNotify->verifyReturn();
        if ($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取连连支付的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            //商户编号
//            $oid_partner = $_POST['oid_partner'];
            //签名方式
//            $sign_type = $_POST['sign_type'];
            //签名
//            $sign = $_POST['sign'];
            //商户订单时间
//            $dt_order = $_POST['dt_order'];
            //商户订单号
            $no_order = $_POST['no_order'];
            //支付单号
//            $oid_paybill = $_POST['oid_paybill'];
            //交易金额
//            $money_order = $_POST['money_order'];
            //支付结果
            $result_pay = $_POST['result_pay'];
            //清算日期
//            $settle_date = $_POST['settle_date'];
            //订单描述
//            $info_order = $_POST['info_order'];
            //支付方式
//            $pay_type = $_POST['pay_type'];
            //银行编号
//            $bank_code = $_POST['bank_code'];

            if (strtoupper($result_pay) == 'SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（no_order）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $this->load->model('m_recharge');
                $this->load->model('m_account');
                $order = $this->m_recharge->detail_by_sn($no_order);
                //验证订单是否真实存在，状态是否正确
                if (!empty($order) && $order->status->id == m_recharge::STATUS_RECHARGE_UNFINISH) {
                    //交易成功逻辑处理，请不要在这个判断外做业务逻辑
                    $r = $this->m_recharge->success($order->recharge_id, new obj_user(array('user_id' => 0)), '成功充值');
                    if ($r) {
                        //资金操作记录
                        //充值记录
                        $user_id = $order->user_id;
                        $account = $this->m_account->lock($user_id);
                        $param = array(
                            'income' => $order->money,
                            'expend' => 0,
                            'balance' => $order->money,
                            'balance_cash' => $order->money,
                            'balance_frost' => 0,
                            'frost' => 0,
                            'await' => 0,
                        );
                        $param['user_id'] = intval($user_id);
                        $param['type'] = 'online_recharge';
                        $param['money'] = $order->money;
                        $param['remark'] = '在线充值';
                        $param['borrow_id'] = 0;
                        $param['tender_id'] = 0;
                        $param['to_userid'] = 0;
                        $this->m_account->add_log($param);
                        $this->m_account->unlock($user_id);
                        $account = $this->m_account->lock($user_id);
                        //手续费记录
                        $param = array(
                            'income' => 0,
                            'expend' => $order->poundage,
                            'balance' => -$order->poundage,
                            'balance_cash' => -$order->poundage,
                            'balance_frost' => 0,
                            'frost' => 0,
                            'await' => 0,
                        );
                        $param['user_id'] = intval($user_id);
                        $param['type'] = 'recharge_fee';
                        $param['money'] = $order->poundage;
                        $param['remark'] = "充值扣除手续费{$order->poundage}元";
                        $param['borrow_id'] = 0;
                        $param['tender_id'] = 0;
                        $param['to_userid'] = 0;
                        $this->m_account->add_log($param);
                        $this->m_account->unlock($user_id);
                        //ancun 异步处理 script/recharge_third_party
                        $this->db->insert(TABLE_QUEUE_RECHARGE, array(
                            'log_id' => $order->recharge_id,
                            'create_time' => time()
                        ));
                    }
                }
            } else {
                $this->api->output_string("result_pay=" . $result_pay);
            }
            echo "验证成功<br />";
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            //如要调试，请看llpay_notify.php页面的verifyReturn函数
            echo "验证失败";
        }
    }

}
