<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of task
 *
 * @author win7
 */
class task {
    
    private $id = 0;
    private $percent = 0;
    private $status = array();
    private $pid = 0;
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_percent() {
        return $this->percent;
    }
    
    public function get_status() {
        return $this->status;
    }
    
    public function start() {
        
    }
    
    public function stop() {
        
    }
    
    public function create() {
        
    }
    
    public function remove() {
        
    }
    
    public function on_finish() {
        
    }
    
    public function on_start() {
        
    }
    
    public function on_stop() {
        
    }
    
    public function on_percent_change() {
        $user = array(
            'uid1'=>array(
                'form1'=>array(
                    'token1',
                    'token2',
                ),
            )
        );
        $user = array(
            'uid1'=>array(
                array('token'=>'token1','from'=>'from1'),
                array('token'=>'token2','from'=>'from2'),
                array('token'=>'token3','from'=>'from3'),
            )
        );
    }
    
}
