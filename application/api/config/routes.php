<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
    /*
 * | -------------------------------------------------------------------------
 * | URI ROUTING
 * | -------------------------------------------------------------------------
 * | This file lets you re-map URI requests to specific controller functions.
 * |
 * | Typically there is a one-to-one relationship between a URL string
 * | and its corresponding controller class/method. The segments in a
 * | URL normally follow this pattern:
 * |
 * | example.com/class/method/id/
 * |
 * | In some instances, however, you may want to remap this relationship
 * | so that a different class/function is called than the one
 * | corresponding to the URL.
 * |
 * | Please see the user guide for complete details:
 * |
 * | http://codeigniter.com/user_guide/general/routing.html
 * |
 * | -------------------------------------------------------------------------
 * | RESERVED ROUTES
 * | -------------------------------------------------------------------------
 * |
 * | There area two reserved routes:
 * |
 * | $route['default_controller'] = 'welcome';
 * |
 * | This route indicates which controller class should be loaded if the
 * | URI contains no data. In the above example, the "welcome" class
 * | would be loaded.
 * |
 * | $route['404_override'] = 'errors/page_missing';
 * |
 * | This route will tell the Router what URI segments to use if those provided
 * | in the URL cannot be matched to a valid route.
 * |
 */

$route['default_controller'] = "home";
$route['404_override'] = '';

//user
$route['user.login'] = 'user/login';
$route['user.get'] = 'user/get';
$route['user.token'] = 'user/get';
$route['user.find'] = 'user/find';
$route['user.is.loginname.exists'] = 'user/is_loginname_exists';

//order
$route['order.get.sn'] = 'order/get_sn';
$route['order.get'] = 'order/get';
$route['order.add'] = 'order/add';
$route['order.add.prize'] = 'order/add_prize';
$route['order.update'] = 'order/update';
$route['order.lists'] = 'order/lists';
$route['order.lists.admin'] = 'order/lists_admin';
$route['order.get.admin'] = 'order/get_admin';

//goods
$route['goods.get'] = 'goods/get';
$route['goods.add'] = 'goods/add';
$route['goods.update'] = 'goods/update';
$route['goods.lists'] = 'goods/lists';
//goods zone
$route['goods.get.zones'] = 'goods/get_zones';
$route['goods.set.zones'] = 'goods/set_zones';
$route['goods.zone.add'] = 'goods/zone_add';
$route['goods.zone.update'] = 'goods/zone_update';
$route['goods.zone.lists'] = 'goods/zone_lists';
$route['goods.zone.get'] = 'goods/zone_get';
$route['goods.zone.delete'] = 'goods/zone_delete';
$route['goods.zone.item.add'] = 'goods/zone_item_add';
$route['goods.zone.item.delete'] = 'goods/zone_item_delete';
$route['goods.zone.lists.admin'] = 'goods/zone_lists_admin';

//address
$route['address.get'] = 'address/get';
$route['address.add'] = 'address/add';
$route['address.update'] = 'address/update';
$route['address.lists'] = 'address/lists';

//credit
$route['credit.enough'] = 'credit/enough';
$route['credit.log.lists'] = 'credit/log_lists';

//file
$route['file.upload.form'] = 'file/upload_form';
$route['file.upload.form.notoken'] = 'file/upload_form_notoken';
$route['file.image.resize'] = 'file/image_resize';

//脚本
$route['script.phone.recharge'] = 'script/phone_recharge';
$route['script.phone.recharge.finish'] = 'script/phone_recharge_finish';
$route['script.invest.account.process'] = 'script/invest_account_process';
$route['script.bouns.interval'] = 'script/bouns_interval';
$route['script.coupon.interval'] = 'script/coupon_interval';
$route['script.coupon.activity.161124'] = 'script/coupon_activity_161124';
$route['script.borrow.tender.activity'] = 'script/borrow_tender_activity';
$route['script.borrow.tender.third.party'] = 'script/borrow_tender_third_party';
$route['script.borrow.tender.verify'] = 'script/borrow_tender_verify';
$route['script.borrow.tender.auto'] = 'script/borrow_tender_auto';
$route['script.borrow.tender.ba'] = 'script/borrow_tender_ba';
$route['script.borrow.tender.verify.ba'] = 'script/borrow_tender_verify_ba';
$route['script.borrow.tender.freeze.before.repay.ba'] = 'script/borrow_tender_freeze_before_repay_ba';
$route['script.borrow.tender.repay.ba'] = 'script/borrow_tender_repay_ba';
$route['script.borrow.tender.repay'] = 'script/borrow_tender_repay';
$route['script.borrow.tender.finish.ba'] = 'script/borrow_tender_finish_ba';
$route['script.borrow.third.party'] = 'script/borrow_third_party';
$route['script.file.sync.to.upyun'] = 'script/file_sync_to_upyun';
$route['script.sms.send'] = 'script/sms_send';
$route['script.mika18'] = 'script/mika18';
$route['script.recharge.third.party'] = 'script/recharge_third_party';
$route['script.experience.interval'] = 'script/experience_interval';
$route['script.user.register'] = 'script/user_register';
$route['script.flow.recharge'] = 'script/flow_recharge';
$route['script.borrow.monitor'] = 'script/borrow_monitor';
$route['script.finance.borrow.full'] = 'script/finance_borrow_full';
$route['script.wechat.send'] = 'script/wechat_send';
$route['script.cash.fail'] = 'script/cash_fail';

//统计
$route['stats.business.city.daily'] = 'stats/business_city_daily';
$route['stats.business.city.monthly'] = 'stats/business_city_monthly';
$route['stats.business.province.daily'] = 'stats/business_province_daily';
$route['stats.business.province.monthly'] = 'stats/business_province_monthly';
$route['stats.business.city.monthly2'] = 'stats/business_city_monthly2';
$route['stats.manager.daily'] = 'stats/manager_daily';
$route['stats.manager.daily.logs'] = 'stats/manager_daily_logs';
$route['stats.manager.monthly'] = 'stats/manager_monthly';
$route['stats.manager.monthly.export'] = 'stats/manager_monthly_export';
$route['stats.manager.monthly.logs'] = 'stats/manager_monthly_logs';
$route['stats.manager.monthly.logs.export'] = 'stats/manager_monthly_logs_export';
$route['stats.manager.owner.monthly'] = 'stats/manager_owner_monthly';

//业务城市
$route['business.city.log.get'] = 'business_city/log_get';
$route['business.city.log.add'] = 'business_city/log_add';
$route['business.city.log.update'] = 'business_city/log_update';
$route['business.city.log.lists'] = 'business_city/log_lists';
$route['business.city.lists'] = 'business_city/lists';

//抽奖机会
$route['prize.chance.get'] = 'prize/chance_get';
$route['prize.chance.increase'] = 'prize/chance_increase';

//中奖
$route['winning.lists.admin'] = 'winning/lists_admin';

//加息券
$route['coupon.add'] = 'coupon/add';
$route['coupon.update'] = 'coupon/update';
$route['coupon.lists'] = 'coupon/lists';
$route['coupon.lists.admin'] = 'coupon/lists_admin';
$route['coupon.get'] = 'coupon/get';
$route['coupon.delete'] = 'coupon/delete';
$route['coupon.use'] = 'coupon/make_use';
$route['coupon.use.lock'] = 'coupon/use_lock';
$route['coupon.use.unlock'] = 'coupon/use_unlock';
$route['coupon.send'] = 'coupon/send';
$route['coupon.send.batch'] = 'coupon/send_batch';
$route['coupon.send.lists.admin'] = 'coupon/send_lists_admin';
$route['coupon.user.close'] = 'coupon/user_close';
$route['coupon.user.open'] = 'coupon/user_open';
$route['coupon.user.autouse'] = 'coupon/user_auto_use';
$route['coupon.user.autouse.cancel'] = 'coupon/user_auto_use_cancel';
$route['coupon.user.lists'] = 'coupon/user_lists';
$route['coupon.user.use.lists'] = 'coupon/user_use_lists';

//体验金
$route['experience.add'] = 'experience/add';
$route['experience.update'] = 'experience/update';
$route['experience.lists'] = 'experience/lists';
$route['experience.lists.admin'] = 'experience/lists_admin';
$route['experience.get'] = 'experience/get';
$route['experience.delete'] = 'experience/delete';
$route['experience.send'] = 'experience/send';
$route['experience.send.lists.admin'] = 'experience/send_lists_admin';
$route['experience.send.lists.admin.export'] = 'experience/send_lists_admin_export';
$route['experience.user.close'] = 'experience/user_close';
$route['experience.user.open'] = 'experience/user_open';
$route['experience.user.lists'] = 'experience/user_lists';
$route['experience.user.activate'] = 'experience/user_activate';
$route['experience.account.get'] = 'experience/account_get';
$route['experience.account.transfer'] = 'experience/transfer';
$route['experience.log.lists'] = 'experience/log_lists';
$route['experience.log.lists.admin'] = 'experience/log_lists_admin';
$route['experience.log.lists.admin.export'] = 'experience/log_lists_admin_export';

//标的、项目
$route['borrow.tender.add'] = 'tender/add';
$route['borrow.tender.add.redirect'] = 'tender/add_redirect';
$route['borrow.bill.verify'] = 'borrow/bill_verify';
$route['borrow.tender.lists'] = 'tender/lists';
$route['borrow.tender.bouns.recommend'] = 'tender/bouns_recommend';
$route['borrow.lists.admin'] = 'borrow/lists_admin';
$route['borrow.detail.admin'] = 'borrow/detail_admin';

//对账
$route['balance.account.check'] = 'balance/account_check';
$route['balance.log.check'] = 'balance/log_check';
$route['balance.borrow.check'] = 'balance/borrow_check';
$route['balance.account.list'] = 'balance/account_list';
$route['balance.borrow.list'] = 'balance/borrow_list';
$route['balance.log.list'] = 'balance/log_list';
$route['balance.error.list'] = 'balance/error_list';

//第三方
//米咖
$route['mika18.borrow.lists'] = 'mika18/borrow_lists';//可投标的
$route['mika18'] = 'mika18/index';//注册链接
$route['mika18.send.register'] = 'mika18/send_register';//推送注册数据
$route['mika18.send.tender'] = 'mika18/send_tender';//推送投资数据

//连连支付
$route['llpay.add'] = 'llpay/add';//创建支付订单
$route['llpay.notify'] = 'llpay/notify_url';//连连支付异步通知
$route['llpay.return'] = 'llpay/return_url';//连连支付同步

//提现
$route['cash.lists'] = 'cash/lists';

//支付方式
$route['payment.bank.lists'] = 'payment/bank_lists';//支付方式支持的银行列表

//流量充值
$route['flow_recharge.lists'] = 'flow_recharge/lists';
$route['flow_recharge.recharge'] = 'flow_recharge/recharge';
$route['flow_recharge.callback'] = 'flow_recharge/callback';
$route['flow_recharge.sendcallback'] = 'flow_recharge/sendcallback';
$route['flow_recharge.add'] = 'flow_recharge/add';

//角色
$route['role.lists.user.module'] = 'role/lists_user_module';
$route['role.lists.user.method'] = 'role/lists_user_method';
$route['role.lists.module'] = 'role/lists_module';
$route['role.lists.method'] = 'role/lists_method';
$route['role.lists.user'] = 'role/lists_user';
$route['role.add.user'] = 'role/add_user';
$route['role.delete.user'] = 'role/delete_user';
$route['role.permission.tree'] = 'permission/tree';
$route['role.get'] = 'role/get';
$route['role.add'] = 'role/add';
$route['role.update'] = 'role/update';
$route['role.lists'] = 'role/lists';
$route['role.delete'] = 'role/delete';

//融资平台
$route['finance.account.update'] = 'finance/account_update';
$route['finance.account.get'] = 'finance/account_get';
$route['finance.bill.get'] = 'finance/bill_get';
$route['finance.bill.get.admin'] = 'finance/bill_get_admin';
$route['finance.bill.add'] = 'finance/bill_add';
$route['finance.bill.update'] = 'finance/bill_update';
$route['finance.bill.lists'] = 'finance/bill_lists';
$route['finance.bill.lists.admin'] = 'finance/bill_lists_admin';
$route['finance.bill.pay.verify'] = 'finance/bill_pay_verify';
$route['finance.bill.repay.verify'] = 'finance/bill_repay_verify';
$route['finance.bill.action.lists'] = 'finance/bill_action_lists';
$route['finance.bill.action.lists.admin'] = 'finance/bill_action_lists_admin';
$route['finance.account.sub.add'] = 'finance/account_sub_add';
$route['finance.account.sub.update'] = 'finance/account_sub_update';
$route['finance.account.sub.get'] = 'finance/account_sub_get';
$route['finance.account.sub.delete'] = 'finance/account_sub_delete';
$route['finance.account.sub.lists'] = 'finance/account_sub_lists';

//风控
$route['risk.bill.verify'] = 'risk_manager/bill_verify';
$route['risk.verify.stats'] = 'risk_stats/monthly';


//红包
$route['bouns.lists'] = 'bouns/lists';
$route['bouns.get'] = 'bouns/get';
$route['bouns.update'] = 'bouns/update';
$route['bouns.user.lists'] = 'bouns/user_lists';


//文章
$route['article.get'] = 'article/get';

//微信
$route['wechat.web.oauth'] = 'wechat/web_oauth';
$route['wechat.mobile.oauth'] = 'wechat/mobile_oauth';
$route['wechat.mp.oauth'] = 'wechat/mp_oauth';
$route['wechat.mp'] = 'wechat_mp/output';
$route['wechat.mp.oauth.login'] = 'wechat/mp_oauth_login';
$route['wechat.mp.recover.lists'] = 'wechat_mp/recover_lists';
$route['wechat.mp.account.log.lists'] = 'wechat_mp/account_log_lists';

//贷罗盘
$route['dailuopan.login'] = 'dailuopan/login';
$route['dailuopan.borrow.lists'] = 'dailuopan/borrow_lists';

//网贷之家
$route['wangdaizhijia.login'] = 'wangdaizhijia/login';
$route['wangdaizhijia.borrow.lists'] = 'wangdaizhijia/borrow_lists';

//鸣金网
$route['mingjinwang.borrow.lists'] = 'mingjinwang/borrow_lists';
$route['mingjinwang.tender.lists'] = 'mingjinwang/tender_lists';

//存管
$route['ba.notify'] = 'bank_account/notify';
$route['ba.return.url'] = 'bank_account/return_url';
$route['ba.account.open'] = 'bank_account/account_open';
$route['ba.sms.code.apply'] = 'bank_account/sms_code_apply';
$route['ba.password.set'] = 'bank_account/password_set';
$route['ba.password.reset'] = 'bank_account/password_reset';
$route['ba.card.bind'] = 'bank_account/card_bind';
$route['ba.card.unbind'] = 'bank_account/card_unbind';
$route['ba.recharge'] = 'bank_account/recharge';
$route['ba.withdraw'] = 'bank_account/withdraw';
$route['ba.withdraw.cancel'] = 'bank_account/withdraw_cancel';
$route['ba.balance.query'] = 'bank_account/balance_query';
$route['ba.balance.query.admin'] = 'bank_account/balance_query_admin';
$route['ba.account.details.query'] = 'bank_account/account_details_query';
$route['ba.auto.bid.auth'] = 'bank_account/auto_bid_auth';
$route['ba.auto.bid.auth.cancel'] = 'bank_account/auto_bid_auth_cancel';
$route['ba.sync.balance.query'] = 'bank_account/sync_balance_query';
$route['ba.bid.apply.query'] = 'bank_account/bid_apply_query';
$route['ba.debt.details.query'] = 'bank_account/debt_details_query';
$route['ba.credit.details.query'] = 'bank_account/credit_details_query';
$route['ba.file.download'] = 'bank_account/fileDownload';

//资金
$route['account_log.lists.admin'] = 'account_log/lists_admin';

//站内信
$route['message.lists.admin'] = 'message/lists_admin';
$route['message.send.admin'] = 'message/send_admin';
$route['message.lists.user'] = 'message/lists_user';
$route['message.get.user'] = 'message/get_user';


//加上前缀
foreach($route as $k=>$v) {
    $route['jc.' . $k] = $v;
}

/* End of file routes.php */
/* Location: ./application/config/routes.php */
