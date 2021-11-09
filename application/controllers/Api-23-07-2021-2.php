<?php
defined('BASEPATH') or exit('No direct script access allowed');
require(APPPATH . '/libraries/REST_Controller.php');

use Restserver\Libraries\REST_Controller;

class Api extends REST_Controller
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     *	- or -
     * 		http://example.com/index.php/welcome/index
     *	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['form_validation', 'encryption', 'encrypt', 'session', 'javascript', 'image_lib', 'pagination', 'Authorization_Token']);
        $this->load->helper(['url', 'form', 'date', 'admin_helper']);
        // $this->encryption->create_key(16);?
        $this->load->model(['api_model']);
        $this->gallery_path = realpath(APPPATH . '../uploads');
    }
    /*************************************************  REGISTRATION & LOGIN    *************************************************/

    public function verify_token()
    {
        $headers        =   $this->input->request_headers();
        @$decodeToken   =   $this->authorization_token->validateToken($headers);

        //echo $headers['Status'];
        // print_obj($headers);
        // print_obj($decodeToken);die;
        return $decodeToken;
    }


    //user register module
    public function register_post()
    {
        $postData = $this->input->post();
        //echo "<pre>"; print_r($postData);

        if (isset($postData) && !empty($postData)) {

            $firstname     = $this->input->post('firstname', TRUE);
            $lastname     = $this->input->post('lastname', TRUE);
            $email         = $this->input->post('email', TRUE);
            $phone         = $this->input->post('phone', TRUE);
            $password     = $this->input->post('password', TRUE);
            $usertype     = $this->input->post('usertype', TRUE);
            $device_id    = $this->input->post('device_id', TRUE);
            $device_type = $this->input->post('device_type', TRUE);
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);
            //echo "<pre>"; print_r($postData); exit();
            $firstname     = $postData['firstname'];
            $lastname     = $postData['lastname'];
            $email         = $postData['email'];
            $phone         = $postData['phone'];
            $password     = $postData['password'];
            $usertype     = $postData['usertype'];
            $device_id     = $postData['device_id'];
            $device_type = $postData['device_type'];
        }
        //exit();

        if ($firstname != '' && $lastname != '' && $email != '' && $phone != '' && $password != '') {
            $out_message = array();
            //email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // $out_message = $email . " is a valid email address" ;
                $if_email = 1; //email valid
            } else {
                $if_email = 0;
                $out_msg[] = array('statuscode' => '200', 'status' => 'failure', 'message' => 'email validation failed');
                //$out_message += array('valid_email' => $email . " is not a valid email address");
            }

            //phone with 10 digit
            if (preg_match('/^[0-9]{10}+$/', $phone)) {
                //$out_message = $phone . " is a valid phone" ;
                $if_phone = 1; //phone valid

                if (isset($out_msg)) {
                    $out_message = $out_msg;
                }
            } else {
                $if_phone = 0;
                $out_msg2[] = array('statuscode' => '200', 'status' => 'failure', 'message' => 'phone validation failed');

                if (isset($out_msg)) {
                    $out_message = array_merge($out_msg, $out_msg2);
                } else {
                    $out_message = $out_msg2;
                }

                //$out_message += array('valid_phone' => $phone . " is not a valid phone");
            }

            // echo $if_email. ' \\ ' . $if_phone;die;
            // print_obj($out_message);die;


            $fourDigitOtp = rand(1000, 9999);

            if ($if_email == 1 && $if_phone == 1) {

                $checkUserExists =     [
                    'email'     =>  $email,
                    'phone'     =>  $phone
                ];


                $validateUser   =   $this->api_model->check_user_exists($checkUserExists);

                if ($validateUser == true) {

                    $data = [
                        'statuscode'    =>  '405',
                        'status'        =>  'warning',
                        'message'       =>  'User already exist!!',
                        'usrMobileNo'   =>  $phone,
                        'usrEmail'      =>  $email
                    ];
                } else {

                    $config =   [
                        'protocol'  => 'smtp',
                        // 'smtp_host' => 'ssl://smtp.googlemail.com',
                        'smtp_host' => 'dev.solutionsfinder.co.uk',
                        'smtp_port' => 465,
                        'smtp_user' => 'dev@dev.solutionsfinder.co.uk',
                        'smtp_pass' => 'India_2021',
                        'mailtype'  => 'html',
                        //'charset'   => 'iso-8859-1'
                    ];

                    if (TRUE) {
                        $this->load->library('email', $config);
                        $this->email->from('noreply@dev.solutionsfinder.co.uk', 'Dataar');
                        $this->email->to($email);
                        $this->email->subject('Dataar OTP');
                        $this->email->message('Hello ' . $firstname . ', your OTP is ' . $fourDigitOtp);
                        $this->email->send();
                    }

                    $data = [
                        'first_name' =>  ucfirst($firstname),
                        'last_name' =>  ucfirst($lastname),
                        'email'     =>  $email,
                        'phone'     =>  $phone,
                        'password'  =>  md5($password),
                        'user_type' =>  $usertype,
                        'status'    =>  '1',
                        'otp'       =>  $fourDigitOtp
                    ];

                    $insertNewUser      =   $this->api_model->insert_new_user($data);

                    if ($insertNewUser > 0) {
                        //register device for push notification
                        $device['user_id']            =   $insertNewUser;
                        $device['firebase_token']    =   $device_id;
                        $device['user_type']        =   $device_type;

                        $registeredDevice           =   $this->api_model->register_device_notification($device);

                        $data = [
                            'statuscode'    =>  '200',
                            'status'        =>  'success',
                            'message'       =>  'User Registered Sucessfully',
                        ];
                    } else {
                        $data   =   ['statuscode' => '200', 'status' => 'failure', 'message' => 'something went wrong'];
                    }
                }
            } else {
                $data   =   $out_message;
            }
        } else {
            $data   =   ['statuscode' => '200', 'status' => 'failure', 'message' => 'form validation failed'];
        }

        $result = $this->response($data);
        //echo json_encode($data);

    }


    //otp verification
    public function user_otp_verification_post()
    {
        header('Content-Type: application/json');
        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {

            $phone     = $this->input->post('phone', TRUE);
            $otp     = $this->input->post('otp', TRUE);
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $phone     = $postData['phone'];
            $otp     = $postData['otp'];
        }

        if ($phone != '' && $otp != '') {
            $params =   [
                'phone'     =>  $phone,
                'otp'         =>  $otp
            ];

            $otp_status     =   $this->api_model->check_user_otp_status($params);
            if ($otp_status == true) {
                $return     =   $this->api_model->get_user_data($params)[0];

                $token['user_id']       =   $return->user_id;
                $token['first_name']    =   $return->first_name;
                $token['last_name']     =   $return->last_name;
                $token['email']         =   $return->email;

                $tokenData  =   $this->authorization_token->generateToken($token);
                $return->token          =   $tokenData;
                $data   = $return;
            } else {
                $data   =   ['status' => 'error', 'message'  => 'OTP Verification Failed'];
            }
        } else {
            $data = ['status' => 'warning', 'message' => 'Some Parameter Missing'];
        }
        $this->response($data);
    }



    //resend otp
    public function resend_user_otp_post()
    {
        //$this->load->library('twilio');
        header('Content-Type: application/json');

        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {

            $phone   = $this->input->post('phone');
            $firstname   = $this->input->post('firstname');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $phone   = $postData['phone'];
            $firstname   = $postData['firstname'];
        }

        if ($phone) {
            $params =   ['phone' => $phone];
            $otp    =   substr(str_shuffle("1234056789"), 0, 4);

            $email  =   $this->api_model->get_email_id_otp($params)[0];

            $config =   [
                'protocol'  => 'smtp',
                // 'smtp_host' => 'ssl://smtp.googlemail.com',
                'smtp_host' => 'dev.solutionsfinder.co.uk',
                'smtp_port' => 465,
                'smtp_user' => 'dev@dev.solutionsfinder.co.uk',
                'smtp_pass' => 'India_2021',
                'mailtype'  => 'html',
                //'charset'   => 'iso-8859-1'
            ];

            $this->load->library('email', $config);
            $this->email->from('noreply@dev.solutionsfinder.co.uk', 'Dataar');
            $this->email->to($email->email);
            $this->email->subject('Dataar OTP');
            $this->email->message('Hello ' . $firstname . ', your OTP is ' . $otp);
            $this->email->send();

            $mobile_number_exist = $this->api_model->check_mobile_number_exist($params);
            if ($mobile_number_exist == true) {
                $params_update  =   ['otp' => $otp];
                $update_user    =   $this->api_model->update_user_otp($params, $params_update);
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'OTP Send Successfully'];
            } else {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Mobile number not exist'];
            }
        } else {
            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Some Parameter Missing'];
        }
        $this->response($data);
    }
    //user login module
    public function login_post()
    {

        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {

            $username   = $this->input->post('username');
            $password   = $this->input->post('password');
            $login_ip   = $this->input->ip_address();
            $login_time = date('Y-m-d H:i:s');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $username   = $postData['username'];
            $password   = $postData['password'];
            $login_ip   = $this->input->ip_address();
            $login_time = date('Y-m-d H:i:s');
        }

        /* $username   =   $this->input->post('username');
        $password   =   $this->input->post('password');
        $login_ip   =   $this->input->ip_address();
        $login_time =   date('Y-m-d H:i:s'); */

        if (!empty($username) && !empty($password)) {
            $return     =   $this->api_model->validate_login($username, $password, $login_ip, $login_time);

            if ($return == 'not_found') {
                $data   =   ['statuscode' => '404', 'status' => 'error', 'message' => 'user not found', 'userdata' => ''];
            } else if ($return == 'password_incorrect') {
                $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'password incorrect', 'userdata' => ''];
            } else if ($return == 'inactive') {
                $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'user inactive', 'userdata' => ''];
            } else if ($return == 'cannot_validate') {
                $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'cannot login now', 'userdata' => ''];
            } else {
                $token['user_id']       =   $return->user_id;
                $token['first_name']    =   $return->first_name;
                $token['last_name']     =   $return->last_name;
                $token['email']         =   $return->email;

                $tokenData  =   $this->authorization_token->generateToken($token);
                $return->token          =   $tokenData;
                $data   = $return;
            }
        } else {
            $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'Some Parameter Missing'];
        }
        $this->response($data);
    }


    //login with google
    public function login_with_google_post()
    {
        $social            =    [];
        $check             =    [];
        $userInfo         =    [];

        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {
            //snigdho
            $firstName       =    $this->input->post('firstName');
            $lastName        =    $this->input->post('lastName');
            $email            =    $this->input->post('email');
            $googleToken     =    $this->input->post('googleToken');

            $check['email']             =     $this->input->post('email');
            $check['google_token']      =     $this->input->post('googleToken');
            $device_id = $this->input->post('device_id');
            $device_type = $this->input->post('device_type');
            $usertype     = $this->input->post('usertype');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $firstName       =    $postData['firstName'];
            $lastName        =    $postData['lastName'];
            $email            =    $postData['email'];
            $googleToken     =    $postData['googleToken'];

            $check['email']             =     $postData['email'];
            $check['google_token']      =     $postData['googleToken'];
            $device_id     = $postData['device_id'];
            $device_type     = $postData['device_type'];
            $usertype     = $postData['usertype'];
        }

        $ip = $this->input->ip_address();

        $userData   =   $this->api_model->check_google_user_exist($check);

        // print_obj($userData);die;

        //check google user exists
        if (empty($userData)) {

            $social['first_name']       =    $firstName;
            $social['last_name']        =    $lastName;
            $social['email']            =    $email;
            $social['google_token']     =    $googleToken;
            $social['status']           =    "1";
            $social['user_type']           =    $usertype;
            $social['last_login_ip']    =    $ip;
            $social['last_login_time']    =    DTIME;

            $user_id = $this->api_model->insert_new_user($social);

            $token['user_id']       =   $user_id;
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $user_id));


            //register device for push notification
            $device['user_id']          =   $user_id;
            $device['firebase_token']   =   $device_id;
            $device['user_type']        =   $device_type;
            //$device['user_type']        =   $usertype;
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            if (!empty($getUser)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => $getUser];
            } else {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => ''];
            }
        } else {

            $dataU   =   [
                'last_login_time'  =>  DTIME,
                'last_login_ip'  =>  $ip,
                'user_type' => $usertype,
                'google_token' => $googleToken
            ];
            $update =   $this->api_model->update_user_profile_info($userData->user_id, $dataU);

            //register device for push notification
            $device['user_id']          =   $userData->user_id;
            $device['firebase_token']    =  $device_id;
            $device['user_type']        =   $device_type;
            //$device['user_type']        =   $usertype;
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            $token['user_id']       =   $userData->user_id;
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $userData->user_id));

            if (!empty($getUser)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => $getUser];
            } else {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => ''];
            }
        }
        $this->response($data);
    }
    //login with facebook
    public function login_with_facebook_post()
    {
        $social     =    [];
        $check      =    [];
        $userInfo   =   [];

        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {
            //snigdho

            $firstName   =    $this->input->post('firstName');
            $lastName   =    $this->input->post('lastName');

            //$splitName  =   explode(' ', $fullName);
            // $firstName       =    $splitName[0];
            // $lastName        =    $splitName[1];

            $email            =    $this->input->post('email');
            $fb_token     =    $this->input->post('facebookToken');
            $facebook_id     =    $this->input->post('facebook_id');

            $check['facebook_id']             =     $facebook_id;
            // $check['email']             =     $this->input->post('email');
            $check['fb_token']          =     $this->input->post('facebookToken');

            $device_id = $this->input->post('device_id');
            $device_type = $this->input->post('device_type');
            $usertype     = $this->input->post('usertype');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $firstName   =    $postData['firstName'];
            $lastName   =    $postData['lastName'];
            //$splitName  =   explode(' ', $fullName);
            // $firstName       =    $splitName[0];
            // $lastName        =    $splitName[1];

            $email           =    $postData['email'];
            $fb_token         =    $postData['facebookToken'];
            $facebook_id         =    $postData['facebook_id'];

            $check['facebook_id']             =     $facebook_id;
            // $check['email']             =     $postData['email'];
            $check['fb_token']          =     $postData['facebookToken'];

            $device_id     = $postData['device_id'];
            $device_type     = $postData['device_type'];
            $usertype     = $postData['usertype'];
        }

        $ip = $this->input->ip_address();

        $userData   =   $this->api_model->check_google_user_exist($check);

        //check facebook user exists
        if (empty($userData)) {

            $social['first_name']       =    $firstName;
            $social['last_name']        =    $lastName;
            $social['email']            =    ($email != '')?$email:'';
            $social['fb_token']     =    $fb_token;
            $social['facebook_id']     =    $facebook_id;
            $social['status']           =    "1";
            $social['user_type']           =    $usertype;
            $social['last_login_ip']    =    $ip;
            $social['last_login_time']    =    DTIME;

            $user_id = $this->api_model->insert_new_user($social);

            $token['user_id']       =   $user_id;
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $user_id));

            //register device for push notification
            $device['user_id']        =   $user_id;
            $device['firebase_token']   =   $device_id;
            $device['user_type']        =   $device_type;
            //$device['user_type']        =   $usertype;
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            if (!empty($getUser)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => $getUser];
            } else {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => ''];
            }
        } else {

            $dataU   =   [
                'last_login_time'  =>  DTIME,
                'last_login_ip'  =>  $ip,
                'user_type' => $usertype,
                'fb_token' => $fb_token
            ];
            $update =   $this->api_model->update_user_profile_info($userData->user_id, $dataU);

            //register device for push notification
            $device['user_id']        =   $userData->user_id;
            $device['firebase_token']   =   $device_id;
            $device['user_type']        =   $device_type;
            // $device['user_type']        =   $usertype;
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            $token['user_id']       =   $userData->user_id;
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $userData->user_id));

            if (!empty($getUser)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => $getUser];
            } else {
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'token' => $tokenData, 'userdata' => ''];
            }
        }
        $this->response($data);
    }

    public function validate_user_kyc_post()
    {
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
            }
            if (!empty($user_id)) {
                $validate   =   $this->api_model->validate_current_profile($user_id);
                if ($validate) {
                    $data   =   ['successcode' => '405', 'status' =>  'warning', 'message' => 'Please get your KYC done'];
                } else {
                    $data   =   ['successcode' => '200', 'status' => 'success', 'message' => 'KYC Verified'];
                }
            } else {
                $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'params missing'];
            }
        }
        $this->response($data);
    }

    /*************************************************  REGISTRATION & LOGIN    *************************************************/

    /*************************************************  PROFILE DATA    *************************************************/

    public function fetch_profile_data_post()
    {
        if ($this->verify_token()) {
            header('Content-Type: application/json');
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id    =   $this->input->post('user_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id      =    $postData['user_id'];
            }

            if (!empty($user_id)) {
                $return = $this->api_model->my_profile($user_id);
                //print_obj($return);die;
                if ($return == 'user_not_found') {
                    $data   =   ['successcode' => '405', 'status' => 'failed', 'message' => 'user not found'];
                } else if ($return == 'user_inactive') {
                    $data   =   ['successcode' => '405', 'status' => 'warning', 'message' => 'user inactive'];
                } else {
                    $data   =   ['successcode' => '200', 'status' => 'success', 'data' => $return];
                }
            } else {
                $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'params missing'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function update_user_profile_info_post()
    {
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $usrId       =    $this->input->post('usrId');
                $firstname        =    $this->input->post('firstname');
                $lastname            =    $this->input->post('lastname');
                $email     =    $this->input->post('email');
                $phone             =     $this->input->post('phone');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $usrId       =    $postData['usrId'];
                $firstname        =    $postData['firstname'];
                $lastname            =    $postData['lastname'];
                $email     =    $postData['email'];
                $phone             =     $postData['phone'];
            }
            if ($usrId != '' && $firstname != '' && $lastname != '' && $email != '' && $phone != '') {

                $params_email_or_mobile_number_exist    =   [
                    'phone' =>     $phone,
                    'email' =>     $email,
                    'usrId' =>  $usrId
                ];

                $checkBothValidation    =   $this->api_model->check_user_exists($params_email_or_mobile_number_exist);

                if ($checkBothValidation) {
                    $data   =   [
                        'statuscode'    =>  '405',
                        'status'        =>  'warning',
                        'message'       =>  'Email or Phone already exist!!',
                        'usrMobileNo'   =>  $phone,
                        'usrEmail'      =>  $email
                    ];
                } else {
                    $data               =   [];
                    $data['first_name'] =   ucfirst($firstname);
                    $data['last_name']  =   ucfirst($lastname);
                    $data['email']      =   $email;
                    $data['phone']      =   $phone;

                    $update_user    =   $this->api_model->update_user_profile_info($usrId, $data);
                    $userdata       =   $this->api_model->get_user_profile_info($usrId);
                    if ($userdata['profile_img'] ==  "" || $userdata['profile_img'] == null) {
                        $user_profile_image =   base_url() . 'uploads/profile_images/default.png';
                    } else {
                        $user_profile_image =   base_url() . 'uploads/profile_images/' . $userdata['profile_img'];
                    }
                    $data   =   [
                        'statuscode'     =>  '200',
                        'status'        =>  'success',
                        'message'       =>  'Profile Info Updated Successfully',
                        'first_name'     =>  $userdata['first_name'],
                        'last_name'     =>  $userdata['last_name'],
                        'email'         =>  $userdata['email'],
                        'phone'         =>  $userdata['phone'],
                        'created_at'     =>  $userdata['created_at'],
                        'profile_img'     =>  $user_profile_image
                    ];
                }
            } else {
                $data   =   ['statuscode' => '404', 'status' => 'warning', 'message' => 'Some Parameter Missing'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }


    public function update_kyc_post()
    {
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id       =    $this->input->post('user_id');
                $firstName       =    $this->input->post('firstName');
                $lastName        =    $this->input->post('lastName');
                $email            =    $this->input->post('email');
                $phone            =    $this->input->post('phone');
                $address            =    $this->input->post('address');
                $kycfile_type            =    $this->input->post('kycfile_type');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id       =    $postData['user_id'];
                $firstName       =    $postData['firstName'];
                $lastName        =    $postData['lastName'];
                $email            =    $postData['email'];
                $phone            =    $postData['phone'];
                $address            =    $postData['address'];
                $kycfile_type            =    $postData['kycfile_type'];
            }

            if ($user_id != '') {

                /************************************************* KYC UPLOAD *************************************************/

                if (!is_dir('uploads/kyc'))
                    mkdir('uploads/kyc');

                if ($kycfile_type == 'regular') {
                    $new_name                  =   time() . '-' . $_FILES["kycfile"]['name'];
                    $config['file_name']       =   $new_name;
                    $config['upload_path']     =   "uploads/kyc/";
                    $config['allowed_types']   =   'gif|jpg|png|pdf';

                    $this->load->library('upload', $config);
                    $this->upload->initialize($config);

                    if ($this->upload->do_upload('kycfile')) {
                        $uploadData          =     $this->upload->data();
                        $uploadedFile        =     $uploadData['file_name'];

                        $data   =   [
                            'kyc_file'  =>  $uploadedFile,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $email,
                            'phone' => $phone,
                            'address' => $address,
                            'kycfile_type' => $kycfile_type
                        ];

                        $update =   $this->api_model->update_user_profile_info($user_id, $data);
                        if ($update) {
                            $data   =   ['statuscode' => '200', 'status' => 'success', 'message' => 'KYC uploaded successfully'];
                        } else {
                            $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'Something went wrong'];
                        }
                    } else {
                        $data   =   ['statuscode' => '404', 'status' => 'failed', 'message' => 'KYC upload failed'];
                    }
                } else if ($kycfile_type == 'base64') {

                    // $image_parts = explode(";base64,", $_POST['image']);
                    // $image_type_aux = explode("image/", $image_parts[0]);
                    // $image_type = $image_type_aux[1];
                    // $image_base64 = base64_decode($image_parts[1]);
                    // $file = "uploads/kyc/" . uniqid() . '.png';
                    // file_put_contents($file, $image_base64);
                    $postData = $this->input->post();

                    if (isset($postData) && !empty($postData)) {

                        $kyc_file       =    $this->input->post('kyc_file');
                    } else {

                        $jsonData = file_get_contents('php://input');
                        $postData = json_decode($jsonData, true);

                        $kyc_file       =    $postData['kyc_file'];
                    }
                    $data   =   [
                        'kyc_file'  =>  $kyc_file,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'kycfile_type' => $kycfile_type
                    ];
                    // print_obj($data);

                    $update =   $this->api_model->update_user_profile_info($user_id, $data);
                    //echo $user_id;die;
                    if ($update) {
                        $data   =   ['statuscode' => '200', 'status' => 'success', 'message' => 'KYC uploaded successfully'];
                    } else {
                        $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'Previous data and current data is same.'];
                    }
                } else {
                    $data   =   ['statuscode' => '404', 'status' => 'failed', 'message' => 'KYC upload failed. kyc file type is missing!'];
                }



                /************************************************* KYC UPLOAD *************************************************/
            } else {
                $data   =   ['statuscode' => '404', 'status' => 'failed', 'invalid user'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    /*************************************************  PROFILE DATA    *************************************************/

    /*************************************************  CAMPAIGN DATA    *************************************************/

    public function campaign_list_post()
    {
        if ($this->verify_token()) {
            $data   =   $this->api_model->campaign_list();
            //print_obj($data);die;
            if (count($data)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
            } else {
                $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function campaign_details_post()
    {
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $campaign_id   = $this->input->post('campaign_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $campaign_id   = $postData['campaign_id'];
            }

            $data  =   $this->api_model->get_single_campaign($campaign_id);

            if (count($data)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
            } else {
                $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function campaign_details_by_user_post()
    {
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
            }

            $data  =   $this->api_model->get_campaign(array('user_id' => $user_id));

            //print_obj($data);die;

            if (count($data)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
            } else {
                $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function add_campaign_post()
    {
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $campaign_name       =    $this->input->post('campaign_name');
                $user_id       =    $this->input->post('user_id');
                $donation_mode        =    $this->input->post('donation_mode');
                $kind_id        =    $this->input->post('kind_id');
                $campaign_details            =    $this->input->post('campaign_details');
                $campaign_start_date            =    $this->input->post('campaign_start_date');
                $campaign_end_date            =    $this->input->post('campaign_end_date');
                $campaign_target_amount            =    $this->input->post('campaign_target_amount');
                $campaign_image            =    $this->input->post('campaign_image');
                $area_lat            =    $this->input->post('area_lat');
                $area_long            =    $this->input->post('area_long');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $campaign_name       =    $postData['campaign_name'];
                $user_id       =    $postData['user_id'];
                $donation_mode        =    $postData['donation_mode'];
                $kind_id        =    (isset($postData['kind_id'])) ? $postData['kind_id'] : '';
                $campaign_details            =    $postData['campaign_details'];
                $campaign_start_date            =    $postData['campaign_start_date'];
                $campaign_end_date            =    $postData['campaign_end_date'];
                $campaign_target_amount            =    $postData['campaign_target_amount'];
                $campaign_image            =    $postData['campaign_image'];
                $area_lat            =    (isset($postData['area_lat'])) ? $postData['area_lat'] : '';
                $area_long            =    (isset($postData['area_long'])) ? $postData['area_long'] : '';
            }
            if ($campaign_name && $user_id && $donation_mode && $campaign_details && $campaign_start_date &&  $campaign_end_date && $campaign_target_amount) {
                $data   =   [
                    'user_id'                   =>  $user_id,
                    'kind_id'                   =>  $kind_id,
                    'campaign_name'             =>  $campaign_name,
                    'donation_mode'             =>  $donation_mode,
                    'campaign_details'          =>  $campaign_details,
                    'campaign_start_date'       =>  $campaign_start_date,
                    'campaign_end_date'         =>  $campaign_end_date,
                    'campaign_image'            =>  $campaign_image,
                    'area_lat'                  =>  $area_lat,
                    'area_long'                 =>  $area_long,
                    'campaign_target_amount'    =>  $campaign_target_amount
                ];
                //print_obj($data);die;
                if ($this->api_model->is_user_verified($user_id)) {
                    if ($this->api_model->auth_to_create_camp($user_id)) {
                        $inserted   =   $this->api_model->insert_new_campaign($data);
                        if ($inserted) {
                            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Campaign added sucessfully'];
                        } else {
                            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'Something went wrong'];
                        }
                    } else {
                        $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'You are not authorised to create campaign'];
                    }
                } else {
                    $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'Contact us for KYC approval'];
                }
            } else {
                $data = ['statuscode' => '404', 'status' => 'error', 'message' => 'Some params missing'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function update_campaign_post()
    {
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
                $campaign_name   = $this->input->post('campaign_name');
                $donation_mode   = $this->input->post('donation_mode');
                $campaign_details   = $this->input->post('campaign_details');
                $campaign_start_date   = $this->input->post('campaign_start_date');
                $campaign_end_date   = $this->input->post('campaign_end_date');
                $campaign_image   = $this->input->post('campaign_image');
                $campaign_target_amount   = $this->input->post('campaign_target_amount');
                $campaign_id   = $this->input->post('campaign_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
                $campaign_name   = $postData['campaign_name'];
                $donation_mode   = $postData['donation_mode'];
                $campaign_details   = $postData['campaign_details'];
                $campaign_start_date   = $postData['campaign_start_date'];
                $campaign_end_date   = $postData['campaign_end_date'];
                $campaign_image   = $postData['campaign_image'];
                $campaign_target_amount   = $postData['campaign_target_amount'];
                $campaign_id   = $postData['campaign_id'];
            }
            if ($campaign_id && $user_id) {
                $data   =   [
                    'user_id'                   =>  $user_id,
                    'campaign_name'             =>  $campaign_name,
                    'donation_mode'             =>  $donation_mode,
                    'campaign_details'          =>  $campaign_details,
                    'campaign_start_date'       =>  $campaign_start_date,
                    'campaign_end_date'         =>  $campaign_end_date,
                    'campaign_image'            =>  $campaign_image,
                    'campaign_target_amount'    =>  $campaign_target_amount
                ];
                $update_campaign    =   $this->api_model->update_single_campaign($campaign_id, $data);
                $campaigndata       =   $this->api_model->get_single_campaign($campaign_id);

                $data   =   ['statuscode' => '200', 'status' => 'success', 'message' => 'Campaign updated successfully', 'data' => $campaigndata];
            } else {
                $data   =   ['statuscode' => '404', 'status' => 'error', 'message' => 'Some params missing'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    } //snigdho

    /*************************************************  CAMPAIGN DATA    *************************************************/

    public function kind_list_post()
    {
        // if ($this->verify_token()['status]) {
        $data   =   $this->api_model->kind_list();
        if (count($data)) {
            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no kind found'];
        }
        $this->response($data);
        // } else {
        //     $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
        //     $this->response($data);
        // }
    }

    /*************************************************  DONATION DATA    *************************************************/

    public function add_donation_post()
    {
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
                $campaign_id   = $this->input->post('campaign_id');
                $status   = $this->input->post('status');
                $kind_id   = $this->input->post('kind_id');
                $quantity   = $this->input->post('quantity');
                $amountpaid   = $this->input->post('amountpaid');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
                $campaign_id   = $postData['campaign_id'];
                $status   = $postData['status'];
                $kind_id   = $postData['kind_id'];
                $quantity   = $postData['quantity'];
                $amountpaid   = $postData['amountpaid'];
            }
            if ($user_id && $campaign_id && $status) {
                $data   =   [
                    'user_id'       =>  $user_id,
                    'campaign_id'   =>  $campaign_id,
                    'kind_id'       =>  $kind_id,
                    'quantity'      =>  $quantity,
                    'amountpaid'    =>  $amountpaid,
                    'status'        =>  $status
                ];
                $checkUser  =   $this->api_model->is_user_verified($user_id);
                if ($checkUser) {
                    $inserted   =   $this->api_model->insert_new_payment($data);
                    if ($inserted) {
                        $data   =   ['statuscode' => '200', 'status' => 'success', 'message' => 'Transanction completed successfully'];
                    } else {
                        $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'Something went wrong'];
                    }
                } else {
                    $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'Contact us for KYC approval'];
                }
            } else {
                $data   =   ['statuscode' => '404', 'status' => 'error', 'message' => 'Some Parameter Missing'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
        //snigdho
    }

    public function user_donation_data_list_post()
    {
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
                $donation_type   = $this->input->post('donation_type');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
                $donation_type   = $postData['donation_type'];
            }
            if ($user_id && $donation_type) {
                if ($donation_type == 'kind') {
                    $data   =   $this->api_model->fetch_data_by_kind($user_id);
                    $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
                } else if ($donation_type == 'cash') {
                    $data   =   $this->api_model->fetch_data_by_cash($user_id);
                    $data = ['statuscode'    =>  '200', 'status' => 'success', 'data' => $data];
                } else {
                    $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
                }
            } else {
                $data = ['statuscode' => '404', 'status' => 'warning', 'message' => 'invalid params'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }
	
	//*** Show All Donation List data Starts ***//
	public function donation_list_post()
    {
        $data 	= 	$this->api_model->all_donation_list();
		//echo $this->db->last_query();exit();
		
        if (count($data) > 0) {
            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no data found'];
        }
        $this->response($data);
    }
	//*** Show All Donation List data Ends ***//
	

    /*************************************************  DONATION DATA    *************************************************/

    /*************************************************  FAVOURITE DATA    *************************************************/

    public function add_to_favourite_post()
    {
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
                $campaign_id   = $this->input->post('campaign_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
                $campaign_id   = $postData['campaign_id'];
            }
            if ($user_id && $campaign_id) {
                $data   =   [
                    'user_id'       =>  $user_id,
                    'campaign_id'   =>  $campaign_id
                ];
                $inserted   =   $this->api_model->add_my_favourite($data);
                if ($inserted) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Campaign added to favourites'];
                } else {
                    $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Campaign removed from favourites'];
                }
            } else {
                $data = ['statuscode' => '404', 'status' => 'error', 'message' => 'Invalid params'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function my_favourites_post()
    {
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = $this->input->post('user_id');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = $postData['user_id'];
            }
            if ($user_id) {
                $favourites   =   $this->api_model->get_my_favourite($user_id);
                if (count($favourites)) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'data' => $favourites];
                } else {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'no favourites found'];
                }
            } else {
                $data = ['statuscode' => '404', 'status' => 'error', 'message' => 'Invalid params'];
            }
            $this->response($data);
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    /*************************************************  FAVOURITE DATA    *************************************************/

    public function emailtest_get()
    {
        $config =   [
            'protocol'  => 'smtp',
            // 'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_host' => 'dev.solutionsfinder.co.uk',
            'smtp_port' => 465,
            'smtp_user' => 'dev@dev.solutionsfinder.co.uk',
            'smtp_pass' => 'India_2021',
            'mailtype'  => 'html',
            //'charset'   => 'iso-8859-1'
        ];
        $this->load->library('email', $config);

        $this->email->from('noreply@dev.solutionsfinder.co.uk', 'Dataar');
        $this->email->to('soumyajeet.lnsel@gmail.com');
        $this->email->subject(' My mail through codeigniter from devsolutions ');
        $this->email->message('Hello World…');
        if (!$this->email->send()) {
            show_error($this->email->print_debugger());
        } else {
            echo 'Your e-mail has been sent!';
        }
    }
}
