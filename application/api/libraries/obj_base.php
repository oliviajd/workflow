<?php

class obj_base {
    
}

class obj_item {

    public $id;
    public $text;

    public function __construct($item) {
        if (is_array($item)) {
            $this->id = $item['id'];
            $this->text = $item['text'];
        } else if (is_object($item)) {
            $this->id = $item->id;
            $this->text = $item->text;
        } else {
            //todo throw error
            $this->id = 0;
            $this->text = '未知';
        }
    }

}

class obj_user {

    public $user_id = 0;
    public $loginname = '';
    public $credit = 0;
    public $realname = '';
    public $mobile = '';
    public $id_card = '';
    public $verify_time = 0;
    public $register_time = 0;
    public $status = array();
    public $wx_openid = '';
    public $wx_openid_app = '';
    public $wx_openid_mp = '';
    public $wx_nickname = '';
    public $wx_headimgurl = '';
    public $wx_unionid = '';
    public $bank_account = array();

    public function __construct($user) {
        $this->user_id = $user['user_id'];
        $this->loginname = $user['username'];
        $this->credit = $user['credit'];
        $this->realname = $user['realname'];
        $this->mobile = $user['mobile'];
        $this->status = new obj_item($user['status']);
        $this->id_card = $user['id_card'];
        $this->register_time = $user['register_time'];
        $this->verify_time = $user['verify_time'];
        $this->wx_openid = $user['wx_openid'];
        $this->wx_openid_app = $user['wx_openid_app'];
        $this->wx_openid_mp = $user['wx_openid_mp'];
        $this->wx_nickname = $user['wx_nickname'];
        $this->wx_headimgurl = $user['wx_headimgurl'];
        $this->wx_unionid = $user['wx_unionid'];
        $this->bank_account = new obj_bank_account($user);
    }

}

class obj_token {

    public $token = '';
    public $over_time = 0;
    public $refresh_token = '';
    public $refresh_time = 0;
    public $create_time = 0;

    public function __construct($token) {
        $this->token = $token['token'];
        $this->over_time = $token['over_time'];
        $this->refresh_token = $token['refresh_token'];
        $this->refresh_time = $token['refresh_time'];
        $this->create_time = $token['create_time'];
        $this->device = $token['device'];
        $this->ip = $token['ip'];
    }

}

class obj_file {
    public $fid = '';
    public $size = 0;
    public $url = '';
    public $type = '';
    public $suffix = '';
    
    public function __construct($file) {
        $this->fid = $file['fid'];
        $this->size = $file['size'];
        $this->url = $file['url'];
        $this->type = $file['type'];
        $this->suffix = $file['suffix'];
    }
}

class obj_goods {
    public $iid = 0;
    public $category = array();
    public $store = 0;
    public $title = '';
    public $title_small = '';
    public $pic = array();
    public $price = 0;
    public $price_retail = 0;
    public $desc = 0;
    public $option = 0;
    public $status = array();
    public $limit = 0;
    public $is_rec = 0;
    public $num_sold = 0;
    
    public function __construct($goods) {
        $this->iid = $goods['iid'];
        $this->category = new obj_item($goods['category']);
        $this->store = $goods['store'];
        $this->limit = $goods['limit'];
        $this->title = $goods['title'];
        $this->title_small = $goods['title_small'];
        $this->pic = $goods['pic'];
        $this->pic_small = $goods['pic_small'];
        $this->pic_large = $goods['pic_large'];
        $this->price = $goods['price'];
        $this->price_retail = $goods['price_retail'];
        $this->desc = $goods['desc'];
        $this->desc_other = $goods['desc_other'];
        $this->option = $goods['option'];
        $this->status = new obj_item($goods['status']);
        $this->is_rec = $goods['is_rec'];
        $this->num_sold = $goods['num_sold'];
        $this->limit_on_time = $goods['limit_on_time'];
        $this->limit_off_time = $goods['limit_off_time'];
    }
}

class obj_goods_desc_other {
    public $exchange_process = '';//兑换流程
    public $important_note = '';//重要说明
    public $exchange_process_default = '';//兑换流程的默认文本
    public $important_note_default = '';//重要说明的默认文本
    
    public function __construct($desc) {
        $this->exchange_process = $desc['exchange_process'];
        $this->important_note = $desc['important_note'];
        $this->exchange_process_default = '默认文本1';
        $this->important_note_default = '默认文本2';
    }
}

class obj_goods_category {
    public $id = 0;
    public $text = '';
    
    public function __construct($category) {
        $this->id = $category['id'];
        $this->text = $category['title'];
    }
}

class obj_goods_zone {
    public $zone_id = 0;
    public $text = '';
    public $status = array();
    public $desc = '';
    public $is_rec = 0;
    public $goods_num = 0;
    
    public function __construct($zone) {
        $this->zone_id = $zone['id'];
        $this->title = $zone['title'];
        $this->status = $zone['status'];
        $this->desc = $zone['desc'];
        $this->is_rec = $zone['is_rec'];
        $this->goods_num = $zone['goods_num'];
    }
}

class obj_order {
    public $oid = 0;
    public $order_sn = '';
    public $user = array();
    public $address = array();
    public $price = 0;
    public $status = array();
    public $pay_status = array();
    public $pay_time = 0;
    public $create_time = 0;
    public $modify_time = 0;
    public $order_goods = array();
    
    public function __construct($order) {
        $this->oid = $order['oid'];
        $this->order_sn = $order['order_sn'];
        $this->user = $order['user'];
        $this->address = $order['address'];
        $this->price = $order['price'];
        $this->status = $order['status'];
        $this->pay_status = $order['pay_status'];
        $this->pay_time = $order['pay_time'];
        $this->shipping_status = $order['shipping_status'];
        $this->shipping = $order['shipping'];
        $this->create_time = $order['create_time'];
        $this->modify_time = $order['modify_time'];
        $this->order_goods = $order['order_goods'];
    }
}

class obj_order_goods {
    public $oid = 0;
    public $iid = 0;
    public $num = 0;
    public $price = 0;
    public $price_retail = 0;
    public $title = '';
    public $category = array();
    public $pic = '';
    public $option = '';
    
    public function __construct($order_goods) {
        $this->oid = $order_goods['oid'];
        $this->iid = $order_goods['iid'];
        $this->num = $order_goods['num'];
        $this->price = $order_goods['price'];
        $this->price_retail = $order_goods['price_retail'];
        $this->title = $order_goods['title'];
        $this->category = new obj_item($order_goods['category']);
        $this->pic = $order_goods['pic'];
        $this->option = $order_goods['option'];
    }
}

class obj_order_shipping {
    public $time = 0;
    public $company = '';
    public $company_title = '';
    public $sn = '';
    
    public function __construct($ship) {
        $this->time = $ship['time'];
        $this->company = $ship['company'];
        $this->company_title = $ship['company_title'];
        $this->sn = $ship['sn'];
    }
}

class obj_address {
    public $address_id = 0;
    public $user_id = 0;
    public $province = array();
    public $city = array();
    public $country = array();
    public $area = '';
    public $name = '';
    public $mobile = '';
    public $is_default = 0;
    
    public function __construct($address) {
        $this->address_id = $address['id'];
        $this->user_id = $address['user_id'];
        $this->province = new obj_item($address['province']);
        $this->city = new obj_item($address['city']);
        $this->country = new obj_item($address['country']);
        $this->area = $address['area'];
        $this->name = $address['name'];
        $this->mobile = $address['mobile'];
        $this->is_default = $address['is_default'];
    }
}

class obj_credit {
    public $user_id = 0;
    public $current = 0;
    public $mall_cost = 0;
    public $parts = array();
    public $sign = 0;
    
    public function __construct($credit) {
        $this->user_id = $credit['user_id'];
        $this->current = $credit['credit'];
        $this->parts = $credit['parts'];
        $this->mall_cost = $credit['mall_cost'];
        $this->sign = $credit['sign'];
    }
}

class obj_credit_class {
    public $credit_class_id = 0;
    public $name = '';
    public $nid = '';
    
    public function __construct($credit_class) {
        $this->credit_class_id = $credit_class['id'];
        $this->name = $credit_class['name'];
        $this->nid = $credit_class['nid'];
    }
}

class obj_credit_type {
    public $credit_type_id = 0;
    public $name = '';
    public $nid = '';
    public $code = '';
    public $credit_class_id = 0;
    
    public function __construct($credit_type) {
        $this->credit_type_id = $credit_type['id'];
        $this->name = $credit_type['name'];
        $this->nid = $credit_type['nid'];
        $this->code = $credit_type['code'];
        $this->credit_class_id = $credit_type['class_id'];
    }
}

class obj_credit_log {
    public $log_id = 0;
    public $value = 0;
    public $code = '';
    public $create_time = '';
    public $sign = 0;
    
    public function __construct($credit_log) {
        $this->log_id = $credit_log['id'];
        $this->user = $credit_log['user'];
        $this->class = $credit_log['class'];//积分一级分类
        $this->type = $credit_log['type'];//积分二级分类
        $this->remark = $credit_log['remark'];//积分备注
        $this->num = $credit_log['num'];
        $this->item_id = $credit_log['article_id'];
        $this->create_time = $credit_log['addtime'];
        $this->modify_time = $credit_log['update_time'];
        $this->sign = $credit_log['sign'];
        $this->value = $credit_log['value'];
    }
}

class obj_bouns {
    public $bouns_id = 0;
    public $money = 0;
    public $creator = 0;
    public $num_current = 0;
    public $num_total = 0;
    public $expire = 0;
    public $start_time = 0;
    public $end_time = 0;
    public $use_channel = '';//使用渠道
    public $use_type = '';//使用方式
    public $remark = '';//备注
    public $create_time = 0;
    public $modify_time = 0;
    public $title = '';
    public $limit_upper_times = 0;
    public $has_sent_times = 0;
    public $ratio = 0;
    public $limit_on_time = 0;
    public $limit_off_time = 0;
    public $limit_lower_money = 0;
    public $limit_upper_money = 0;
    
    public function __construct($bouns) {
        $this->bouns_id = $bouns['hid'];
        $this->money = $bouns['money'];
        $this->creator = $bouns['username'];
        $this->num_current = $bouns['nums'];
        $this->num_total = $bouns['totalnum'];
        $this->expire = $bouns['ytime'];
        $this->start_time = $bouns['starttime'];
        $this->use_channel = $bouns['use_channel'];
        $this->use_type = $bouns['usetype'];
        $this->remark = $bouns['whyadd'];
        $this->create_time = $bouns['addttime'];
        $this->modify_time = $bouns['edittime'];
        $this->title = $bouns['title'];
        $this->limit_upper_times = $bouns['limit_upper_times'];
        $this->has_sent_times = $bouns['has_sent_times'];
        $this->ratio = $bouns['ratio'];
        $this->limit_on_time = $bouns['limit_on_time'];
        $this->limit_off_time = $bouns['limit_off_time'];
        $this->limit_lower_money = $bouns['limit_lower_money'];
        $this->limit_upper_money = $bouns['limit_upper_money'];
    }
}

class obj_bouns_user {
    public $bouns_user_id = 0;
    public $bouns_id = 0;
    public $user_id = 0;
    public $title = '';
    public $receive_time = 0;
    public $expire_time = 0;
    public $status = 0;
    public $use_time = 0;
    public $use_for = '';
    public $remark = '';
    public $money = 0;
    public $enable_time = 0;
    public $disable_time = 0;
    public $limit_lower_money = 0;
    public $limit_upper_money = 0;
    public $ratio = 0;
    public $use_desc = '';
    
    public function __construct($bouns_user) {
        $this->bouns_user_id = $bouns_user['id'];
        $this->bouns_id = $bouns_user['hid'];
        $this->user_id = $bouns_user['uid'];
        $this->title = $bouns_user['title'];
        $this->receive_time = $bouns_user['addtime'];
        $this->expire_time = $bouns_user['endtime'];
        $this->status = new obj_item($bouns_user['status']);
        $this->use_time = $bouns_user['usetime'];
        $this->use_for = $bouns_user['pname'];
        $this->remark = $bouns_user['remarks'];
        $this->money = $bouns_user['money'];
        $this->enable_time = $bouns_user['enable_time'];
        $this->disable_time = $bouns_user['disable_time'];
        $this->limit_lower_money = $bouns_user['limit_lower_money'];
        $this->limit_upper_money = $bouns_user['limit_upper_money'];
        $this->ratio = $bouns_user['ratio'];
        $this->use_desc = '';
    }

}

class obj_prize {
    public $activity_id = 0;
    public $pid = 0;
    public $store = 0;
    public $title = '';
    public $rate = 0;
    public $background = 0;
    public $category = array();
    public $iid = 0;


    public function __construct($prize) {
        $this->category = new obj_item($prize['category']);
        $this->activity_id = $prize['activity_id'];
        $this->pid = $prize['pid'];
        $this->store = $prize['store'];
        $this->title = $prize['title'];
        $this->rate = $prize['rate'];
        $this->background = $prize['background'];
        $this->iid = $prize['iid'];
    }

}

class obj_script {
    public $script_id = 0;
    public $name = '';
    public $desc = '';
    public $path = '';
    public $cmd = '';
    public $pid = 0;
    public $pid_path = '';
    public $status = array();
    public $last_start_time = 0;
    public $allow_remote_start = 0;
    public $create_time = 0;
    
    public function __construct($script) {
        $this->script_id = $script['id'];
        $this->name = $script['name'];
        $this->desc = $script['desc'];
        $this->path = $script['path'];
        $this->cmd = $script['cmd'];
        $this->pid = $script['pid'];
        $this->pid_path = $script['pid_path'];
        $this->status = new obj_item($script['status']);
        $this->last_start_time = $script['last_start_time'];
        $this->allow_remote_start = $script['allow_remote_start'];
        $this->create_time = $script['create_time'];
    }

}

class obj_business_city {
    public $city_id = 0;
    public $title = '';
    public $is_rec = 0;
    public $status = array();
    
    public function __construct($city) {
        $this->city_id = $city['city_id'];
        $this->title = $city['title'];
        $this->is_rec = $city['is_rec'];
        $this->status = new obj_item($city['status']);
    }
}

class obj_business_city_log {
    public $log_id = 0;
    public $city = array();
    public $data_credit_investigation = 0;
    public $data_home_visits = 0;
    public $data_refuse = 0;
    public $data_paid = 0;
    public $data_paid_nums = 0;
    public $data_bank_repay_nums = 0;
    public $data_bank_repay = 0;
    public $data_mortgage_nums = 0;
    public $data_mortgage = 0;
    public $data_overdue_nums = 0;
    public $data_overdue = 0;
    public $status = array();
    public $date = '';
    public $create_time = 0;
    
    public function __construct($log) {
        $this->log_id = $log['id'];
        $this->city = new obj_item($log['city']);
        $this->data_credit_investigation = $log['data_credit_investigation'];
        $this->data_home_visits = $log['data_home_visits'];
        $this->data_refuse = $log['data_refuse'];
        $this->data_paid = $log['data_paid'];
        $this->data_paid_nums = $log['data_paid_nums'];
        $this->data_bank_repay_nums = $log['data_bank_repay_nums'];
        $this->data_bank_repay = $log['data_bank_repay'];
        $this->data_mortgage_nums = $log['data_mortgage_nums'];
        $this->data_mortgage = $log['data_mortgage'];
        $this->data_overdue_nums = $log['data_overdue_nums'];
        $this->data_overdue = $log['data_overdue'];
        $this->status = new obj_item($log['status']);
        $this->date = $log['ymd'];
        $this->create_time = $log['create_time'];
    }
}

class obj_prize_chance {
    public $id = 0;
    public $user_id = 0;
    public $activity_id = 0;
    public $chance = 0;
    public $flag = 0;
    public $num = 0;

    public function __construct($prize) {
        $this->id = $prize['id'];
        $this->user_id = $prize['user_id'];
        $this->activity_id = $prize['activity_id'];
        $this->chance = $prize['chance'];
        $this->flag = $prize['flag'];
        $this->num = $prize['num'];
    }


}

class obj_winning {
    public $wid = 0;
    public $pid = 0;
    public $iid = 0;
    public $pname = '';
    public $create_time = 0;
    public $order_sn = '';
    public $shipping_status = 0;
    public $user = '';
    public $activity_id = 0;
    public $decrease_type = '';

    public function __construct($winning) {
        $this->wid = $winning['wid'];
        $this->pid = $winning['pid'];
        $this->iid = $winning['iid'];
        $this->pname = $winning['pname'];
        $this->create_time = $winning['create_time'];
        $this->order_sn = $winning['order_sn'];
        $this->shipping_status = $winning['shipping_status'];
        $this->user = $winning['user'];
        $this->activity_id = $winning['activity_id'];
        $this->decrease_type = $winning['decrease_type'];
    }

}

class obj_activity {
    public $activity_id = 0;
    public $title = '';
    public $remark = '';
    public $limit_on_time = 0;
    public $limit_off_time = 0;
    public $status = array();
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($activity) {
        $this->activity_id = $activity['id'];
        $this->title = $activity['title'];
        $this->remark = $activity['remark'];
        $this->limit_on_time = $activity['limit_on_time'];
        $this->limit_off_time = $activity['limit_off_time'];
        $this->status = new obj_item($activity['status']);
        $this->create_time = $activity['create_time'];
        $this->modify_time = $activity['modify_time'];
    }
    

}

class obj_coupon {
    public $coupon_id = 0;
    public $title = '';
    public $rate = 0;
    public $type = '';
    public $coupon_type = '';//同type 兼容老版本
    public $status = array();
    public $num_sent = 0;
    public $store = 0;
    public $check_store = 0;
    public $expire = 0;
    public $level = 0;
    public $limit_lower_money = 0;
    public $limit_upper_money = 0;
    public $limit_on_time = 0;
    public $limit_off_time = 0;
    public $use_times = 0;
    public $limit_use_for = 0;
    public $use_for = 0;
    public $is_auto_use = 0;
    public $start_time = 0;
    public $end_time = 0;
    public $remark = '';
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($activity) {
        $this->coupon_id = $activity['id'];
        $this->title = $activity['title'];
        $this->remark = $activity['remark'];
        $this->limit_on_time = $activity['limit_on_time'];
        $this->limit_off_time = $activity['limit_off_time'];
        $this->status = new obj_item($activity['status']);
        $this->create_time = $activity['create_time'];
        $this->modify_time = $activity['modify_time'];
        $this->rate = $activity['rate'];
        $this->type = $activity['type'];
        $this->coupon_type = $activity['type'];
        $this->num_sent = $activity['num_sent'];
        $this->store = $activity['store'];
        $this->check_store = $activity['check_store'];
        $this->expire = $activity['expire'];
        $this->level = $activity['level'];
        $this->limit_lower_money = $activity['limit_lower_money'];
        $this->limit_upper_money = $activity['limit_upper_money'];
        $this->use_times = $activity['use_times'];
        $this->limit_use_for = $activity['limit_use_for'];
        $this->use_for = $activity['use_for'];
        $this->is_force_use = $activity['is_force_use'];
        $this->start_time = $activity['start_time'];
        $this->end_time = $activity['end_time'];
    }
    

}

class obj_coupon_user {
    public $coupon_user_id = 0;
    public $coupon_id = 0;
    public $user_id = 0;
    public $type = '';
    public $user = array();
    public $receive_time = 0;
    public $expire_time = 0;
    public $status = array();
    public $use_times = 0;
    public $unuse_times = 0;
    public $use_for = '';
    public $limit_use_for = 0;
    public $remark = '';
    public $title = '';
    public $rate = 0;
    public $enable_time = 0;
    public $disable_time = 0;
    public $level = 0;
    public $is_force_use = 0;
    public $is_auto_use = 0;
    public $limit_lower_money = 0;
    public $limit_upper_money = 0;
    
    public function __construct($coupon_user) {
        $this->coupon_user_id = $coupon_user['id'];
        $this->coupon_id = $coupon_user['coupon_id'];
        $this->user_id = $coupon_user['user_id'];
        $this->type = $coupon_user['type'];
        $this->user = $coupon_user['user'];
        $this->receive_time = $coupon_user['receive_time'];
        $this->expire_time = $coupon_user['expire_time'];
        $this->status = new obj_item($coupon_user['status']);
        $this->use_times = $coupon_user['use_times'];
        $this->use_for = $coupon_user['use_for'];
        $this->limit_use_for = $coupon_user['limit_use_for'];
        $this->unuse_times = $coupon_user['unuse_times'];
        $this->remark = $coupon_user['remark'];
        $this->title = $coupon_user['title'];
        $this->rate = $coupon_user['rate'];
        $this->enable_time = $coupon_user['enable_time'];
        $this->disable_time = $coupon_user['disable_time'];
        $this->level = $coupon_user['level'];
        $this->is_force_use = $coupon_user['is_force_use'];
        $this->is_auto_use = $coupon_user['is_auto_use'];
        $this->limit_lower_money = $coupon_user['limit_lower_money'];
        $this->limit_upper_money = $coupon_user['limit_upper_money'];
    }

}

class obj_coupon_user_used {
    public $coupon_user_used_id = 0;
    public $coupon_user_id = 0;
    public $coupon_id = 0;
    public $user_id = 0;
    public $status = array();
    public $receive_time = 0;
    public $use_time = 0;
    public $use_times = 0;
    public $use_for = '';
    public $use_for_title = '';
    public $remark = '';
    public $rate = 0;
    
    public function __construct($coupon_used) {
        $this->coupon_user_used_id = $coupon_used['id'];
        $this->coupon_user_id = $coupon_used['coupon_user_id'];
        $this->coupon_id = $coupon_used['coupon_id'];
        $this->user_id = $coupon_used['user_id'];
        $this->status = new obj_item($coupon_used['status']);
        $this->receive_time = $coupon_used['receive_time'];
        $this->use_time = $coupon_used['use_time'];
        $this->use_times = $coupon_used['use_times'];
        $this->use_for = $coupon_used['use_for'];
        $this->use_for_title = $coupon_used['use_for_title'];
        $this->remark = $coupon_used['remark'];
        $this->rate = $coupon_used['rate'];
    }
}

class obj_borrow {
    public $borrow_id = 0;
    public $title = '';
    public $status = array();
    public $category = array();
    public $cards = array();
    public $rate = 0;
    public $period = 0;//暂时冗余旧字段
    public $money_total = 0;
    public $money_get = 0;
    public $money_unget = 0;
    public $days = 0;
    public $repay_way = 0;
    public $guarantee = array();//评估单位
    public $assessment = array();//担保单位
    public $is_for_new_comer = 0;
    public $is_for_single = 0;
    public $expire = 0;
    public $limit_on_time = 0;
    public $limit_off_time = 0;
    public $limit_upper_money = 0;
    public $limit_lower_money = 0;
    public $desc = array();
    public $pic = array();
    public $is_rec = 0;
    public $is_top = 0;
    public $create_time = 0;
    public $full_time = 0;
    public $tender_times = 0;
    public $ba_id = 0;
    public $ba_account_id = '';
    public $list_type = '';
    public $queue_status = '';
    public $queue_ba_status = '';
    public $ancun_url = '';
    public $recordNo = '';
    public $activity_color = '';
    public $activity_text = '';
    public $activity_pic = '';
    
    public function __construct($borrow) {
        $this->borrow_id = $borrow['borrow_nid'];
        $this->title = $borrow['name'];
        $this->status = new obj_item($borrow['status']);
        $this->category = new obj_item($borrow['category']);
        $this->cards = $borrow['cards'];
        $this->rate = $borrow['borrow_apr'];
        $this->period = $borrow['borrow_period'];
        $this->money_total = $borrow['account'];
        $this->money_get = $borrow['borrow_account_yes'];
        $this->money_unget = $borrow['borrow_account_wait'];
        $this->days = $borrow['days'];
        $this->repay_way = $borrow['borrow_style'];
        $this->guarantee = $borrow['pinggu'];
        $this->assessment = $borrow['danbao'];
        $this->is_for_new_comer = $borrow['xszx'] == 1 ? 1 : 2;
        $this->is_for_single = $borrow['dan'] == 1 ? 1 : 2;
        $this->expire = $borrow['borrow_valid_time']*3600*24;
        $this->limit_on_time = $borrow['limit_on_time'];
        $this->limit_off_time = $borrow['limit_off_time'];
        $this->limit_upper_money = $borrow['limit_upper_money'];
        $this->limit_lower_money = $borrow['limit_lower_money'];
        $this->desc = array(
            'desc'=>$borrow['borrow_contents'],
            'user_desc'=>$borrow['userinfo'],
            'pay_from'=>$borrow['fengxian'],
        );
        $this->pic = $borrow['pic'];
        $this->is_rec = $borrow['recommend'] == 1 ? 1 : 2;
        $this->is_top = $borrow['is_ding'] == 1 ? 1 : 2;
        $this->create_time = $borrow['verify_time'];
        $this->full_time = $borrow['reverify_time'];
        $this->tender_times = $borrow['tender_times'];
        $this->ba_id = $borrow['ba_id'];
        $this->ba_account_id = $borrow['ba_account_id'];
        $this->list_type = $borrow['list_type'];
        $this->queue_status = $borrow['queue_status'];
        $this->queue_ba_status = $borrow['queue_ba_status'];
        $this->ancun_url = $borrow['ancun_url'];
        $this->recordNo = $borrow['recordNo'];
        $this->activity_color = $borrow['activity_color'];
        $this->activity_text = $borrow['activity_text'];
        $this->activity_pic = $borrow['activity_pic'];
    }
}

class obj_tender {
    public $tender_id = 0;
    public $borrow_id = '';
    public $status = array();
    public $user_id = 0;
    public $money = 0;
    public $money_bouns = 0;
    public $from = '';
    public $create_time = 0;
    public $borrow_name = '';
    public $reverify_time = 0;
    public $recover_account_interest = 0;
    public $coupon_amount = 0;
    public $recover_time = 0;
    
    public function __construct($tender) {
        $this->tender_id = $tender['id'];
        $this->borrow_id = $tender['borrow_nid'];
        $this->user_id = $tender['user_id'];
        $this->money = $tender['account'];
        $this->money_bouns = $tender['hbagmoney'];
        $this->status = new obj_item($tender['status']);
        $this->from = $tender['source'];
        $this->create_time = $tender['addtime'];
        $this->borrow_name = $tender['borrow_name'];
        $this->reverify_time = $tender['reverify_time'];
        $this->recover_account_interest = $tender['recover_account_interest'];
        $this->coupon_amount = $tender['coupon_amount'];
        $this->recover_time = $tender['recover_time'];
    }
}

class obj_balance_account {
    public $id = 0;
    public $user_id = 0;
    public $status = array();
    public $remark = '';
    public $account_log_id = 0;
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($balance_account) {
        $this->id = $balance_account['id'];
        $this->user_id = $balance_account['user_id'];
        $this->remark = $balance_account['remark'];
        $this->status = new obj_item($balance_account['status']);
        $this->account_log_id = $balance_account['account_log_id'];
        $this->create_time = $balance_account['create_time'];
        $this->modify_time = $balance_account['modify_time'];
    }
}

class obj_balance_log {
    public $id = 0;
    public $user_id = 0;
    public $status = array();
    public $remark = array();
    public $account_log_id = 0;
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($balance_log) {
        $this->id = $balance_log['id'];
        $this->user_id = $balance_log['user_id'];
        $this->remark = $balance_log['remark'];
        $this->status = new obj_item($balance_log['status']);
        $this->account_log_id = $balance_log['account_log_id'];
        $this->create_time = $balance_log['create_time'];
        $this->modify_time = $balance_log['modify_time'];
    }
}

class obj_account {
    public $user_account_id = 0;
    public $user_id = 0;
    public $total = 0.0;
    public $income = 0.0;
    public $expend = 0.0;
    public $balance = 0.0;
    public $balance_cash = 0.0;
    public $balance_frost = 0.0;
    public $frost = 0.0;
    public $await = 0.0;
    public $bouns_used = 0.0;
    public $modify_time = 0;
    public $ba_inpDate = 0;
    public $recover_capital_sum = 0;
    public $recover_interest_sum = 0;
    public $ba_processing = 0;
    public $experience_interest = 0;
    public $transfer_money = 0;
    
    public function __construct($account) {
        $this->user_account_id = $account['id'];
        $this->user_id = $account['user_id'];
        $this->total = $account['total'];
        $this->income = $account['income'];
        $this->expend = $account['expend'];
        $this->balance = $account['balance'];
        $this->balance_cash = $account['balance_cash'];
        $this->balance_frost = $account['balance_frost'];
        $this->frost = $account['frost'];
        $this->await = $account['await'];
        $this->bouns_used = $account['hbag'];
        $this->modify_time = $account['modify_time'];
        $this->ba_inpDate = $account['ba_inpDate'];
        $this->recover_capital_sum = $account['recover_capital_sum'];
        $this->recover_interest_sum = $account['recover_interest_sum'];
        $this->ba_processing = $account['ba_processing'];
        $this->experience_interest = $account['experience_interest'];
        $this->transfer_money = $account['transfer_money'];
    }
}

class obj_balance_borrow {
    public $id = 0;
    public $borrow_id = 0;
    public $status = array();
    public $remark = '';
    public $tender_id = 0;
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($balance_borrow) {
        $this->id = $balance_borrow['id'];
        $this->borrow_id = $balance_borrow['borrow_id'];
        $this->remark = $balance_borrow['remark'];
        $this->status = new obj_item($balance_borrow['status']);
        $this->tender_id = $balance_borrow['tender_id'];
        $this->create_time = $balance_borrow['create_time'];
        $this->modify_time = $balance_borrow['modify_time'];
    }
}

class obj_balance_error {
    public $id = 0;
    public $user_id = 0;
    public $status = array();
    public $remark = '';
    public $borrow_id = 0;
    public $create_time = 0;
    public $modify_time = 0;
    public $category = '';
    
    public function __construct($balance_error) {
        $this->id = $balance_error['id'];
        $this->user_id = $balance_error['user_id'];
        $this->remark = $balance_error['remark'];
        $this->status = new obj_item($balance_error['status']);
        $this->borrow_id = $balance_error['borrow_id'];
        $this->create_time = $balance_error['create_time'];
        $this->modify_time = $balance_error['modify_time'];
        $this->category = $balance_error['category'];
    }
}

class obj_sms {
    public $id = 0;
    public $mobile = '';
    public $content = '';
    public $status = array();
    public $send_time = 0;
    
    public function __construct($sms) {
        $this->sms_id = $sms['id'];
        $this->mobile = $sms['mobile'];
        $this->content = $sms['content'];
        $this->status = new obj_item($sms['status']);
        $this->send_time = $sms['send_time'];
    }
}

class obj_cash {
    public $cash_id = 0;
    public $user_id = 0;
    public $nid = '';
    public $status = 0;
    public $account = 0;
    public $bank = '';
    public $bank_id = 0;//暂时冗余旧字段
    public $branch = '';
    public $province = '';
    public $city = '';
    public $total = 0;
    public $credited = 0;
    public $fee = 0;//评估单位
    public $verify_userid = 0;//担保单位
    public $verify_time = 0;
    public $verify_remark = '';
    public $addtime = 0;
    public $addip = '';
    public $source = '';
    public $record_no = 0;
    public $is_first = 0;
    public $user = array();
    public $balance = 0;
    public $card_id = '';
    public $name = '';
    public $mobile = '';
    public $routeCode = '';
    public $cardBankCnaps = '';
    
    public function __construct($cash) {
        $this->cash_id = $cash['id'];
        $this->user_id= $cash['user_id'];
        $this->nid = $cash['nid'];
        $this->status = new obj_item($cash['status']);
        $this->account = $cash['account'];
        $this->bank = $cash['bank'];
        $this->bank_id = $cash['bank_id'];
        $this->branch = $cash['branch'];
        $this->province = $cash['province'];
        $this->city = $cash['city'];
        $this->total = $cash['total'];
        $this->credited = $cash['credited'];
        $this->fee = $cash['fee'];
        $this->verify_time = $cash['verify_time'];
        $this->verify_remark = $cash['verify_remark'];
        $this->addtime = $cash['addtime'];
        $this->addip = $cash['addip'];
        $this->source = $cash['source'];
        $this->record_no = $cash['record_no'];
        $this->is_first = $cash['is_first'];
        $this->user = $cash['user'];
        $this->balance = $cash['balance'];
        $this->card_id = $cash['card_id'];
        $this->name = $cash['name'];
        $this->mobile = $cash['mobile'];
        $this->routeCode = $cash['routeCode'];
        $this->cardBankCnaps = $cash['cardBankCnaps'];
    }
}

class obj_recharge {
    public $recharge_id = 0;
    public $user_id = 0;
    public $money = 0;
    public $poundage = 0;//手续费
    public $status = array();
    public $remark = '';
    public $order_sn = '';
    public $create_time = 0;
    public $verify_time = 0;
    
    public function __construct($recharge) {
        $this->recharge_id = $recharge['id'];
        $this->user_id = $recharge['user_id'];
        $this->money = $recharge['money'];
        $this->poundage = $recharge['fee'];
        $this->status = new obj_item($recharge['status']);
        $this->remark = $recharge['remark'];
        $this->order_sn = $recharge['nid'];
        $this->create_time = $recharge['addtime'];
        $this->verify_time = $recharge['verify_time'];
    }
}

class obj_payment_bank {
    public $bank_name = '';
    public $bank_code = '';
    public $payment = '';
    public $sort = 0;
    
    public function __construct($bank) {
        $this->bank_name  = $bank['bank_name'];
        $this->bank_code  = $bank['bank_code'];
        $this->payment  = $bank['payment'];
        $this->sort  = $bank['sort'];
    }
}

class obj_experience {
    public $experience_id = 0;
    public $title = '';
    public $limit_upper_money = 0;
    public $has_sent_money = 0;
    public $rate = 0;
    public $days = 0;
    public $expire = 0;
    public $status = array();
    public $create_time = 0;
    public $remark = '';
    
    public function __construct($experience) {
        $this->experience_id = $experience['id'];
        $this->title = $experience['title'];
        $this->limit_upper_money = $experience['limit_upper_money'];
        $this->has_sent_money = $experience['has_sent_money'];
        $this->rate = $experience['rate'];
        $this->days = $experience['days'];
        $this->expire = $experience['expire'];
        $this->status = new obj_item($experience['status']);
        $this->create_time = $experience['create_time'];
        $this->remark = $experience['remark'];
    }
}

class obj_experience_user {
    public $experience_user_id = 0;
    public $user_id = 0;
    public $user = array();
    public $experience_id = 0;
    public $money = 0;
    public $rate = 0;
    public $days = 0;
    public $profit = 0;
    public $profit_unget = 0;
    public $profit_last_time = 0;
    public $status = array();
    public $receive_time = 0;
    public $create_time = 0;
    public $expire_time = 0;
    public $start_time = 0;
    public $end_time = 0;
    public $remark = '';
    
    
    public function __construct($experience_user) {
        $this->experience_user_id = $experience_user['id'];
        $this->user_id = $experience_user['user_id'];
        $this->user = $experience_user['user'];
        $this->experience_id = $experience_user['experience_id'];
        $this->money = $experience_user['money'];
        $this->rate = $experience_user['rate'];
        $this->days = $experience_user['days'];
        $this->profit = $experience_user['profit'];
        $this->profit_unget = $experience_user['profit_unget'];
        $this->profit_last_time = $experience_user['profit_last_time'];
        $this->status = new obj_item($experience_user['status']);
        $this->receive_time = $experience_user['receive_time'];
        $this->create_time = $experience_user['create_time'];
        $this->expire_time = $experience_user['expire_time'];
        $this->start_time = $experience_user['start_time'];
        $this->end_time = $experience_user['end_time'];
        $this->remark = $experience_user['remark'];
    }
}

class obj_experience_account {
    public $user_id = 0;
    public $money = 0;
    public $moeny_total = 0;
    public $money_out = 0;
    public $money_real_time = 0;//体验金实时收益
    public $experience_real_time = 0;//体验金实时金额
    public $real_time = 0;//体验金实时当前时间
    public $in_use = array();//当前生效的体验金列表
    
    public function __construct($experience_account) {
        $this->user_id = $experience_account['user_id'];
        $this->money = $experience_account['money'];
        $this->moeny_total = $experience_account['money_total'];
        $this->money_out = $experience_account['money_out'];
        $this->money_real_time = $experience_account['money_real_time'];
        $this->experience_real_time = $experience_account['experience_real_time'];
        $this->real_time = $experience_account['real_time'];
        $this->in_use = $experience_account['in_use'];
    }
}

class obj_experience_log {
    public $experience_log_id = 0;
    public $experience_id = 0;
    public $experience_user_id = 0;
    public $user_id = 0;
    public $user = array();
    public $money = 0;
    public $type = array();
    public $create_time = 0;
    
    public function __construct($experience_log) {
        $this->experience_log_id = $experience_log['id'];
        $this->experience_id = $experience_log['experience_id'];
        $this->experience_user_id = $experience_log['experience_user_id'];
        $this->user_id = $experience_log['user_id'];
        $this->user = $experience_log['user'];
        $this->money = $experience_log['money'];
        $this->type = $experience_log['type'];
        $this->create_time = $experience_log['create_time'];
    }
}

class obj_prize_range {
    public $id = 0;
    public $pid = 0;
    public $activity_id = 0;
    public $title = '';
    public $rate = 0;
    public $start = 0;
    public $end = 0;
    public $create_time = 0;
    public $modify_time = 0;

    public function __construct($prize) {
        $this->id = $prize['id'];
        $this->pid = $prize['pid'];
        $this->activity_id = $prize['activity_id'];
        $this->title = $prize['title'];
        $this->rate = $prize['rate'];
        $this->start = $prize['start'];
        $this->end = $prize['end'];
        $this->create_time = $prize['create_time'];
        $this->modify_time = $prize['modify_time'];
    }


}

class obj_error {
    public $error_id = 0;
    public $status = array();
    public $item_type = '';
    public $item_id = '';
    public $error_no = '';
    public $error_msg = '';
    public $has_noticed = '';
    public $repeat_notice = '';
    public $last_time = 0;
    public $create_time = 0;
}

class obj_role {
    public $role_id = 0;
    public $title = '';
    public $desc = '';
    public $parent_id = 0;
    public $has_child = 0;
    
    public function __construct($role) {
        $this->role_id = $role['id'];
        $this->title = $role['title'];
        $this->desc = $role['desc'];
        $this->parent_id = $role['parent_id'];
        $this->has_child = $role['has_child'];
    }
}

class obj_role_user {
    public $role = array();
    public $user = array();
    public $create_time = 0;
    
    public function __construct($role_user) {
        $this->role = $role_user['role'];
        $this->user = $role_user['user'];
        $this->create_time = $role_user['create_time'];
    }
}

class obj_role_module {
    public $module_id = 0;
    
    public function __construct($role_module) {
        $this->module_id = $role_module['module_id'];
    }
}

class obj_role_method {
    public $method_id = 0;
    
    public function __construct($role_method) {
        $this->method_id = $role_method['method_id'];
    }
}

class obj_permission_module {
    public $module_id = 0;
    public $title = 0;
    
    public function __construct($module) {
        $this->module_id = $module['id'];
        $this->title = $module['menu_title'];
    }
}

class obj_permission_method {
    public $method_id = 0;
    public $module_id = 0;
    public $title = 0;
    
    public function __construct($method) {
        $this->method_id = $method['method_id'];
        $this->module_id = $method['cid'];
        $this->title = $method['method_name_cn'];
    }
}

class obj_finance_bill {
    
    public $finance_bill_id = 0;
    public $bill_sn = 0;
    public $user = array();
    public $status = array();
    public $online_status = array();
    public $money = 0;
    public $name = '';
    public $id_card = '';
    public $car = '';
    public $car_type = 0;
    public $finance_account_sub_id = 0;
    public $company = '';
    public $advance = 0;
    public $payment_certificate = '';
    public $paid_time = 0;
    public $pic = '';
    public $cards = '';
    public $attach = '';
    public $pay_account = '';
    public $has_verified = 0;
    public $has_online = 0;
    public $has_full = 0;
    public $has_paid = 0;
    public $has_repaid = 0;
    public $has_expired = 0;
    public $has_added = 0;
    public $user_remark = '';
    public $verify_remark = '';
    public $online_remark = '';
    public $verify_success_time = 0;
    public $online_success_time = 0;
    public $full_success_time = 0;
    public $pay_success_time = 0;
    public $repay_success_time = 0;
    public $borrow_title = '';
    public $borrow_id = '';
    public $borrow_days = 0;
    public $version = 0;
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($finance_bill) {
        $this->finance_bill_id = $finance_bill['id'];
        $this->bill_sn = $finance_bill['bill_sn'];
        $this->user = $finance_bill['user'];
        $this->status = new obj_item($finance_bill['status']);
        $this->online_status = new obj_item($finance_bill['online_status']);
        $this->money = $finance_bill['money'];
        $this->name = $finance_bill['name'];
        $this->id_card = $finance_bill['id_card'];
        $this->car = $finance_bill['car'];
        $this->car_type = new obj_item($finance_bill['car_type']);
        $this->finance_account_sub_id = $finance_bill['finance_account_sub_id'];
        $this->company = $finance_bill['company'];
        $this->advance = $finance_bill['advance'];
        $this->payment_certificate = $finance_bill['payment_certificate'];
        $this->paid_time = $finance_bill['paid_time'];
        $this->pic = $finance_bill['pic'];
        $this->cards = $finance_bill['cards'];
        $this->attach = $finance_bill['attach'];
        $this->pay_account = $finance_bill['pay_account'];
        $this->has_verified = new obj_item($finance_bill['has_verified']);
        $this->has_online = new obj_item($finance_bill['has_online']);
        $this->has_full = new obj_item($finance_bill['has_full']);
        $this->has_paid = new obj_item($finance_bill['has_paid']);
        $this->has_repaid = new obj_item($finance_bill['has_repaid']);
        $this->has_expired = new obj_item($finance_bill['has_expired']);
        $this->has_added = new obj_item($finance_bill['has_added']);
        $this->user_remark = $finance_bill['user_remark'];
        $this->verify_remark = $finance_bill['verify_remark'];
        $this->online_remark = $finance_bill['online_remark'];
        $this->pay_remark = $finance_bill['pay_remark'];
        $this->repay_remark = $finance_bill['repay_remark'];
        $this->online_remark = $finance_bill['online_remark'];
        $this->verify_success_time = $finance_bill['verify_success_time'];
        $this->online_success_time = $finance_bill['online_success_time'];
        $this->full_success_time = $finance_bill['full_success_time'];
        $this->pay_success_time = $finance_bill['pay_success_time'];
        $this->repay_success_time = $finance_bill['repay_success_time'];
        $this->borrow_title = $finance_bill['borrow_title'];
        $this->borrow_id = $finance_bill['borrow_id'];
        $this->borrow_days = $finance_bill['borrow_days'];
        $this->version = $finance_bill['version'];
        $this->create_time = $finance_bill['create_time'];
        $this->modify_time = $finance_bill['modify_time'];
    }
    
}

class obj_finance_account {
    
    public $finance_account_id = 0;
    public $user = array();
    public $status = array();
    public $money = 0;
    public $company = '';
    public $name = '';
    public $bank = '';
    public $bank_card = '';
    public $id_card = '';
    public $mobile = '';
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($finance_account) {
        $this->finance_account_id = $finance_account['user_id'];
        $this->user = $finance_account['user'];
        $this->status = new obj_item($finance_account['status']);
        $this->money = $finance_account['money'];
        $this->company = $finance_account['company'];
        $this->name = $finance_account['name'];
        $this->bank = $finance_account['bank'];
        $this->bank_card = $finance_account['bank_card'];
        $this->id_card = $finance_account['id_card'];
        $this->mobile = $finance_account['mobile'];
        $this->create_time = $finance_account['create_time'];
        $this->modify_time = $finance_account['modify_time'];
    }
    
}

class obj_finance_account_sub {
    
    public $finance_account_sub_id = 0;
    public $user = array();
    public $status = array();
    public $money = 0;
    public $company = '';
    public $name = '';
    public $bank = '';
    public $bank_card = '';
    public $id_card = '';
    public $mobile = '';
    public $create_time = 0;
    public $modify_time = 0;
    
    public function __construct($finance_account) {
        $this->finance_account_sub_id = $finance_account['id'];
        $this->user = $finance_account['user'];
        $this->status = new obj_item($finance_account['status']);
        $this->money = $finance_account['money'];
        $this->company = $finance_account['company'];
        $this->name = $finance_account['name'];
        $this->bank = $finance_account['bank'];
        $this->bank_card = $finance_account['bank_card'];
        $this->id_card = $finance_account['id_card'];
        $this->mobile = $finance_account['mobile'];
        $this->create_time = $finance_account['create_time'];
        $this->modify_time = $finance_account['modify_time'];
    }
    
}

class obj_article {
    public $article_id = 0;
    public $content = '';
    
    public function __construct($article) {
        $this->article_id = $article['id'];
        $this->content = $article['content'];
    }
}

class obj_old_article {
    public $article_id = 0;
    public $user_id = 0;
    public $type_id = 0;
    public $nid = 0;
    public $name = '';
    public $title = '';
    public $status = 0;
    public $litpic = '';
    public $flag = '';
    public $publish = '';
    public $contents = '';
    public $tags = '';
    public $order = '';
    public $hits = 0;
    public $addtime = '';
    public $update_time = '';
    public $start_time = '';
    public $end_time = '';
    public $share_title = '';
    public $share_content = '';
    public $share_url = '';
    public $start_timestamp = '';
    public $end_timestamp = '';
    
    public $url = '';
    
    public function __construct($old_article) {
        $this->article_id = $old_article['id'];
        $this->user_id = $old_article['user_id'];
        $this->type_id = $old_article['type_id'];
        $this->nid = $old_article['nid'];
        $this->name = $old_article['name'];
        $this->title = $old_article['title'];
        $this->status = $old_article['status'];
        $this->litpic = $old_article['litpic'];
        $this->flag = $old_article['flag'];
        $this->publish = $old_article['publish'];
        $this->contents = $old_article['contents'];
        $this->tags = $old_article['tags'];
        $this->order = $old_article['order'];
        $this->hits = $old_article['hits'];
        $this->addtime = $old_article['addtime'];
        $this->update_time = $old_article['update_time'];
        $this->start_time = $old_article['start_time'];
        $this->end_time = $old_article['end_time'];
        $this->share_title = $old_article['share_title'];
        $this->share_content = $old_article['share_content'];
        $this->share_url = $old_article['share_url'];
        $this->start_timestamp = $old_article['start_timestamp'];
        $this->end_timestamp = $old_article['end_timestamp'];
        $this->url = $old_article['url'];
    }
}

class obj_feedback {
    public $id = 0;
    public $content = 0;
    public $fids = 0;
    public $pics = 0;
    public $contact = '';
    public $create_time = 0;

    public function __construct($feedback) {
        $this->id = $feedback['id'];
        $this->content = $feedback['content'];
        $this->pics = $feedback['pics'];
        $this->contact = $feedback['contact'];
        $this->create_time = $feedback['create_time'];
    }

}

class obj_wechat_msg {
    public $id = 0;
    public $user_id = 0;
    public $mobile = '';
    public $wx_unionid = '';
    public $wx_openid = '';
    public $template_id = '';
    public $url = '';
    public $data = '';
    public $status = array();
    public $send_time = 0;
    
    public function __construct($wechat_msg) {
        $this->id = $wechat_msg['id'];
        $this->user_id = $wechat_msg['user_id'];
        $this->mobile = $wechat_msg['mobile'];
        $this->wx_unionid = $wechat_msg['wx_unionid'];
        $this->wx_openid = $wechat_msg['wx_openid'];
        $this->template_id = $wechat_msg['template_id'];
        $this->url = $wechat_msg['url'];
        $this->data = $wechat_msg['data'];
        $this->status = new obj_item($wechat_msg['status']);
        $this->send_time = $wechat_msg['send_time'];
    }
}

class obj_userbank {
    public $id = 0;
    public $user_id = 0;
    public $status = 0;
    public $account = '';
    public $bank = '';
    public $branch = '';
    public $province = '';
    public $city = '';
    public $area = '';
    public $addtime = '';
    public $addip = '';
    public $update_time = '';
    public $update_ip = '';
    public $check_status = '';
    public $bank_name = '';

    public function __construct($userbank) {
        $this->id = $userbank['id'];
        $this->user_id = $userbank['user_id'];
        $this->status = $userbank['status'];
        $this->account = $userbank['account'];
        $this->bank = $userbank['bank'];
        $this->branch = $userbank['branch'];
        $this->province = $userbank['province'];
        $this->city = $userbank['city'];
        $this->area = $userbank['area'];
        $this->addtime = $userbank['addtime'];
        $this->addip = $userbank['addip'];
        $this->update_time = $userbank['update_time'];
        $this->update_ip = $userbank['update_ip'];
        $this->check_status = $userbank['check_status'];
        $this->bank_name = $userbank['bank_name'];
    }

}

class obj_userbank_modify_log {
    public $id = 0;
    public $mobile = '';
    public $user_id = 0;
    public $realname = '';
    public $status = 0;
    public $account = '';
    public $bank = '';
    public $branch = '';
    public $province = '';
    public $city = '';
    public $area = '';
    public $reason = '';
    public $front_pic = '';
    public $behind_pic = '';
    public $hand_pic = '';
    public $newbank_pic = '';
    public $old_account = '';
    public $old_bank = '';
    public $old_province = '';
    public $old_city = '';
    public $old_area = '';
    public $is_delete = '';
    public $create_time = '';
    public $modify_time = '';
    public $fail_reason = '';
    public $advice = '';
    

    public function __construct($modify_log) {
        $this->id = $modify_log['id'];
        $this->mobile = $modify_log['mobile'];
        $this->user_id = $modify_log['user_id'];
        $this->realname = $modify_log['realname'];
        $this->status = $modify_log['status'];
        $this->account = $modify_log['account'];
        $this->bank = $modify_log['bank'];
        $this->branch = $modify_log['branch'];
        $this->province = $modify_log['province'];
        $this->city = $modify_log['city'];
        $this->area = $modify_log['area'];
        $this->reason = $modify_log['reason'];
        $this->front_pic = $modify_log['front_pic'];
        $this->behind_pic = $modify_log['behind_pic'];
        $this->hand_pic = $modify_log['hand_pic'];
        $this->newbank_pic = $modify_log['newbank_pic'];
        $this->old_account = $modify_log['old_account'];
        $this->old_bank = $modify_log['old_bank'];
        $this->old_province = $modify_log['old_province'];
        $this->old_city = $modify_log['old_city'];
        $this->old_area = $modify_log['old_area'];
        $this->is_delete = $modify_log['is_delete'];
        $this->create_time = $modify_log['create_time'];
        $this->modify_time = $modify_log['modify_time'];
        $this->fail_reason = $modify_log['fail_reason'];
        $this->advice = $modify_log['advice'];
    }

}

class obj_wechat_mp {
    public $id = '';
    public $wx_openid_mp = '';
    public $wx_unionid = '';
    public $create_time = '';
    public $modify_time = '';
    
    public function __construct($wechat_mp) {
        $this->id = $wechat_mp['id'];
        $this->wx_openid_mp = $wechat_mp['wx_openid_mp'];
        $this->wx_unionid = $wechat_mp['wx_unionid'];
        $this->create_time = $wechat_mp['create_time'];
        $this->modify_time = $wechat_mp['modify_time'];
    }
}

class obj_bank_account {
    public $account_id = '';
    public $user_id = 0;
    public $realname = '';
    public $mobile = '';
    public $id_No = '';
    public $card_No = '';
    public $bank_No = '';
    public $has_set_password = 0;
    public $status = array();
    public $auto_bid = array();
    public $has_bind_card = array();
    public $auto_bid_max_money_single = 0;
    public $auto_bid_order_sn = '';
    public $cash_control = '';
    
    public function __construct($bank_account) {
        $this->account_id = $bank_account['ba_id'];
        $this->user_id = $bank_account['user_id'];
        $this->realname = $bank_account['ba_realname'];
        $this->mobile = $bank_account['ba_mobile'];
        $this->id_No = $bank_account['ba_id_No'];
        $this->card_No = $bank_account['ba_card_No'];
        $this->card = $bank_account['ba_card'];
        $this->has_set_password = new obj_item($bank_account['ba_has_set_password']);
        $this->status = new obj_item($bank_account['ba_status']);
        $this->auto_bid = new obj_item($bank_account['ba_auto_bid']);
        $this->has_bind_card = new obj_item($bank_account['ba_has_bind_card']);
        $this->auto_bid_max_money_single = $bank_account['ba_auto_bid_max_money_single'];
        $this->auto_bid_order_sn = $bank_account['ba_auto_bid_order_sn'];
        $this->bank_No = $bank_account['ba_card_bank_cnaps'];
        $this->cash_control = $bank_account['ba_cash_control'];
    }
}

class obj_bank_account_action {
    public $action_id = 0;
    public $order_sn = '';
    public $user_id = 0;
    public $action = '';
    public $request = '';
    public $result = '';
    public $ip = '';
    public $create_time = 0;
    public $receive_time = 0;
    
    public function __construct($action) {
        $this->action_id = $action['id'];
        $this->order_sn = $action['order_sn'];
        $this->user_id = $action['user_id'];
        $this->action = $action['action'];
        $this->request = $action['request'];
        $this->result = $action['result'];
        $this->ip = $action['ip'];
        $this->create_time = $action['create_time'];
        $this->receive_time = $action['receive_time'];
    }
}

class obj_bank_card {
    public $card_No = '';
    public $bank_name = '';
    public $bank_logo = '';
    
    public function __construct($card) {
        $this->card_No = $card['card_No'];
        $this->bank_name = $card['bank_name'];
        $this->bank_logo = $card['bank_logo'];
    }
}

class obj_account_log {
    public $id = 0;
    public $nid = '';
    public $user_id = 0;
    public $type = 0;
    public $total = 0.0;
    public $total_old = 0.0;
    public $money = 0.0;
    public $income = 0.0;
    public $income_old = 0.0;
    public $income_new = 0.0;
    public $expend = 0.0;
    public $expend_old = 0.0;
    public $expend_new = 0.0;
    public $balance = 0.0;
    public $balance_old = 0.0;
    public $balance_new = 0.0;
    public $balance_cash = 0.0;
    public $balance_cash_old = 0.0;
    public $balance_cash_new = 0.0;
    public $balance_frost = 0.0;
    public $balance_frost_old = 0.0;
    public $balance_frost_new = 0.0;
    public $frost = 0.0;
    public $frost_old = 0.0;
    public $frost_new = 0.0;
    public $await = 0.0;
    public $await_old = 0.0;
    public $await_new = 0.0;
    public $to_userid = 0;
    public $remark = '';
    public $addtime = 0;
    public $addip = '';
    public $hbag = 0;
    public $hbag_old = 0;
    public $hbag_new = 0;
    public $capital = 0;
    public $interest = 0;
    public $borrow_nid = '';
    public $tender_id = '';
    public $recover_id = '';
    public $ba_id = '';
    public $ba_accDate = '';
    public $ba_inpDate = '';
    public $ba_relDate = '';
    public $ba_inpTime = '';
    public $ba_traceNo = '';
    public $ba_tranType = '';
    public $ba_tranTypeMsg = '';
    public $ba_type = '';
    public $ba_orFlag = '';
    public $ba_txFlag = '';
    public $ba_currBal = '';
    public $ba_forAccountId = '';
    public $realname = '';
    public $mobile = '';
    public $sign = '';
    
    public function __construct($account_log) {
        $this->id = $account_log['id'];
        $this->nid = $account_log['nid'];
        $this->user_id = $account_log['user_id'];
        $this->type = new obj_item($account_log['type']);
        $this->total = $account_log['total'];
        $this->total_old = $account_log['total_old'];
        $this->money = $account_log['money'];
        $this->income = $account_log['income'];
        $this->income_old = $account_log['income_old'];
        $this->income_new = $account_log['income_new'];
        $this->expend = $account_log['expend'];
        $this->expend_old = $account_log['expend_old'];
        $this->expend_new = $account_log['expend_new'];
        $this->balance = $account_log['balance'];
        $this->balance_old = $account_log['balance_old'];
        $this->balance_new = $account_log['balance_new'];
        $this->balance_cash = $account_log['balance_cash'];
        $this->balance_cash_old = $account_log['balance_cash_old'];
        $this->balance_cash_new = $account_log['balance_cash_new'];
        $this->balance_frost = $account_log['balance_frost'];
        $this->balance_frost_old = $account_log['balance_frost_old'];
        $this->frost = $account_log['frost'];
        $this->frost_old = $account_log['frost_old'];
        $this->frost_new = $account_log['frost_new'];
        $this->await = $account_log['await'];
        $this->await_old = $account_log['await_old'];
        $this->await_new = $account_log['await_new'];
        $this->to_userid = $account_log['to_userid'];
        $this->remark = $account_log['remark'];
        $this->addtime = $account_log['addtime'];
        $this->addip = $account_log['addip'];
        $this->hbag = $account_log['hbag'];
        $this->hbag_old = $account_log['hbag_old'];
        $this->hbag_new = $account_log['hbag_new'];
        $this->capital = $account_log['capital'];
        $this->interest = $account_log['interest'];
        $this->borrow_nid = $account_log['borrow_nid'];
        $this->tender_id = $account_log['tender_id'];
        $this->recover_id = $account_log['recover_id'];
        $this->ba_id = $account_log['ba_id'];
        $this->ba_accDate = $account_log['ba_accDate'];
        $this->ba_inpDate = $account_log['ba_inpDate'];
        $this->ba_relDate = $account_log['ba_relDate'];
        $this->ba_inpTime = $account_log['ba_inpTime'];
        $this->ba_traceNo = $account_log['ba_traceNo'];
        $this->ba_tranType = $account_log['ba_tranType'];
        $this->ba_tranTypeMsg = $account_log['ba_tranTypeMsg'];
        $this->ba_type = $account_log['ba_type'];
        $this->ba_type = new obj_item($account_log['ba_type']);
        $this->ba_orFlag = $account_log['ba_orFlag'];
        $this->ba_txFlag = $account_log['ba_txFlag'];
        $this->ba_currBal = $account_log['ba_currBal'];
        $this->ba_forAccountId = $account_log['ba_forAccountId'];
        $this->realname = $account_log['realname'];
        $this->mobile = $account_log['mobile'];
        $this->sign = $account_log['sign'];
    }
}

class obj_banner {
    public $id = 0;
    public $title = '';
    public $pic = '';
    public $mobile_pic = '';
    public $url = '';
    public $mobile_url = '';
    public $share_title = '';
    public $share_content = '';
    public $share_url = '';
    public $status = '';
    public $order = 0;
    
    public function __construct($banner) {
        $this->id = $banner['id'];
        $this->title = $banner['title'];
        $this->pic = $banner['pic'];
        $this->mobile_pic = $banner['mobile_pic'];
        $this->url = $banner['url'];
        $this->mobile_url = $banner['mobile_url'];
        $this->share_title = $banner['share_title'];
        $this->share_content = $banner['share_content'];
        $this->share_url = $banner['share_url'];
        $this->starttime = $banner['starttime'];
        $this->endtime = $banner['endtime'];
        $this->status = new obj_item($banner['status']);
        $this->order = $banner['order'];
    }
}

class obj_recover {
    public $id = 0;
    public $status = 0;
    public $user_id = '';
    public $borrow_nid = '';
    public $tender_id = '';
    public $recover_status = '';
    public $recover_period = '';
    public $recover_time = '';
    public $recover_yestime = '';
    public $recover_account = '';
    public $recover_interest = '';
    public $recover_capital = '';
    public $recover_account_yes = '';
    public $recover_interest_yes = '';
    public $recover_capital_yes = '';
    public $create_time = '';
    public $borrow_name = '';
    public $coupon_amount = 0;
    public $reverify_time = 0;
    public $reverify_bank_time = 0;
    
    
    public function __construct($banner) {
        $this->id = $banner['id'];
        $this->user_id = $banner['status'];
        $this->borrow_nid = $banner['borrow_nid'];
        $this->tender_id = $banner['tender_id'];
        $this->recover_status = $banner['recover_status'];
        $this->recover_period = $banner['recover_period'];
        $this->recover_time = $banner['recover_time'];
        $this->recover_yestime = $banner['recover_yestime'];
        $this->recover_account = $banner['recover_account'];
        $this->recover_interest = $banner['recover_interest'];
        $this->recover_capital = $banner['recover_capital'];
        $this->recover_account_yes = $banner['recover_account_yes'];
        $this->recover_interest_yes = $banner['recover_interest_yes'];
        $this->recover_capital_yes = $banner['recover_capital_yes'];
        $this->create_time = $banner['addtime'];
        $this->borrow_name = $banner['borrow_name'];
        $this->coupon_amount = $banner['coupon_amount'];
        $this->reverify_time = $banner['reverify_time'];
        $this->reverify_bank_time = $banner['reverify_bank_time'];
    }
}

class obj_risk_test {
    public $id = 0;
    public $user_id = 0;
    public $score = 0;
    public $times = 0;
    
    public function __construct($risk_test) {
        $this->id = $risk_test['id'];
        $this->user_id = $risk_test['user_id'];
        $this->score = $risk_test['score'];
        $this->times = $risk_test['times'];
    }
}

class obj_message {
    public $id = 0;
    public $sender_id = 0;
    public $receiver_id = 0;
    public $message_text_id = 0;
    public $title = '';
    public $text = '';
    public $send_time = 0;
    
    public function __construct($message) {
        $this->id = $message['id'];
        $this->sender_id = $message['sender_id'];
        $this->receiver_id = $message['receiver_id'];
        $this->message_text_id = $message['message_text_id'];
        $this->title = $message['title'];
        $this->text = $message['text'];
        $this->send_time = $message['send_time'];
    }
}

class obj_message_text {
    public $id = 0;
    public $sender_id = 0;
    public $title = '';
    public $text = '';
    public $send_time = 0;
    
    public function __construct($message_text) {
        $this->id = $message_text['id'];
        $this->sender_id = $message_text['sender_id'];
        $this->title = $message_text['title'];
        $this->text = $message_text['text'];
        $this->send_time = $message_text['send_time'];
    }
}

class obj_message_user {
    public $id = 0;
    public $sender_id = 0;
    public $user_id = '';
    public $read_flag = 0;
    public $source_message_id = 0;
    public $message_text_id = 0;
    public $title = '';
    public $text = '';
    public $send_time = 0;
    
    public function __construct($message_user) {
        $this->id = $message_user['id'];
        $this->sender_id = $message_user['sender_id'];
        $this->user_id = $message_user['user_id'];
        $this->read_flag = $message_user['read_flag'];
        $this->source_message_id = $message_user['source_message_id'];
        $this->message_text_id = $message_user['message_text_id'];
        $this->title = $message_user['title'];
        $this->text = $message_user['text'];
        $this->send_time = $message_user['send_time'];
    }
}