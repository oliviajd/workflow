<?php
/* * 
 * 功能：连连支付页面跳转同步通知页面
 * 版本：1.0
 * 日期：2014-06-16
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。

 *************************页面功能说明*************************
 * 该页面可在本机电脑测试
 * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
 * 该页面可以使用PHP开发工具调试，也可以使用写文本函数logResult，该函数已被默认关闭，见llpay_notify_class.php中的函数verifyReturn
 */

require_once("llpay.config.php");
require_once("lib/llpay_notify.class.php");
include_once ('lib/llpay_cls_json.php');
?>
<!DOCTYPE HTML>
<html>
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
//计算得出通知验证结果
$llpayNotify = new LLpayNotify($llpay_config);
$verify_result = $llpayNotify->verifyReturn();
if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代码
	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取连连支付的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
	//商户编号
	$oid_partner = $_POST['oid_partner' ];
	//签名方式
	$sign_type = $_POST['sign_type' ];
	//签名
	$sign= $_POST['sign' ];
	//商户订单时间
	$dt_order= $_POST['dt_order' ];
	//商户订单号	$no_order = $_POST['no_order' ];
	//支付单号
	$oid_paybill = $_POST['oid_paybill' ];
	//交易金额
	$money_order = $_POST['money_order' ];
	//支付结果
	$result_pay =  $_POST['result_pay'];
	//清算日期
	$settle_date =  $_POST['settle_date'];
	//订单描述
	$info_order =  $_POST['info_order'];
	//支付方式
	$pay_type =  $_POST['pay_type'];
	//银行编号
	$bank_code =  $_POST['bank_code'];

    if($result_pay == 'SUCCESS') {
		//判断该笔订单是否在商户网站中已经做过处理
		//如果没有做过处理，根据订单号（no_order）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		//如果有做过处理，不执行商户的业务程序
    }else {
       echo "result_pay=".$result_pay;
    }
    file_put_contents("log.txt","同步通知:成功\n", FILE_APPEND);
	echo "验证成功<br />";
	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    //如要调试，请看llpay_notify.php页面的verifyReturn函数
    file_put_contents("log.txt","同步通知 验证失败\n", FILE_APPEND);
    echo "验证失败";
}
?>
        <title>连连支付WEB交易接口</title>
	</head>
    <body>
    </body>
</html>