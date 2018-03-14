<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of script
 *
 * @author win7
 */
class script extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    private function _curl_get($url, $param)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url . '?' . http_build_query($param));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // php version >= 5.5 需要 传filesize
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        if ($error) {
            //do_log('CURL_GET_ERROR:', $error);
            return $error;
        } else {
            return $result;
        }
    }

    //通知脚本
    public function queue_task_notify()
    {
        $this->load->model('m_queue_task_notify');
        $rows = $this->m_queue_task_notify->lists(array('status' => 2), 1, 50);
        foreach ($rows as $k => $v) {
            $this->db->update(TABLE_QUEUE_TASK_NOTIFY, array('status' => 3, 'start_time' => time()), array('id' => $v->id, 'status' => 2));
            if ($this->db->affected_rows() == 0) {
                continue;
            }
            $r = $this->_curl_get($v->url, $v->param);
            $status = json_decode($r) ? 1 : 4;
            $this->db->update(TABLE_QUEUE_TASK_NOTIFY, array('status' => $status, 'end_time' => time(), 'response' => $r), array('id' => $v->id, 'status' => 3));
        }
        echo '<pre>';
        var_dump($this->db->all_query());
        if ($_REQUEST['run_for'] == 'web') {
            $this->api->output_string('<script>setTimeout(function(){window.location.reload()},5000)</script>');
        } else {
            $this->api->output(true);
        }
    }


    //每天站岗资金统计
    public function day_balance_record()
    {
        $this->load->model('m_stats_daily');
        $this->m_stats_daily->day_balance_record();
    }

    /**
     * 列出所有高息用户下的人
     */
    public function set_all_high_interest()
    {
        $this->load->model('m_stats_daily');
        $this->db->truncate(TABLE_MANAGER_WITHOUT_CUSTOMER); //清空表
        $high = $this->m_stats_daily->get_all_manager_without();

        foreach ($high as $v) {
            $children = $this->m_stats_daily->find_children($v['user_id']);
            if ($children) {
                if (count($children) > 1) {
                    foreach ($children as $c) {
                        $data[] = array('user_id' => $v['user_id'], 'customer_id' => $v);
                    }
                } else {
                    $data[] = array('user_id' => $v['user_id'], 'customer_id' => current($children));
                }

            }
        }
        if ($data) {
            $this->db->insert_batch(TABLE_MANAGER_WITHOUT_CUSTOMER, $data);
        }

    }


    /**
     * 设置所属高息用户
     */
    public function update_user_high_belong()
    {
        $this->load->model('m_stats_daily');

        $high = $this->m_stats_daily->get_all_manager_without();

        foreach ($high as $v) {
            $this->m_stats_daily->update_children($v['user_id'], $v['user_id']);
        }

    }
}
