<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_model class.
 * 
 * @extends CI_Model
 */
class Login_model extends CI_Model
{
    public function __construct()
    {		
	parent::__construct();
    }
    
    public function registerUser($data)
    {
        $this->db->insert('administrator', $data);
        return $this->db->insert_id();
    }
    
    public function login($data)
    {
        $this->db->select('*');
        $this->db->from('administrator');
        $this->db->where($data);
        $query  =   $this->db->get();
        //echo $this->db->last_query(); die();
        $row = $query->num_rows();
        if ($row == 1){
            //return true;            
            return $query->result();
        }
        else{
            //return false;
            return $row;
        }
    }
}