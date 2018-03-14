<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of wechat_msg
 *
 * @author win7
 */
class m_wechat_msg extends CI_Model implements ObjInterface {

    const STATUS_SEND_SUCCESS = 1;
    const STATUS_SEND_FAILED = 2;
    const STATUS_SEND_ING = 3;
    const STATUS_SEND_INIT = 5;

    public function add($data) {
        $param = array();
        $param['user_id'] = intval($data['user_id']);
        $param['mobile'] = trim($data['mobile']);
        $param['wx_unionid'] = trim($data['wx_unionid']);
        $param['wx_openid'] = trim($data['wx_openid']);
        $param['template_id'] = trim($data['template_id']);
        $param['url'] = trim($data['url']);
        $param['data'] = trim($data['data']);
        $param['status'] = self::STATUS_SEND_INIT;

        $param['create_time'] = time();
        $this->db->insert(TABLE_QUEUE_WECHAT_MSG, $param);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_QUEUE_WECHAT_MSG)->result_array();
        foreach ($rows as $k => $v) {
            $v['status'] = $this->get_wechat_msg_status($v['status']);
            $rows[$k] = new obj_wechat_msg($v);
        }
        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['status']) {
            is_array($condition['status']) ? $this->db->where_in('status', $condition['status']) : $this->db->where('status', $condition['status']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_QUEUE_WECHAT_MSG, array('id' => $id))->result_array();
        if (empty($detail)) {
            return false;
        } else {
            return $detail;
        }
    }

    public function send_start($id) {
        $this->db->update(TABLE_QUEUE_WECHAT_MSG, array('status' => self::STATUS_SEND_ING, 'send_time' => time()), array('id' => $id));
        return $this->db->affected_rows();
    }

    public function send($openid,$template_id,$link_url,$data,$access_token) {
        $this->load->config('myconfig');
        $config = $this->config->item('wechat');
        $url = $config['api_url'].'cgi-bin/message/template/send?access_token='.$access_token;
        $param =  array(
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => $link_url,            
            'data' => $data
        );
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // php version >= 5.5 需要 传filesize

        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        if ($error) {
            do_log('SEND_WECHAT_MSG_ERROR:', $error);
            return false;
        } else {
            return json_decode(json_encode($result), true);
        }
    }

    public function send_success($id, $result) {
        $this->db->update(TABLE_QUEUE_WECHAT_MSG, array('status' => self::STATUS_SEND_SUCCESS, 'result_query' => json_encode($result)), array('id' => $id, 'status' => self::STATUS_SEND_ING));
        return $this->db->affected_rows();
    }

    public function send_failed($id, $result) {
        $this->db->update(TABLE_QUEUE_WECHAT_MSG, array('status' => self::STATUS_SEND_FAILED, 'result_query' => json_encode($result)), array('id' => $id, 'status' => self::STATUS_SEND_ING));
        return $this->db->affected_rows();
    }

    public function get_wechat_msg_status($key = false) {
        $data = array(
            self::STATUS_SEND_SUCCESS => '成功',
            self::STATUS_SEND_FAILED => '失败',
            self::STATUS_SEND_ING => '发送中',
            self::STATUS_SEND_INIT => '初始化',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'WECHAT_MSG_ERROR', 'text' => '微信消息状态错误');
    }
    
    public function mp_add_msg($user_id,$template_id,$url,$data) {       //添加消息到队列
        $this->load->model('m_wechat');
        $this->load->library('wechat_lib');
        $this->load->model('m_wechat_mp');
        $condition['user_id'] = $user_id;
        $user = $this->m_wechat->detail($condition);
        if($user['wx_unionid']){
            $mp_condition['wx_unionid'] = $user['wx_unionid'];
            $mp = $this->m_wechat_mp->detail($mp_condition);
            if($mp){   //该用户关注过公众号
                $this->load->model('m_wechat_msg');
                $param = array(
                    'user_id' => $user_id,
                    'mobile' => $user['phone'],
                    'wx_unionid' => $user['wx_unionid'],
                    'wx_openid' => $mp->wx_openid_mp,   //发送消息使用公众号里的openid
                    'template_id' => $template_id,
                    'url' => $url,
                    'data' => $data
                );
                $r = $this->add($param);
            }
            return $r;
        }
        else{
            do_log('错误用户微信信息:'.json_encode($user));
            return false;
        }
        
        
        
    }
    
    public function mp_add_all_msg($user_id,$template_id,$url,$data) {       //添加消息到队列
        $config = $this->config->item('wechat');
        $access_token_obj = $this->wechat_lib->getAccessToken($config['mp_appid'],$config['mp_secret']);
        $access_token = $access_token_obj -> access_token;
        $userlist_obj = $this->wechat_lib->getUserList($access_token);
        if($userlist_obj -> data ->openid){
            foreach ($userlist_obj -> data ->openid as $k => $v) {
                $userinfo_obj = $this->wechat_lib->getUserInfo($v,$access_token);
                $param['wx_unionid'] = $userinfo_obj->unionid;
                $userinfo_array = $this->m_wechat->detail($param);
                
                //添加消息到队列
               if($userinfo_obj->unionid && $userinfo_array['wx_unionid']){     //用户已绑定微信
                   $this->load->model('m_wechat_msg');
                   $param = array(
                       'user_id' => $userinfo_array['user_id'],
                       'mobile' => $userinfo_array['phone'],
                       'wx_unionid' => $userinfo_array['wx_unionid'],
                       'wx_openid' => $v,   //发送消息使用公众号里的openid
                       'template_id' => $template_id,
                       'url' => $url,
                       'data' => $data
                   );
                   $r = $this->add($param);
                   
                   //var_dump($r);
               }
                //var_dump($userinfo_array);
            }
        }
        
        exit;
    }
    
    public function add_repay_msg($user_id,$title,$recover_capital,$recover_interest,$time) { //添加还款消息到队列
        $template_id = 'FByvcMBznrOyCZKwheCJs2WEJsBshTc4z3gnWGFa1nQ';
        $url = 'https://www.ifcar99.com/?user&q=code/users/money&status=9';
        $data = array(
                'first' => array(
                    'value' => '您投资的项目有还款，请注意查收',
                    'color' => '#173177'
                ),
                'keyword1' => array(
                    'value' => $title,
                    'color' => '#173177'
                ),
                'keyword2' => array(
                    'value' => $recover_capital,
                    'color' => '#173177'
                ),
                'keyword3' => array(
                    'value' => $recover_interest,
                    'color' => '#173177'
                ),
                'keyword4' => array(
                    'value' => date("Y-m-d H:i:s",$time),
                    'color' => '#173177'
                ),
                'keyword5' => array(
                    'value' => '到期还款还息',
                    'color' => '#173177'
                ),
                'remark' => array(
                    'value' => '感谢您的使用，欢迎再次投资！',
                    'color' => '#173177'
                )
            );
        $this->load->model('m_wechat_msg');
        $r = $this->mp_add_msg($user_id,$template_id,$url,json_encode($data));
    }
    
    public function add_tender_msg($user_id,$title,$money) { //添加还款消息到队列
        $template_id = 'c3AA9H1N3SCGcdlarlybmYkXBKPpoeEmoTR62eKqRbk';
        $url = 'https://www.ifcar99.com/?user';
        $data = array(
                'first' => array(
                    'value' => '您已成功投资项目:'.$title,
                    'color' => '#173177'
                ),
                'keyword1' => array(
                    'value' => $title,
                    'color' => '#173177'
                ),
                'keyword2' => array(
                    'value' => $money,
                    'color' => '#173177'
                ),
                'remark' => array(
                    'value' => '感谢您的使用，欢迎再次投资！',
                    'color' => '#173177'
                )
            );
        $this->load->model('m_wechat_msg');
        $r = $this->mp_add_msg($user_id,$template_id,$url,json_encode($data));
    }
}
