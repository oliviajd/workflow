<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author win7
 */
interface ObjInterface {
    
    public function add($data) ;
    
    public function update($id,$data) ;
    
    public function detail($id) ;
    
    public function lists($condition, $page, $size, $order) ;
    
    public function count($condition) ;
    
    public function delete($id) ;
    
}
