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
            // $this->db->where('phone', $params['phone']);
            // $this->db->where('email', $params['email']);
            $st = "(`phone` = '" . $params['phone'] . "' OR `email` = '" . $params['email'] . "')";
            $this->db->where($st, NULL, FALSE);
            // $this->db->where_not_in('`user_id`', $params['usrId']);
        } else {
            //check for non register users
            // $this->db->where('phone', $params['phone']);
            // $this->db->or_where('email', $params['email']);
            $st = "(`phone` = '" . $params['phone'] . "' OR `email` = '" . $params['email'] . "')";
            $this->db->where($st, NULL, FALSE);
        }
        $query = $this->db->get('users');
        // echo $this->db->last_query(); die();
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

    public function validate_login($username, $password, $login_ip, $login_time, $fcm_token)
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
                    $data['fcm_token']          =   $fcm_token;
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

    public function getUsers($params = null, $many = FALSE)
    {
        $this->db->select('users.*');
        if ($params != null) {
            $this->db->where($params);
        }

        $query = $this->db->get('users');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result_array();
        } else {
            $data = $query->row_array();
        }

        return $data;
    }


    public function getCampaignUsers($params = null, $many = FALSE)
    {
        $this->db->select('campaign_master.user_id, users.*');
        if ($params != null) {
            $this->db->where($params);
        }
        $this->db->join('users', 'campaign_master.user_id=users.user_id', 'inner');

        $query = $this->db->get('campaign_master');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result_array();
        } else {
            $data = $query->row_array();
        }

        return $data;
    }

    //
    public function get_user_data($params)
    {
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('phone', $params['phone']);
        return $this->db->get()->result();
    }


    public function check_user_exist($params)
    {
        //$data['email']          =   $params['email'];
        $data['user_id']          =   $params['user_id'];
        //check for registered gmail user id
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where($data);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        return $query->row();
    }

    //Login with Google
    public function check_google_user_exist_3($params)
    {
        //$data['email']          =   $params['email'];
        $data['google_token']    =   $params['google_token'];
        //check for registered gmail user id
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where($data);
        $query = $this->db->get();
        //echo $this->db->last_query(); die();
        return $query->row();
    }

    public function check_google_user_exist($params)
    {
        $data['status']  =   '1';
        $data['email']          =   $params['email'];
        $token['google_token']    =   $params['google_token'];
        $where  =  "`google_token` != '' ";
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
        $data['status']  =   '1';
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
        //return $this->db->get('campaign_master')->result();
        $where  =   ['status' => 1, 'is_closed' => 0];
        //return $this->db->get_where('campaign_master', $where)->result();
		
		$this->db->from('campaign_master');
		$this->db->where($where);
		$this->db->order_by("campaign_id", "DESC");
		$query = $this->db->get(); 
		return $query->result();
    }
	
	public function user_campaign_list($user_id)
    {
        $where  =   ['status' => 1, 'is_closed' => 0, 'user_id' => $user_id];
        return $this->db->get_where('campaign_master', $where)->result();
    }

    public function kind_list()
    {
        $where  =   ['status' => 1];
        return $this->db->get_where('kind_master', $where)->result();
    }

    public function get_preference_list($params = null, $many = FALSE)
    {
        $this->db->select('kind_id as pref_id, kind_name as pref_name');

        if ($params != null) {
            $this->db->where($params);
        }

        $query = $this->db->get('kind_master');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result_array();
        } else {
            $data = $query->row_array();
        }

        return $data;
    }



    public function getcampaignList($params = null, $search = null, $many = TRUE)
    {
        $this->db->select('
        `campaign_master`.campaign_id,
        `donation_master`.`donation_id`,
		`campaign_master`.user_id,
		`campaign_master`.kind_id,
		`campaign_master`.campaign_name,
		`campaign_master`.donation_mode,
		`campaign_master`.campaign_details,
		`campaign_master`.zip,
		`campaign_master`.campaign_start_date,
		`campaign_master`.campaign_end_date,
		`campaign_master`.campaign_image,
		`campaign_master`.campaign_target_amount,
		`campaign_master`.campaign_target_qty,
		`campaign_master`.area_lat,
		`campaign_master`.area_long,
		`campaign_master`.state,
		`campaign_master`.status,
		`campaign_master`.created_at,
		`campaign_master`.updated_at,
		SUM(`donation_master`.`quantity`) AS `total_donation_quantity`,
        SUM(`donation_master`.`amountpaid`) AS `total_donation_amountpaid`');

        $this->db->join('donation_master', 'campaign_master.campaign_id=donation_master.campaign_id', 'left');

        if ($params != null) {
            $this->db->where($params);
        }

        if ($search != null) {
            $st = "(`campaign_master`.`campaign_name` LIKE '%" . $search . "%' OR `campaign_master`.`campaign_details` LIKE '%" . $search . "%'  OR `campaign_master`.`zip` LIKE '%" . $search . "%')";
            $this->db->where($st, NULL, FALSE);
        }

        $this->db->group_by('campaign_master.`campaign_id`');

        // $this->db->having('(case when (`campaign_master`.`donation_mode` = 1) THEN (SUM(`donation_master`.`amountpaid`) < `campaign_master`.`campaign_target_amount`) ELSE (SUM(`donation_master`.`quantity`) < `campaign_master`.`campaign_target_qty`) END)');

        $query = $this->db->get('campaign_master');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $campaignList = $query->result_array();
        } else {
            $campaignList = $query->row_array();
        }

        return $campaignList;
    }

    public function getcampaignListByPref($params = null, $kind_mon_ids = null, $money = null, $many = TRUE)
    {
        $this->db->select('
        `campaign_master`.campaign_id,
        `donation_master`.`donation_id`,
		`campaign_master`.user_id,
		`campaign_master`.kind_id,
		`campaign_master`.campaign_name,
		`campaign_master`.donation_mode,
		`campaign_master`.campaign_details,
		`campaign_master`.zip,
		`campaign_master`.campaign_start_date,
		`campaign_master`.campaign_end_date,
		`campaign_master`.campaign_image,
		`campaign_master`.campaign_target_amount,
		`campaign_master`.campaign_target_qty,
		`campaign_master`.area_lat,
		`campaign_master`.area_long,
		`campaign_master`.state,
		`campaign_master`.status,
		`campaign_master`.created_at,
		`campaign_master`.updated_at,
		SUM(`donation_master`.`quantity`) AS `total_donation_quantity`,
        SUM(`donation_master`.`amountpaid`) AS `total_donation_amountpaid`');

        $this->db->join('donation_master', 'campaign_master.campaign_id=donation_master.campaign_id', 'left');

        if ($params != null) {
            $this->db->where($params);
        }

        if ($kind_mon_ids != null) {
            if ($money != null && $money != 0) {
                $st = "(`campaign_master`.`donation_mode` = '1' OR `campaign_master`.`kind_id` in(" . $kind_mon_ids . "))";
                $this->db->where($st, NULL, FALSE);
            } else {
                $st = "`campaign_master`.`kind_id` in(" . $kind_mon_ids . ")";
                $this->db->where($st, NULL, FALSE);
            }
        }

        $this->db->group_by('campaign_master.`campaign_id`');

        //$this->db->having('(case when (`campaign_master`.`donation_mode` = 1) THEN (SUM(`donation_master`.`amountpaid`) < `campaign_master`.`campaign_target_amount`) ELSE (SUM(`donation_master`.`quantity`) < `campaign_master`.`campaign_target_qty`) END)');

        $query = $this->db->get('campaign_master');
        //echo $this->db->last_query();die();

        if ($many == TRUE) {
            $campaignList = $query->result_array();
        } else {
            $campaignList = $query->row_array();
        }

        return $campaignList;
    }

    public function getLikesCount($params = null, $many = FALSE)
    {
        $this->db->select('COUNT(`liked_campaign`.`like_status`) AS `count_likes`');

        if ($params != null) {
            $this->db->where($params);
        }
        $query = $this->db->get('liked_campaign');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result();
        } else {
            $data = $query->row();
        }

        return $data;
    }

    public function getCommentsCount($params = null, $many = FALSE)
    {
        $this->db->select('COUNT(`campaign_comments`.`comment`) AS `count_comments`');

        if ($params != null) {
            $this->db->where($params);
        }
        $query = $this->db->get('campaign_comments');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result();
        } else {
            $data = $query->row();
        }

        return $data;
    }

    public function getLikes($params = null, $many = FALSE)
    {
        $this->db->select('like_status');

        if ($params != null) {
            $this->db->where($params);
        }


        $query = $this->db->get('liked_campaign');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result();
        } else {
            $data = $query->row();
        }

        return $data;
    }

    public function getComments($params = null, $many = FALSE)
    {
        $this->db->select('*');

        if ($params != null) {
            $this->db->where($params);
        }


        $query = $this->db->get('campaign_comments');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result();
        } else {
            $data = $query->row();
        }

        return $data;
    }

    public function updateCampaign($donation_id, $params_update)
    {
        $this->db->where('donation_id', $donation_id);
        if ($this->db->update('donation_master', $params_update)) {
            return true;
        } else {
            return false;
        }
    }



    public function campaignLikeDislike($userID, $campaign_id)
    {
        $where = array('user_id' => $userID, 'campaign_id' => $campaign_id);
        $this->db->select('*');
        $this->db->from('liked_campaign');
        $this->db->where($where);
        $query = $this->db->get();
        $GetLikedDonation = $query->row_array();
        //print_obj($GetLikedDonation);die;
        $data = array();
        if (!empty($GetLikedDonation) && $GetLikedDonation['like_status'] == 1) {

            $params = array("like_status" => 2);
            $data = $this->db->where($where)->update('liked_campaign', $params);
            $data = 2;
        } else if (!empty($GetLikedDonation) && $GetLikedDonation['like_status'] == 2) {

            $params = array("like_status" => 1);
            $data = $this->db->where($where)->update('liked_campaign', $params);
            $data = 1;
        } else {
            $insertData = array('user_id' => $userID, 'campaign_id' => $campaign_id);
            $added = $this->db->insert("liked_campaign", $insertData);
            $data = 1;
        }
        return $data;
    }


    public function campaignAddComment($userID, $campaign_id, $rating, $comment)
    {
        $insertData = array('user_id' => $userID, 'campaign_id' => $campaign_id, 'rating' => $rating, 'comment' => $comment);
        $added = $this->db->insert("campaign_comments", $insertData);
        $data = $this->db->insert_id();
        return $data;
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

    public function getDonations($params = null, $many = TRUE)
    {
        $this->db->select('donation_master.*, CONCAT(users.first_name, " ", users.last_name) AS donor_name, users.pan_number');
        $this->db->join('users', 'donation_master.user_id = users.user_id', 'inner');

        if ($params != null) {
            $this->db->where($params);
        }

        $query = $this->db->get('donation_master');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result();
        } else {
            $data = $query->row();
        }

        return $data;
    }

    public function get_campaign($param = null)
    {
        if ($param != null) {
            $this->db->select('*')
                ->from('campaign_master')
                ->where($param);
            $query  =   $this->db->get();
            $result =   $query->result();
            //unset($result[0]->status);
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



    public function add_user_preferences($data)
    {
        $this->db->insert('user_preferences', $data);
        return $this->db->insert_id();
    }

    public function get_user_preferences($params = null, $many = FALSE)
    {
        $this->db->select('user_preferences.*, (case when (`user_preferences`.`selected_pref_id` = "1010") then "Money" else (`kind_master`.`kind_name`) end) as `selected_pref_name`');
        $this->db->join('kind_master', 'user_preferences.selected_pref_id = kind_master.kind_id', 'left');

        if ($params != null) {
            $this->db->where($params);
        }

        $query = $this->db->get('user_preferences');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result_array();
        } else {
            $data = $query->row_array();
        }

        return $data;
    }

    public function getFilterByType($params = null, $many = FALSE)
    {
        $this->db->select('id, name, added_dtime, status');

        if ($params != null) {
            $this->db->where($params);
        }

        $query = $this->db->get('filter_by_type');
        //echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $data = $query->result_array();
        } else {
            $data = $query->row_array();
        }

        return $data;
    }
    public function delete_user_preferences($data)
    {
        $this->db->where($data)->delete('user_preferences');
        return true;
    }

    public function get_my_favourite($id)
    {
        $where  =   ['`favourite_master`.user_id' => $id];
        //return $this->db->get_where('favourite_master', $where)->result();
		
		
		$this->db->select('
        `favourite_master`.fav_id,
		`campaign_master`.campaign_id,
		`campaign_master`.user_id,
		`campaign_master`.kind_id,
		`campaign_master`.campaign_name,
		`campaign_master`.donation_mode,
		`campaign_master`.campaign_details,
		`campaign_master`.zip,
		`campaign_master`.campaign_start_date,
		`campaign_master`.campaign_end_date,
		`campaign_master`.campaign_image,
		`campaign_master`.campaign_target_amount,
		`campaign_master`.campaign_target_qty,
		`campaign_master`.area_lat,
		`campaign_master`.area_long,
		`campaign_master`.state,
		`campaign_master`.status,
		`campaign_master`.created_at,
		`campaign_master`.updated_at
		');

        $this->db->join('campaign_master', 'favourite_master.campaign_id=campaign_master.campaign_id', 'left');

        //if ($params != null) {
            $this->db->where($where);
        //}

        /*if ($search != null) {
            $st = "(`campaign_master`.`campaign_name` LIKE '%" . $search . "%' OR `campaign_master`.`campaign_details` LIKE '%" . $search . "%'  OR `campaign_master`.`zip` LIKE '%" . $search . "%')";
            $this->db->where($st, NULL, FALSE);
        }*/

        $this->db->group_by('campaign_master.`campaign_id`');

        // $this->db->having('(case when (`campaign_master`.`donation_mode` = 1) THEN (SUM(`donation_master`.`amountpaid`) < `campaign_master`.`campaign_target_amount`) ELSE (SUM(`donation_master`.`quantity`) < `campaign_master`.`campaign_target_qty`) END)');

        $query = $this->db->get('favourite_master');
        //echo $this->db->last_query(); die();

        
        $campaignList = $query->result();

        return $campaignList;
    }


    public function getUserLikes($params = null, $many = FALSE)
    {
        $this->db->select('
        `campaign_master`.campaign_id,
		`campaign_master`.user_id,
		`campaign_master`.kind_id,
		`campaign_master`.campaign_name,
		`campaign_master`.donation_mode,
		`campaign_master`.campaign_details,
		`campaign_master`.zip,
		`campaign_master`.campaign_start_date,
		`campaign_master`.campaign_end_date,
		`campaign_master`.campaign_image,
		`campaign_master`.campaign_target_amount,
		`campaign_master`.campaign_target_qty,
		`campaign_master`.area_lat,
		`campaign_master`.area_long,
		`campaign_master`.state,
		`campaign_master`.status,
		`campaign_master`.created_at,
		`campaign_master`.updated_at,
        `liked_campaign`.liked_id,
        `liked_campaign`.like_status
        ');

        $this->db->join('campaign_master', 'campaign_master.campaign_id=liked_campaign.campaign_id', 'inner');

        if ($params != null) {
            $this->db->where($params);
        }
        $this->db->order_by('liked_campaign.`liked_id`');

        //$this->db->group_by('campaign_master.`campaign_id`');

        $query = $this->db->get('liked_campaign');
        // echo $this->db->last_query(); die();

        if ($many == TRUE) {
            $campaignList = $query->result_array();
        } else {
            $campaignList = $query->row_array();
        }

        return $campaignList;
    }

    /*****************************************  PAYMENT  *****************************************/

    public function get_demo_list()
    {
        $this->db->select('`name`');
        $this->db->from('langdemolist');
        return $this->db->get()->result();
        //echo $this->db->last_query(); die();
    }
	
	public function getDonationUserData()
    {
        $this->db->select('dm.*, u.first_name, u.last_name, u.email, u.fcm_token, cm.campaign_name');
        $this->db->from('donation_master dm');
		$this->db->join('users u', 'dm.user_id = u.user_id', 'left');
		$this->db->join('campaign_master cm', 'dm.campaign_id = cm.campaign_id', 'left');
        $this->db->where('dm.campaign_type', '1');
		$this->db->group_by(array("dm.campaign_id", "dm.user_id"));
		$this->db->order_by("dm.donation_id", "DESC");
        $query  = $this->db->get();
		//echo $this->db->last_query(); die();
        $result = $query->result_array();
		if(!empty($result)) {
        	return $result;
		} else {
			return 'not_found';
		}
    }
}
