<?php
/*
 * Memcache
 * 
 */
$config['memcache']['host'] = '127.0.0.1';
$config['memcache']['port'] = 11211;

/*
 * Admin_runner
 * 
 */
$config['admin_runner']['loginname'] = 'admin_runner';
$config['admin_runner']['password_md5'] = 'ce76182201cbdd6db9f0428bea698f66';
$config['admin_runner']['url'] = "http://{$_SERVER['HTTP_HOST']}/user/login";

/*
 * upyun
 * 
 */
$config['upyun']['bucketname'] = 'ifcar99';
$config['upyun']['username'] = 'ifcar99admin';
$config['upyun']['password'] = 'car99@yx123';
$config['upyun']['timeout'] = 600;

/*
 * sms
 * 
 */
$config['sms']['account'] = 'cf_2217659726';
$config['sms']['password'] = '2217659726';
$config['sms']['url'] = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";

/*
 * 51存证
 * 
 */
$config['51cunzheng']['key'] = 'a188a50b7e9e2576043a10136d8bb473';
$config['51cunzheng']['secret'] = 'c65143dec80fb0654334abab7866b7712e1f0adf';
$config['51cunzheng']['api_url'] = "https://www.51cunzheng.com/openapi";
$config['51cunzheng']['url'] = "https://www.51cunzheng.com/";

/*
 * 融途
 * 
 */
$config['rongtu']['key'] = '1929';
$config['rongtu']['api_url'] = "http://shuju.erongtu.com/api/borrow";
$config['rongtu']['test_url'] = "http://shuju.erongtu.com/api/test";

/* *
 * 配置文件
 * 版本：1.0
 * 日期：2014-06-16
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201306081000001016
//$config['llpay']['oid_partner'] = '201408071000001539';//test
$config['llpay']['oid_partner'] = '201511301000617537';//public

//秘钥格式注意不能修改（左对齐，右边有回车符）
$config['llpay']['RSA_PRIVATE_KEY'] ='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCmRl6Zn4MmtoBoelHRT6j6ounts/x1+GiJTB9/eBTl01cBK50h
mOUtGBcOVrJCa0C1NkR8BYgOT/WLfFT8cICw6XSJtf2uzZco71jbwXfFe8MiEx/L
XiQNQHuclpkUa1hXFUUo6Qat8X8L++pVZfjav40dPKf7oFWCYLWBCDOdyQIDAQAB
AoGANe0mqz4/o+OWu8vIE1F5pWgG5G/2VjBtfvHwWUARzwP++MMzX/0dfsWMXLsj
b0UnpF3oUizdFn86TLXTPlgidDg6h0RbGwMZou/OIcwWRzgMaCVePT/D1cuhyD7Y
V8YkjVHGnErfxyia1COswAqcpiS4lcTG/RqkAMsdwSZe640CQQDRvkQ7M2WJdydc
9QLQ9FoIMnKx9mDge7+aN6ijs9gEOgh1gKUjenLr6hcGlLRyvYDKQ4b1kes22FUT
/n+AMaEPAkEAyvH05KRzax3NNdRPI45N1KuT1kydIwL3KpOK6mWuHlffed2EiWLS
dhZNiZy9wWuwFPqkrZ8g+jL0iKcCD0mjpwJBAKbWxWmeCZ+eY3ZjAtl59X/duTRs
ekU2yoN+0KtfLG64RvBI45NkHLQiIiy+7wbyTNcXfewrJUIcNRjRcVRkpesCQEM8
BbX6BYLnTKUYwV82NfLPJRtKJoUC5n/kgZFGPnkvA4qMKOybIL6ehPGiS/tYge1x
XD1pCrPZTco4CiambuECQDNtlC31iqzSKmgSWmA5kErqVJB0f1i+a0CbQLlaPGYN
/qwa7TE13yByaUdDDaTIEUrDyuqWd5+IvlbwuVsSlMw=
-----END RSA PRIVATE KEY-----';	

//安全检验码，以数字和字母组成的字符
//$config['llpay']['key'] ='201408071000001539_sahdisa_20141205';//test
$config['llpay']['key'] ='JucheJinrong_201544121KHEWJLdag';//public

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//版本号
$config['llpay']['version'] = '1.0';

//防钓鱼ip 可不传或者传下滑线格式 
$config['llpay']['userreq_ip'] = '';//'10_10_246_110';

//证件类型
$config['llpay']['id_type'] = '0';

//签名方式 不需修改
$config['llpay']['sign_type'] = strtoupper('MD5');

//订单有效时间  分钟为单位，默认为10080分钟（7天） 
$config['llpay']['valid_order'] ="10080";

//字符编码格式 目前支持 gbk 或 utf-8
$config['llpay']['input_charset'] = strtolower('utf-8');

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$config['llpay']['transport'] = 'http';

//异步通知URL
$config['llpay']['notify_url'] = 'http://www.ifcar99.com/api_v2_test/llpay/notify';

//同步通知URL
$config['llpay']['return_url'] = 'http://www.ifcar99.com/api_v2_test/llpay/return';