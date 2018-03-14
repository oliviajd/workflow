<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bouns
 *
 * @author win7
 */
class bouns extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_bouns');
    }

    public function get() {
        $r = $this->m_bouns->detail($this->api->in['bouns_id']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }
    
    public function find() {
        $r = $this->m_bouns->find($this->api->user()->user_id,$this->api->in['remark']);
        if ($r) {
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function add() {
        $param = $this->api->in;
        $param['admin_user_id'] = $this->api->user()->user_id;

        $bouns_id = $this->m_bouns->add($param);
        $detail = $this->m_bouns->detail($bouns_id);
        $this->api->output($detail);
    }

    public function update() {
        $bouns_id = $this->api->in['bouns_id'];
        $param = $this->api->in;

        $old = $this->m_bouns->detail($bouns_id);
        if (empty($old)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $this->m_bouns->update($bouns_id, $param);
        $detail = $this->m_bouns->detail($bouns_id);
        $this->api->output($detail);
    }

    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'hid desc';
        } else {
            $order = $this->api->in['order'];
        }
        //$condition['status'] = m_bouns::STATUS_ON;
        $r['rows'] = $this->m_bouns->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_bouns->count($condition);
        $this->api->output($r);
    }

    public function lists_admin() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_bouns->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_bouns->count($condition);
        $this->api->output($r);
    }

    public function delete() {
        $detail = $this->m_bouns->detail($this->api->in['bouns_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        $r = $this->m_bouns->delete($this->api->in['bouns_id']);
        $this->api->output($r);
    }

    public function send() {
        $detail = $this->m_bouns->detail($this->api->in['bouns_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->status->id != m_bouns::STATUS_EXPERIENCE_ON) {
            $this->api->output(false, ERR_EXPERIENCE_DISABLE_NO, ERR_EXPERIENCE_DISABLE_MSG);
        }
        //累计额度
        if (!$this->m_bouns->increase($this->api->in['bouns_id'], $this->api->in['money'])) {
            $this->api->output(false, ERR_EXPERIENCE_UPPER_MONEY_LIMIT_NO, ERR_EXPERIENCE_UPPER_MONEY_LIMIT_MSG);
        }
        $eu_id = $this->m_bouns->send_to_user($this->api->in['bouns_id'], $this->api->in['money'], $this->api->in['user_id'], $this->api->in['remark']);
        $r = $this->m_bouns->user_detail($eu_id);
        $this->api->output($r);
    }

    public function send_lists_admin() {
        $this->load->model('m_user');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($this->api->in['user']) {
            $user_ids = $this->m_user->find($this->api->in['user']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_bouns->user_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_bouns->user_count($condition);
        $this->api->output($r);
    }
    
    public function send_lists_admin_export() {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $this->load->model('m_user');
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
        if ($this->api->in['user']) {
            $user_ids = $this->m_user->find($this->api->in['user']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_bouns->user_lists($condition, $page, $size, $order);
        $obj = new PHPExcel();
        // Excel表格式,
        $letter = array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
        );
        // 表头数组
        $tableheader = array(
            'ID',
            '体验金编号',
            '用户名',
            '姓名',
            '金额',
            '利率',
            '总收益',
            '已取得收益',
            '状态',
            '领取时间',
            '过期时间',
            '备注',
        );

        // 填充表头信息
        for ($i = 0; $i < count($tableheader); $i ++) {
            $obj->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }

        $data = array();
        foreach ($r['rows'] as $k => $v) {
            $data[] = array(
                $v->bouns_user_id,
                $v->bouns_id,
                $v->user->mobile,
                $v->user->realname,
                $v->money,
                $v-rate,
                $v->profit,
                $v->profit - $v->profit_unget,
                $v->status->text,
                date('Y-m-d H:i:s',$v->receive_time),
                date('Y-m-d H:i:s',$v->expire_time),
                $v->remark,
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

    public function transfer() {
        $money = sprintf('%.2f', floatval($this->api->in['money']));
        $user_id = $this->api->user()->user_id;
        $account = $this->m_bouns->account_detail($user_id);
        //判断金额是否大于0
        if (floatval($money) < 0.01) {
            $this->api->output(false, ERR_EXPERIENCE_SHOULD_GREATER_NO, ERR_EXPERIENCE_SHOULD_GREATER_MSG);
        }
        //判断可转金额是否足够
        if (floatval(bcsub($account->money_real_time, $money, 6)) < 0) {
            $this->api->output(false, ERR_EXPERIENCE_NOT_ENOUGH_NO, ERR_EXPERIENCE_NOT_ENOUGH_MSG);
        }
        //结算待结算收益
        $this->m_bouns->user_settle($user_id, time());
        //转出体验金收益
        $this->m_bouns->transfer($user_id, $money);
        do_log(__FUNCTION__,$this->db->all_query());
        $this->api->output(true);
    }

    public function user_activate() {
        $r = $this->m_bouns->user_detail($this->api->in['bouns_user_id']);
        if ($r->user_id != $this->api->user()->user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($r->status->id != m_bouns::STATUS_USER_INIT) {
            $this->api->output(false, ERR_EXPERIENCE_WRONG_STATUS_NO, ERR_EXPERIENCE_WRONG_STATUS_MSG);
        }
        if ($r) {
            $r = $this->m_bouns->user_activate($this->api->in['bouns_user_id']);
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function user_close() {
        $r = $this->m_bouns->user_detail($this->api->in['bouns_user_id']);
        if ($r) {
            $r = $this->m_bouns->user_close($this->api->in['bouns_user_id']);
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function user_open() {
        $r = $this->m_bouns->user_detail($this->api->in['bouns_user_id']);
        if ($r) {
            $r = $this->m_bouns->user_open($this->api->in['bouns_user_id']);
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
    }

    public function user_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        if (count(explode(',', $this->api->in['status'])) > 1) {
            $condition['status'] = explode(',', $this->api->in['status']);
        }
        $r['rows'] = $this->m_bouns->user_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_bouns->user_count($condition);
        $this->api->output($r);
    }

    public function account_get() {
        $user_id = $this->api->user()->user_id;
        $r = $this->m_bouns->account_detail($user_id);
        if (!$r) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        } else {
            $this->api->output($r);
        }
    }

    public function log_lists_admin() {
        $this->load->model('m_user');
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if ($this->api->in['q']) {
            $user_ids = $this->m_user->find($this->api->in['q']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_bouns->log_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_bouns->log_count($condition);
        $sum = $this->m_bouns->log_sum($condition);
        $r['in_sum'] = $sum['in_sum'];
        $r['out_sum'] = $sum['out_sum'];
        $this->api->output($r);
    }
    
    public function log_lists_admin_export() {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $this->load->model('m_user');
        $this->load->model('m_file');
        $this->load->library('PHPExcel');
        
        $page = false;
        $size = false;
        $condition = $this->api->in;
        if ($this->api->in['q']) {
            $user_ids = $this->m_user->find($this->api->in['q']);
            if (count($user_ids) > 0) {
                $condition['user_id'] = $user_ids;
            } else {
                unset($condition['user_id']);
            }
        }
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_bouns->log_lists($condition, $page, $size, $order);
        
        $obj = new PHPExcel();
        // Excel表格式,
        $letter = array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
        );
        // 表头数组
        $tableheader = array(
            'ID',
            '体验金编号',
            '体验金名称',
            '用户名',
            '姓名',
            '金额',
            '类型',
            '时间',
        );

        // 填充表头信息
        for ($i = 0; $i < count($tableheader); $i ++) {
            $obj->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }

        $data = array();
        foreach ($r['rows'] as $k => $v) {
            $data[] = array(
                $v->bouns_log_id,
                $v->bouns_id,
                $v->title,
                $v->user->mobile,
                $v->user->realname,
                $v->money,
                $v->type == 1 ? '收入' : '支出',
                date('Y-m-d H:i:s',$v->create_time),
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

    public function log_lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        $condition['user_id'] = $this->api->user()->user_id;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_bouns->log_lists($condition, $page, $size, $order);
        $r['total'] = $this->m_bouns->log_count($condition);
        $this->api->output($r);
    }

}
