<?php

class api {

    public $api_prefix = 'w'; // 接口前缀
    public $log = true; // 是否写日志
    public $in = array(); //api输入参数
    private $token = '';
    private $user = array();
    private $_times = array();

    function __construct() {
        //请求接收时间
        $this->_times['request'] = $_SERVER['REQUEST_TIME'];
        //接口开始执行时间
        $this->_times['start'] = microtime(1);

        $this->CI = & get_instance();
        $this->CI->load->library('token');

        //处理路由信息，并载入接口配置信息
        if ($this->_load($this->_get_api_method_name())) {
            //TODO 接口请求次数限制
        } else {
            $this->output(false, ERR_API_NOT_EXISTS_NO, ERR_API_NOT_EXISTS_MSG);
        }

        //处理接收到的参数，并检查是否需要token信息
        $this->input();

        //初始化用户信息
        if ($this->_need_token()) {
            //验证令牌有效性
            $this->check_token($this->in['token']);
            //获取用户详细信息
            $this->user = $this->CI->token->user($this->in['token']);
            //保存token信息
            $this->token = $this->CI->token->detail($this->in['token']);
            if ($this->_need_permission()) {
                $this->check_permission($this->user->user_id, $this->info['method_id']);
            }
        }
        $this->_times['start_run'] = microtime(1); //脚本开始执行时间
    }

    /**
     * 载入接口信息
     * false: 接口不存在 true: 接口存在
     */
    private function _load($api_name) {
        $r = $this->CI->db->select('system_input_id, application_input_id, response_id, check_permission, method_id')
                ->from(TABLE_API2_METHOD)
                ->where('method_name_en', $api_name)
                ->get()
                ->row_array(0);
        if (!empty($r)) {
            $this->info = $r;
            return true;
        } else {
            return false;
        };
    }

    /**
     * 获得接口名称
     */
    private function _get_api_method_name() {
        if ($this->CI->router->uri->segments[0] == $this->api_prefix) {
            unset($this->CI->router->uri->segments[0]);
        }
        return $this->api_prefix . '.' . implode('.', $this->CI->router->uri->segments);
    }

    /**
     * 对api输入参数 进行规划
     */
    public function input() {
        if (0) {
            error_reporting(E_ALL ^ E_NOTICE);
        }
        $input = array(); //最终输入参数
        $arr_input = array(); // 接口定义的输入参数的规则
        $user_input = is_array($this->CI->input->post(NULL, TRUE)) ? $this->CI->input->post(NULL, TRUE) : $this->CI->input->get(NULL, TRUE); // 用户提交的参数
        // 系统输入
        if ($this->info['system_input_id']) {
            $this->CI->db->select(TABLE_API2_CATEGORY . '.cate_type,' . TABLE_API2_CATEGORY . '.cate_name,' . TABLE_API2_FIELD . '.*,');
            $this->CI->db->join(TABLE_API2_CATEGORY, TABLE_API2_FIELD . '.obj_id = ' . TABLE_API2_CATEGORY . '.id', 'INNER');
            $this->in_sys = $system_input = $this->CI->db->get_where(TABLE_API2_FIELD, array(TABLE_API2_FIELD . '.item_id' => $this->info['system_input_id']))->result_array();
            $arr_input = array_merge($arr_input, $system_input);
        }
        // 应用输入
        if ($this->info['application_input_id']) {
            $this->CI->db->select(TABLE_API2_CATEGORY . '.cate_type,' . TABLE_API2_CATEGORY . '.cate_name,' . TABLE_API2_FIELD . '.*,');
            $this->CI->db->join(TABLE_API2_CATEGORY, TABLE_API2_FIELD . '.obj_id = ' . TABLE_API2_CATEGORY . '.id', 'INNER');
            $this->in_app = $app_input = $this->CI->db->get_where(TABLE_API2_FIELD, array(TABLE_API2_FIELD . '.item_id' => $this->info['application_input_id']))->result_array();
            $arr_input = array_merge($arr_input, $app_input);
        }
        // unset 为空的参数
        foreach ($user_input as $k => $v) {
            if ($v == '') {
                unset($user_input[$k]);
            }
        }
        // 循环接口输入条件，依条件判断输入参数，数据是否合法，是否必须，是否默认值
        foreach ($arr_input as $k => $v) {
            $field_type = trim($v['cate_name']); // 字段类型
            $field_name = trim($v['field_name']); // 字段名称
            if ((isset($user_input[$field_name]) && $field_name != '') || isset($_FILES[$field_name])) {
                if ($field_type == 'Int') {
                    $input[$field_name] = (int) (trim($user_input[$field_name]));
                } elseif ($field_type == 'FormInputFile') {
                    if ($_FILES[$field_name]) {
                        $input[$field_name] = $_FILES[$field_name]; // FILES
                    }
                } else {
                    if ($field_type == 'String') {
                        $input[$field_name] = $user_input[$field_name]; // string
                    }
                }
            } else {
                if ($v['is_necessary']) { // 1:yes
                    $this->output(false, ERR_FILED_NECESSARY_NO, '[(' . $field_type . ')' . $field_name . '] ' . ERR_FILED_NECESSARY_MSG);
                } elseif (isset($v['default_value']) && $v['default_value'] != '') {
                    $input[$field_name] = $v['default_value'];
                }
            }
        }
        $this->in = $input;
        return $input;
    }

    /**
     * error_no : 错误类型 (0: 正常 非0：消息提示)
     * error_msg : 消息提示
     * result : 返回结果
     */
    public function output($result, $error_no = false, $error_msg = false, $callback = '') {
        $output = array();
        if ($error_no === false) {
            $error_no = ERR_SUCCESS_NO;
            $error_msg = ERR_SUCCESS_MSG;
        }
        $output['error_no'] = intval($error_no);
        $output['error_msg'] = $error_msg;
        $output['result'] = $result;

        $this->_times['end'] = microtime(1);

        header('REQUEST-TIME:' . $this->_times['request']);
        header('START-TIME:' . $this->_times['start']);
        header('END-TIME:' . $this->_times['end']);
        header('COST-TIME:' . ($this->_times['end'] - $this->_times['start']));
        header('RUN-TIME:' . ($this->_times['end'] - $this->_times['start_run']));

        // 有错误写入日志
        // if ($output['error_no'] != 200)
        $this->_do_log(var_export($output, true), $output['error_no']);

        if (isset($_REQUEST['t']) && $_REQUEST['t'] == 'a') {
            echo '<pre>';
            if (isset($_REQUEST['sql']) && $_REQUEST['sql'] == true && DUMP_SQL) {
                var_dump($this->db->queries);
                exit();
            }
            var_dump($output);
            echo '</pre>';
            exit();
        } else {
            exit(json_encode($output));
        }
    }

    /**
     * 输出字符串
     */
    public function output_string($result) {
        $this->_times['end'] = microtime(1);
        header('REQUEST-TIME:' . $this->_times['request']);
        header('START-TIME:' . $this->_times['start']);
        header('END-TIME:' . $this->_times['end']);
        header('COST-TIME:' . ($this->_times['end'] - $this->_times['start']));
        header('RUN-TIME:' . ($this->_times['end'] - $this->_times['start_run']));

        // 有错误写入日志
        // if ($output['error_no'] != 200)
        $this->_do_log(var_export($result, true), ERR_SUCCESS_NO);
        echo $result;
        exit;
    }

    /**
     * 检查是否需要令牌
     */
    private function _need_token() {
        $need_token = false;
        foreach ($this->in_sys as $v) {
            if (isset($v['field_name']) && $v['field_name'] == 'token' && $v['is_necessary'] == 1) {
                $need_token = true;
                break;
            }
        }
        return $need_token;
    }

    /**
     * 检查是否需要令牌
     */
    private function _need_permission() {
        return intval($this->info['check_permission']) === 1;
    }

    /**
     * 检查令牌是否有效
     */
    public function check_token($token) {
        $r = $this->CI->token->check($token);
        if ($r === ERR_TOKEN_NOT_EXISTS_NO) {
            $this->output(FALSE, ERR_TOKEN_NOT_EXISTS_NO, ERR_TOKEN_NOT_EXISTS_MSG);
        } elseif ($r === ERR_TOKEN_EXPIRE_NO) {
            $this->output(FALSE, ERR_TOKEN_EXPIRE_NO, ERR_TOKEN_EXPIRE_MSG);
        } elseif ($r === ERR_TOKEN_DISABLED_NO) {
            //todo 查该用户的下一个token
            $info = $this->CI->token->next($token);
            $device = json_decode($info->device) ? json_decode($info->device)->model . '[' . json_decode($info->device)->os . ']' : $info->device;
            $msg = empty($info->device) ? 
                    '您的聚车账号已于' . date('m月d日H:i:s', $info->create_time) . '在其他地方登录。登录IP是' . $info->ip . '，请注意账号安全。':
                '您的聚车账号已于' . date('m月d日H:i:s', $info->create_time) . '在其他地方登录。登录设备是' . $device . '，请注意账号安全。';
            $this->output(FALSE, ERR_TOKEN_DISABLED_NO, $msg);
        } else {
            
        }
    }

    /**
     * 检查用户是否拥有权限
     */
    public function check_permission($user_id, $method_id) {
        $this->CI->load->library('privilege');
        $r = $this->CI->privilege->check($user_id, $method_id);
        if (!$r) {
            $this->output(FALSE, ERR_PERMISSION_DENIED_NO, ERR_PERMISSION_DENIED_MSG);
        }
    }

    /**
     * 用数据库API日志
     */
    private function _do_log($str, $error_no) {
        if ($this->log == TRUE) {
            $data = array();
            if (isset($this->user)) {
                $data['uid'] = $this->user->user_id;
                $data['username'] = $this->user->loginname;
            }
            $data['action'] = $this->_get_api_method_name();
            $data['url'] = $_SERVER['REQUEST_URI'];
            $data['error_no'] = $error_no;
            $data['msg'] = serialize($str);
            $data['script_time'] = $this->_times['end'] - $this->_times['start']; // api 脚本执行时间
            $data['create_time'] = date('Y-m-d H:i:s', $this->_times['request']);
            $this->CI->db->insert(TABLE_API2_LOG, $data);
            $data['post'] = $_POST;
            $data['get'] = $_GET;
            $str = array();
            $str[] = 'date:' . date('Y-m-d H:i:s');
            $str[] = print_r($data, true);
            $str[] = "\n\n";
            return file_put_contents(APPPATH . 'logs/api_' . date('Y-m-d') . '.log', implode("\n", $str), FILE_APPEND);
        }
    }

    public function user() {
        return $this->user;
    }

    public function token() {
        return $this->token;
    }

}

?>
