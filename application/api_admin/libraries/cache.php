<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of memcache
 *
 * @author Administrator
 */
if (!class_exists('Memcache')) {
    class Memcache {
        public function connect(){}
        public function set(){
            return false;
        }
        public function get(){
            return false;
        }
    }
}
class Cache extends Memcache {

    public function __construct() {
        parent::connect(MEMCACHE_HOST, MEMCACHE_PORT);
    }

    public function set($key, $value, $flag, $time) {
        // static $i;
        //echo 'cache:',++$i;
        return parent::set($key, $value, $flag, $time);
    }

   public function get($key) {
       return parent::get($key);
   }
}
