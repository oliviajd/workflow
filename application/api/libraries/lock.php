<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lock
 *
 * @author win7
 */
class lock {

    private $CI;

    public function __construct() {
        $this->CI = get_instance();
        $this->CI->load->library('cache_memcache');
    }

    public function get($key, $expire) {
        return $this->CI->cache_memcache->add($key, $value = true, $expire);
    }

    public function release($key) {
        return $this->CI->cache_memcache->delete($key);
    }

}
