<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_file
 *
 * @author win7
 */
class m_file extends CI_Model implements ObjInterface {

    public function add($data) {
        $param['user_id'] = intval($data['user_id']);
        $param['type'] = trim($data['type']);
        $param['path'] = trim($data['path']);
        $param['suffix'] = trim($data['suffix']);
        $param['size'] = intval($data['size']);
        $param['create_time'] = time();
        $param['sync_to_upyun'] = in_array(intval($data['sync_to_upyun']), array(1, 2)) ? intval($data['sync_to_upyun']) : 2;

        $param['image_width'] = intval($data['image_width']);
        $param['image_height'] = intval($data['image_height']);
        $param['org_fid'] = trim($data['org_fid']);

        $this->db->insert(TABLE_FILE, $param);
        $id = $this->db->insert_id();
        $fid = $this->create_file_id($id);
        $this->db->update(TABLE_FILE, array('fid' => $fid), array('id' => $id));
        return $fid;
    }

    public function update($id, $data) {
        foreach ($data as $k => $v) {
            switch (trim($k)) {
                case 'image_width':
                    $param['image_width'] = intval($data['image_width']);
                    break;
                case 'image_height':
                    $param['image_height'] = intval($data['image_height']);
                    break;
                case 'size':
                    $param['size'] = intval($data['size']);
                    break;
                case 'sync_to_upyun':
                    $param['sync_to_upyun'] = in_array(intval($data['sync_to_upyun']), array(1, 2)) ? intval($data['sync_to_upyun']) : 2;
                    break;
                default:
                    break;
            }
        }
        $this->db->update(TABLE_FILE, $param, array('fid' => $id));
        return $this->db->affected_rows() > 0;
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['url'] = STATIC_HOST . $detail['path'] . '/' . $detail['fid'] . '.' . $detail['suffix'];
            return new obj_file($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($page && $size) {
            $this->db->limit(intval($size), intval(($page - 1) * $size));
        }
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_FILE)->result_array();
        foreach ($rows as $k => $v) {
            $rows[$k]['url'] = STATIC_HOST . $v['path'] . '/' . $v['fid'] . '.' . $v['suffix'];
            $rows[$k] = new obj_file($rows[$k]);
        }
        return $rows;
    }

    public function count($condition) {
        
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        if ($condition['sync_to_upyun']) {
            $this->db->where('sync_to_upyun', $condition['sync_to_upyun']);
        }
        if ($condition['sync_to_upyun_status']) {
            is_array($condition['sync_to_upyun_status']) ? $this->db->where_in('sync_to_upyun_status', $condition['sync_to_upyun_status']) : $this->db->where('sync_to_upyun_status', $condition['sync_to_upyun_status']);
        }
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_FILE, array('fid' => $id))->row_array(0);
        return empty($detail) ? false : $detail;
    }

    public function create_file_id($id) {
        $sn_left = microtime(1) . $_SERVER['SERVER_ADDR'];
        if ($id > 1000) {
            //方法1,每秒不超过100w的并发数,
            $sn_right = str_pad($id % 1000000, 6, '0', STR_PAD_LEFT);
        } else {
            //方法2,每毫秒不超过1000个并发
            $str = str_pad(microtime(1) * 1000 % 1000, 3, '0', STR_PAD_LEFT);
            $sn_right = $str . str_pad($id % 1000, 3, '0', STR_PAD_LEFT);
        }
        return md5($sn_left . $sn_right);
    }

    //图片的等比缩放，指定最大边 或者指定最大宽 或最大高
    public function image_resize($filepath, $outputpath, $max = 0, $w = 0, $h = 0) {
        //取得源图片的宽度和高度和类别
        $src = getimagesize($filepath);
        $old_w = $src[0];
        $old_h = $src[1];
        $type = $src[2];
        switch ($type) {
            case 1://gif
                $img = imagecreatefromgif($filepath);
                break;
            case 2://jpg
                $img = imagecreatefromjpeg($filepath);
                break;
            case 3://png
                $img = imagecreatefrompng($filepath);
                break;
            default:
                return false;
        }

        $new_w = 0;
        $new_h = 0;

        if ($max > 0) {
            //根据最大值，算出另一个边的长度，得到缩放后的图片宽度和高度
            if ($old_w > $old_h) {
                $new_w = $max;
                $new_h = $old_h * ($max / $old_w);
            } else {
                $new_h = $max;
                $new_w = $old_w * ($max / $old_h);
            }
        } else if ($w > 0) {
            //根据最大宽，算出另一个边的长度，得到缩放后的图片宽度和高度
            $new_w = $w;
            $new_h = $old_h * ($max / $old_w);
        } else if ($h > 0) {
            //根据最大高，算出另一个边的长度，得到缩放后的图片宽度和高度
            $new_h = $h;
            $new_w = $old_w * ($max / $old_h);
        }

        //声明一个$w宽，$h高的真彩图片资源
        $image = imagecreatetruecolor($new_w, $new_h);

        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($image, $img, 0, 0, 0, 0, $new_w, $new_h, $old_w, $old_h);

        imagepng($image, $outputpath);

        //销毁资源
        imagedestroy($image);
    }

}
