<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of admin_token
 *
 * @author win7
 */
class admin_runner {

    public function __construct() {
        
    }
    
    public function get_token() {
        $CI = get_instance();
        $CI->load->library('cache_memcache');

        $key = 'ADMIN_RUNNER1_' . date('ymd'); //每天更新一次
        $token = $CI->cache_memcache->get($key);
        if (empty($token)) {
            $CI->load->config('myconfig');
            $config = $CI->config->item('admin_runner');

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $config['url']);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, array(
                'loginname' => $config['loginname'],
                'password_md5' => $config['password_md5'],
                'from' => 'admin'
            ));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            // php version >= 5.5 需要 传filesize

            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
            $result = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            if ($error) {
                $token = '';
            } else {
                $r = json_decode($result);
                $token = $r->result->token->token;
                $CI->cache_memcache->set($key, $token, 3600 * 24);
            }
        }
        return $token;
    }

}
