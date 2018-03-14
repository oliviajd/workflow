<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_api_category
 *
 * @author wangyuan
 */
class m_api_database_sync extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		$database =  'localhost';
        $this->db = $this->load->database($database, true);
        // $this->load->library('cache');
    }

    /**
     * 需要同步的数据库列表
     * local: 本地 test:test.iasku.net:12306 test199 : 10.26.162.199:3306；
     */
    public function database_lists()
    {
        $lists = array('localhost'=>'127.0.0.1');

        return $lists;
    }

    /**
     * 同步数据库
     * iasku_api2_category iasku_api2_field iasku_api2_method
     */
    public function database_sync($from, $to)
    {
        $this->db1 = $this->load->database($from,true);
        $this->db2 = $this->load->database($to,true);
        $this->load->dbutil();
        $this->dbutil->db = $this->db1; // 更改 dbutil 的数据库连接为 $from

        $prefs = array(
            'tables'      => array(TABLE_API2_CATEGORY, TABLE_API2_FIELD, TABLE_API2_METHOD),  // 包含了需备份的表名的数组.
            'ignore'      => array(),           // 备份时需要被忽略的表
            'format'      => 'txt',             // gzip, zip, txt
            'filename'    => 'mybackup.sql',    // 文件名 - 如果选择了ZIP压缩,此项就是必需的
            'add_drop'    => TRUE,              // 是否要在备份文件中添加 DROP TABLE 语句
            'add_insert'  => TRUE,              // 是否要在备份文件中添加 INSERT 语句
            'newline'     => "\n"               // 备份文件中的换行符
        );

        $backup_sql = $this->dbutil->backup($prefs);

        $this->load->library('exec_sql_file');

        $arr_sql = $this->exec_sql_file->init($backup_sql);
        unset($sql);
        // dump($arr_sql);exit;

        $n = 0;
        if(!empty($arr_sql))
        {
            foreach($arr_sql as $sql)
            {
                $n++;
                $this->db2->query($sql);
            }
            unset($arr_sql, $sql);
        }

        return $n;
    }

}
