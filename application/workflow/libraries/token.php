<?php

class token {

    private $_tokens;

    public function __construct() {
        $this->CI = & get_instance();
        $this->db = $this->CI->db;
        $this->_tokens = array();
    }

    public function detail($token) {
        $token_str = trim($token);
        if (!$token_str) {
            return false;
        }
        return new obj_token($this->_get($token_str));
    }

    public function next($token) {
        $info = $this->_get($token);
        $this->db->limit(1);
        $this->db->order_by('id asc');
        $this->db->where('id > ' . $info['id'], false, false);
        $r = $this->db->where('user_id', $info['user_id'])
                ->where('from', $info['from'])
                ->get(TABLE_TOKEN)
                ->row_array(0);
        if (!empty($r)) {
            return new obj_token($r);
        } else {
            return false;
        }
    }

    public function check($token) {
        $token_str = trim($token);
        if (!$token_str) {
            return false;
        }
        $info = $this->_get($token_str);
        //token存在且未过期才有效
        if (!$info) {
            return ERR_TOKEN_NOT_EXISTS_NO;
        } else if ($info['over_time'] < time()) {
            return ERR_TOKEN_EXPIRE_NO;
        } else if ($info['status'] != 1) {
            return ERR_TOKEN_DISABLED_NO;
        } else {
            return true;
        }
    }

    public function user($token) {
        $token_str = trim($token);
        if (!$token_str) {
            return false;
        }
        $token_info = $this->_get($token_str);
        if ($token_info && $token_info['over_time'] > time()) {
            return json_decode($token_info['cache']);
        } else {
            //todo 退出
            return false;
        }
    }

    // 通过token获取用户信息
    private function _get($token) {
        if (isset($this->_tokens[$token])) {
            //读缓存数据
        } else {
            $r = $this->db->where('token', $token)
//                    ->where('status', 1)
                    ->get(TABLE_TOKEN)
                    ->row_array(0);
            if (!empty($r)) {
                $this->_tokens[$token] = $r;
            } else {
                return false;
            }
        }
        return $this->_tokens[$token];
    }

    public function create($data) {
        $param['token'] = md5($data['user_id'] . microtime(1) . $data['device_id'] . $data['from'] . rand(0, 999));
        $param['refresh_token'] = md5($data['user_id'] . microtime(1) . $data['device_id'] . $data['from'] . rand(1000, 1999) . 'refresh');
        $param['over_time'] = time() + 3600 * 24 * 60;
        $param['refresh_time'] = time() + 3600 * 24 * 180;
        $param['user_id'] = intval($data['user_id']);
        $param['device_id'] = intval($data['device_id']);
        $param['device'] = trim($data['device']);
        $param['ip'] = get_ip();
        $param['from'] = strtolower($data['from']);
        $param['cache'] = json_encode($data['cache']);
        $param['create_time'] = time();
        //检查是否有同一user_id和from的token，有的话设置为无效
        $this->db->update(TABLE_TOKEN, array(
            'status' => 2,
            'modify_time' => time(), //失效时间
                ), array(
            'user_id' => $param['user_id'],
            'from' => $param['from'],
            'status' => 1
        ));
        //插入新token
        $this->db->insert(TABLE_TOKEN, $param);
        return new obj_token($param);
    }

}
