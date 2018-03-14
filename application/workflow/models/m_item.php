<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_item
 *
 * @author win7
 */
class m_item extends CI_Model implements ObjInterface {

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            return new obj_item($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_PROCESS_ITEM, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }
    //执行某个任务、事件、网关
    public function run($process_instance_id, $item, $data) {
        switch ($item->type) {
            case 'event':              //事件：包括空开始事件、空结束事件、终止结束事件
                $this->_run_event($process_instance_id, $item, $data);
                break;
            case 'gateway':             //网关：包括唯一网关、并行网关、包含网关
                $this->_run_gateway($process_instance_id, $item, $data);
                break;
            case 'task':                //任务，包括人工任务、服务任务、脚本任务、手工任务、接收任务
                return $this->_run_task($process_instance_id, $item, $data);
                break;
            case 'end' :
                $this->_run_end($process_instance_id, $item, $data);
            default:
                break;
        }
        return 0;
    }
    //执行到事件的时候，一般不会有next了
    private function _run_event($process_instance_id, $item, $data) {
        //开始或停止事件，记录状态
        $this->load->model('m_process_instance');
//        $this->db->insert(TABLE_ITEM_HISTORY, array(
//            ''
//        ));
        $next = $this->m_process_instance->next($process_instance_id, $item->item_id);
        foreach ($next as $k => $v) {
            $this->run($process_instance_id, $this->detail($v->item_id), $data);
        }
        do_Log('触发了一个事件');
    }
    //执行网关
    private function _run_gateway($process_instance_id, $item, $data) {
        $this->load->model('m_process_instance');
        //根据condition来判断网关能否往下走
        if (!empty($item->condition)) {
            eval('$r = ' . $item->condition . ' ;'); //执行规则
            do_Log('路过了一个网关', array('内置条件'=>$item->condition,'内置条件判断结果' => $r));
            if (!$r) {
                return false;
            }
        }
        //流向判断
        $next = $this->m_process_instance->next($process_instance_id, $item->item_id);
        $matchs = array();
        foreach ($next as $k => $v) {
            $matchs[] = array(
                '$v' => $v,
                '$data' => $data,
                '$match' => $this->_match_condition($v, $data)
            );
            if ($this->_match_condition($v, $data)) {           //只要符合条件都run一次
                $this->run($process_instance_id, $v, $data);
            }
        }
        do_Log('路过了一个网关', array('判断结果' => $matchs));
    }
    //执行任务
    private function _run_task($process_instance_id, $item, $data) {
        //产生任务，供用户拾取或根据data直接分配
        $this->load->model('m_item_instance');
        $param = array();
        $param['process_id'] = $item->process_id;
        $param['process_instance_id'] = $process_instance_id;
        $param['item_id'] = $item->item_id;
        $param['role_id'] = trim($item->role_id);
        $param['user_id'] = intval($data['user_id']);
        $item_instance_id = $this->m_item_instance->add($param);
        do_Log('创建了一个任务实例');
        $param['item_instance_id'] = $item_instance_id;
        $this->_on_run_task($param);
        return $item_instance_id;
    }
    //结束流程
    private function _run_end($process_instance_id, $item, $data)
    {
        $this->load->model('m_item_instance');
        $param['process_id'] = $item->process_id;
        $param['process_instance_id'] = $process_instance_id;
        $end = $this->m_item_instance->end($param);
        return $end;
    }

    private function _on_run_task($data) {
        $this->load->library('on_item_run');
        $this->on_item_run->load($data);
    }

    //用于判断网关流向
    private function _match_condition($item, $data) {
        extract($data); //数组转变量
        eval('$r = ' . $item->condition . ' ;'); //执行规则
//        var_dump($data,$r);
        do_Log('比较规则' . $item->condition . '结果为：' . $r);
        return $r;
    }

}
