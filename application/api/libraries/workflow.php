<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of workflow
 *
 * @author win7
 */
define('TABLE_PROCESS', 'v1_process');
define('TABLE_PROCESS_ITEM', 'v1_process_item');
define('TABLE_PROCESS_LINK', 'v1_process_link');
define('TABLE_PROCESS_INSTANCE', 'v1_process_instance');
define('TABLE_PROCESS_ITEM_INSTANCE', 'v1_process_item_instance');

namespace WorkFlow;

class workflow {

    public function aa() {
        
    }

}

//事件
class event {
    
}

//网关
class gateways {
    
}

//任务
class task {

    public $id = 0;

    public function get_id() {
        
    }

}

//顺序流
class link {
    
}

//流程
class ProcessService {

    public function start_process($id) {
        $process = new process_instance($id);
        $process->start();
        return $process;
    }

}

//流程实例
class ProcessInstance {

    private $id = 0;
    private $process_id = 0;
    private $name = '';
    private $CI;

    public function __construct() {
        $this->CI = get_instance();
    }

    public function get_id() {
        return $this->id;
    }

    public function get_item() {
        
    }

    //建立流程实例并开始执行流程
    public function start($process_id) {
        $process = $this->CI->db->get_where(TABLE_PROCESS_DETAIL, array('id' => $process_id))->row_array(0);
        if (empty($process)) {
            throw new Exception('流程不存在!');
        }
        $this->CI->db->insert(TABLE_PROCESS_INSTANCE, array(
            'process_id' => $process_id,
            'status' => 1,
            'create_time' => time(),
        ));
        $this->id = $this->CI->db->insert_id();
        $this->run();
    }

    //执行流程
    private function run() {
        //查看当前进度
        $items = $this->CI->db->get_where(TABLE_PROCESS_ITEM_INSTANCE, array(
                    'process_instance_id' => $this->id,
                ))->result_array();
        if (empty($items)) {
            $link = $this->CI->db->get_where(TABLE_PROCESS_LINK, array(
                        'process_id' => $this->process_id,
                        'prev_id' => 0,
                    ))->row(0);
            $item = $this->CI->db->get_where(TABLE_PROCESS_ITEM, array(
                        'id' => $link->current_id,
                    ))->row(0);
            $this->_create_item_instance($item);
        } else {
            foreach ($items as $k => $v) {
                
            }
        }
    }

    //查询当前流程
    private function _get_current_flow() {
        //从数据库中读取流程图数据
        $this->CD->db->get_where(TABLE_PROCESS_LINK, array(
            'process_id' => $this->process_id
        ))->result();
        //数据存成图结构
        
    }

    private function _create_item_instance($data) {
        $this->CI->db->insert(TABLE_PROCESS_ITEM_INSTANCE, array(
            'process_instance_id' => $this->id,
            'process_id' => $this->process_id,
            'item_id' => $data->item_id,
            'status' => 1,
            'create_time' => time()
        ));
        return $this->CI->db->insert_id();
    }

    //从任务池中拾取任务，拾取后该任务只能由自己进行
    public function pick_task() {
        
    }

    //将任务放回任务池，其他用户可以拾取任务
    public function release_task() {
        
    }

    //获得已分配的任务
    public function get_task() {
        
    }

    //完成任务
    public function finish_task() {
        
    }

    public function finish_gateway() {
        
    }

    public function finish_event() {
        
    }

}

class ProcessInstanceListener {

    public function beforeProcessStarted() {
        
    }

    public function afterProcessStarted() {
        
    }

    public function beforeProcessCompleted() {
        
    }

    public function afterProcessCompleted() {
        
    }

    public function beforeNodeTriggered() {
        
    }

    public function afterNodeTriggered() {
        
    }

    public function beforeNodeLeft() {
        
    }

    public function afterNodeLeft() {
        
    }

    public function beforeVariableChanged() {
        
    }

    public function afterVariableChanged() {
        
    }

}

class ProcessActs {

    public function test();
}

class ProcessAct {

    private $_data = array();

    public function __construct($data) {
        $this->_data = $data;
    }

    public function before() {
        
    }

    public function run() {
        
    }

    public function after() {
        
    }

    public function start() {
        $this->before();
        $this->run();
        $this->after();
    }

}

class GraphNode {
    public $id = 0;
    public $type = '';
    public $next = array();//下级节点的地址
    public $prev = 0;//上级级节点的地址
}

class Graph {
    
    public function __construct($links) {
        foreach($links as $k=>$v) {
            new GraphNode($v);
        }
    }
    
}

namespace Actions;

class db {

    static function set_value() {
        
    }

}

//使用
class manager {

    const PID = 1;

    //经销商提交按揭申请
    //内容包括多张图片：身份证、征信查询授权、签署合同时的照片；用户基本信息
    public function r1() {
        $data = $_REQUEST;

        $pid = self::PID;
        $process = new \WorkFlow\ProcessService();
        $process_instance = $process->start_process($pid);
        $task = $process_instance->get_task();
        $task_id = $task->get_id();

        $ms = new ManagerSubmit($data);
        $ms->start();

        $process_instance->finish_task();
    }

    public function r2() {
        $data = $_REQUEST;

        $pid = self::PID;
        $process = new \WorkFlow\ProcessService();
        $process_instance = $process->start_process($pid);
        $task = $process_instance->get_task();
        $task_id = $task->get_id();

        $ms = new ManagerSubmit($data);
        $ms->start();

        $process_instance->finish_task();
    }

}

class ManagerSubmit extends \WorkFlow\ProcessAct {

    public function before() {
        echo 1;
    }

    public function run() {
        echo 2;
        $bill_id = db::set_value(array(
                    'user' => '',
                    'images' => '',
                    'process_instance_id' => $pi_id
        ));
    }

    public function after() {
        echo 3;
    }

}

class BankPointSubmit extends \WorkFlow\ProcessAct {

    public function before() {
        echo 1;
    }

    public function run() {
        echo 2;
        $bill_id = db::set_value(array(
                    'user' => '',
                    'images' => '',
                    'process_instance_id' => $pi_id
        ));
    }

    public function after() {
        echo 3;
    }

}

