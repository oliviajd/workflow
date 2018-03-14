<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of on_item_run
 *
 * @author win7
 */
class on_item_run {

    private $CI = '';

    public function __construct() {
        $this->CI = get_instance();
    }

    private function _common($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_queue_task_notify');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $url = "http://test-linrun.anjietest-feature.ifcar99.com/Api/workflow/workflowitem";
        $param = array(
            'work_id' => $data['work_id']['value'],
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'current_item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
        );
        do_log(array(
            '$url' => $url,
            '$param' => $param
        ));
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '任务主动通知'
        ));
    }

    public function load($item_instance) {
        $method = "run_{$item_instance['process_id']}_{$item_instance['item_id']}";
        do_log(array(
            'function' => $method,
            'function_exists' => method_exists($this, $method)
        ));
        $this->_common($item_instance);
        if (method_exists($this, $method)) {
            $this->$method($item_instance);
        }
    }
    //家访任务
    public function run_1_7($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_queue_task_notify');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $url = "http://test-linrun.anjietest-feature.ifcar99.com/Api/workflow/workflowvisit";
        $param = array(
            'work_id' => $data['work_id']['value'],
            'task_instance_id' => $item_instance['item_instance_id'],
            'type' => 1,
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '家访任务推送'
        ));
    }
    //工行认领
    public function run_1_60($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_item_instance');
        $this->CI->load->model('m_item');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $user_id = $data['zhaohui_user_id']['value'];
        
        $url = "http://release-anjie.anjietest-feature.ifcar99.com/Api/workplatform/bankpickupandpush";
        $param = array(
            'user_id' => $user_id,
            'work_id' => $data['work_id']['value'],
            'item_instance_id' => $item_instance['item_instance_id'],
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '朝晖执行认领推送',
        ));
    }
    //家访补件
    public function run_1_13($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_queue_task_notify');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $url = "http://test-linrun.anjietest-feature.ifcar99.com/Api/workflow/workflowvisit";
        $param = array(
            'work_id' => $data['work_id']['value'],
            'task_instance_id' => $item_instance['item_instance_id'],
            'type' => 2,
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '家访补件任务推送'
        ));
    }
    //销售补件
    public function run_1_45($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_item_instance');
        $this->CI->load->model('m_item');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $user_id = $data['request_user_id']['value'];
        
        $url = "http://test-linrun.anjietest-feature.ifcar99.com/Api/workplatform/workflowpickup";
        $param = array(
            'user_id' => $user_id,
            'work_id' => $data['work_id']['value'],
            'item_instance_id' => $item_instance['item_instance_id'],
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '销售补件任务推送'
        ));
    }

    //申请打款前银行推送通知
    public function run_1_47($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_item_instance');
        $this->CI->load->model('m_item');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $user_id = $data['credit_user_id']['value'];
        $loan_bank = $data['loan_bank']['value'];
        if ($loan_bank == '04') {
            $url = "http://release-anjie.anjietest-feature.ifcar99.com/Api/workplatform/transtozhaohuibank";
        } else {
            $url = "http://release-anjie.anjietest-feature.ifcar99.com/Api/workplatform/transtobank";
        }
        
        $param = array(
            'user_id' => $user_id,
            'work_id' => $data['work_id']['value'],
            'item_instance_id' => $item_instance['item_instance_id'],
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '银行推送通知'
        ));
    }

    //回款确认前银行推送通知
    public function run_1_40($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_item_instance');
        $this->CI->load->model('m_item');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $user_id = $data['credit_user_id']['value'];
        
        $url = "http://test-linrun.anjietest-feature.ifcar99.com/Api/workplatform/suppletobank";
        $param = array(
            'user_id' => $user_id,
            'work_id' => $data['work_id']['value'],
            'item_instance_id' => $item_instance['item_instance_id'],
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '银行推送通知'
        ));
    }

    //上标申请
    public function run_2_58($item_instance) {
        $this->CI->load->model('m_process_instance');
        $this->CI->load->model('m_item_instance');
        $this->CI->load->model('m_item');
        $data = $this->CI->m_process_instance->get_value(array(
            'process_instance_id' => $item_instance['process_instance_id'],
            'process_id' => $item_instance['process_id'],
        ));
        $user_id = $data['csrrequest_user_id']['value'];
        
        $url = "http://test-linrun.anjietest-feature.ifcar99.com/Jcr/jcd/workflowpickup";
        $param = array(
            'user_id' => $user_id,
            'csr_id' => $data['csr_id']['value'],
            'item_instance_id' => $item_instance['item_instance_id'],
        );
        $this->CI->m_queue_task_notify->add(array(
            'process_id' => $item_instance['process_id'],
            'process_instance_id' => $item_instance['process_instance_id'],
            'item_id' => $item_instance['item_id'],
            'item_instance_id' => $item_instance['item_instance_id'],
            'url' => $url,
            'param' => $param,
            'remark' => '上标申请任务推送'
        ));
    }

    public function run_1_2() {
        do_log(__METHOD__);
    }

}
