<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of user_task
 *
 * @author win7
 */
class user_task extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_user_task');
    }
    //任务列表
    public function lists() {
        $this->load->model('m_item_instance');
        $this->load->model('m_process_instance');
        $this->load->model('m_process');
        $condition = $this->api->in;
        if ($condition['role_id']) {
            $condition['role_id'] = explode(',', $condition['role_id']);
        }
        if ($condition['condition']) {
            if (!$condition['process_id']) {
                $this->api->output(false, ERR_FILED_NECESSARY_NO, '[(int)process] ' . ERR_FILED_NECESSARY_MSG);   //condition不为空的时候，process_id不为空
            }
            $condition['remote_condition'] = json_decode($condition['condition'], true);
            $keys = $this->m_process->get_extends_keys($condition['process_id']);     //获取该流程扩展表的所有column_name和data_type
            foreach ($condition['remote_condition'] as $k => $v) {
                if (!isset($keys[$k]) && $k !== 'start_time' && $k !== 'end_time') {
                    //如果所搜索的condition的key在表中不存在，则报错
                    $this->api->output(false, ERR_FILED_NOT_EXISTS_NO, '[' . $k . '] ' . ERR_FILED_NOT_EXISTS_MSG);     
                }
            }
        }
        $r['rows'] = $this->m_item_instance->lists($condition, $this->api->in['page'], $this->api->in['size'] , $this->api->in['order']);
        $r['total'] = $this->m_item_instance->count($condition);
        if ($condition['fields']) {
            $keys = explode(',', $condition['fields']);
            foreach ($r['rows'] as $k => $v) {
                $r['rows'][$k]->fields = new stdClass();
                foreach ($keys as $k2 => $v2) {
                    $r['rows'][$k]->fields->$v2 = null;
                }
                $values = $this->m_process_instance->get_value(array(
                    'process_instance_id' => $v->process_instance_id
                ));
                foreach ($values as $k3 => $v3) {
                    if (in_array($k3, $keys)) {
                        $r['rows'][$k]->fields->$k3 = $v3['value'];
                    }
                }
            }
        }
        $this->api->output($r);
    }

    //拾取任务
    public function pickup() {
        $this->load->model('m_item_instance');
        $this->load->model('m_item');
        $task_instance_id = $this->api->in['task_instance_id'];
        $user_id = $this->api->in['user_id'];
        //验证该任务实例是否存在
        $detail = $this->m_item_instance->detail($task_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断是否可拾取
        if ($detail->has_completed->id == 1) {
            $this->api->output(false, ERR_TASK_FINISHED_NO, ERR_TASK_FINISHED_MSG);   //任务已完成
        }
        if ($this->m_user_task->lock($task_instance_id, $user_id)) {           //锁上任务实例
            $this->m_user_task->pickup($task_instance_id, $user_id);           //拾取任务
            $task = $this->m_item_instance->detail($task_instance_id);
            $item = $this->m_item->detail($task->item_id);
            $r = array(
                'item_instance' => $task,
                'item' => $item
            );
            $this->api->output($r);
        } else {
            $this->api->output(false, ERR_TASK_IS_PICKED_NO, ERR_TASK_IS_PICKED_MSG);
        }
    }

    //丢弃任务
    public function giveup() {
        $this->load->model('m_item_instance');
        $user_id = $this->api->in['user_id'];
        $task_instance_id = $this->api->in['task_instance_id'];
        //验证该任务实例是否存在
        $detail = $this->m_item_instance->detail($task_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        if ($detail->has_completed->id == 1) {
            $this->api->output(false, ERR_TASK_FINISHED_NO, ERR_TASK_FINISHED_MSG);   //任务已完成
        }
        //todo 判断拥有者是否为当前用户 
        if ($detail->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($this->m_user_task->giveup($task_instance_id, $user_id)) {           //放弃任务
            $this->m_user_task->unlock($task_instance_id, $user_id);             //解锁
            $this->api->output(true);
        } else {
            $this->api->output(false, ERR_FAILED_NO, ERR_FAILED_MSG);
        }
    }

    //指派任务，暂时没有用上
    public function assign() {
        $user_id = $this->api->in['user_id'];
        $to_user_id = $this->api->in['to_user_id'];
        $task_instance_id = $this->api->in['task_instance_id'];
        //验证该任务实例是否存在
        $detail = $this->m_item_instance->detail($task_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo权限判断
        //判断拥有者是否为当前用户
        if ($detail->user_id && $detail->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        if ($detail->has_completed->id == 1) {
            $this->api->output(false, ERR_TASK_FINISHED_NO, ERR_TASK_FINISHED_MSG);         //任务已完成
        }
        $this->m_user_task->assign($task_instance_id, $to_user_id, $detail->user_id);
        $r = $this->m_item_instance->detail($task_instance_id);
        $this->api->output($r);
    }

    //追回任务，暂时没有用上
    public function fetch() {
        $user_id = $this->api->in['user_id'];
        $task_instance_id = $this->api->in['task_instance_id'];
        $detail = $this->m_item_instance->detail($task_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断拥有者是否为当前用户
        if ($detail->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        //todo 判断是否可以追回

        $this->m_user_task->fetch($task_instance_id);
        $this->api->output(true);
    }

    public function edit()
    {
        $this->load->model('m_process');
        $this->load->model('m_process_instance');
        $this->load->model('m_item_instance');
        $this->load->model('m_item');
        $process_instance_id = $this->api->in['process_instance_id'];
        $process_id = $this->api->in['process_id'];
        $keys = $this->m_process->get_extends_keys($process_id);   //获取该表的所有COLUMN_NAME和对应的DATA_TYPE
        $extends_param = array();
        $values = json_decode($this->api->in['data'], true);
        foreach ($values as $k => $v) {         //将传入数据存入v1_process_instance_value表中
            $this->m_process_instance->set_value(array(
                'process_id' => $process_id,
                'process_instance_id' => $process_instance_id,
                'item_id' => '',
                'key' => $k,
                'value' => $v,
            ));
            if (isset($keys[$k])) {
                $extends_param[$k] = $v;
            }
        }
        $this->m_process->set_extends_value($process_id, $process_instance_id, $extends_param);   //更新v1_process_extends_1表
        $this->api->output(true);
    }

    //完成任务
    public function complete() {
        $this->load->model('m_process');
        $this->load->model('m_process_instance');
        $this->load->model('m_item_instance');
        $this->load->model('m_item');
        $user_id = $this->api->in['user_id'];
        $task_instance_id = $this->api->in['task_instance_id'];
        $data = json_decode($this->api->in['data'], true);
        //验证该任务实例是否存在
        $detail = $this->m_item_instance->detail($task_instance_id);
        if (!$detail) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //todo 判断拥有者是否为当前用户
        if ($detail->user_id != $user_id) {
            $this->api->output(false, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
        //todo 判断是否已完成
        if ($detail->has_completed->id == 1) {
            $this->api->output(false, ERR_TASK_FINISHED_NO, ERR_TASK_FINISHED_MSG);     //任务已完成
        }
        $keys = $this->m_process->get_extends_keys($detail->process_id);   //获取该表的所有COLUMN_NAME和对应的DATA_TYPE
        $extends_param = array();
        $values = json_decode($this->api->in['data'], true);
        foreach ($values as $k => $v) {         //将传入数据存入v1_process_instance_value表中
            $this->m_process_instance->set_value(array(
                'process_id' => $detail->process_id,
                'process_instance_id' => $detail->process_instance_id,
                'item_id' => $detail->item_id,
                'key' => $k,
                'value' => $v,
            ));
            if (isset($keys[$k])) {
                $extends_param[$k] = $v;
            }
        }
        $this->m_process->set_extends_value($detail->process_id, $detail->process_instance_id, $extends_param);   //更新v1_process_extends_1表
        $r = $this->m_user_task->complete($task_instance_id);     //完成任务
        if ($r) {
            $data_run = $this->m_process_instance->get_value(array(
                'process_id' => $detail->process_id,
                'process_instance_id' => $detail->process_instance_id,
            ));
            foreach ($data_run as $k => $v) {
                $data_run[$k] = $v['value'];
            }
            $items = $this->m_process_instance->next($detail->process_instance_id, $detail->item_id);
            $task_ids = array();
            foreach ($items as $k => $v) {
                $task_id = $this->m_item->run($detail->process_instance_id, $v, $data_run);
                if ($task_id) {
                    $task_ids[] = $task_id;
                }
            }
        }
        do_log($this->db->all_query());
        $this->api->output(true);
    }

}
