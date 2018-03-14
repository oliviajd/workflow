<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of stats
 *
 * @author win7
 */
class stats extends CI_Controller {

    //统计段时间的商品销量，默认全部
    public function goods_sale() {
        $this->load->model('m_order');
    }

    //统计业务城市当日的情况，默认统计最近一天有数据的某天
    public function business_city_daily() {
        $this->load->model('m_business_city_log');
        if (empty($this->api->in['date'])) {
            $this->db->select_max('ymd', 'max');
            $date = $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->row(0)->max;
        } else {
            $date = $this->api->in['date'];
        }
        $condition['date'] = $date;
        $condition['status'] = m_business_city_log::STATUS_LOG_ENABLE;
        $condition['with_out'] = array(3256);
        $order = 'city_id asc,ymd desc';
        $r['rows'] = $this->m_business_city_log->lists($condition, false, false, $order);
        $r['total'] = $this->m_business_city_log->count($condition);
        $this->api->output($r);
    }

    //统计业务城市当日的情况，默认统计最近一天有数据的某天
    public function business_province_daily() {
        $this->load->model('m_business_city_log');
        $this->load->model('m_address');
        if (empty($this->api->in['date'])) {
            $this->db->select_max('ymd', 'max');
            $date = $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->row(0)->max;
        } else {
            $date = $this->api->in['date'];
        }
        $condition['date'] = $date;
        $condition['status'] = m_business_city_log::STATUS_LOG_ENABLE;
        $condition['with_out'] = array(3256);
        $order = 'city_id asc,ymd desc';
        $rows = $this->m_business_city_log->lists($condition, false, false, $order);
        $areas = array();
        $r['rows'] = array();
        foreach ($rows as $k => $v) {
            if (!isset($areas[$v->city->id])) {
                $city = $this->m_address->get_area($v->city->id);
                if ($city['province'] == 0) {
                    $province = $city;
                } else {
                    $province = $this->m_address->get_area($city['province']);
                }
                $province_obj = new obj_item(array(
                    'id' => $province['id'],
                    'text' => $province['name'],
                ));
                $areas[$v->city->id] = $province_obj;
            }
            $area = $areas[$v->city->id];
            if (!isset($r['rows'][$area->id])) {
                $r['rows'][$area->id] = $v;
                $r['rows'][$area->id]->province = $area;
            } else {
                $r['rows'][$area->id]->data_credit_investigation += $v->data_credit_investigation;
                $r['rows'][$area->id]->data_home_visits += $v->data_home_visits;
                $r['rows'][$area->id]->data_refuse += $v->data_refuse;
                $r['rows'][$area->id]->data_paid += $v->data_paid;
                $r['rows'][$area->id]->data_paid_nums += $v->data_paid_nums;
                $r['rows'][$area->id]->data_bank_repay_nums += $v->data_bank_repay_nums;
                $r['rows'][$area->id]->data_bank_repay += $v->data_bank_repay;
                $r['rows'][$area->id]->data_mortgage_nums += $v->data_mortgage_nums;
                $r['rows'][$area->id]->data_mortgage += $v->data_mortgage;
                $r['rows'][$area->id]->data_overdue_nums += $v->data_overdue_nums;
                $r['rows'][$area->id]->data_overdue += $v->data_overdue;
            }
        }
        $r['total'] = count($r['rows']);
        $this->api->output($r);
    }

    public function business_city_monthly() {
        $this->load->model('m_business_city_log');
        if (empty($this->api->in['month'])) {
            $this->db->order_by('ym desc');
            $this->db->limit(1);
            $date = $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->row(0)->ym;
        } else {
            $date = $this->api->in['month'];
        }
        $condition = $this->api->in;
        $condition['year_month'] = $date;
        $condition['status'] = m_business_city_log::STATUS_LOG_ENABLE;
        $condition['with_out'] = array(3256);
        $rows = $this->m_business_city_log->lists($condition, false, false, false);
        $r['rows'] = array();
        foreach ($rows as $k => $v) {
            if (!isset($r['rows'][$v->city->id])) {
                $r['rows'][$v->city->id] = $v;
            } else {
                $r['rows'][$v->city->id]->data_credit_investigation += $v->data_credit_investigation;
                $r['rows'][$v->city->id]->data_home_visits += $v->data_home_visits;
                $r['rows'][$v->city->id]->data_refuse += $v->data_refuse;
                $r['rows'][$v->city->id]->data_paid += $v->data_paid;
                $r['rows'][$v->city->id]->data_paid_nums += $v->data_paid_nums;
                $r['rows'][$v->city->id]->data_bank_repay_nums += $v->data_bank_repay_nums;
                $r['rows'][$v->city->id]->data_bank_repay += $v->data_bank_repay;
                $r['rows'][$v->city->id]->data_mortgage_nums += $v->data_mortgage_nums;
                $r['rows'][$v->city->id]->data_mortgage += $v->data_mortgage;
                $r['rows'][$v->city->id]->data_overdue_nums += $v->data_overdue_nums;
                $r['rows'][$v->city->id]->data_overdue += $v->data_overdue;
            }
        }
        $r['total'] = count($r['rows']);
        $this->api->output($r);
    }

    public function business_province_monthly() {
        $this->load->model('m_business_city_log');
        if (empty($this->api->in['month'])) {
            $this->db->order_by('ym desc');
            $this->db->limit(1);
            $date = $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->row(0)->ym;
        } else {
            $date = $this->api->in['month'];
        }
        $condition = $this->api->in;
        $condition['year_month'] = $date;
        $condition['status'] = m_business_city_log::STATUS_LOG_ENABLE;
        $condition['with_out'] = array(3256);
        $rows = $this->m_business_city_log->lists($condition, false, false, false);
        $areas = array();
        $r['rows'] = array();
        foreach ($rows as $k => $v) {
            if (!isset($areas[$v->city->id])) {
                $city = $this->m_address->get_area($v->city->id);
                if ($city['province'] == 0) {
                    $province = $city;
                } else {
                    $province = $this->m_address->get_area($city['province']);
                }
                $province_obj = new obj_item(array(
                    'id' => $province['id'],
                    'text' => $province['name'],
                ));
                $areas[$v->city->id] = $province_obj;
            }
            $area = $areas[$v->city->id];
            if (!isset($r['rows'][$area->id])) {
                $r['rows'][$area->id] = $v;
                $r['rows'][$area->id]->province = $area;
            } else {
                $r['rows'][$area->id]->data_credit_investigation += $v->data_credit_investigation;
                $r['rows'][$area->id]->data_home_visits += $v->data_home_visits;
                $r['rows'][$area->id]->data_refuse += $v->data_refuse;
                $r['rows'][$area->id]->data_paid += $v->data_paid;
                $r['rows'][$area->id]->data_paid_nums += $v->data_paid_nums;
                $r['rows'][$area->id]->data_bank_repay_nums += $v->data_bank_repay_nums;
                $r['rows'][$area->id]->data_bank_repay += $v->data_bank_repay;
                $r['rows'][$area->id]->data_mortgage_nums += $v->data_mortgage_nums;
                $r['rows'][$area->id]->data_mortgage += $v->data_mortgage;
                $r['rows'][$area->id]->data_overdue_nums += $v->data_overdue_nums;
                $r['rows'][$area->id]->data_overdue += $v->data_overdue;
            }
        }
        $r['total'] = count($r['rows']);
        $this->api->output($r);
    }

    public function business_city_monthly2() {
        $this->load->model('m_business_city_log');
        if (empty($this->api->in['month'])) {
            $this->db->order_by('ym desc');
            $this->db->limit(1);
            $date = $this->db->get_where(TABLE_BUSINESS_CITY_LOG)->row(0)->ym;
        } else {
            $date = $this->api->in['month'];
        }
        $condition = $this->api->in;
        $condition['year_month'] = $date;
        $condition['status'] = m_business_city_log::STATUS_LOG_ENABLE;
        $rows = $this->m_business_city_log->lists($condition, false, false, false);
        $r['rows'] = array();
        foreach ($rows as $k => $v) {
            if (!isset($r['rows'][$v->city->id])) {
                $r['rows'][$v->city->id] = $v;
            } else {
                $r['rows'][$v->city->id]->data_credit_investigation += $v->data_credit_investigation;
                $r['rows'][$v->city->id]->data_home_visits += $v->data_home_visits;
                $r['rows'][$v->city->id]->data_refuse += $v->data_refuse;
                $r['rows'][$v->city->id]->data_paid += $v->data_paid;
                $r['rows'][$v->city->id]->data_paid_nums += $v->data_paid_nums;
                $r['rows'][$v->city->id]->data_overdue_nums += $v->data_overdue_nums;
                $r['rows'][$v->city->id]->data_overdue += $v->data_overdue;
            }
        }
        $r['total'] = count($r['rows']);
        //累计前N月的回款、抵押的金额和数量
        $n = intval($this->api->in['n']) > 0 ? intval($this->api->in['n']) : 1;
        unset($condition['year_month']);
        $condition['m_start'] = date('Y-m', strtotime("-{$n} month", strtotime($date . '-01')));
        $condition['m_end'] = $date;
        $rows2 = $this->m_business_city_log->lists($condition, false, false, false);
        foreach ($rows2 as $k => $v) {
            if (!isset($r['rows'][$v->city->id])) {
                $r['rows'][$v->city->id] = $v;
            } else {
                $r['rows'][$v->city->id]->data_bank_repay_nums += $v->data_bank_repay_nums;
                $r['rows'][$v->city->id]->data_bank_repay += $v->data_bank_repay;
                $r['rows'][$v->city->id]->data_mortgage_nums += $v->data_mortgage_nums;
                $r['rows'][$v->city->id]->data_mortgage += $v->data_mortgage;
            }
        }
        $this->api->output($r);
    }

    public function manager_daily() {
        $this->load->model('m_invest_account_stat');
        $this->load->model('m_user');

        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$condition['date']) {
//            $condition['date'] = $this->m_invest_account_stat->get_last_ymd();
        }
        $r['rows'] = $this->m_invest_account_stat->lists($condition, $page, $size, $order = 'ymd desc,owner_user_id desc');
        foreach ($r['rows'] as $k => $v) {
            $r['rows'][$k]['manager'] = $v['owner_user_id'] ? $this->m_user->detail($v['owner_user_id']) : array(
                'user_id' => 0,
                'mobile' => 0,
                'realname' => '',
            );
        }
        $r['total'] = $this->m_invest_account_stat->count($condition);
        $this->api->output($r);
    }

    public function manager_daily_logs() {
        $this->load->model('m_invest_account_stat');
        $this->load->model('m_user');

        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$condition['date']) {
//            $condition['date'] = $this->m_invest_account_stat->get_last_ymd();
        }
        $r['rows'] = $this->m_invest_account_stat->log_lists($condition, $page, $size, $order = 'ymd desc,owner_user_id desc');
        foreach ($r['rows'] as $k => $v) {
            $r['rows'][$k]['manager'] = $v['owner_user_id'] ? $this->m_user->detail($v['owner_user_id']) : array(
                'user_id' => 0,
                'mobile' => 0,
                'realname' => '',
            );
            $r['rows'][$k]['user'] = $this->m_user->detail($v['user_id']);
        }
        $r['total'] = $this->m_invest_account_stat->log_count($condition);
        $this->api->output($r);
    }

    public function manager_monthly() {
        $this->load->model('m_invest_account_stat');
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
        if ($this->api->in['manager_user_id']) {
            $user_ids = $this->m_user->find($this->api->in['manager_user_id']);
            if (count($user_ids) > 0) {
                $condition['manager_user_id'] = $user_ids;
            } else {
                unset($condition['manager_user_id']);
            }
        }
        $r['rows'] = $this->m_invest_account_stat->month_lists($condition, $page, $size, $order = 'ym desc,user_id desc');
        foreach ($r['rows'] as $k => $v) {
            $r['rows'][$k]['manager'] = $v['owner_user_id'] ? $this->m_user->detail($v['owner_user_id']) : array(
                'user_id' => 0,
                'mobile' => 0,
                'realname' => '',
            );
            $r['rows'][$k]['user'] = $this->m_user->detail($v['user_id']);
        }
        $r['total'] = $this->m_invest_account_stat->month_count($condition);
        $sum = $this->m_invest_account_stat->month_sum($condition);
        $r['sum'] = $sum['sum'];
        $r['sum_achievement'] = $sum['sum_achievement'];
        $this->api->output($r);
    }

    public function manager_monthly_export() {
        set_time_limit(0);
        $this->load->model('m_invest_account_stat');
        $this->load->model('m_user');
        $this->load->model('m_file');
        $this->load->library('PHPExcel');

        if (intval($this->api->in['size'])) {
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
        if ($this->api->in['manager_user_id']) {
            $user_ids = $this->m_user->find($this->api->in['manager_user_id']);
            if (count($user_ids) > 0) {
                $condition['manager_user_id'] = $user_ids;
            } else {
                unset($condition['manager_user_id']);
            }
        }
        $r['rows'] = $this->m_invest_account_stat->month_lists($condition, $page, $size, $order = 'ym desc,user_id desc');
        $users = array();
        foreach ($r['rows'] as $k => $v) {
            if (!isset($users[$v['owner_user_id']])) {
                $users[$v['owner_user_id']] = intval($v['owner_user_id']) ? $this->db->select('user_id,phone as mobile,realname')->get_where(TABLE_USER_INFO, array('user_id' => $v['owner_user_id']))->row(0) : array(
                    'user_id' => 0,
                    'mobile' => 0,
                    'realname' => '',
                );
            }
            if (!isset($users[$v['user_id']])) {
                $users[$v['user_id']] = $this->db->select('user_id,phone as mobile,realname')->get_where(TABLE_USER_INFO, array('user_id' => $v['user_id']))->row(0);
            }
            $r['rows'][$k]['manager'] = $users[$v['owner_user_id']];
            $r['rows'][$k]['user'] = $users[$v['user_id']];
        }
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
        );
        // 表头数组
        $tableheader = array(
            '用户名',
            '姓名',
            '经理',
            '月度实际投资总额',
            '日均的月合计',
            '月份',
        );

        // 填充表头信息
        for ($i = 0; $i < count($tableheader); $i ++) {
            $obj->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }

        $data = array();
        foreach ($r['rows'] as $k => $v) {
            $data[] = array(
                $v['user']->mobile,
                $v['user']->realname,
                "{$v['manager']->realname}({$v['manager']->user_id})",
                $v['avg'],
                $v['avg_achievement'],
                $v['ym'],
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

    public function manager_monthly_logs() {
        $this->load->model('m_invest_account_stat');
        $this->load->model('m_user');
        $this->load->model('m_borrow');

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
        if ($this->api->in['manager_user_id']) {
            $user_ids = $this->m_user->find($this->api->in['manager_user_id']);
            if (count($user_ids) > 0) {
                $condition['manager_user_id'] = $user_ids;
            } else {
                unset($condition['manager_user_id']);
            }
        }
        if ($this->api->in['borrow_max']) {
            $borrow_max = $this->m_borrow->find_by_title(strtoupper(trim($this->api->in['borrow_max'])));
            if (!empty($borrow_max)) {
                $condition['borrow_max'] = $borrow_max->borrow_id;
            } else {
                unset($condition['borrow_max']);
            }
        }
        if ($this->api->in['borrow_min']) {
            $borrow_min = $this->m_borrow->find_by_title(strtoupper(trim($this->api->in['borrow_min'])));
            if (!empty($borrow_min) > 0) {
                $condition['borrow_min'] = $borrow_min->borrow_id;
            } else {
                unset($condition['borrow_min']);
            }
        }
        $r['rows'] = $this->m_invest_account_stat->month_log_lists($condition, $page, $size, $order = 'ym desc,user_id desc');
        $users = array();
        foreach ($r['rows'] as $k => $v) {
            if (!isset($users[$v['owner_user_id']])) {
                $users[$v['owner_user_id']] = $v['owner_user_id'] ? $this->m_user->detail($v['owner_user_id']) : array(
                    'user_id' => 0,
                    'mobile' => 0,
                    'realname' => '',
                );
            }
            if (!isset($users[$v['user_id']])) {
                $users[$v['user_id']] = $this->m_user->detail($v['user_id']);
            }
            $r['rows'][$k]['manager'] = $users[$v['owner_user_id']];
            $r['rows'][$k]['user'] = $users[$v['user_id']];
        }
        $r['total'] = $this->m_invest_account_stat->month_log_count($condition);
        $sum = $this->m_invest_account_stat->month_sum($condition);
        $r['sum'] = $sum['sum'];
        $r['sum_achievement'] = $sum['sum_achievement'];
        $this->api->output($r);
    }

    public function manager_monthly_logs_export() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->model('m_invest_account_stat');
        $this->load->model('m_user');
        $this->load->model('m_borrow');
        $this->load->model('m_file');
        $this->load->library('PHPExcel');

        if (intval($this->api->in['size'])) {
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
        if ($this->api->in['manager_user_id']) {
            $user_ids = $this->m_user->find($this->api->in['manager_user_id']);
            if (count($user_ids) > 0) {
                $condition['manager_user_id'] = $user_ids;
            } else {
                unset($condition['manager_user_id']);
            }
        }
        if ($this->api->in['borrow_max']) {
            $borrow_max = $this->m_borrow->find_by_title(strtoupper(trim($this->api->in['borrow_max'])));
            if (!empty($borrow_max)) {
                $condition['borrow_max'] = $borrow_max->borrow_id;
            } else {
                unset($condition['borrow_max']);
            }
        }
        if ($this->api->in['borrow_min']) {
            $borrow_min = $this->m_borrow->find_by_title(strtoupper(trim($this->api->in['borrow_min'])));
            if (!empty($borrow_min) > 0) {
                $condition['borrow_min'] = $borrow_min->borrow_id;
            } else {
                unset($condition['borrow_min']);
            }
        }
        $r['rows'] = $this->m_invest_account_stat->month_log_lists($condition, $page, $size, $order = 'ym desc,user_id desc');
        foreach ($r['rows'] as $k => $v) {
            if (!isset($users[$v['owner_user_id']])) {
                $users[$v['owner_user_id']] = intval($v['owner_user_id']) ? $this->db->select('user_id,phone as mobile,realname')->get_where(TABLE_USER_INFO, array('user_id' => $v['owner_user_id']))->row(0) : array(
                    'user_id' => 0,
                    'mobile' => 0,
                    'realname' => '',
                );
            }
            if (!isset($users[$v['user_id']])) {
                $users[$v['user_id']] = $this->db->select('user_id,phone as mobile,realname')->get_where(TABLE_USER_INFO, array('user_id' => $v['user_id']))->row(0);
            }
            $r['rows'][$k]['manager'] = $users[$v['owner_user_id']];
            $r['rows'][$k]['user'] = $users[$v['user_id']];
        }
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
        );
        // 表头数组
        $tableheader = array(
            '用户名',
            '姓名',
            '经理',
            '投资金额',
            '借款标题',
            '天数',
            '日均',
            '投资日期',
            '月份',
            '本月合计',
        );

        // 填充表头信息
        for ($i = 0; $i < count($tableheader); $i ++) {
            $obj->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }

        $data = array();
        foreach ($r['rows'] as $k => $v) {
            $data[] = array(
                $v['user']->mobile,
                $v['user']->realname,
                "{$v['manager']->realname}({$v['manager']->user_id})",
                $v['amount'],
                $v['borrow_title'],
                $v['days'],
                $v['avg'],
                $v['invest_time'],
                $v['ym'],
                $v['avg_achievement'],
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

    public function manager_owner_monthly() {
        $this->load->model('m_invest_account_stat');
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
        if ($this->api->in['manager_user_id']) {
            $user_ids = $this->m_user->find($this->api->in['manager_user_id']);
            if (count($user_ids) > 0) {
                $condition['manager_user_id'] = $user_ids;
            } else {
                unset($condition['manager_user_id']);
            }
        }
        $r['rows'] = $this->m_invest_account_stat->month_owner_lists($condition, $page, $size, $order = 'ym desc,avg_achievement desc');
        foreach ($r['rows'] as $k => $v) {
            $r['rows'][$k]['manager'] = $v['owner_user_id'] ? $this->m_user->detail($v['owner_user_id']) : array(
                'user_id' => 0,
                'mobile' => 0,
                'realname' => '',
            );
        }
        $r['total'] = $this->m_invest_account_stat->month_count($condition);
        $sum = $this->m_invest_account_stat->month_owner_sum($condition);
        $r['sum'] = $sum['sum'];
        $r['sum_achievement'] = $sum['sum_achievement'];
        $this->api->output($r);
    }

}
