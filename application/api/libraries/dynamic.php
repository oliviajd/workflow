<?php

/**
 * 题目，视频，分享， 答疑 ... 动态
 */
class dynamic
{

    var $module = null;

    /**
     * 初始化类
     */
    public function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
        $this->router = $CI->router;
        $this->module = $module;
    }

    /**
     * 设置模型类型
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * 设置 ID (question.id, video.id, ask.id, shall.id)
     */
    public function setItemId($item_id)
    {
        $this->item_id = $item_id;
    }

    /**
     * $module 取值 1.question 2.video 3.paper 4.ask 5.share
     */
    public function getDynamic($module = '', $item_id = '')
    {
        if($module) $this->module = $module;
        if($item_id) $this->item_id = $item_id;

        if(isset($this->module) && isset($this->item_id))
        {
            $this->db->select('views, favorites, praises');
            $this->db->where('type', $this->module);
            $this->db->where('item_id', $this->item_id);
            return $this->db->get_where(TABLE_DYNAMIC)->row_array(0);
        }
        else
        {
            return false;
        }
    }

    /**
     * 插入新的动态内容 add update
     */
    public function SaveDynamic($data, $config = false)
    {
        //TODO 每次浏览，每次收藏，每次点赞，更新在这里
    }

    /**
     * 初始化所有的题目动态
     */
    public function initDynamicQuestion()
    {
        $count = $this->db->select('count(*) as count')->from(TABLE_QUESTION)->get()->row(0)->count;

        // $count = 10000;
        // echo $count;exit;
        for($i=1; $i<=ceil($count/1000); $i++)
        {
            $this->db->limit(1000, intval(($i - 1) * 1000));
            $this->db->select('question_id, views, collects, praises');
            $r = $this->db->get(TABLE_QUESTION)->result_array();
            // echo $this->db->last_query();
            if(!empty($r))
            {
                $d = array();
                foreach($r as $k=>$v)
                {
                    $_data = array();
                    // dump($v);exit;
                    $_data['views'] = $v['views'];
                    $_data['favorites'] = $v['collects'];
                    $_data['praises'] = $v['praises'];
                    $_data['type'] = 1; // 1.question; 2.video; 3.ask; ...
                    $_data['item_id'] = $v['question_id'];
                    $d[] = $_data;
                }
                $sql = insertArrSql(TABLE_DYNAMIC, $d);
                $this->db->query($sql);

                unset($r, $d);
            }
        }
        echo 'question dynamic ok\n';
    }

    /**
     * 初始化所有的视频动态 脚本执行一次
     */
    public function initDynamicVideo()
    {
        $count = $this->db->select('count(*) as count')->from(TABLE_VIDEO)->get()->row(0)->count;

        // $count = 10000;
        // echo $count;exit;
        for($i=1; $i<=ceil($count/1000); $i++)
        {
            $this->db->limit(1000, intval(($i - 1) * 1000));
            $this->db->select('video_id, views, collects, praises');
            $r = $this->db->get(TABLE_VIDEO)->result_array();
            // echo $this->db->last_query();
            if(!empty($r))
            {
                $d = array();
                foreach($r as $k=>$v)
                {
                    $_data = array();
                    // dump($v);exit;
                    $_data['views'] = $v['views'];
                    $_data['favorites'] = $v['collects'];
                    $_data['praises'] = $v['praises'];
                    $_data['type'] = 2; // 1.question; 2.video; 3.ask; ...
                    $_data['item_id'] = $v['video_id'];
                    $d[] = $_data;
                }
                $sql = insertArrSql(TABLE_DYNAMIC, $d);
                $this->db->query($sql);

                unset($r, $d);
            }
        }
        echo 'video dynamic ok\n';
    }

    /**
     * 初始化所有的试卷动态
     */
    public function initDynamicPaper()
    {
        $count = $this->db->select('count(*) as count')->from(TABLE_PAPER)->get()->row(0)->count;

        // echo $count;exit;
        for($i=1; $i<=ceil($count/1000); $i++)
        {
            $this->db->limit(1000, intval(($i - 1) * 1000));
            $this->db->select('paper_id, views, collects');
            $r = $this->db->get(TABLE_PAPER)->result_array();
            // echo $this->db->last_query();
            if(!empty($r))
            {
                $d = array();
                foreach($r as $k=>$v)
                {
                    $_data = array();
                    // dump($v);exit;
                    $_data['views'] = $v['views'];
                    $_data['favorites'] = $v['collects'];
                    $_data['praises'] = 0;
                    $_data['type'] = 3; // 1.question; 2.video; 3.paper; 4.ask; ...
                    $_data['item_id'] = $v['paper_id'];
                    $d[] = $_data;
                }
                $sql = insertArrSql(TABLE_DYNAMIC, $d);
                $this->db->query($sql);

                unset($r, $d);
            }
        }
        echo 'paper dynamic ok\n';
    }


}

?>
