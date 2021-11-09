<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Admin
 *
 */

class Frontend_model extends CI_Model
{
    function __construct()
    {
       $this->load->database();
    }
    
    function numrowsPackage($table)
    {
        $fieldName = $table.'.status';
        
        $where = [ $fieldName => '1' ];
        $this->db->select('*');
        $this->db->from($table);
        $this->db->where($where);
        $query = $this->db->get();
        return  $query->num_rows();
    }
    
    /**************************************** DOMESTIC FUNCTIONS ****************************************/
    
    function domesticHomePackage()
    {
        $where = ['domestic_package.status' => '1'];
        $this->db->select('*');
        $this->db->from('domestic_package');
        $this->db->where($where);
        $this->db->limit(4, 0);
        $this->db->order_by("created_at", "desc");
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            return $row;
        }
    }
    
    /*function domesticPackage()
    {
        $where = ['domestic_package.status' => '1'];
        $this->db->select('*');
        $this->db->from('domestic_package');
        $this->db->where($where);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            return $row;
        }
    }*/
    
    function domesticPackage($limit, $offset)
    {
        $where = ['domestic_package.status' => '1'];
        $this->db->select('*');
        $this->db->from('domestic_package');
        $this->db->where($where);
        $this->db->limit($limit, $offset);
        $this->db->order_by("created_at", "desc");
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            return $row;
        }
    }
    
    function domesticPackageDetails($id)
    {
        $query  =   $this->db->get_where('domestic_package', array('id' => $id));
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    function domesticDaysIternary($id)
    {
        $where = ['`domestic_days_iternary.package_id`' => $id];
        $this->db->select('`domItrNo`,`days`');
        $this->db->from('`domestic_days_iternary`');
        $this->db->where($where);
        $query = $this->db->get();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    function domesticPackageMultiImages($id)
    {
        //$query  =   $this->db->get_where('package_related_images', array('package_id' => $id));
        $where = ['domestic_related_images.package_id' => $id];
        $this->db->select('`file_name`');
        $this->db->from('`domestic_related_images`');
        $this->db->where($where);
        $query = $this->db->get();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    /**************************************** INTERNATIONAL FUNCTIONS ****************************************/
    
    function internationalHomePackage()
    {
        $where = ['international_package.status' => '1'];
        $this->db->select('*');
        $this->db->from('international_package');
        $this->db->where($where);
        $this->db->limit(4, 0);
        $this->db->order_by("created_at", "desc");
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            return $row;
        }
    }
    
    function internationalPackage($limit, $offset)
    {
        $where = ['international_package.status' => '1'];
        $this->db->select('*');
        $this->db->from('international_package');
        $this->db->where($where);
        $this->db->limit($limit, $offset);
        $this->db->order_by("created_at", "desc");
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            return $row;
        }
    }
    
    /*function internationalPackage()
    {
        $where = ['international_package.status' => '1'];
        $this->db->select('*');
        $this->db->from('international_package');
        $this->db->where($where);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            return $row;
        }
    }*/
    
    function internationalPackageDetails($id)
    {
        $query  =   $this->db->get_where('international_package', array('id' => $id));
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    function internationalDaysIternary($id)
    {
        $where = ['`international_days_iternary.package_id`' => $id];
        $this->db->select('`inerItrNo`,`days`');
        $this->db->from('`international_days_iternary`');
        $this->db->where($where);
        $query = $this->db->get();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    function internationalPackageMultiImages($id)
    {
        //$query  =   $this->db->get_where('package_related_images', array('package_id' => $id));
        $where = ['international_related_images.package_id' => $id];
        $this->db->select('`file_name`');
        $this->db->from('`international_related_images`');
        $this->db->where($where);
        $query = $this->db->get();
        $row    =   $query->num_rows();
        if ($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }     
    
    /******************************************** BESTBUY FEATURED MODULE ********************************************/
    
    public function bestBuyFeaturedDomestic()
    {
        $where = ['domestic_package.status' => 1, 'domestic_package.tourFeatured' => 1];
        $this->db->select('*');
        $this->db->from('`domestic_package`');
        $this->db->where($where);
        $query = $this->db->get();
        $row    =   $query->num_rows();
        if($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    public function bestBuyFeaturedInternational()
    {
        $where = ['international_package.status' => 1, 'international_package.tourFeatured' => 1];
        $this->db->select('*');
        $this->db->from('`international_package`');
        $this->db->where($where);
        $query = $this->db->get();
        $row    =   $query->num_rows();
        if($row > 0)
        {
            //return true;
            //$row['id']  =   $id;
            $row        =   $query->result();
            return  $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    /******************************************** INCLUDE MODULE ********************************************/
    
    public function getIncludeList()
    {
        //$query = $this->db->get('tourisms', ['tourCategory' => '0']);
        
        $this->db->select('*');
        $this->db->from('package_features');
        $this->db->where('featureMode', 1);
        $query  =   $this->db->get();
        
        $row = $query->num_rows();
        if ($row > 0)
        {
            //return true;            
            $row = $query->result();
            return $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
    
    /******************************************** EXCLUDE MODULE ********************************************/
    
    public function getExcludeList()
    {
        //$query = $this->db->get('tourisms', ['tourCategory' => '0']);
        
        $this->db->select('*');
        $this->db->from('package_features');
        $this->db->where('featureMode', 0);
        $query  =   $this->db->get();
        
        $row = $query->num_rows();
        if ($row > 0)
        {
            //return true;            
            $row = $query->result();
            return $row;            
        }
        else
        {
            //return false;
            return $row;
        }
    }
}