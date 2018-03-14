<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of obj_base
 *
 * @author win7
 */
class obj_base {
    //put your code here
}

class obj_kv {

    public $id;
    public $text;

    public function __construct($item) {
        if (is_array($item)) {
            $this->id = $item['id'];
            $this->text = $item['text'];
        } else if (is_object($item)) {
            $this->id = $item->id;
            $this->text = $item->text;
        } else {
            //todo throw error
            $this->id = 0;
            $this->text = '未知';
        }
    }

}

class obj_process {
    public $process_id = 0;
    public $title = '';
    
    public function __construct($process) {
        $this->process_id = $process['id'];
        $this->title = $process['title'];
    }
}

class obj_process_instance {
    public $process_instance_id = 0;
    public $process_id = 0;
    
    public function __construct($process_instance) {
        $this->process_instance_id = $process_instance['id'];
        $this->process_id = $process_instance['process_id'];
        $this->title = $process_instance['title'];
    }
}

class obj_item {
    public $item_id = 0;
    public $process_id = 0;
    public $title = '';
    public $type = '';
    public $condition = '';
    public $role_id = 0;
    
    public function __construct($item) {
        $this->item_id = $item['id'];
        $this->process_id = $item['process_id'];
        $this->title = $item['title'];
        $this->type = $item['node_type'];
        $this->condition = $item['condition'];
        $this->role_id = $item['role_id'];
    }
}

class obj_item_instance {
    public $item_id = 0;
    public $item_instance_id = 0;
    public $process_id = 0;
    public $process_instance_id = 0;
    public $user_id = 0;
    public $title = '';
    public $has_completed = array();
    
    public function __construct($item) {
        $this->item_instance_id = $item['id'];
        $this->item_id = $item['item_id'];
        $this->process_id = $item['process_id'];
        $this->process_instance_id = $item['process_instance_id'];
        $this->user_id = $item['user_id'];
        $this->title = $item['title'];
        $this->has_completed = new obj_kv($item['has_completed']);
    }
    
}

class obj_process_link {
    public $process_id = 0;
    public $current_id = 0;
    public $next_id = 0;
    public $prev_id = 0;
    
    public function __construct($process_link) {
        $this->process_id = $process_link['process_id'];
        $this->current_id = $process_link['current_id'];
        $this->next_id = $process_link['next_id'];
        $this->prev_id = $process_link['prev_id'];
    }
}
