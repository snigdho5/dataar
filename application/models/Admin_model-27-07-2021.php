<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_model class.
 * 
 * @extends CI_Model
 */
class Admin_model extends CI_Model
{
    public function __construct()
    {		
        parent::__construct();
    }
    
    /********************************************   PROFILE   ********************************************/
    
    public function getReplacedSingleImgName($email)
    {
        $this->db->select('`profile_image`')
                ->from('administrator')
                ->where('`email_address`', $email);
        $query  =   $this->db->get();
        
        $row    =   $query->num_rows();
        if ($row > 0){
            //return true;            
            $row    =   $query->result();
            $row    =   $row[0]->profile_image;
            return $row;
        }
        else{
            //return false;
            return $row;
        }
    }
    
    public function get_profile_data($email)
    {
        $query  =   $this->db->get_where('administrator', array('email_address' => $email));
        $row    =   $query->num_rows();
        if ($row > 0){
            return $query->result();
        }
        else{
            return [];
        }
    }
    
    public function updateprofile($email,$data)
    {
        $this->db->set($data);
        $this->db->where('email_address',$email);
        $this->db->update('administrator',$data);        
        /*$query = $this->db->last_query();
        echo $query;*/        
        return $this->db->affected_rows();
    }
    
    public function changePassword($data,$confirmPassword)
    {
        $query  =   $this->db->get_where('administrator', $data);
        $row    =   $query->num_rows();
        if($row > 0){
            $email  =   $data['email_address'];
            unset($data['email_address']);
            $data['password']   =   password_hash($confirmPassword, PASSWORD_BCRYPT);            
            $this->db->set($data);
            $this->db->where('email_address',$email);
            $this->db->update('administrator',$data);
            return $this->db->affected_rows();
        }
        else{
            return false;
        }
    }
    
    /********************************************   PROFILE   ********************************************/
    
    /********************************************   DTB   ********************************************/
    
    public function fetch_dtb_data($alias)
    {
        return $this->db->get($alias);
    }
    
    public function fetch_dtb_campaign()
    {
        $this->db->select('*')
                ->from('campaign_master')
                ->join('users', 'campaign_master.campaign_id = users.user_id', 'left');
        return $this->db->get();
    }
    
    public function fetch_data_by_kind()
    {
        $this->db->select('
                    CONCAT(`users`.`first_name`, `users`.`last_name`) as user,
                    `users`.`phone`,
                    `kind_master`.`kind_name` as kind,
                    `donation_master`.`quantity`,
                    `campaign_master`.`campaign_name` as campaign,
                    `campaign_master`.`campaign_start_date` as start,
                    `campaign_master`.`campaign_end_date` as expiry,                    
                ')
                ->from('donation_master')
                ->where('`kind_master`.`kind_name` is NOT NULL', NULL, FALSE)
                ->where_not_in('`donation_master`.`quantity`',0)
                ->join('campaign_master', 'donation_master.campaign_id = campaign_master.campaign_id')
                ->join('kind_master', 'donation_master.kind_id = kind_master.kind_id')
                ->join('users', 'donation_master.user_id = users.user_id');
        return  $this->db->get();
    }
    
    public function fetch_data_by_cash()
    {
        $this->db->select('
                    CONCAT(`users`.`first_name`, `users`.`last_name`) as user,
                    `users`.`phone`,
                    `donation_master`.`amountpaid` as donation_amount,
                    `campaign_master`.`campaign_name` as campaign,
                    `campaign_master`.`campaign_start_date` as start,
                    `campaign_master`.`campaign_end_date` as expiry,                    
                ')
                ->from('donation_master')
                ->where_not_in('`donation_master`.`amountpaid`',0)
                ->join('users', 'donation_master.user_id = users.user_id')
                ->join('campaign_master', 'donation_master.campaign_id = campaign_master.campaign_id', 'left');
        return $this->db->get();
        //echo $this->db->last_query();
    }

    /********************************************   DTB   ********************************************/
    
    /********************************************   KIND   ********************************************/
    
    public function insert_kind($data)
    {
        $this->db->insert('kind_master', $data);
        return $this->db->insert_id();
    }
    
    public function edit_kind($id)
    {
        $query  =   $this->db->get_where('kind_master', ['kind_id' => $id]);
        $row    =   $query->num_rows();
        if($row > 0){
            return  $query->result();            
        }
        else{
            return [];
        }
    }
    
    public function update_kind($id,$data)
    {
        $this->db->set($data);
        $this->db->where('kind_id',$id);
        $this->db->update('kind_master',$data);
        return $this->db->affected_rows();
    }


    /********************************************   KIND   ********************************************/
    
    /********************************************   CAMPAIGN   ********************************************/
    
    public function edit_campaign($id)
    {
        $this->db->select('*')
                ->from('campaign_master')
                ->join('users', 'campaign_master.user_id = users.user_id')
                ->where('`campaign_master`.`campaign_id`', $id);        
        $query  =   $this->db->get();
        //$query  =   $this->db->get_where('campaign_master', ['campaign_id' => $id]);
        $row    =   $query->num_rows();
        if($row > 0){
            return  $query->result();            
        }
        else{
            return [];
        }
    }
    
    public function update_campaign($id,$data)
    {
        $this->db->set($data);
        $this->db->where('campaign_id',$id);
        $this->db->update('campaign_master',$data);
        return $this->db->affected_rows();
    }
    
    /********************************************   CAMPAIGN   ********************************************/
    
    /********************************************   USER   ********************************************/
    
    public function edit_user($id)
    {
        $query  =   $this->db->get_where('users', ['user_id' => $id]);
        $row    =   $query->num_rows();
        if($row > 0){
            return  $query->result();            
        }
        else{
            return [];
        }
    }
    
    public function update_user($id,$data)
    {
        $this->db->set($data);
        $this->db->where('user_id',$id);
        $this->db->update('users',$data);
        return $this->db->affected_rows();
    }
    
    /********************************************   USER   ********************************************/


    /********************************************   CMS   ********************************************/

    public function fetch_cms_data($data){
        $query  =   $this->db->get_where('cms_master', ['id' => $data]);
        $row    =   $query->num_rows();
        if($row > 0){
            return  $query->result();            
        }
        else{
            return [];
        }
    }

    public function update_cms($id,$data){
        $this->db->set($data);
        $this->db->where('id',$id);
        $this->db->update('cms_master',$data);
        return $this->db->affected_rows();
    }

    /********************************************   CMS   ********************************************/
}