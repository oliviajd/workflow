<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of file
 *
 * @author win7
 */
class file extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('m_file');
    }

    public function upload_form() {
        $data = array();
        $data['user_id'] = $this->api->user()->user_id;
        $data['type'] = $this->api->in['type'];
        $data['suffix'] = strtolower(pathInfo($this->api->in['file']['name'], PATHINFO_EXTENSION));
        $data['size'] = filesize($this->api->in['file']['tmp_name']);
        $path = '/data/upload/' . $data['type'] . '/' . date('Ymd') . '/';
        $data['path'] = $path;
        $data['sync_to_upyun'] = $this->api->in['sync_to_upyun'];
        mkdir(FCPATH . $path, 0777, true);
        $fid = $this->m_file->add($data);
        move_uploaded_file($this->api->in['file']['tmp_name'], FCPATH . $path . $fid . '.' . $data['suffix']);
        $file = $this->m_file->detail($fid);
        $this->api->output($file);
    }
    
    public function upload_form_notoken() {
        $data = array();
        do_log($this->api->in);
        $data['user_id'] = $this->api->user()->user_id;
        $data['type'] = $this->api->in['type'];
        $data['suffix'] = strtolower(pathInfo($this->api->in['file']['name'], PATHINFO_EXTENSION));
        $data['size'] = filesize($this->api->in['file']['tmp_name']);
        $path = '/data/upload/' . $data['type'] . '/' . date('Ymd') . '/';
        $data['path'] = $path;
        $data['sync_to_upyun'] = $this->api->in['sync_to_upyun'];
        mkdir(FCPATH . $path, 0777, true);
        $fid = $this->m_file->add($data);
        do_log($fid);
        move_uploaded_file($this->api->in['file']['tmp_name'], FCPATH . $path . $fid . '.' . $data['suffix']);
        $file = $this->m_file->detail($fid);
        do_log($file);
        $this->api->output($file);
    }

    public function image_resize() {
        //检查是否开启GD库
        if (!function_exists('gd_info')) {
            $this->api->output(false, ERR_REQUIRE_GD_NO, ERR_REQUIRE_GD_MSG);
        }
        $detail = $this->m_file->detail($this->api->in['file_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        //旧文件
        $filepath = FCPATH . parse_url($detail->url, PHP_URL_PATH);
        //判断是否为图片
        $file_info = getimagesize($filepath);
        if (!$file_info) {
            $this->api->output(false, ERR_FILE_FORMAT_ERROR_NO, ERR_FILE_FORMAT_ERROR_MSG);
        }
        //新文件
        $data = array();
        $data['user_id'] = $this->api->user()->user_id;
        $data['type'] = $detail->type;
        $data['suffix'] = $detail->suffix;
        $data['size'] = 0;
        $path = '/data/upload/image_resize/' . date('Ymd') . '/';
        $data['path'] = $path;
        $data['org_fid'] = $detail->fid;
        mkdir(FCPATH . $path, 0777, true);
        $fid = $this->m_file->add($data);
        $new_filepath = FCPATH . $path . $fid . '.' . $data['suffix'];
        $this->m_file->image_resize($filepath, $new_filepath, $max = 120);
        $new_file_info = getimagesize($new_filepath);
        $this->m_file->update($fid, array(
            'image_width' => $new_file_info[0],
            'image_height' => $new_file_info[1],
            'size' => filesize($new_filepath),
            'sync_to_upyun' => 1,
        ));
        $new_file = $this->m_file->detail($fid);
        $this->api->output($new_file);
    }

    public function download() {
        $detail = $this->m_file->detail($this->api->in['file_id']);
        if (empty($detail)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG);
        }
        ob_clean();
        $filename = $this->api->in['download_name'] ? $this->api->in['download_name'] : "{$detail->fid}.{$detail->suffix}";
        $filepath = FCPATH . parse_url($detail->url, PHP_URL_PATH);
        header("Content-type:  application/octet-stream ");
        header("Accept-Ranges:  bytes ");
        header("Content-Disposition:  attachment;  filename= {$filename}");
        $size = filesize($filepath);
        header("Accept-Length: " . $size);
        readfile($filepath);
    }

}
