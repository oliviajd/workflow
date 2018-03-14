<?php

/**
 * 意见反馈类
 *
 */
class Feedback extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_feedback');
    }

    // 添加意见反馈
    public function add() {
        $pid = $this->m_feedback->add($this->api->in);
        $r = $this->m_feedback->detail($pid);
        $this->api->output($r);
    }
    
    // 意见反馈列表
    public function lists() {
        $page = intval($this->api->in['page']) > 0 ? intval($this->api->in['page']) : 1;
        $size = intval($this->api->in['size']) > 0 ? intval($this->api->in['size']) : 20;
        $condition = $this->api->in;
        if (!$this->api->in['order']) {
            $order = 'id desc';
        } else {
            $order = $this->api->in['order'];
        }
        $r['rows'] = $this->m_feedback->lists($condition, $page, $size, $order);
        $r['total'] = $this->m_feedback->count($condition);
        $this->api->output($r);
    }
    
    // 意见反馈详情
    public function get() {
        $detail = $this->m_feedback->detail($this->api->in['id']);
        if (!$detail) {
            $this->api->output(false, ERR_FEEDBACK_NOT_EXISTS_NO, ERR_FEEDBACK_NOT_EXISTS_MSG);
        }
        $this->api->output($detail);
    }
}
