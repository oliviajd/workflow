<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of cache_memcache
 *
 * @author win7
 */
class cache_memcache {

    static $connect;

    public function __construct() {
        $CI = get_instance();
        $CI->load->config('myconfig');
        $config = $CI->config->item('memcache');
        if (!self::$connect) {
            self::$connect = new Memcache;
            self::$connect->pconnect($config['host'], $config['port']);
        }
    }
    
    //不存在则新增，存在返回false
    public function add($key, $value, $expire = 86400) {
        return self::$connect->add(trim($key), $value, 0, $expire);
    }

    //不存在则新增，存在则覆盖
    public function set($key, $value, $expire = 86400) {
        return self::$connect->set(trim($key), $value, false, $expire);
    }

    public function get($key) {
        return self::$connect->get(trim($key), false);
    }
    
    public function delete($key) {
        return self::$connect->delete(trim($key));
    }

    public function increment($key, $value = 1) {//Memcached支持的最大整数型Value值为18446744073709551615
        return self::$connect->increment(trim($key), intval($value));
    }

    public function decrement($key, $value = 1) {//Memcached支持的最小整数型Value值为0
        return self::$connect->decrement(trim($key), intval($value));
    }

}
