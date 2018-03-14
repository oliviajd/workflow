<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of process
 *
 * @author win7
 */
class process extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_process');
        $this->load->model('m_process_instance');
    }

    //创建实例
    public function create() {
        $process_id = $this->api->in['process_id'];
        $user_id = $this->api->in['user_id'];
        $detail = $this->m_process->detail($process_id);     
        //判断流程是否存在
        if (!$detail) {                         
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //添加任务实例
        $process_instance_id = $this->m_process_instance->add(array(
            'process_id' => $process_id,
            'user_id' => $user_id,
            'title' => $detail->title
        ));
        //获取任务详情
        $process_instance = $this->m_process_instance->detail($process_instance_id);
        $this->api->output($process_instance);
    }

    //开始任务实例
    public function start() {
        $this->load->model('m_user_task');
        $this->load->model('m_item');
        $this->load->model('m_item_instance');
        $process_instance_id = $this->api->in['process_instance_id'];
        $user_id = $this->api->in['user_id'];
        //判断任务实例是否存在
        $detail = $this->m_process_instance->detail($process_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断是否已开始
        //开始执行
        $task_ids = $this->m_process_instance->start($process_instance_id);
        $keys = $this->m_process->get_extends_keys($detail->process_id);
        $values = json_decode($this->api->in['data'], true);
        foreach ($values as $k => $v) {
            $this->m_process_instance->set_value(array(
                'process_id' => $detail->process_id,
                'process_instance_id' => $process_instance_id,
                'item_id' => 0,
                'key' => $k,
                'value' => $v,
            ));
            if (isset($keys[$k])) {
                $extends_param[$k] = $v;
            }
        }
        $this->m_process->set_extends_value($detail->process_id, $detail->process_instance_id, $extends_param);
        $rows = array();
        foreach ($task_ids as $k => $v) {
            if ($this->m_user_task->lock($v, $user_id)) {
                $this->m_user_task->pickup($v, $user_id);
            }
            $task = $this->m_item_instance->detail($v);
            $item = $this->m_item->detail($task->item_id);
            $rows[] = array(
                'item_instance' => $task,
                'item' => $item
            );
        }
        $this->api->output(array('rows' => $rows));
    }
    //暂时没有完成流程实例的功能
    public function complete() {
        $process_instance_id = $this->api->in['process_instance_id'];
        $detail = $this->m_process_instance->detail($process_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断是否已开始
        //todo 判断是否未完成

        $this->m_process_instance->complete($process_instance_id);
        $this->api->output(true);
    }
    //
    public function stop() {
        $this->load->model('m_user_task');
        $this->load->model('m_item');
        $this->load->model('m_item_instance');
        $process_instance_id = $this->api->in['process_instance_id'];
        //判断任务实例是否存在
        $detail = $this->m_process_instance->detail($process_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断是否已开始
        //开始执行
        $delete_process = $this->m_process_instance->delete($process_instance_id);
        $data['process_instance_id'] = $process_instance_id;
        $end = $this->m_item_instance->end($data);
        $this->api->output(array('result' => $end));
    }

    /*
      public function stop() {

      }
     * 
     */
}
