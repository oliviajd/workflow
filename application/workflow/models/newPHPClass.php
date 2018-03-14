<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author win7
 */
class newPHPClass extends CI_Model implements ObjInterface{
    
    public function add($data) {}
    
    public function update($id,$data) {}
    
    public function detail($id) {}
    
    public function lists($condition, $page, $size, $order) {}
    
    public function count($condition) {}
    
    public function delete($condition) {}
    
    private function _condition($condition) {}
    
}
