<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('redirectPreviousPage'))
{
    function redirectPreviousPage()
    {
        if (isset($_SERVER['HTTP_REFERER']))
        {
            header('Location: '.$_SERVER['HTTP_REFERER']);
        }
        else
        {
            header('Location: http://'.$_SERVER['SERVER_NAME']);
        }
        
        exit;
    }
}

/*if ( ! function_exists('get_seo_single_page_info'))
{
    function get_seo_single_page_info($id)
    {
        $ci =& get_instance();
        $ci->load->database();
        
        $where = ['id' => $id];
        //SELECT QUERY
        $ci->db->select('*');
        $ci->db->from('seo_details');			
        $ci->db->where($where);        
        
        $query = $ci->db->get();

        $row = $query->num_rows();
        if($row > 0)
        {
            $row = $query->result();
            return $row;
        }
        exit;
    }
}*/