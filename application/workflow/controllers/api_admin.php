<?php

/**
 *  api_V2_admin
 *
 * @author wangyuan
 */
class api_admin extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        if (isset($_COOKIE['Is_login']) && authcode($_COOKIE['Is_login'], 'DECODE') == '123456' || authcode($_COOKIE['Is_login'], 'DECODE') == '123') {} else {
            if ($this->router->method != 'index') {
                header('location: /api_admin/index');
                exit();
            }
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        setcookie('Is_login', null, $_SERVER['REQUEST_TIME'] - 3600 * 24 * 7, '/');
        setcookie('type', null, $_SERVER['REQUEST_TIME'] - 3600 * 24 * 7, '/');

        header('location: /api_admin/index');
        exit();
    }

    public function index()
    {
        $this->load->model('m_api_database_sync', 'sync');
        $data['database_lists'] = $this->sync->database_lists();
        // unset($_COOKIE['Is_login']);
        // dump($_COOKIE);
        if (IS_POST) {
            setcookie('database', $_REQUEST['database_from'], $_SERVER['REQUEST_TIME'] + 3600 * 24 * 7, '/');
            if (isset($_POST['passwd']) && $_POST['passwd'] == '123') {
                // dump($_POST);exit;
                // 设置cookie，可以登录
                $code = authcode($_POST['passwd'], 'ENCODE');
                setcookie('Is_login', $code, $_SERVER['REQUEST_TIME'] + 3600 * 24 * 7, '/');
                setcookie('type', 'admin', $_SERVER['REQUEST_TIME'] + 3600 * 24 * 7, '/');
                $_COOKIE['Is_login'] = $code;
                $_COOKIE['type'] = 'admin';
                header('location: /api_admin/manage');
                exit();
            } else if(isset($_POST['passwd']) && $_POST['passwd'] == '123'){
                $code = authcode($_POST['passwd'], 'ENCODE');
                setcookie('Is_login', $code, $_SERVER['REQUEST_TIME'] + 3600 * 24 * 7, '/');
                $_COOKIE['Is_login'] = $code;
                setcookie('type', 'guest', $_SERVER['REQUEST_TIME'] + 3600 * 24 * 7, '/');
                $_COOKIE['type'] = 'guest';
                header('location: /api_admin/manage');
                exit();
            }else{
                header('location: /api_admin/index');
                exit();
            }
        } else {
            $this->load->view('api_admin/index', $data);
        }
    }

    /**
     * 管理页面 - 首页
     */
    public function manage()
    {
        // dump($_COOKIE);exit;
        // category list
        $this->load->model('m_api_category');
        $config['cate_type'] = 1;
        $data['api_category_list'] = $this->m_api_category->api_category_list($config);

        // method_list
        $this->load->model('m_api_method');
        $config = array();
        $config['select'] = array(
            'method_id',
            'method_name_en',
            'description',
            'cid'
        );
        $config['where']['cid'] = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : 1;

        $data['api_method_list'] = $this->m_api_method->api_method_list($config);
        // dump($data);exit;

        $this->load->view('api_admin/manage', $data);
    }

    /**
     * 接口详情显示
     */
    public function show()
    {
        $this->load->model('m_api_method');
        $config = array();
        $config['select'] = array(
            'method_id',
            'method_name_cn',
            'method_name_en',
            'description',
            'cid'
        );
        $config['where']['cid'] = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : 1;
        $data['api_method_list'] = $this->m_api_method->api_method_list($config);

        $method_id = isset($_REQUEST['apiid']) ? $_REQUEST['apiid'] : 1;
        $arr = array(
            'method_id' => $method_id
        );

        $data['api_method_detail'] = $this->m_api_method->api_method_detail($arr);

        $system_input_id = $data['api_method_detail']['system_input_id']; // 系统级输入参数ID
        $application_input_id = $data['api_method_detail']['application_input_id']; // 应用级输入参数ID
        $response_id = $data['api_method_detail']['response_id']; // 返回ID，应用级输出

        $data['system_input_list'] = $this->getFileds($system_input_id);
        $data['application_input_list'] = $this->getFileds($application_input_id);
        $data['application_output_list'] = $this->getFileds($response_id);

        $this->load->view('api_admin/show', $data);
    }

    /**
     * 对象属性列表
     */
    public function obj_show()
    {
        $this->load->model('m_api_method');
        $config = array();
        $config['select'] = array( 'method_id', 'method_name_cn', 'method_name_en', 'description', 'cid');
        $config['where']['cid'] = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : 1;
        $data['api_method_list'] = $this->m_api_method->api_method_list($config);

        $this->load->model('m_api_obj');
        // if($_REQUEST['cate_type'])
        // $config['where']['cate_type'] = intval(trim($_REQUEST['cate_type']));
        if ($_REQUEST['item_id'])
            $item_id = intval(trim($_REQUEST['item_id']));

        $data['api_obj_detail_list'] = $this->getFileds($item_id);
        // dump($data);exit;

        $this->load->model('m_api_category');
        $config['id'] = $item_id; // cate_type:2 对象,字段类型
        $data['api_cate_detail'] = $this->m_api_category->api_category_detail($config);

        $this->load->view('api_admin/obj_show', $data);
    }

    /**
     * API 在线测试工具
     */
    public function api_tools()
    {
        // 获取API接口详情
        $this->load->model('m_api_method');
        $method_id = isset($_REQUEST['apiid']) ? $_REQUEST['apiid'] : 1;
        $arr = array(
            'method_id' => $method_id
        );
        $data['api_method_detail'] = $this->m_api_method->api_method_detail($arr);

        // 获取同类接口列表
        $config['where']['cid'] = $data['api_method_detail']['cid'];
        $data['api_method_list'] = $this->m_api_method->api_method_list($config);

        // 获取API接口应用级输入属性
        $data['api_obj_detail_list'] = $this->getFileds($data['api_method_detail']['application_input_id']);
        $data['api_obj_detail_sys_list'] = $this->getFileds($data['api_method_detail']['system_input_id']);

        // 获取接口所属类目详情
        $this->load->model('m_api_category');
        $config['id'] = $data['api_method_detail']['cid'];
        $data['api_cate_detail'] = $this->m_api_category->api_category_detail($config);

        // 获取类目列表
        $data['cate_type'] = 1; // 1: cate 2:obj_cate
        $data['api_category_list'] = $this->m_api_category->api_category_list($data);

        $this->load->view('api_admin/api_tools', $data);
    }

    // 获取字段列表
    private function getFileds($item_id = 0)
    {
        $this->load->model('m_api_obj');
        $config['where']['item_id'] = $item_id;

        $config['order_by'] = array(
            'sort' => 'desc'
        );

        $config['join'] = array(
            'cate_name'
        );

        return $this->m_api_obj->api_obj_detail_list($config);
    }

    /**
     * 分类管理
     */
    public function category_list()
    {
        $this->load->model('m_api_category');
        $data['cate_type'] = intval(trim($_REQUEST['cate_type'])); // 1: cate 2:obj_cate
        $data['api_category_list'] = $this->m_api_category->api_category_list($data);

        $this->load->view('api_admin/category_list', $data);
    }

    /**
     * [添加 / 保存] 分类
     */
    public function category_update()
    {
        $this->load->model('m_api_category');
        $data['api_category_detail'] = $this->m_api_category->api_category_detail(array(
            'id' => $_REQUEST['cid']
        ));

        $this->load->view('api_admin/category_update', $data);
    }

    /**
     * [添加 / 保存] 分类
     */
    public function obj_detail()
    {
        $this->load->model('m_api_obj');
        // if($_REQUEST['cate_type'])
        // $config['where']['cate_type'] = intval(trim($_REQUEST['cate_type']));
        if ($_REQUEST['item_id'])
            $config['where']['item_id'] = intval(trim($_REQUEST['item_id']));

        $config['order_by'] = array(
            'sort' => 'desc'
        );

        $config['join'] = array(
            'cate_name'
        );

        $data['api_obj_detail_list'] = $this->m_api_obj->api_obj_detail_list($config);
         //dump($data);exit;

        $this->load->model('m_api_category');
        $data['category'] = $this->m_api_category->api_category_detail(array('id'=>$_REQUEST['item_id']));

        // ojb_detail 中 字段类型的列表
        $this->load->model('m_api_category');
        $config['cate_type'] = 2; // cate_type:2 对象,字段类型
        $data['api_obj_list'] = trans_arr($this->m_api_category->api_category_list($config));

        $this->load->view('api_admin/obj_detail', $data);
    }

    /**
     * [添加 / 保存] detail
     */
    public function obj_detail_update()
    {
        $this->load->model('m_api_category');
        $this->load->model('m_api_obj');

        // field_detail
        $condition['field_id'] = $_REQUEST['field_id'];
        $data['field_detail'] = $this->m_api_obj->api_obj_field_detail($condition);

        // cate_type = 2
        $condition['cate_type'] = 2;
        $data['category_list'] = $this->m_api_category->api_category_list($condition);

        // dump($data);exit;

        $this->load->view('api_admin/obj_detail_update', $data);
    }

    /**
     * 接口list by 接口分类 (cate_type)
     */
    public function method_list()
    {
        $this->load->model('m_api_method');

        $config['where']['cid'] = intval(trim($_REQUEST['item_id']));
        $config['order_by']['sort'] = 'desc';

        $data['api_method_list'] = $this->m_api_method->api_method_list($config);
        // dump($data);exit;

        $this->load->view('api_admin/method_list', $data);
    }

    /**
     * 数据库同步
     */
    public function database_sync()
    {
        $this->load->model('m_api_database_sync', 'sync');

        $data['database_lists'] = $this->sync->database_lists();

        //dump($data);exit;
        $this->load->view('api_admin/database_sync', $data);
    }

    /**
     * All ajax in here
     */
    public function ajax()
    {
        $data = array(
            'no' => 0,
            'msg' => 'error'
        ); // default output

        if (IS_AJAX) {
            switch ($_REQUEST['m']) {
                // 分类删除
                case 'category_delete':
                    if (isset($_REQUEST['id']) && trim($_REQUEST['id']) != '') {
                        $this->load->model('m_api_category');
                        $r = $this->m_api_category->api_category_delete(array(
                            intval(trim($_REQUEST['id']))
                        ));
                        if ($r) {
                            $data['no'] = 1;
                            $data['msg'] = 'success';
                        }
                    }

                // 分类 update
                case 'category_update':
                    $this->load->model('m_api_category');

                    $d = array();
                    $d['sort'] = intval(trim($_REQUEST['sort']));
                    $d['cate_type'] = trim($_REQUEST['cate_type']);
                    $d['cate_name'] = trim($_REQUEST['cate_name']);

                    if (isset($_REQUEST['id']) && trim($_REQUEST['id']) != '') {
                        $d['id'] = intval(trim($_REQUEST['id']));
                    }

                    $r = $this->m_api_category->api_category_update($d);
                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = 'success';
                    }

                    break;

                // 分类 is_exists
                case 'category_is_exists':
                    $this->load->model('m_api_category');

                    $d = array();
                    $d['cate_name'] = trim($_REQUEST['cate_name']);
                    $d['cate_type'] = trim($_REQUEST['cate_type']);

                    $r = $this->m_api_category->api_category_is_exists($d);
                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = 'cate_name 已经存在';
                    }

                    break;

                // field data add
                case 'field_update':
                    $this->load->model('m_api_obj');

                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_obj->api_obj_field_update($_REQUEST);
                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = '保存成功';
                        $data['field_id'] = (! isset($_REQUEST['field_id'])) ? $r : $_REQUEST['field_id'];
                    }

                    break;

                // field delete
                case 'field_delete':
                    $this->load->model('m_api_obj');
                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_obj->api_obj_field_delete($_REQUEST);

                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = '删除成功';
                    }

                    break;

                // field detail
                case 'field_detail':
                    $this->load->model('m_api_obj');
                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_obj->api_obj_field_detail($_REQUEST);
                    // dump($r);exit;

                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = 'success';
                        $data['detail'] = $r[0];
                    }

                    break;

                // method data update
                case 'method_update':
                    $this->load->model('m_api_method');

                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);
                    $r = $this->m_api_method->api_method_update($_REQUEST);
                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = '保存成功';
                        $data['insert_id'] = $r;
                        $data['request_time'] = date('Y-m-d H:i', $_SERVER['REQUEST_TIME']);
                    }

                    break;

                // field delete
                case 'method_delete':
                    $this->load->model('m_api_method');
                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_method->api_method_delete($_REQUEST);

                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = '删除成功';
                    }
                    break;
                // field delete
                case 'method_show':
                    $this->load->model('m_api_method');
                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_method->api_method_detail($_REQUEST);
                    if ($r) {
                        $data =  $r ;
                        $data['no'] = 1;
                       // echo $data;
                    }
                    break;
                // system input list
                case 'input_list':
                    $this->load->model('m_api_category');
                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_category->api_category_list($_REQUEST);

                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = 'success';
                        $data['input_list'] = $r;
                    }
                    break;

                // system,application input id update
                case 'input_update':
                    $this->load->model('m_api_method');
                    // dump($_REQUEST);exit;
                    unset($_REQUEST['m']);

                    $r = $this->m_api_method->api_method_update($_REQUEST);

                    if ($r) {
                        $data['no'] = 1;
                        $data['msg'] = 'success';
                    }
                    break;
                case 'get_api':
                    $this->load->model('m_api_method');
                    $config['where']['cid'] = $_REQUEST['cid'];
                    $method_list = $this->m_api_method->api_method_list($config);
                    $data['no'] = 1;
                    $str = '<option value="0">--请选择API--</option>';
                    foreach ($method_list as $v) {
                        $str .= '<option value="' . $v['method_id'] . '">' . $v['method_name_en'] . '</option>';
                    }
                    $data['msg'] = $str;

                    break;
                case 'get_method':
                    $this->load->model('m_api_method');
                    $arr = array(
                        'method_id' => $_REQUEST['mid']
                    );
                    $data['no'] = 1;
                    $api_method_detail = $this->m_api_method->api_method_detail($arr);
                    // 获取API接口应用级输入属性
                    $api_obj_detail_list = $this->getFileds($api_method_detail['application_input_id']);
                    $api_obj_detail_sys_list = $this->getFileds($api_method_detail['system_input_id']);

                    foreach ($api_obj_detail_list as $k => $v) {
                        $temp .= $v['field_name'] . ',';
                        $is_necessary = $v['is_necessary'] ? '<font color="red">*</font>' : '&nbsp;';
                        $str .= '<tr>	<td align="right">' . $v['field_name'] . ':</td><td align="left"><input type="text"
                    					name="' . $v['field_name'] . '" value="'.$v['default_value'].'">&nbsp;&nbsp;' . $is_necessary . '<span
                    					title="' . $v['description'] . '"
                    					style="color: #00aaff; cursor: pointer;">'.$v['description'].'</span></td>
                    			</tr>';
                    }

              foreach ($api_obj_detail_sys_list as $k => $v) {
                        $temp .= $v['field_name'] . ',';
                        $is_necessary = $v['is_necessary'] ? '<font color="red">*</font>' : '&nbsp;';
                        $str1 .= '<tr>	<td align="right">' . $v['field_name'] . ':</td><td align="left"><input type="text"
                    					name="' . $v['field_name'] . '" value="'.$v['default_value'].'">&nbsp;&nbsp;' . $is_necessary . '<span
                    					title="' . $v['description'] . '"
                    					style="color: #00aaff; cursor: pointer;">'.$v['description'].'</span></td>
                    			</tr>';
                    }

                    $data['msg'] = $str;
                    $data['msg1'] = $str1;
                    $data['method'] = $temp;
                    break;

                case 'database_sync':
                    set_time_limit(0);
                    $this->load->model('m_api_database_sync', 'sync');
                    //dump($_REQUEST);exit;
                    $r = $this->sync->database_sync($_REQUEST['database_from'], $_REQUEST['database_to']);

                    if($r)
                    {
                        // success
                        $data['no'] = 1;
                        $data['msg'] = 'success';
                    }
                    else
                    {
                        // error
                        $data['no'] = 2;
                        $data['msg'] = 'error';
                    }

                    break;
                default:
            }
        } else {
            $data = array(
                'no' => 0,
                'msg' => '不是ajax请求'
            );
        }

        echo json_encode($data);
    }
}

?>
