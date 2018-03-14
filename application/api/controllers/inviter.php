<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of invite
 *
 * @author win7
 */
class inviter extends CI_controller {

    public function change_manager() {
        $this->load->model('m_user');
        $this->load->model('m_inviter');

        $user_id = $this->api->in['user_id'];
        $manager_user_id = $this->api->in['manager_user_id'];

        $user = $this->m_user->detail($user_id);
        if (empty($user)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG . '[用户]');
        }
        $manager = $this->m_inviter->detail($manager_user_id);
        if (empty($manager)) {
            $this->api->output(false, ERR_ITEM_NOT_EXISTS_NO, ERR_ITEM_NOT_EXISTS_MSG . '[管理员]');
        }
        $r = $this->m_inviter->change_manager($user_id, $manager_user_id);
        $this->api->output($r);
    }

}
