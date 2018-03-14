<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of borrow
 *
 * @author win7
 */
class borrow extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_borrow');
    }

    public function add() {
        if ($this->api->in['money_total'] % 100 != 0) {
            $this->api->output(false, ERR_TENDER_MONEY_FORMAT_100_NO, ERR_TENDER_MONEY_FORMAT_100_MSG);
        }
        if ($this->api->in['money_total'] > 2000000) {
            $this->api->output(false, ERR_BORROW_UPPER_MONEY_LIMIT_NO, '[2000000]' . ERR_BORROW_UPPER_MONEY_LIMIT_NO);
        }
        if ($this->api->in['money_total'] < 100) {
            $this->api->output(false, ERR_BORROW_LOWER_MONEY_LIMIT_NO, '[100]' . ERR_BORROW_LOWER_MONEY_LIMIT_MSG);
        }
        $borrow_id = $this->m_borrow->add($this->api->in);
        $r = $this->m_borrow->detail($borrow_id);
        $this->api->output($r);
    }

    public function update() {
        $borrow_id = $this->api->in['borrow_id'];
        $detail = $this->m_borrow->detail($borrow_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id != m_borrow::STATUS_VERIFY_INIT) {
            //上标后、满标前、满标后可以编辑用户证件、项目描述、用户基本信息、还款来源、项目图片
            $this->m_borrow->update($borrow_id, array(
                'cards' => $this->api->in['cards'],
                'desc' => $this->api->in['desc'],
                'user_desc' => $this->api->in['user_desc'],
                'pay_from' => $this->api->in['pay_from'],
                'pic' => $this->api->in['pic'],
            ));
            //$this->api->output(false, ERR_BORROW_IS_NOT_EDITABLE_NO, ERR_BORROW_IS_NOT_EDITABLE_MSG);
        } else {
            $this->m_borrow->update($borrow_id, $this->api->in);
        }
        $r = $this->m_borrow->detail($borrow_id);
        $this->api->output($r);
    }

    public function get() {
        $r = $this->m_borrow->detail($this->api->in['borrow_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    public function get_admin() {
        $r = $this->m_borrow->detail_admin($this->api->in['borrow_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function delete() {
        $r = $this->m_borrow->detail($this->api->in['borrow_id']);
        if ($r) {
            $r2 = $this->m_borrow->delete($this->api->in['borrow_id']);
            $this->api->output($r2);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = TABLE_BORROW.'.id desc';
        } else {
            $order = $this->api->in['order'];
        }
        if($condition['list_count'] == 1){
            $r['list_count']['list_count_1'] = $this->m_borrow->get_list_type_count(1);
            $r['list_count']['list_count_2'] = $this->m_borrow->get_list_type_count(2);
            $r['list_count']['list_count_3'] = $this->m_borrow->get_list_type_count(3);
            $r['list_count']['list_count_4'] = $this->m_borrow->get_list_type_count(4);
        }
        $r['rows'] = $this->m_borrow->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_borrow->count($condition);
        $this->api->output($r);
    }
    
    public function lists_admin() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = TABLE_BORROW.'.id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_borrow->lists_admin($condition, $page, $size, $order);
        $r['total'] = $this->m_borrow->count_admin($condition);
        $this->api->output($r);
    }

    //上标
    public function on() {
        $borrow_id = $this->api->in['borrow_id'];
        $detail = $this->m_borrow->detail($borrow_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id != m_borrow::STATUS_VERIFY_INIT) {
            $this->api->output(false, ERR_BORROW_IS_NOT_EDITABLE_NO, ERR_BORROW_IS_NOT_EDITABLE_MSG);
        }
        //存管发标
        //生成存管用的标的编号
        $ba_id = $this->m_borrow->ba_create_id($detail);
        JiXin\api_log::set(array(
            'request_jc' => $this->api->in,
            'request_time' => $_SERVER['REQUEST_TIME'],
            'start_time' => microtime(1),
            'api_name_cn' => '上标',
            'api_name_en' => 'debtRegister',
            'user_id' => $this->api->user()->user_id,
        ));
        $ba_account_id = '6212461960000000317';
        $jx = new JiXin\api();
        $r = $jx->debtRegister(array(
            'accountId' => $ba_account_id, //暂使用固定的发标人账号
            'productId' => ARY2::_10_to_38($ba_id), //标号
            'productDesc' => $detail->title, //implode(';', $detail->desc), //描述
            'raiseDate' => date('Ymd'), //开始募集时间
            'raiseEndDate' => date('Ymd', time() + $detail->expire), //结束募集时间
            'duration' => "{$detail->days}", //天数
            'txAmount' => "{$detail->money_total}", //金额
            'rate' => "{$detail->rate}", //利率
        ));
        JiXin\api_log::set(array(
            'response' => array(
                'result' => $r['result'],
                'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                'error_msg' => $r['msg'],
            ),
        ));
        if ($r['retcode'] == '00000000') {
            $this->m_borrow->on($borrow_id);
            $detail = $this->m_borrow->detail($borrow_id);
            $this->m_borrow->ba_on($detail->borrow_id, $ba_id);
            $this->m_borrow->ba_result($ba_id, $r);
            JiXin\api_log::write();

            $this->db->queries = array();
            $this->db->query_times = array();
            //查询存管标的信息
            JiXin\api_log::set(array(
                'request_jc' => $this->api->in,
                'request_time' => $_SERVER['REQUEST_TIME'],
                'start_time' => microtime(1),
                'api_name_cn' => '标的信息查询',
                'api_name_en' => 'debtDetailsQuery',
                'user_id' => $this->api->user()->user_id,
            ));
            $r = $jx->debtDetailsQuery(array(
                'accountId' => $ba_account_id, //证件号码
                'productId' => ARY2::_10_to_38($ba_id), //查询标的号，选填，为空查询所有名下所有债权
                'state' => 0, //查询记录状态,0-所有债权,1-有效债权（投标成功，且本息尚未返还完成）
                'startDate' => date('Ymd'), //起始日期
                'endDate' => date('Ymd'), //结束日期
                'pageNum' => 1, //查询页数
                'pageSize' => 20, //每页笔数
            ));
            JiXin\api_log::set(array(
                'response' => array(
                    'result' => $r['result'],
                    'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                    'error_msg' => $r['msg'],
                ),
            ));
            JiXin\api_log::write();
            $this->api->output($detail);
        } else {
            //错误处理
            $this->m_borrow->ba_result($ba_id, $r);
            JiXin\api_log::write();
            //todo 错误码
            $this->api->output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
        }
    }

    //下标
    public function off() {
        $borrow_id = $this->api->in['borrow_id'];
        $detail = $this->m_borrow->detail($borrow_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($this->m_borrow->tender_count(array('borrow_id' => $this->api->in['borrow_id'])) > 0) {
            $this->api->output(false, ERR_BORROW_HAS_TENDERED_NO, ERR_BORROW_HAS_TENDERED_MSG);
        }
        if ($detail->status->id != m_borrow::STATUS_VERIFY_ONLINE) {
            $this->api->output(false, ERR_BORROW_IS_NOT_EDITABLE_NO, ERR_BORROW_IS_NOT_EDITABLE_MSG);
        }
        //存管撤标
        JiXin\api_log::set(array(
            'request_jc' => $this->api->in,
            'request_time' => $_SERVER['REQUEST_TIME'],
            'start_time' => microtime(1),
            'api_name_cn' => '撤标',
            'api_name_en' => 'debtRegisterCancel',
            'user_id' => $this->api->user()->user_id,
        ));
        $ba_id = $detail->ba_id;
        $ba_account_id = '6212461960000000317';
        $jx = new JiXin\api();
        $r = $jx->debtRegisterCancel(array(
            'accountId' => $ba_account_id, //暂使用固定的发标人账号
            'productId' => ARY2::_10_to_38($ba_id), //标号
            'raiseDate' => date('Ymd'), //开始募集时间
        ));
        JiXin\api_log::set(array(
            'response' => array(
                'result' => $r['result'],
                'error_no' => $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'],
                'error_msg' => $r['msg'],
            ),
        ));
        if ($r['retcode'] == '00000000') {
            $this->m_borrow->off($borrow_id);
            $detail = $this->m_borrow->detail($borrow_id);
            $this->m_borrow->ba_off($detail->borrow_id, $ba_id);
            JiXin\api_log::write();
            $this->api->output($detail);
        } else {
            //错误处理
            JiXin\api_log::write();
            //todo 错误码
            $this->api->output($r['result'], $r['retcode'] === '00000000' ? ERR_SUCCESS_NO : $r['retcode'], $r['msg']);
        }
    }

    public function dorec() {
        $borrow_id = $this->api->in['borrow_id'];
        if (!$this->m_borrow->detail($borrow_id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_borrow->dorec($borrow_id, $this->api->in);
        $r = $this->m_borrow->detail($borrow_id);
        $this->api->output($r);
    }

    public function unrec() {
        $borrow_id = $this->api->in['borrow_id'];
        if (!$this->m_borrow->detail($borrow_id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_borrow->unrec($borrow_id, $this->api->in);
        $r = $this->m_borrow->detail($borrow_id);
        $this->api->output($r);
    }

    public function dotop() {
        $borrow_id = $this->api->in['borrow_id'];
        if (!$this->m_borrow->detail($borrow_id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_borrow->dotop($borrow_id, $this->api->in);
        $r = $this->m_borrow->detail($borrow_id);
        $this->api->output($r);
    }

    public function untop() {
        $borrow_id = $this->api->in['borrow_id'];
        if (!$this->m_borrow->detail($borrow_id)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_borrow->untop($borrow_id, $this->api->in);
        $r = $this->m_borrow->detail($borrow_id);
        $this->api->output($r);
    }

    public function find() {
        $titles = json_decode($this->api->in['titles'], true);
        $r = array(
            'not_found' => array(),
            'rows' => array(),
            'total' => 0
        );
        foreach ($titles as $k => $v) {
            $b = $this->m_borrow->find_by_title($v);
            if ($u !== false) {
                $r['rows'][] = $b;
            } else {
                $r['not_found'][] = new obj_user(array('borrow_id' => $v));
            }
        }
        $r['total'] = count($r['rows']);
        $this->api->output($r);
    }

    public function bill_verify() {
        $this->load->model('m_finance_bill');
        $this->load->model('m_borrow');
        $bill_id = $this->api->in['finance_bill_id'];
        $detail = $this->m_finance_bill->detail($bill_id);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->has_online->id == 1) {
            $this->api->output(false, ERR_BILL_IS_ONLINE_NO, ERR_BILL_IS_ONLINE_MSG);
        }
        $r = $this->m_finance_bill->online($bill_id, $this->api->in['status'], $this->api->in['cards'], $this->api->in['remark']);
        if (!$r) {
            $this->api->output(false, ERR_BILL_VERIFY_FAILED_NO, ERR_BILL_VERIFY_FAILED_MSG);
        }
        //发布到标的管理
        if (in_array(intval($this->api->in['status']), array(3, 5))) {
            if (!$this->m_finance_bill->is_set_borrow($bill_id)) {
                $count = $this->m_borrow->count(array(
                    'start_time' => strtotime(date('Y-m-d 00:00:00')),
                ));
                $has_married = in_array('6', explode(',', $this->api->in['cards'])) ? true : false;
                $borrow_id = $this->m_borrow->add(array(
                    'title' => 'XCH' . date('Ymd') . sprintf("%03d", $count + 1) . 'A',
                    'money_total' => floor($detail->money / 100),
                    'pic' => implode(',', $detail->pic),
                    'category' => 7,
                    'days' => 30,
                    'rate' => 12.00,
                    'repay_way' => 2,
                    'expire' => 5 * 3600 * 24,
                    'assessment' => 1,
                    'guarantee' => 1,
                    'is_for_single' => 2,
                    'is_for_new_comer' => 2,
                    'cards' => $this->api->in['cards'],
                    'desc' => '本项目资金主要用于购车人在申请分期购车后、银行审批放款前，为其垫付汽车按揭尾款，以实现购车客户提前提车和汽车经销商资金快速回笼的需求。银行审批放款后，将回款资金以受托返还的方式至聚车金融。还款来源得到保证，风险极低，第三方合作机构本息保障。',
                    'user_desc' => '姓 名：' . $detail->name[0] . $detail->name[1] . $detail->name[2] . '**　　　|　　出生：' . $detail->id_card[6] . $detail->id_card[7] . $detail->id_card[8] . $detail->id_card[9] . '年　　|      机动车汽车品牌为' . $detail->car,
                    'pay_from' => $has_married ?
                            '客户夫妻共同担保，工作稳定，收入还贷比高。银行已受理客户贷款资料，还款保障为银行发放（批贷率99.99%），且有项目管理公司回购债权。汽车金融公司提供本息全额担保。风险系数低。' : '客户工作稳定，收入还贷比高。银行已受理客户贷款资料，还款保障为银行发放（批贷率99.99%），且有项目管理公司回购债权。汽车金融公司提供本息全额担保。风险系数低。',
                ));
                $count1 = $this->m_borrow->count(array(
                    'start_time' => strtotime(date('Y-m-d 00:00:00')),
                    'end_id' => $borrow_id,
                ));
                if ($count + 1 != $count1) {
                    $borrow_title = 'XCH' . date('Ymd') . sprintf("%03d", $count1) . 'A';
                    $this->m_borrow->update($bill_id, array('title' => $borrow_title));
                } else {
                    $borrow_title = 'XCH' . date('Ymd') . sprintf("%03d", $count + 1) . 'A';
                }
                $this->m_finance_bill->set_borrow($bill_id, array('borrow_id' => $borrow_id, 'borrow_title' => rtrim($borrow_title, 'A')));
            } else {
                $this->m_borrow->update($detail->borrow_id, array(
                    'money_total' => floor($detail->money / 100),
                    'pic' => implode(',', $detail->pic),
                    'user_desc' => '姓 名：' . $detail->name . '**　　　|　　出生：' . $detail->id_card[6] . $detail->id_card[7] . $detail->id_card[8] . $detail->id_card[9] . '年　　|      机动车汽车品牌为' . $detail->car,
                ));
            }
        }
        //添加操作流水
        $this->m_finance_bill->action_add(array(
            'user_id' => $this->api->user()->user_id,
            'user_type' => 3, //客服
            'finance_bill_id' => $detail->finance_bill_id,
            'title' => '上标复核',
            'msg' => $this->api->in['status'] == 4 ?
                    '<div class="timeline-body text-red">融资复核核失败：' . strip_tags($this->api->in['remark']) . '</div>' :
                    '<div class="timeline-body text-green">融资单复核通过：' . strip_tags($this->api->in['remark']) . '</div>'
        ));
        $this->api->output(true);
    }

    public function get_item_for_new() {
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        if ($this->api->in['limit']) {
            $limit = intval($this->api->in['limit']);
        }
        $r['rows'] = $this->m_borrow->get_item_for_new($condition, $order, $limit);
        $this->api->output($r);
    }

}
