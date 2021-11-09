<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin_model class.
 * 
 * @extends CI_Model
 */
class Api_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    /*****************************************  REGISTRATION & LOGIN    *****************************************/

    public function check_user_exists($params)
    {
        //check for profile update
        if (array_key_exists("usrId", $params)) {
            $this->db->where('phone', $params['phone']);
            $this->db->where('email', $params['email']);
            $this->db->where_not_in('`user_id`', $params['usrId']);
        } else {
            //check for non register users
            $this->db->where('phone', $params['phone']);
            $this->db->or_where('email', $params['email']);
        }
        $query = $this->db->get('users');
        //echo $this->db->last_query(); die();
        if ($query->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function insert_new_user($data)
    {
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    public function validate_login($username, $password, $login_ip, $login_time)
    {
        $this->db->select('*');
        $this->db->from('users');
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $this->db->where('email', $username);
        } else {
            $this->db->where('phone', $username);
        }
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $result = $query->row();
        if (!empty($result)) {
            $this->db->select('*');
            $this->db->from('users');
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $this->db->where(['email' => $username, 'password' => md5($password)]);
            } else {
                $this->db->where(['phone' => $username, 'password' => md5($password)]);
            }
            $query      =   $this->db->get();
            $result     =   $query->row();
            if (!empty($result)) {
                if ($result->status == 1) {
                    $data['last_login_time']    =   $login_time;
                    $data['last_login_ip']      =   $login_ip;
                    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                        $this->db->where('email', $username);
                    } else {
                        $this->db->where('phone', $username);
                    }
                    if ($this->db->update('users', $data)) {
                        if ($result->profile_img) {
                            $result->profile_img = base_url() . 'uploads/profile_images/' . $result->profile_img;
                        } else {
                            $result->profile_img = base_url() . 'uploads/profile_images/default.png';
                        }
                        unset($result->password);
                        return $result;
                    } else {
                        return 'cannot_validate';
                    }
                } else {
                    return 'inactive';
                }
            } else {
                return 'password_incorrect';
            }
        } else {
            return 'not_found';
        }
    }

    public function check_user_otp_status($params)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where(['phone' => $params['phone']]);
        $query      =   $this->db->get();
        $result     =   $query->row();
        if (!empty($result)) {
            $Userid = $result->user_id;
            $this->db->select('*');
            $this->db->from('users');
            $this->db->where(['user_id' => $Userid, 'otp' => $params['otp']]);
            $querycheck     =   $this->db->get();
            $resultrow      =   $querycheck->num_rows();
            if ($resultrow > 0) {
                $otparray   =   ['status' => '1'];
                $this->db->where('user_id', $Userid);
                $this->db->update('users', $otparray);
                $query      =   $this->db->get('users');
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function check_mobile_number_exist($params)
    {
        $this->db->where('phone', $params['phone']);
        $query  =   $this->db->get('users');
        $c      =   $query->num_rows();
        if ($c > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function update_user_otp($params, $params_update)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where(['phone' => $params['phone']]);
        $query  = $this->db->get();
        $result = $query->row();
        if (!empty($result)) {
            $Userid = $result->user_id;
            $this->db->where('user_id', $Userid);
            if ($this->db->update('users', $params_update)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function get_email_id_otp($params)
    {
        $this->db->select('email');
        $this->db->from('users');
        $this->db->where('phone', $params['phone']);
        return $this->db->get()->result();
    }

    public function get_email($params = null)
    {
        $this->db->select('email');
        $this->db->from('users');

        if ($params != null) {
            $this->db->where($params);
        }

        return $this->db->get()->result();
    }


    public function getUser($params = null)
    {
        $this->db->select('*');
        $this->db->from('users');

        if ($params != null) {
            $this->db->where($params);
        }

        return $this->db->get()->result();
    }

    //
    public function get_user_data($params)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('phone', $params['phone']);
        return $this->db->get()->result();
    }

    //Login with Google
    public function check_google_user_exist($params)
    {
        $data['email']          =   $params['email'];
        //$token['google_token']    =   $params['google_token'];
        //check for registered gmail user id
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where($data);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        return $query->row();
    }

    public function check_google_user_exist_backup($params)
    {
        $data['email']          =   $params['email'];
        $token['google_token']    =   $params['google_token'];
        $where                  =   "`google_token` != '' ";
        //check for registered gmail user id
        $this->db->select('email');
        $this->db->from('users');
        $this->db->where($data);
        $this->db->where($where);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $numRows    =   $query->num_rows();

        if ($this->db->get_where('users', $params)->num_rows()) {
            return $this->db->get_where('users', $params)->result_array();
        } else {
            //update google token of pre registered users
            if ($numRows) {
                $this->db->where('email', $data['email'])->update('users', $token);
                return $this->db->get_where('users', $params)->result_array();
            } else {
                return false;
            }
        }
    }

    //Login with Facebook
    public function check_facebook_user_exist($params)
    {
        $data['email']      =    $params['email'];
        $token['fb_token']  =    $params['fb_token'];
        $where              =   "`fb_token` != ''";
        //check for registered gmail user id
        $this->db->select('email')
            ->from('users')
            ->where($data)
            ->where($where);
        $query = $this->db->get();
        //echo $this->db->last_query(); die;
        $numRows    =   $query->num_rows();
        if ($this->db->get_where('users', $params)->num_rows()) {
            return $this->db->get_where('users', $params)->result_array();
        } else {
            //update google token of pre registered users
            if ($numRows) {
                $this->db->where('email', $data['email']);
                $this->db->update('users', $token);
                return true;
            } else {
                return false;
            }
        }
    }

    //Login with Apple
    public function check_apple_user_exist($params)
    {
        $data['email']      =    $params['email'];
        $token['apple_id']  =   $params['apple_id'];
        $where              =   "`apple_id` !=''";
        //check for registered gmail user id
        $this->db->select('email')
            ->from('users')
            ->where($data)
            ->where($where);
        $query = $this->db->get();
        //echo $this->db->last_query(); die;
        $numRows    =   $query->num_rows();

        if ($this->db->get_where('users', $params)->num_rows()) {
            return $this->db->get_where('users', $params)->result_array();
        } else {
            //update apple id of pre registered users
            if ($numRows) {
                $this->db->where('email', $data['email'])->update('users', $token);
                return true;
            } else {
                return false;
            }
        }
    }

    public function validate_current_profile($user_id)
    {
        $check_is_null  =   'kyc_file IS NULL';
        $this->db->select('*')
            ->from('users')
            ->where($check_is_null)
            ->where('`users`.user_id', $user_id);
        return $this->db->get()->num_rows();
    }


    /*****************************************  REGISTRATION & LOGIN    *****************************************/

    /*****************************************  PUSH NOTIFICATION   *****************************************/

    public function register_device_notification($params)
    {
        $data['user_id']            =   $params['user_id'];
        $token['firebase_token']    =   $params['firebase_token'];

        //check for device registered
        $this->db->select('firebase_token')
            ->from('push_notification')
            ->where($token);
        //$this->db->where($where);
        $query     =    $this->db->get();
        //echo $this->db->last_query(); die();
        $numRows     =    $query->num_rows();
        if (!$numRows) {
            $this->db->insert('push_notification', $params);
            return true;
        } else {
            $this->db->where('firebase_token', $params['firebase_token']);
            $this->db->update('push_notification', $data);
            return true;
        }
    }

    /*****************************************  PUSH NOTIFICATION   *****************************************/

    /*****************************************  PROFILE  *****************************************/

    public function my_profile($user_id)
    {
        $this->db->select('*')
            ->from('users')
            ->where('users.user_id', $user_id);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        $result = $query->row();
        //Normal user Login data
        if (!empty($result)) {
            if ($result->status == 1) {
                unset($result->password);
                unset($result->status);
                unset($result->kyc_verifed);
                return $result;
            } else {
                return 'user_inactive';
            }
        } else {
            //Social Login data
            $this->db->select('*')
                ->from('users')
                ->where('users.user_id', $user_id);
            $query = $this->db->get();
            //echo $this->db->last_query(); die();
            if ($query->num_rows()) {
                return $query->row();
            } else {
                return 'user_not_found';
            }
        }
    }

    public function update_user_profile_info($id, $params)
    {
        $this->db->where('user_id', $id)->update('users', $params);
        return $this->db->affected_rows();
    }

    public function get_user_profile_info($id)
    {
        $this->db->select('*')
            ->from('users')
            ->where('users.user_id', $id);
        $query  =   $this->db->get();
        return $query->row_array();
    }

    public function is_user_verified($id)
    {
        $this->db->select('`kyc_verified`')
            ->from('users')
            ->where('`users`.`user_id`', $id);
        return $this->db->get()->result()[0]->kyc_verified;
    }

    public function auth_to_create_camp($id)
    {
        $this->db->select('`camp_auth`')
            ->from('users')
            ->where('`users`.`user_id`', $id);
        return $this->db->get()->result()[0]->camp_auth;
    }


    /*****************************************  PROFILE  *****************************************/

    /*****************************************  CAMPAIGN  *****************************************/

    public function campaign_list()
    {
        //$where  =   ['status' => 1];
        return $this->db->get('campaign_master')->result();
        // $where  =   ['status' => 1];
        // return $this->db->get_where('campaign_master',$where)->result();
    }

    public function kind_list()
    {
        $where  =   ['status' => 1];
        return $this->db->get_where('kind_master', $where)->result();
    }

    public function insert_new_campaign($data)
    {
        $this->db->insert('campaign_master', $data);
        return $this->db->insert_id();
    }

    public function update_campaign($id, $params)
    {
        $this->db->where('campaign_id', $id)->update('campaign_master', $params);
        return $this->db->affected_rows();
    }

    public function update_single_campaign($id, $params)
    {
        $this->db->where('campaign_id', $id)->update('campaign_master', $params);
        return $this->db->affected_rows();
    }

    public function get_single_campaign($id)
    {
        $this->db->select('*')
            ->from('campaign_master')
            ->where('campaign_master.campaign_id', $id);
        $query  =   $this->db->get();
        $result =   $query->result();
        unset($result[0]->status);
        return $result;
    }

    public function get_campaign($param = null)
    {
        if ($param != null) {
            $this->db->select('*')
                ->from('campaign_master')
                ->where($param);
            $query  =   $this->db->get();
            $result =   $query->result();
            unset($result[0]->status);
            return $result;
        } else {
            return false;
        }
    }

    /*****************************************  CAMPAIGN  *****************************************/

    /*****************************************  PAYMENT  *****************************************/

    public function insert_new_payment($data)
    {
        $this->db->insert('donation_master', $data);
        return $this->db->insert_id();
    }

    public function fetch_data_by_kind($id)
    {
        $this->db->select('
                    `kind_master`.`kind_name` as kind,
                    `donation_master`.`quantity`,
                    `campaign_master`.`campaign_name` as campaign,
                    `campaign_master`.`campaign_start_date` as start,
                    `campaign_master`.`campaign_end_date` as expiry,                    
                ')
            ->from('donation_master')
            ->where('`donation_master`.`user_id`', $id)
            ->where('`kind_master`.`kind_name` is NOT NULL', NULL, FALSE)
            ->where_not_in('`donation_master`.`quantity`', 0)
            ->join('campaign_master', 'donation_master.campaign_id = campaign_master.campaign_id', 'left')
            ->join('kind_master', 'donation_master.kind_id = kind_master.kind_id', 'left');
        $query  =   $this->db->get();
        return $query->result();
    }

    public function fetch_data_by_cash($id)
    {
        $this->db->select('
                    `donation_master`.`amountpaid` as donateAmount,
                    `campaign_master`.`campaign_name` as campaign,
                    `campaign_master`.`campaign_start_date` as start,
                    `campaign_master`.`campaign_end_date` as expiry,                    
                ')
            ->from('donation_master')
            ->where('`donation_master`.`user_id`', $id)
            ->where_not_in('`donation_master`.`amountpaid`', 0)
            ->join('campaign_master', 'donation_master.campaign_id = campaign_master.campaign_id', 'left');
        $query  =   $this->db->get();
        return $query->result();
    }


    /*****************************************  PAYMENT  *****************************************/

    /*****************************************  PAYMENT  *****************************************/

    public function add_my_favourite($data)
    {
        //$this->db->insert('favourite_master', $data);
        //return $this->db->insert_id();        
        if ($this->db->get_where('favourite_master', $data)->num_rows()) {
            $this->db->where($data)->delete('favourite_master');
            return false;
        } else {
            $this->db->insert('favourite_master', $data);
            return $this->db->insert_id();
        }
    }

    public function get_my_favourite($id)
    {
        $where  =   ['user_id' => $id];
        return $this->db->get_where('favourite_master', $where)->result();
    }

    /*****************************************  PAYMENT  *****************************************/

    public function get_demo_list()
    {
        $this->db->select('`name`');
        $this->db->from('langdemolist');
        return $this->db->get()->result();
        //echo $this->db->last_query(); die();
    }
}
