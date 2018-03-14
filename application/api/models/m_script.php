<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of m_script
 *
 * @author win7
 */
class m_script extends CI_Model implements ObjInterface {

    const STATUS_SCRIPT_RUNNING = 1;
    const STATUS_SCRIPT_STOP = 2;

    private $_run_errors = array();

    public function add($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function detail($id) {
        $detail = $this->_detail($id);
        if (empty($detail)) {
            return false;
        } else {
            $detail['pid'] = intval(file_get_contents($detail['pid_path']));
            if ($this->is_running($detail['pid'])) {
                $status = self::STATUS_SCRIPT_RUNNING;
            } else {
                $status = self::STATUS_SCRIPT_STOP;
            }
            $detail['status'] = new obj_item($this->get_run_status($status));
            return new obj_script($detail);
        }
    }

    public function lists($condition, $page, $size, $order) {
        if ($order) {
            $this->db->order_by($order);
        }
        $this->_condition($condition);
        $rows = $this->db->get_where(TABLE_SCRIPT)->result_array();
        foreach ($rows as $k => $v) {
            $v['pid'] = intval(file_get_contents($v['pid_path']));
            if ($this->is_running($v['pid'])) {
                $status = self::STATUS_SCRIPT_RUNNING;
            } else {
                $status = self::STATUS_SCRIPT_STOP;
            }
            $v['status'] = new obj_item($this->get_run_status($status));
            $rows[$k] = new obj_script($v);
        }
        return $rows;
    }

    public function count($condition) {
        $this->db->select('count(1) as count');
        $this->_condition($condition);
        return $this->db->get_where(TABLE_SCRIPT)->row(0)->count;
    }

    public function delete($condition) {
        
    }

    private function _condition($condition) {
        
    }

    private function _detail($id) {
        $detail = $this->db->get_where(TABLE_SCRIPT, array('id' => $id))->row_array();
        return empty($detail) ? false : $detail;
    }

    public function get_run_status($key) {
        $data = array(
            1 => '运行中',
            2 => '停止',
        );
        return isset($data[$key]) ? array('id' => $key, 'text' => $data[$key]) : array('id' => 'SCRIPT_RUN_STATUS_ERROR', 'text' => '脚本运行状态错误');
    }

    //检查pid对应的线程是否在执行 linux 下可用
    public function is_running($pid) {
        return $pid ? is_dir('/proc/' . intval($pid)) : false;
    }

    //通过脚本ID 执行脚本
    public function run($id) {
        $detail = $this->detail($id);
        if (!$detail) {
            return false;
        }
        if (!$this->is_running($detail->pid)) {
            //运行
            $address = '127.0.0.1';
            $service_port = 21567;
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                do_Log("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
            }
            $result = socket_connect($socket, $address, $service_port);
            if ($result === false) {
                do_log("socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n");
            }
            $in = 'nohup ' . $detail->cmd . ' > /tmp/null &';
            do_log($in);
            //发送命令
            socket_write($socket, $in, strlen($in));
            //获取PID
            $out = socket_read($socket, 1024, PHP_NORMAL_READ);
            do_log($out);
            socket_close($socket);
            $obj = json_decode($out, true);
            if ($obj['error_no'] == 200) {
                $pid = $obj['result']['pid'] = 0;
                $this->db->update(TABLE_SCRIPT, array('pid' => $pid, 'last_start_time' => time(), 'error_start' => ''), array('id' => $id));
                return true;
            } else {
                $this->_run_errors[$id] = $obj['result'];
                $this->db->update(TABLE_SCRIPT, array('error_start' => $obj['result']), array('id' => $id));
                return false;
            }
        } else {//程序已运行则不重新执行
            return false;
        }
    }

    public function last_run_error($id) {
        return $this->_run_errors[$id];
    }

    //通过脚本ID 关闭脚本
    public function stop($id) {
        
    }

    //通过脚本ID 重启脚本
    public function restart($id) {
        
    }

    public function isset_key($method_name, $key) {
        return $this->db->get_where(TABLE_SCRIPT_VALUES, array('method_name' => $method_name, 'key' => $key))->row(0)->id ? true : false;
    }

    public function set_value($method_name, $key, $value) {
        if ($this->isset_key($method_name, $key)) {
            $this->db->update(TABLE_SCRIPT_VALUES, array('value' => json_encode($value)), array(
                'method_name' => $method_name,
                'key' => $key
            ));
        } else {
            $this->db->insert(TABLE_SCRIPT_VALUES, array(
                'value' => json_encode($value),
                'method_name' => $method_name,
                'key' => $key
            ));
        }
        return true;
    }

    public function get_value($method_name, $key) {
        if (!$this->isset_key($method_name, $key)) {
            throw new Exception('not exist');
        }
        return $this->db->get_where(TABLE_SCRIPT_VALUES, array('method_name' => $method_name, 'key' => $key))->row(0)->value;
    }

    public function delete_value($method_name, $key) {
        $this->db->delete(TABLE_SCRIPT_VALUES, array(
            'method_name' => $method_name,
            'key' => $key
        ));
    }

}
