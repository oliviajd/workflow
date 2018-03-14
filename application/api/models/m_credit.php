<?php

/**
 * 用户积分模型
 *
 *
 */
class m_credit extends CI_Model {

    const TYPE_CREDIT_SIGN = 1;

    public function __construct() {
        parent::__construct();
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $this->load->model('m_order');
            $detail['parts'] = unserialize($detail['credits']);
            $detail['mall_cost'] = $this->m_order->get_user_cost($id);
            return new obj_credit($detail);
        }
    }

    public function log_detail($id) {
        $detail = $this->_log_detail($id);
        $this->load->model('m_user');
        $this->load->model('m_credit_class');
        $this->load->model('m_credit_type');
        $detail['user'] = $this->m_user->detail($detail['user_id']);
        $detail['type'] = $this->m_credit_type->detail_by_nid($detail['nid']);
        $detail['class'] = $this->m_credit_type->detail($detail['type']->credit_class_id);
        $detail['num'] = $detail['value'];
        return empty($detail) ? false : new obj_credit_log($detail);
    }

    public function log_lists($condition, $page, $size, $order) {
        $page = intval($page) > 0 ? intval($page) : 1;
        $size = intval($size) ? intval($size) : 20;
        $this->db->limit(intval($size), intval(($page - 1) * $size));
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_log_condition($condition);
        $rows = $this->db->get_where(TABLE_CREDIT_LOG)->result_array();
        do_log($this->db->last_query());
        foreach ($rows as $k => $v) {
            $rows[$k] = $this->log_detail($v['id']);
        }
        return $rows;
    }

    public function log_count($condition) {
        $this->db->select('count(1) as count');
        $this->_log_condition($condition);
        return $this->db->get_where(TABLE_CREDIT_LOG)->row(0)->count;
    }

    private function _log_condition($condition) {
        if ($condition['user_id']) {
            $this->db->where('user_id', $condition['user_id']);
        }
        if ($condition['filter_0']) {
            $this->db->where('value <> 0', false, false);
        }
        if ($condition['type']) {
            $this->db->where('type', $condition['type']);
        }
        if ($condition['class']) {
            $this->db->where('class', $condition['class']);
        }
        if ($condition['pay_flag']) {
            if($condition['pay_flag'] == 1){
                $this->db->where('value > ', 0);
            }
            elseif($condition['pay_flag'] == 2){
                $this->db->where('value < ', 0);
            }
        }
        if (isset($condition['search_date'])&& strlen($condition['search_date']) == 6) {
            $start_year = intval(substr($condition['search_date'],0,4));
            $start_month = intval(substr($condition['search_date'],4,2));
            $end_month = ($start_month == 12) ? 1 : $start_month + 1;
            $end_year = ($start_month == 12) ? $start_year + 1 : $start_year;
            $starttime = mktime(0,0,0,$start_month,1,$start_year);
            $endtime = mktime(0,0,0,$end_month,1,$end_year);
            $this->db->where('addtime >=', $starttime);
            $this->db->where('addtime <', $endtime);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_CREDIT, array('user_id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    private function _log_detail($id) {
        $detail = $this->db->get_where(TABLE_CREDIT_LOG, array('id' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function increase($user_id, $num, $action) {
        $this->load->model('m_credit_type');
        $credit_type = $this->m_credit_type->detail_by_nid($action['type']);
        $this->_create_info($user_id);
        //开始事物
        $this->db->trans_start();
        $this->db->insert(TABLE_CREDIT_LOG, array(
            'code' => $credit_type->code,
            'user_id' => $user_id,
            'value' => abs($num),
            'credit' => abs($num),
            'type' => $credit_type->nid,
            'nid' => $credit_type->nid,
            'article_id' => $action['item_id'],
            'addtime' => time(),
            'remark' => $action['remark'],
        ));
        $this->db->select('sum(' . TABLE_CREDIT_LOG . '.credit) as num,' . TABLE_CREDIT_TYPE . '.class_id');
        $this->db->group_by(TABLE_CREDIT_TYPE . '.class_id');
        $this->db->join(TABLE_CREDIT_TYPE, TABLE_CREDIT_LOG . '.nid = ' . TABLE_CREDIT_TYPE . '.nid', 'LEFT');
        $credits = $this->db->get_where(TABLE_CREDIT_LOG, array(TABLE_CREDIT_LOG . '.user_id' => $user_id))->result_array();
        $sum = array_sum(array_column($credits, 'num'));
        //统计数量
        $this->db->update(TABLE_CREDIT, array(
            'credit' => $sum,
            'credits' => serialize($credits),
                ), array(
            'user_id' => $user_id
        ));
        $this->db->trans_complete();
        do_log('积分变动通知');
        //积分增加发送站内信
        $this->load->model('m_message');
        $send_message = $this->m_message->send_admin(array(
            'receiver_id' => $user_id,
            'title' => '积分变动通知',
            'text' => date('Y-m-d H:i:s',time()).' '.$action['remark'].' 奖励'.abs($num).'积分',
        ));
        
        return true;
    }

    public function decrease($user_id, $num, $action) {
        $this->load->model('m_credit_type');
        $credit_type = $this->m_credit_type->detail_by_nid($action['type']);

        $this->_create_info($user_id);
        //开始事物
        $this->db->trans_start();
        $credit = $this->db->query("SELECT credit FROM " . TABLE_CREDIT . " WHERE user_id = " . intval($user_id) . " LIMIT 1 FOR UPDATE")->row(0)->credit;
        if (intval($credit) < abs($num)) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->insert(TABLE_CREDIT_LOG, array(
            'code' => $credit_type->code,
            'user_id' => $user_id,
            'value' => -abs($num),
            'credit' => -abs($num),
            'type' => $credit_type->nid,
            'nid' => $credit_type->nid,
            'article_id' => $action['item_id'],
            'addtime' => time(),
            'remark' => $action['remark'],
        ));
        $this->db->select('sum(' . TABLE_CREDIT_LOG . '.credit) as num,' . TABLE_CREDIT_TYPE . '.class_id');
        $this->db->group_by(TABLE_CREDIT_TYPE . '.class_id');
        $this->db->join(TABLE_CREDIT_TYPE, TABLE_CREDIT_LOG . '.nid = ' . TABLE_CREDIT_TYPE . '.nid', 'LEFT');
        $credits = $this->db->get_where(TABLE_CREDIT_LOG, array(TABLE_CREDIT_LOG . '.user_id' => $user_id))->result_array();
        $sum = array_sum(array_column($credits, 'num'));
        //统计数量
        $this->db->update(TABLE_CREDIT, array(
            'credit' => $sum,
            'credits' => serialize($credits),
                ), array(
            'user_id' => $user_id
        ));
        $this->db->trans_complete();
        do_log('积分变动通知');
        //积分扣除发送站内信
        $this->load->model('m_message');
        $send_message = $this->m_message->send_admin(array(
            'receiver_id' => $user_id,
            'title' => '积分变动通知',
            'text' => date('Y-m-d H:i:s',time()).' '.$action['remark'].' 扣除'.abs($num).'积分',
        ));
        return true;
    }

    //随机签到积分
    public function rand_sign() {
        return rand(1, 50);
    }

    public function _create_info($user_id) {
        $r = $this->db->get_where(TABLE_CREDIT, array('user_id' => $user_id))->row_array(0);
        if (empty($r)) {
            $this->db->insert(TABLE_CREDIT, array(
                'user_id' => $user_id
            ));
        } else {
            return true;
        }
    }

    /**
     * 积分分类列表
     *
     * @return Array
     */
    function GetClassList($data = array()) {
        global $mysql;

        $_sql = " where 1=1 ";
        $_select = " p1.*";
        $_order = " order by p1.id desc";
        $_limit = "";
        $sql = "select SELECT from `{credit_class}` as p1  SQL ORDER LIMIT ";

        //是否显示全部的信息
        $data['limit'] = isset($data['limit']) ? $data['limit'] : "";
        if (IsExiest($data['limit']) != false) {
            if ($data['limit'] != "all") {
                $_limit = "  limit " . $data['limit'];
            }
            return $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));
        }

        //判断总的条数
        $row = $mysql->db_fetch_array(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array('count(1) as num', $_sql, '', ''), $sql));
        $total = intval($row['num']);

        //分页返回结果
        $data['page'] = !IsExiest($data['page']) ? 1 : $data['page'];
        $data['epage'] = !IsExiest($data['epage']) ? 10 : $data['epage'];
        $total_page = ceil($total / $data['epage']);
        $_limit = " limit " . ($data['epage'] * ($data['page'] - 1)) . ", {$data['epage']}";
        $list = $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));

        //返回最终的结果
        $result = array('list' => $list ? $list : array(), 'total' => $total, 'page' => $data['page'], 'epage' => $data['epage'], 'total_page' => $total_page);
        return $result;
    }

    /**
     * 查看积分分类
     *
     * @param Array $data
     * @return Array
     */
    public static function GetClassOne($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_class_id_empty";
        $sql = "select p1.* from `{credit_class}` as p1 where p1.id={$data['id']}";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false)
            return "credit_class_not_exiest";
        return $result;
    }

    /**
     * 添加积分分类
     *
     * @param Array $data
     * @return Boolen
     */
    function AddClass($data = array()) {
        global $mysql;
        //手机号码不能为空
        if (!IsExiest($data['name']))
            return "credit_class_name_empty";
        if (!IsExiest($data['nid']))
            return "credit_class_nid_empty";

        //判断标识名是否存在
        $sql = "select 1 from `{credit_class}` where nid='{$data['nid']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result != false)
            return "credit_class_nid_exiest";

        $sql = "insert into `{credit_class}` set `name`='{$data['name']}',`nid`='{$data['nid']}'";
        $mysql->db_query($sql);
        $id = $mysql->db_insert_id();
        return $id;
    }

    /**
     * 修改积分类型
     *
     * @param Array $data
     * @return Boolen
     */
    function UpdateClass($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_class_id_empty";
        if (!IsExiest($data['name']))
            return "credit_class_name_empty";
        if (!IsExiest($data['nid']))
            return "credit_class_nid_empty";

        //判断标识名是否存在
        $sql = "select 1  from `{credit_class}` where nid='{$data['nid']}' and id!='{$data['id']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result != false)
            return "credit_class_nid_exiest";


        $sql = "update `{credit_class}`  set `name`='{$data['name']}',`nid`='{$data['nid']}' where id='{$data['id']}'";
        $mysql->db_query($sql);
        return $data['id'];
    }

    /**
     * 删除积分分类
     *
     * @param Array $data
     * @return Boolen
     */
    function DeleteClass($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_class_id_empty";

        //判断是否有类型是否存在
        $sql = "select 1 from `{credit_type}` where FIND_IN_SET('{$data['id']}',class_id)";
        $result = $mysql->db_fetch_array($sql);
        if ($result != false)
            return "credit_class_del_type_exiest";


        //判断是否有等级是否存在
        $sql = "select 1 from `{credit_rank}` where FIND_IN_SET('{$data['id']}',class_id)";
        $result = $mysql->db_fetch_array($sql);
        if ($result != false)
            return "credit_class_del_rank_exiest";

        $sql = "delete from `{credit_class}` where id='{$data['id']}'";
        $mysql->db_query($sql);
        return $data['id'];
    }

    /**
     * 积分类型列表
     *
     * @return Array
     */
    function GetTypeList($data = array()) {
        global $mysql;

        $_sql = " where 1=1 ";

        //搜到名称
        if (IsExiest($data['name']) != false) {
            $_sql .= " and p1.`name` like '%{$data['name']}%'";
        }

        //搜到标识名
        if (IsExiest($data['nid']) != false) {
            $_sql .= " and p1.nid like '%{$data['nid']}%'";
        }


        //搜到标识名
        if (IsExiest($data['code']) != false) {
            $_sql .= " and p1.code = '{$data['code']}'";
        }

        $_select = " p1.*";
        $_order = " order by p1.id desc";
        $sql = "select SELECT from `{credit_type}` as p1  SQL ORDER LIMIT ";

        //是否显示全部的信息
        if (IsExiest($data['limit']) != false) {
            if ($data['limit'] != "all") {
                $_limit = "  limit " . $data['limit'];
            }
            return $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));
        }

        //判断总的条数
        $row = $mysql->db_fetch_array(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array('count(1) as num', $_sql, '', ''), $sql));
        $total = intval($row['num']);

        //分页返回结果
        $data['page'] = !IsExiest($data['page']) ? 1 : $data['page'];
        $data['epage'] = !IsExiest($data['epage']) ? 10 : $data['epage'];
        $total_page = ceil($total / $data['epage']);
        $_limit = " limit " . ($data['epage'] * ($data['page'] - 1)) . ", {$data['epage']}";
        $list = $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));

        //返回最终的结果
        $result = array('list' => $list ? $list : array(), 'total' => $total, 'page' => $data['page'], 'epage' => $data['epage'], 'total_page' => $total_page);
        return $result;
    }

    /**
     * 查看积分类型
     *
     * @param Array $data
     * @return Array
     */
    function GetTypeOne($data = array()) {
        global $mysql;
        //if (!IsExiest($data['id'])) return "credit_type_id_empty";

        if (IsExiest($data['id']) != false) {
            $_sql .= " and p1.`id`  = '{$data['id']}'";
        }

        if (IsExiest($data['nid']) != false) {
            $_sql .= " and p1.`nid`  = '{$data['nid']}'";
        }

        $sql = "select p1.* from `{credit_type}` as p1 where 1=1" . $_sql;
        $result = $mysql->db_fetch_array($sql);
        //if ($result==false) return "credit_type_not_exiest";
        return $result;
    }

    /**
     * 添加积分类型
     *
     * @param Array $data
     * @return Boolen
     */
    function AddType($data = array()) {
        global $mysql;

        if (!IsExiest($data['name']))
            return "credit_type_name_empty";
        if (!IsExiest($data['nid']))
            return "credit_type_nid_empty";
        if (!IsExiest($data['value']))
            return "credit_type_value_empty";
        if (!IsExiest($data['class_id']))
            return "credit_type_class_id_empty";

        if ($data['cycle'] == 2 && $data['award_times'] == "") {
            return "credit_type_award_times_empty";
        }

        if ($data['cycle'] == 3 && $data['interval'] == "") {
            return "credit_type_interval_empty";
        }

        //判断标识名是否存在
        $sql = "select 1 from `{credit_type}` where nid='{$data['nid']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result != false)
            return "credit_type_nid_exiest";

        $sql = "insert into `{credit_type}` set ";
        foreach ($data as $key => $value) {
            $_sql[].= "`$key` = '$value'";
        }
        $mysql->db_query($sql . join(",", $_sql));
        $id = $mysql->db_insert_id();
        return $id;
    }

    /**
     * 修改积分类型
     *
     * @param Array $data
     * @return Boolen
     */
    function UpdateType($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_type_id_empty";
        if (!IsExiest($data['name']))
            return "credit_type_name_empty";
        if (!IsExiest($data['nid']))
            return "credit_type_nid_empty";

        //判断标识名是否存在
        $sql = "select 1  from `{credit_type}` where nid='{$data['nid']}' and id!='{$data['id']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result != false)
            return "credit_type_nid_exiest";

        $sql = "select nid  from `{credit_type}` where  id='{$data['id']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result['nid'] != $data['nid']) {
            //判断是否有类型是否存在
            $sql = "select 1 from `{credit_log}` as p1 left join `{credit_type}` as p2 on p1.nid=p2.nid where p2.id='{$data['id']}'";
            $result = $mysql->db_fetch_array($sql);
            if ($result != false)
                return "credit_type_update_credit_exiest";
        }

        $sql = "update `{credit_type}`  set ";
        foreach ($data as $key => $value) {
            $_sql[].= "`$key` = '$value'";
        }
        $mysql->db_query($sql . join(",", $_sql) . " where id='{$data['id']}'");
        return $data['id'];
    }

    /**
     * 删除积分类型
     *
     * @param Array $data
     * @return Boolen
     */
    function DeleteType($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_type_id_empty";

        //判断标识名是否存在
        $sql = "select nid from `{credit_type}` where id='{$data['id']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false)
            return "credit_type_not_exiest";



        $sql = "delete from `{credit_type}` where id='{$data['id']}'";
        $mysql->db_query($sql);
        return $data['id'];
    }

    /**
     * 积分等级列表
     *
     * @return Array
     */
    function GetRankList($data = array()) {
        global $mysql;

        $_sql = " where 1=1 ";

        //搜到名称
        $data['class_id'] = isset($data['class_id']) ? $data['class_id'] : "";
        if (IsExiest($data['class_id']) != false) {
            $_sql .= " and p1.`class_id` = '{$data['class_id']}'";
        }


        $_select = " p1.*,p2.nid as class_nid";
        $_order = " order by p1.id desc";
        $_limit = "";
        $sql = "select SELECT from `{credit_rank}` as p1 left join `{credit_class}` as p2 on p1.class_id=p2.id SQL ORDER LIMIT ";

        //是否显示全部的信息

        if (IsExiest($data['limit']) != false) {
            if ($data['limit'] != "all") {
                $_limit = "  limit " . $data['limit'];
            }
            return $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));
        }

        //判断总的条数
        $row = $mysql->db_fetch_array(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array('count(1) as num', $_sql, '', ''), $sql));
        $total = intval($row['num']);

        //分页返回结果
        $data['page'] = !IsExiest($data['page']) ? 1 : $data['page'];
        $data['epage'] = !IsExiest($data['epage']) ? 10 : $data['epage'];
        $total_page = ceil($total / $data['epage']);
        $_limit = " limit " . ($data['epage'] * ($data['page'] - 1)) . ", {$data['epage']}";
        $list = $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));

        //返回最终的结果
        $result = array('list' => $list ? $list : array(), 'total' => $total, 'page' => $data['page'], 'epage' => $data['epage'], 'total_page' => $total_page);
        return $result;
    }

    /**
     * 查看积分等级
     *
     * @param Array $data
     * @return Array
     */
    function GetRankOne($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_rank_id_empty";
        $sql = "select p1.* from `{credit_rank}` as p1 where p1.id={$data['id']}";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false)
            return "credit_rank_not_exiest";
        return $result;
    }

    /**
     * 添加积分等级
     *
     * @param Array $data
     * @return Boolen
     */
    function AddRank($data = array()) {
        global $mysql;

        if (!IsExiest($data['name']))
            return "credit_rank_name_empty";


        $sql = "insert into `{credit_rank}` set ";
        foreach ($data as $key => $value) {
            $_sql[].= "`$key` = '$value'";
        }
        $mysql->db_query($sql . join(",", $_sql));
        $id = $mysql->db_insert_id();
        return $id;
    }

    /**
     * 修改积分等级
     *
     * @param Array $data
     * @return Boolen
     */
    function UpdateRank($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_rank_id_empty";
        if (!IsExiest($data['name']))
            return "credit_rank_name_empty";


        $sql = "update `{credit_rank}`  set ";
        foreach ($data as $key => $value) {
            $_sql[].= "`$key` = '$value'";
        }
        $mysql->db_query($sql . join(",", $_sql) . " where id='{$data['id']}'");
        return $data['id'];
    }

    /**
     * 删除积分类型
     *
     * @param Array $data
     * @return Boolen
     */
    function DeleteRank($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_rank_id_empty";

        $sql = "delete from `{credit_rank}` where id='{$data['id']}'";
        $mysql->db_query($sql);
        return $data['id'];
    }

    /**
     * 积分记录列表
     *
     * @return Array
     */
    function GetLogList($data = array()) {
        global $mysql;

        $_sql = " where 1=1 ";

        //搜索积分分类
        if (IsExiest($data['class_id']) != false) {
            $_sql .= " and p1.`class_id` = '{$data['class_id']}'";
        }

        //搜索用户id
        if (IsExiest($data['user_id']) != false) {
            $_sql .= " and p1.`user_id` = '{$data['user_id']}'";
        }

        //搜索用户名
        if (IsExiest($data['username']) != false) {
            $_sql .= " and p2.`username` like '%" . urldecode($data['username']) . "%'";
        }

        //搜索标识名
        if (IsExiest($data['nid']) != false) {
            $_sql .= " and p1.`nid` like '%{$data['nid']}%'";
        }


        $_select = " p1.*,p2.username,p3.name as type_name,p3.class_id";
        $_order = " order by p1.id desc";
        $sql = "select SELECT from `{credit_log}` as p1 left join `{credit_type}` as p3 on p1.nid=p3.nid left join `{users}` as p2 on p1.user_id=p2.user_id  SQL ORDER LIMIT ";

        //是否显示全部的信息
        if (IsExiest($data['limit']) != false) {
            if ($data['limit'] != "all") {
                $_limit = "  limit " . $data['limit'];
            }
            return $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));
        }

        //判断总的条数
        $row = $mysql->db_fetch_array(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array('count(1) as num', $_sql, '', ''), $sql));
        $total = intval($row['num']);

        //分页返回结果
        $data['page'] = !IsExiest($data['page']) ? 1 : $data['page'];
        $data['epage'] = !IsExiest($data['epage']) ? 10 : $data['epage'];
        $total_page = ceil($total / $data['epage']);
        $_limit = " limit " . ($data['epage'] * ($data['page'] - 1)) . ", {$data['epage']}";
        $list = $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));

        //返回最终的结果
        $result = array('list' => $list ? $list : array(), 'total' => $total, 'page' => $data['page'], 'epage' => $data['epage'], 'total_page' => $total_page);
        return $result;
    }

    /**
     * 查看积分类型
     *
     * @param Array $data
     * @return Array
     */
    function GetLogOne($data = array()) {
        global $mysql;
        if (!IsExiest($data['id']))
            return "credit_log_id_empty";
        $sql = "select p1.*,p2.username,p3.name as type_name,p3.class_id from `{credit_log}` as p1 left join `{users}` as p2 on p1.user_id=p2.user_id left join `{credit_type}` as p3 on p1.nid=p3.nid where p1.id={$data['id']}";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false)
            return "credit_log_not_exiest";
        return $result;
    }

    /**
     * 积分操作
     *
     * @return Array $data = array("code"=>"模块","user_id"=>"用户","type"=>"类型","article_id"=>"文章id","code"=>"","code"=>"",);
     */
    function ActionCreditLog($data) {
        global $mysql;
        $ready = 0;
        $code = -7; //积分操作失败
        $msg = '未知错误';
        if (empty($data['user_id']) || !isset($data['type'])) {
            $code = -1; //用户ID参数缺失
            $msg = '用户ID参数缺失';
            $result = array('code' => $code, 'msg' => $msg);
            return $result;
        }

        $test_sql = "select 1 from `{credit}` where user_id='{$data['user_id']}'";
        $test_result = $mysql->db_fetch_array($test_sql);
        if ($test_result == false) {
            $test_sql = "insert into `{credit}` set user_id='{$data['user_id']}'";
            $mysql->db_query($test_sql);
        }

        if (isset($data['type']) && $data['type'] != "") {//查询积分类型，是否启用和循环次数等
            $nid = $data['type'];
            $type = self::GetTypeOne(array('nid' => $data['type']));

            if ($type) {
                if ($type['status']) {//积分类型是启用状态
                    //第二步加入资金记录
                    if (isset($data['value']) && $data['value'] != "") {//判断是否存在指定积分值，否则按照预先设定的积分值
                        $_value = $data['value'];
                    } else {
                        $sql = "select `value` from `{credit_type}` where nid='{$nid}'";
                        $result = $mysql->db_fetch_array($sql);
                        $_value = $result['value'];
                    }
                    if ($_value < 0) {
                        $old_sql = "select credit from `{credit}` where user_id='{$data['user_id']}'";
                        $old_result = $mysql->db_fetch_array($old_sql);
                        if (abs($_value) > $old_result['credit']) {
                            //积分不足，无法扣除
                            $code = -6;
                            $msg = '积分不足，无法扣除';
                            $ready = 0;
                        }
                    }


                    switch ($type['cycle']) {
                        case 1://奖励周期一次性
                            $sql = "select count(1) as num from `{credit_log}` where type='{$data['type']}' and article_id='{$data['article_id']}' and nid='{$nid}' and user_id='{$data['user_id']}'";
                            $row = $mysql->db_fetch_array($sql);
                            $num = intval($row['num']);

                            if ($num > 1) {
                                $code = -4; //操作超过次数
                                $msg = '操作超过次数';
                                $ready = 0;
                            } else {
                                $ready = 1;
                            }
                            break;
                        case 2: //奖励周期每天
                            // 当天的零点
                            $today = strtotime(date('Y-m-d', time()));
                            // 当天的24
                            $end = $today + 24 * 60 * 60;

                            $sql = "select count(1) as num from `{credit_log}` where type='{$data['type']}' and article_id='{$data['article_id']}' and nid='{$nid}' and user_id='{$data['user_id']}' and addtime >= '" . $today . "' and addtime < '" . $end . "'";
                            $row = $mysql->db_fetch_array($sql);
                            $num = intval($row['num']);

                            if ($type['award_times'] >= 0) {//奖励次数
                                if ($num >= $type['award_times'] && $type['award_times'] != 0) {
                                    $code = -4; //操作超过次数
                                    $msg = '操作超过次数';
                                    $ready = 0;
                                } else {
                                    $sql = "select addtime from `{credit_log}` where type='{$data['type']}' and article_id='{$data['article_id']}' and nid='{$nid}' and user_id='{$data['user_id']}' order by addtime desc";
                                    $row = $mysql->db_fetch_array($sql);
                                    $lasttime = intval($row['addtime']);
                                    $nowtime = time();
                                    if (($nowtime - $lasttime) > ($type['interval'] * 60) && $type['interval'] >= 0) {
                                        $ready = 2;
                                    } else {
                                        $code = -5; //尚未达到可操作时间
                                        $msg = '尚未达到可操作时间';
                                        $ready = 0;
                                    }
                                }
                            } else {
                                $code = -3; //积分类型奖励次数设置错误
                                $msg = '积分类型奖励次数设置错误';
                                $ready = 0;
                            }
                            break;
                        case 3:
                            $sql = "select count(1) as num from `{credit_log}` where type='{$data['type']}' and article_id='{$data['article_id']}' and nid='{$nid}' and user_id='{$data['user_id']}'";
                            $row = $mysql->db_fetch_array($sql);
                            $num = intval($row['num']);

                            if ($type['award_times'] >= 0) {//奖励次数
                                if ($num >= $type['award_times'] && $type['award_times'] != 0) {
                                    $code = -4; //操作超过次数
                                    $msg = '操作超过次数';
                                    $ready = 0;
                                } else {
                                    $sql = "select addtime from `{credit_log}` where type='{$data['type']}' and article_id='{$data['article_id']}' and nid='{$nid}' and user_id='{$data['user_id']}' order by addtime desc";
                                    $row = $mysql->db_fetch_array($sql);
                                    $lasttime = intval($row['addtime']);
                                    $nowtime = time();
                                    if (($nowtime - $lasttime) > ($type['interval'] * 60) && $type['interval'] >= 0) {
                                        $ready = 3;
                                    } else {
                                        $code = -5; //尚未达到可操作时间
                                        $msg = '尚未达到可操作时间';
                                        $ready = 0;
                                    }
                                }
                            } else {
                                $code = -3; //积分类型奖励次数设置错误
                                $msg = '积分类型奖励次数设置错误';
                                $ready = 0;
                            }

                            break;

                        case 4:
                            $sql = "select count(1) as num from `{credit_log}` where type='{$data['type']}' and article_id='{$data['article_id']}' and nid='{$nid}' and user_id='{$data['user_id']}'";
                            $row = $mysql->db_fetch_array($sql);
                            $num = intval($row['num']);
                            if ($type['award_times'] > 0) {
                                if ($num >= $type['award_times']) {
                                    $code = -4; //操作超过次数
                                    $msg = '操作超过次数';
                                    $ready = 0;
                                } else {
                                    $ready = 4;
                                }
                            } else if ($type['award_times'] == 0) {
                                $ready = 4;
                            }
                            break;
                    }


                    if ($ready) {
                        $sql = "insert into `{credit_log}` set code='{$type['code']}',user_id='{$data['user_id']}',`value`='{$_value}',`credit`='{$_value}',type='{$data['type']}',article_id='{$data['article_id']}',nid='{$nid}',addtime='{$data['addtime']}',remark='{$data['remark']}'";
                        $insert_result = $mysql->db_query($sql);

                        $action_result = self::ActionCredit(array("user_id" => $data['user_id']));
                        if ($insert_result && $action_result) {
                            $code = 1; //积分操作成功
                            $msg = '操作成功';
                        } else {
                            $code = -7; //积分操作失败
                            $msg = '未知错误';
                        }
                    }
                } else {
                    $code = 0; //该积分类型已停用
                    $msg = '该积分类型已停用';
                }
            } else {
                $code = -2; //积分类型不存在
                $msg = '积分类型不存在';
            }
        } else {
            $code = -1; //积分类型参数缺失
            $msg = '积分类型参数缺失';
        }

        $result = array('code' => $code, 'msg' => $msg, 'value' => $_value);
        return $result;
    }

    function Sign($data) {
        global $mysql;
        $credit_log['user_id'] = $data['user_id'];
        $credit_log['type'] = "sign";
        $credit_log['value'] = rand(1, 5);
        $credit_log['addtime'] = time();
        $credit_log['article_id'] = $data['user_id'];
        $credit_log['remark'] = "签到获得金币";
        $result = creditClass::ActionCreditLog($credit_log);

        if ($result['code'] > 0) {

            $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));

            $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;

            $sql = "select 1 from `{credit_log}` where user_id='{$data['user_id']}' and addtime>='{$beginYesterday}' and addtime<'{$endYesterday}'";
            $log_result = $mysql->db_fetch_array($sql);

            if ($log_result) {
                $sql = "select sign  from `{credit}` where user_id='{$data['user_id']}'";
                $sign_result = $mysql->db_fetch_array($sql);
                if ($sign_result['sign'] < 15) {
                    $new_sign = $sign_result['sign'] + 1;
                } else {
                    $new_sign = 1;
                }
            } else {
                $new_sign = 1;
            }
            $sql = "update `{credit}` set `sign`='{$new_sign}' where user_id='{$data['user_id']}'";
            $new_result = $mysql->db_query($sql);

            if ($new_result) {
                if ($sign_result['sign'] == 14) {
                    $reword['user_id'] = $data['user_id'];
                    $reword['type'] = "sign_reward";
                    $reword['addtime'] = time();
                    $reword['article_id'] = $data['user_id'];
                    $reword['remark'] = "连续签到奖励";
                    $reword_result = creditClass::ActionCreditLog($reword);
                    if ($reword_result['code'] < 0) {
                        $code = -1; //积分类型参数缺失
                        $msg = '签到奖励错误';
                        $result = array('code' => $code, 'msg' => $msg, 'value' => $credit_log['value']);
                        return $result;
                    }
                }
            } else {
                $code = -1; //积分类型参数缺失
                $msg = '签到奖励错误';
                $result = array('code' => $code, 'msg' => $msg, 'value' => $credit_log['value']);
            }
            return $result;
        } else {
            /* $code=-1;	//积分类型参数缺失
              $msg='签到失败';
              $result = array('code' => $code,'msg' => $msg); */
            return $result;
        }
    }

    /**
     * 删除积分操作
     *
     * @return Array $data = array("user_id"=>"用户","id"=>"积分记录id");
     */
    function DeleteCredit($data) {
        global $mysql;
        //获取用户id
        $sql = "select user_id from `{credit_log}` where id='{$data['id']}' ";
        $result = $mysql->db_fetch_array($sql);
        $user_id = $result['user_id'];

        $_sql = "delete from `{credit_log}` where id='{$data['id']}' ";
        $delete_result = $mysql->db_query($_sql);

        self::ActionCredit(array("user_id" => $user_id));
        return $delete_result;
    }

    /**
     * 删除积分操作
     *
     * @return Array $data = array("code"=>"模块","type"=>"类型","article_id"=>"文章id");
     */
    /* function DeleteCreditLog($data){
      global $mysql;

      //获取用户id
      $sql = "select user_id from `{credit_log}` where code='{$data['code']}'  and type='{$data['type']}' and article_id='{$data['article_id']}' ";
      $result = $mysql->db_fetch_array($sql);
      $user_id= $result['user_id'];

      //第一步先删除没有的积分记录
      $_sql = "delete from `{credit_log}` where code='{$data['code']}'  and type='{$data['type']}' and article_id='{$data['article_id']}' ";
      $mysql->db_query($_sql);

      self::ActionCredit(array("user_id"=>$user_id));

      } */

    /**
     * 积分操作
     *
     * @return Array $data = array("user_id"=>"用户");
     */
    function ActionCredit($data) {
        global $mysql;
        $sql = "select sum(p1.credit) as num,p2.class_id from `{credit_log}` as p1 left join `{credit_type}` as p2 on p1.nid=p2.nid  where p1.user_id='{$data['user_id']}' group by p2.class_id order by p2.class_id desc";
        $result = $mysql->db_fetch_arrays($sql);
        $credits = serialize($result);
        $sql = "select 1 from `{credit}` where user_id='{$data['user_id']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false) {
            $sql = "insert into `{credit}` set user_id='{$data['user_id']}',`credits`='{$credits}'";
        } else {
            $sql = "update `{credit}` set `credits`='{$credits}' where user_id='{$data['user_id']}'"; //更新总积分序列字段
        }
        $action_result = $mysql->db_query($sql);
        $count_result = self::CountCredit(array("user_id" => $data['user_id'], "type" => "catoreasy"));
        if ($count_result) {
            return $action_result;
        }
    }

    /**
     * 修改操作
     *
     * @return Array $data = array("user_id"=>"用户","credit"=>"积分","id"=>"积分记录id");
     */
    function UpdateCredit($data) {
        global $mysql;
        $sql = "update `{credit_log}` set `credit`='{$data['credit']}' where id='{$data['id']}' and user_id='{$data['user_id']}'";
        $mysql->db_query($sql);
        self::ActionCredit(array("user_id" => $data["user_id"]));
        return $data['id'];
    }

    /**
     * 积分等级列表
     *
     * @return Array
     */
    function GetList($data = array()) {
        global $mysql;

        $_sql = " where 1=1 ";
        if (IsExiest($data['username']) != false) {
            $_sql .= " and p2.`username` like '%" . urldecode($data['username']) . "%'";
        }
        $_select = " p1.*,p2.username";
        $_order = " order by p1.id desc";
        $sql = "select SELECT from `{credit}` as p1 left join `{users}` as p2 on p1.user_id=p2.user_id  SQL ORDER LIMIT ";

        $result_type = self::GetClassList(array("limit" => "all"));
        foreach ($result_type as $key => $value) {
            $_type_credit[$value['id']]['num'] = 0;
            $_type_credit[$value['id']]['class_id'] = $value['id'];
        }

        //判断总的条数
        $row = $mysql->db_fetch_array(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array('count(1) as num', $_sql, '', ''), $sql));
        $total = intval($row['num']);

        //分页返回结果
        $data['page'] = !IsExiest($data['page']) ? 1 : $data['page'];
        $data['epage'] = !IsExiest($data['epage']) ? 10 : $data['epage'];
        $total_page = ceil($total / $data['epage']);
        $_limit = " limit " . ($data['epage'] * ($data['page'] - 1)) . ", {$data['epage']}";
        $_list = $mysql->db_fetch_arrays(str_replace(array('SELECT', 'SQL', 'ORDER', 'LIMIT'), array($_select, $_sql, $_order, $_limit), $sql));
        if ($_list != false) {
            foreach ($_list as $key => $value) {
                $list[$key]["username"] = $value['username'];
                /* $list[$key]["user_credit"] = borrowClass::GetBorrowCredit(array("user_id"=>$value['user_id'])); */
                $list[$key]["borrow_credit"] = self::GetClassCount(array("user_id" => $value['user_id'], "class_id" => "3"));
                $list[$key]["approve_credit"] = self::GetClassCount(array("user_id" => $value['user_id'], "class_id" => "6"));
                $list[$key]["gold_credit"] = self::GetClassCount(array("user_id" => $value['user_id'], "class_id" => "4"));
                $list[$key]["expend_credit"] = self::GetClassCount(array("user_id" => $value['user_id'], "class_id" => "7"));

                $list[$key]["total_credit"] = self::GetTotalCredit(array("user_id" => $value['user_id']));
                $list[$key]["credits"] = $_type_credit;
                if ($value['credits'] != "") {
                    $credits = unserialize($value['credits']);
                    foreach ($credits as $_key => $_value) {
                        $list[$key]["credits"][$_value['class_id']] = $_value;
                    }
                }
            }
        }
        //返回最终的结果
        $result = array('list' => $list ? $list : array(), 'total' => $total, 'page' => $data['page'], 'epage' => $data['epage'], 'total_page' => $total_page);
        return $result;
    }

    function CountCredit($data) {
        global $mysql;
        if ($data['type'] == "catoreasy") {
            //require_once(ROOT_PATH."/modules/borrow/borrow.class.php");
            //$result = borrowClass::GetBorrowCredit(array("user_id"=>$data['user_id']));
            /* $sql = "select sum(credit) as credit_total from `{credit_log}` where user_id='{$data['user_id']}' ";
              $result = $mysql->db_fetch_array($sql); */

            $result = self::GetTotalCredit(array("user_id" => $data['user_id']));

            $sql = "update `{credit}` set credit='{$result['total_credit']}' where user_id='{$data['user_id']}'"; //更新用户总积分值
            $count_result = $mysql->db_query($sql);
            return $count_result;
        }
    }

    /**
     * 获取用户总积分
     *
     * @return Array
     */
    function GetTotalCredit($data) {
        global $mysql;

        $sql = "select sum(credit) as total_credit from `{credit_log}` where user_id='{$data['user_id']}' ";
        $result = $mysql->db_fetch_array($sql);

        return $result;
    }

    function GetCreditCount($data) {
        global $mysql;
        $sql = "select sum(credit) as num,type from `{credit_log}` where user_id='{$data['user_id']}' group by type";
        $result = $mysql->db_fetch_arrays($sql);
        $_result = array();
        if ($result != false) {
            foreach ($result as $key => $value) {
                $_result[$value['type']] = $value['num'];
            }
        }
        return $_result;
    }

    function GetUserCredit($data) {
        global $mysql;
        $sql = "select sum(p1.credit) as num,p3.nid from `{credit_log}` as p1 ,`{credit_type}` as p2 ,{credit_class} as p3 where p1.user_id='{$data['user_id']}' and p2.nid=p1.nid and p2.class_id=p3.id group by p3.id";
        $result = $mysql->db_fetch_arrays($sql);
        $_result = array();
        if ($result != false) {
            foreach ($result as $key => $value) {
                $_result[$value['nid']] = $value['num'];
            }
        }
        return $_result;
    }

    /**
     * 获取分类总积分
     *
     * @return Array
     */
    function GetClassCount($data = array()) {
        global $mysql;

        if ($data['class_nid'] == "" && $data['class_id'] == "")
            return false;
        if (IsExiest($data['class_nid']) != false) {
            $_sql .= " and p3.`nid` = '" . $data['class_nid'] . "'";
        }
        if (IsExiest($data['class_id']) != false) {
            $_sql .= " and p3.`id` = '" . $data['class_id'] . "'";
        }
        //邀请金币
        $sql = "select sum(p1.credit) as total from `{credit_log}` as p1 ,`{credit_type}` as p2,`{credit_class}` as p3  where p1.user_id='{$data['user_id']}' and p2.nid=p1.nid and p2.class_id=p3.id " . $_sql;

        $result = $mysql->db_fetch_array($sql);
        return $result['total'];
    }

    /**
     * 获取分类型总积分
     *
     * @return Array
     */
    function GetTypeCount($data = array()) {
        global $mysql;

        if ($data['user_id'] == "")
            return false;
        if ($data['type_nid'] == "" && $data['type_id'] == "")
            return false;

        if (IsExiest($data['type_nid']) != false) {
            $_sql .= " and p1.`nid` = '" . $data['type_nid'] . "'";
        }
        if (IsExiest($data['type_id']) != false) {
            $_sql .= " and p1.`id` = '" . $data['type_id'] . "'";
        }
        //邀请金币
        $sql = "select sum(p1.credit) as total from `{credit_log}` as p1 where p1.user_id='{$data['user_id']}' " . $_sql;

        $result = $mysql->db_fetch_array($sql);
        return $result['total'];
    }

    function GetGoldCount($data = array()) {
        global $mysql;
        if ($data['user_id'] == "")
            return false;
        //邀请金币
        $sql = "select sum(credit) as invite_gold from `{credit_log}` where user_id = {$data['user_id']} and nid='invite'";
        $result = $mysql->db_fetch_array($sql);
        $gold['invite_gold'] = $result['invite_gold'];


        //Vip冲抵金币
        $sql = "select sum(credit) as vip_gold from `{credit_log}` where user_id = {$data['user_id']} and nid='vip_gold'";
        $result = $mysql->db_fetch_array($sql);
        $gold['vip_gold'] = $result['vip_gold'];

        //注册金币
        $sql = "select sum(credit) as reg_gold from `{credit_log}` where user_id = {$data['user_id']} and nid='reg'";
        $result = $mysql->db_fetch_array($sql);
        $gold['reg'] = $result['reg_gold'];

        //邀请投标金币
        $gold['invite_tender'] = 0;
        $sql = "select * from `{users_friends_invite}` where user_id = {$data['user_id']}";
        $result = $mysql->db_fetch_arrays($sql);
        if ($result > 0) {
            foreach ($result as $key => $value) {
                $_sql = "select * from `{borrow_count}` where user_id = {$value['friends_userid']}";
                $result = $mysql->db_fetch_array($_sql);
                $gold['invite_tender'] += $result['tender_success_account'];
            }
        }
        $gold['invite_tender'] = floor($gold['invite_tender'] / 5000);
        //自己投标所得的金币
        $sql = "select tender_success_account from `{borrow_count}` where user_id = {$data['user_id']}";
        $result = $mysql->db_fetch_array($sql);
        $gold['tender'] = floor($result['tender_success_account'] / 10000);

        //总所得金币
        $gold['total'] = $gold['invite_tender'] + $gold['invite_gold'] + $gold['tender'] + $gold['reg'] + $gold['vip_gold'];

        return $gold;
    }

    /**
     * 查看用户积分
     *
     * @param Array $data
     * @return Array
     */
    function GetOne($data = array()) {
        global $mysql;
        if (!IsExiest($data['user_id']))
            return "credit_user_id_empty";
        $sql = "select p1.* from `{credit}` as p1 where p1.user_id={$data['user_id']}";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false) {
            $sql = "insert into `{credit}` set user_id='{$data['user_id']}'";
            $mysql->db_query($sql);
            $result = self::GetOne($data);
        }
        return $result;
    }

    /* 查看用户等级信息
     *
     * @param Array $data
     * @return Array
     */

    function GetUserRank($data) {
        global $mysql;

        $sql = "select * from `{credit_class}` where nid='{$data['nid']}'";
        $result = $mysql->db_fetch_array($sql);
        $class_id = $result['id'];

        $sql = "select sum(p1.credit) as num from `{credit_log}` as p1 where p1.user_id={$data['user_id']} and p1.code='{$data['code']}'";
        $result = $mysql->db_fetch_array($sql);
        if ($result == false)
            return "";
        $sql = "select sum(credit) as num from `{attestations}` where user_id='{$data['user_id']}'";
        $attcredit = $mysql->db_fetch_array($sql);
        $credit = $result['num'] + $attcredit['num'];
        $sql = "select * from `{credit_rank}` where class_id='{$class_id}' and 	point1<={$credit} and point2>={$credit} ";
        $result = $mysql->db_fetch_array($sql);
        return $result;
    }
    
    public function get_sign_status($user_id) {
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $this->db->where('user_id',$user_id);
        $this->db->where('type','sign');
        $this->db->where('addtime >=',$beginYesterday);
        $this->db->where('addtime <',$endYesterday);
        $rows = $this->db->get_where(TABLE_CREDIT_LOG)->result_array();
        
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $this->db->where('user_id',$user_id);
        $this->db->where('type','sign');
        $this->db->where('addtime >=',$beginToday);
        $this->db->where('addtime <',$endToday);
        $rows2 = $this->db->get_where(TABLE_CREDIT_LOG)->result_array();
        $result = $rows || $rows2;
        return $result;
    }

}
