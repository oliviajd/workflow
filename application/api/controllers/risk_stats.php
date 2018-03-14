<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of risk_stats
 *
 * @author win7
 */
class risk_stats extends CI_Controller {

    public function monthly() {
        $this->load->model('m_risk_stats');
        $this->load->model('m_user');

        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;

        $rows = $this->m_risk_stats->monthly_lists($condition, $page, $size, 'ym desc,count desc');
        foreach ($rows as $k => $v) {
            $rows[$k]['user'] = $this->m_user->detail($v['user_id']);
        }
        $r['rows'] = $rows;
        $r['total'] = $this->m_risk_stats->monthly_count($condition);
        $this->api->output($r);
    }

}
