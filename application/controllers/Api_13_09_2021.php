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
            $fcm_token = $this->input->post('fcm_token', TRUE);
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
            $fcm_token = $postData['fcm_token'];
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
                $out_msg[] = array('statuscode' => '400', 'status' => 'failure', 'message' => 'email validation failed');
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
                $out_msg2[] = array('statuscode' => '400', 'status' => 'failure', 'message' => 'phone validation failed');

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
                        'fcm_token' =>  $fcm_token,
                        'status'    =>  '1',
                        'sign_up_type'    =>  '1',
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
            $fcm_token = $this->input->post('fcm_token');
            $login_ip   = $this->input->ip_address();
            $login_time = date('Y-m-d H:i:s');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $username   = $postData['username'];
            $password   = $postData['password'];
            $fcm_token   = $postData['fcm_token'];
            $login_ip   = $this->input->ip_address();
            $login_time = date('Y-m-d H:i:s');
        }

        /* $username   =   $this->input->post('username');
        $password   =   $this->input->post('password');
        $login_ip   =   $this->input->ip_address();
        $login_time =   date('Y-m-d H:i:s'); */

        if (!empty($username) && !empty($password)) {
            $return     =   $this->api_model->validate_login($username, $password, $login_ip, $login_time, $fcm_token);

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

                $tokenData      =   $this->authorization_token->generateToken($token);
                $return->token  =   $tokenData;
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
            $email           =    $this->input->post('email');
            $googleToken     =    $this->input->post('googleToken');
            $fcm_token       = $this->input->post('fcm_token');

            $check['email']             =     $this->input->post('email');
            $check['google_token']      =     $this->input->post('googleToken');
            $device_id = $this->input->post('device_id');
            $device_type = $this->input->post('device_type');
            //$usertype     = $this->input->post('usertype');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $firstName       =    $postData['firstName'];
            $lastName        =    $postData['lastName'];
            $email           =    $postData['email'];
            $googleToken     =    $postData['googleToken'];
            $fcm_token       =    $postData['fcm_token'];

            $check['email']             =     $postData['email'];
            $check['google_token']      =     $postData['googleToken'];
            $device_id     = $postData['device_id'];
            $device_type     = $postData['device_type'];
            //$usertype     = $postData['usertype'];
        }

        $ip = $this->input->ip_address();

        $userData   =   $this->api_model->check_google_user_exist($check);

        $userData = $userData[0];

        // print_obj($userData);die;

        //check google user exists
        if (empty($userData)) {

            $social['first_name']       =    $firstName;
            $social['last_name']        =    $lastName;
            $social['email']            =    $email;
            $social['google_token']     =    $googleToken;
            $social['fcm_token']        =    $fcm_token;
            $social['status']           =    "1";
            //$social['user_type']           =    $usertype;
            $social['last_login_ip']    =    $ip;
            $social['last_login_time']  =    DTIME;
            $social['sign_up_type']     =  '2';

            $user_id = $this->api_model->insert_new_user($social);

            $token['user_id']       =   $user_id;
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $user_id));


            //register device for push notification
            $device['user_id']          =   $user_id;
            $device['firebase_token']   =   $fcm_token;
            $device['user_type']        =   $device_type;
            //$device['user_type']      =   $usertype;
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
                //'user_type' => $usertype,
                'google_token' => $googleToken,
                'fcm_token' => $fcm_token
            ];
            $update =   $this->api_model->update_user_profile_info($userData['user_id'], $dataU);

            //register device for push notification
            $device['user_id']          =   $userData['user_id'];
            $device['firebase_token']   =  $fcm_token;
            $device['user_type']        =   $device_type;
            //$device['user_type']      =   $usertype;
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            $token['user_id']       =   $userData['user_id'];
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $userData['user_id']));

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
            $fcm_token       = $this->input->post('fcm_token');

            $check['facebook_id']             =     $facebook_id;
            // $check['email']             =     $this->input->post('email');
            $check['fb_token']          =     $this->input->post('facebookToken');

            $device_id = $this->input->post('device_id');
            $device_type = $this->input->post('device_type');
            //$usertype     = $this->input->post('usertype');
        } else {

            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $firstName   =    $postData['firstName'];
            $lastName    =    $postData['lastName'];
            $fcm_token   =    $postData['fcm_token'];
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
            //$usertype     = $postData['usertype'];
        }

        $ip = $this->input->ip_address();

        $userData   =   $this->api_model->check_google_user_exist($check);

        //check facebook user exists
        if (empty($userData)) {

            $social['first_name']       =    $firstName;
            $social['last_name']        =    $lastName;
            $social['email']            =    ($email != '') ? $email : '';
            $social['fb_token']     =    $fb_token;
            $social['fcm_token']     =    $fcm_token;
            $social['facebook_id']     =    $facebook_id;
            $social['status']           =    "1";
            //$social['user_type']           =    $usertype;
            $social['last_login_ip']    =    $ip;
            $social['last_login_time']    =    DTIME;
            $social['sign_up_type']    =  '3';

            $user_id = $this->api_model->insert_new_user($social);

            $token['user_id']       =   $user_id;
            $token['first_name']    =   $firstName;
            $token['last_name']     =   $lastName;
            $token['email']         =   $email;

            $tokenData  =   $this->authorization_token->generateToken($token);

            $getUser =   $this->api_model->getUser(array('user_id' => $user_id));

            //register device for push notification
            $device['user_id']        =   $user_id;
            $device['firebase_token']   =   $fcm_token;
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
                //'user_type' => $usertype,
                'fb_token' => $fb_token,
                'fcm_token' => $fcm_token
            ];
            $update =   $this->api_model->update_user_profile_info($userData->user_id, $dataU);

            //register device for push notification
            $device['user_id']        =   $userData->user_id;
            $device['firebase_token']   =   $fcm_token;
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

    //update user type
    public function update_usertype_post()
    {
        $check             =    [];

        $postData = $this->input->post();

        $jsonData = file_get_contents('php://input');
        $jPostData = json_decode($jsonData, true);

        if (isset($postData) && !empty($postData)) {
            //snigdho

            $check['user_id']      =     $this->input->post('user_id');
            $usertype     = $this->input->post('usertype');
        } else if (isset($jPostData) && !empty($jPostData) && isset($jPostData['user_id']) && isset($jPostData['usertype'])) {

            $check['user_id']        = $jPostData['user_id'];
            $usertype         = $jPostData['usertype'];
        } else {
            $check = '';
        }

        if ($check != '') {
            $userData   =   $this->api_model->check_user_exist($check);

            //print_obj($userData);die;
            //check user exists
            if (!empty($userData)) {

                $dataU   =   [
                    'user_type' => $usertype
                ];
                $update =   $this->api_model->update_user_profile_info($userData->user_id, $dataU);

                $token['user_id']       =   $userData->user_id;
                $token['first_name']    =   $userData->first_name;
                $token['last_name']     =   $userData->last_name;
                $token['email']         =   $userData->email;

                $tokenData  =   $this->authorization_token->generateToken($token);

                $getUser =   $this->api_model->getUser(array('user_id' => $userData->user_id));

                if (!empty($getUser)) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User updated', 'token' => $tokenData, 'userdata' => $getUser];
                } else {
                    $data = ['statuscode' => '400', 'status' => 'failure', 'message' => 'User not updated', 'token' => $tokenData, 'userdata' => ''];
                }
            } else {
                $data = ['statuscode' => '400', 'status' => 'failure', 'message' => 'User not found', 'token' => '', 'userdata' => ''];
            }
        } else {
            $data = ['statuscode' => '400', 'status' => 'failure', 'message' => 'Error in field list', 'token' => '', 'userdata' => ''];
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
                $firstname   =    $this->input->post('firstname');
                $lastname    =    $this->input->post('lastname');
                $email       =    $this->input->post('email');
                $phone       =     $this->input->post('phone');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $usrId       =    $postData['usrId'];
                $firstname   =    $postData['firstname'];
                $lastname    =    $postData['lastname'];
                $email       =    $postData['email'];
                $phone       =     $postData['phone'];
            }

            if ($usrId != '' && $firstname != '' && $lastname != '') {

                $params_email_or_mobile_number_exist = [
                    'phone' =>     $phone,
                    'email' =>     $email,
                    'usrId' =>     $usrId
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
                        'statuscode'    =>  '200',
                        'status'        =>  'success',
                        'message'       =>  'Profile Info Updated Successfully',
                        'first_name'    =>  $userdata['first_name'],
                        'last_name'     =>  $userdata['last_name'],
                        'email'         =>  $userdata['email'],
                        'phone'         =>  $userdata['phone'],
                        'created_at'    =>  $userdata['created_at'],
                        'profile_img'   =>  $user_profile_image
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

                $user_id           =    $this->input->post('user_id');
                $firstName         =    $this->input->post('firstName');
                $lastName          =    $this->input->post('lastName');
                $email             =    $this->input->post('email');
                $phone             =    $this->input->post('phone');
                $address           =    $this->input->post('address');
                $kycfile_type      =    $this->input->post('kycfile_type');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id       =    $postData['user_id'];
                $firstName     =    $postData['firstName'];
                $lastName      =    $postData['lastName'];
                $email         =    $postData['email'];
                $phone         =    $postData['phone'];
                $address       =    $postData['address'];
                $kycfile_type  =    $postData['kycfile_type'];
            }

            if ($user_id != '') {

                /********* KYC UPLOAD ***********/

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

            $data['capmain_details']  =   $this->api_model->get_single_campaign($campaign_id);

            if (!empty($data['capmain_details'])) {
                $user_id = $data['capmain_details'][0]->user_id;
                $donations = $this->api_model->getDonations(array('donation_master.campaign_id' => $campaign_id));
                $data['donations']  =   (!empty($donations)) ? $donations : '';

                //foreach ($data['capmain_details'] as $key => $value) {
                $params = array(
                    'campaign_id' => $campaign_id
                );

                $CommentsData   =   $this->api_model->getComments($params);
                //print_obj($likesData);die;
                if (!empty($CommentsData)) {
                    $data['comments_data'] = $CommentsData;
                } else {
                    $data['comments_data'] = '';
                }
                //}

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

    public function donations_by_donor_post()
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


            $donations = $this->api_model->getDonations(array('donation_master.user_id' => $user_id));
            $data['donations']  =   (!empty($donations)) ? $donations : '';

            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];

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
                $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $zip   = xss_clean(($this->input->post('zip') != '') ? $this->input->post('zip') : '');
            } else {
                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $zip = xss_clean((isset($postData['zip'])) ? $postData['zip'] : '');
            }

            if ($user_id != '') {

                if ($zip != '') {
                    $data  =   $this->api_model->get_campaign(array('user_id' => $user_id, 'zip' => $zip));
                } else {
                    $data  =   $this->api_model->get_campaign(array('user_id' => $user_id));
                }

                //print_obj($data);die;

                if (count($data)) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
                } else {
                    $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
                }
            } else {
                $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Required fields are missing!'];
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
                $campaign_details   =    $this->input->post('campaign_details');
                $zip                   =    $this->input->post('zip');
                $campaign_start_date     =  $this->input->post('campaign_start_date');
                $campaign_end_date   =    $this->input->post('campaign_end_date');
                $campaign_target_amount =  $this->input->post('campaign_target_amount');
                $campaign_image           = $this->input->post('campaign_image');
                $area_lat             =    $this->input->post('area_lat');
                $area_long            =    $this->input->post('area_long');
                $filter_by_type_id    =    $this->input->post('filter_by_type');
                $campaign_target_qty  =    $this->input->post('campaign_target_qty');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $campaign_name       =    $postData['campaign_name'];
                $user_id       =    $postData['user_id'];
                $donation_mode        =    $postData['donation_mode'];
                $kind_id        =    (isset($postData['kind_id'])) ? $postData['kind_id'] : '';
                $campaign_details       =  $postData['campaign_details'];
                $zip                       =  $postData['zip'];
                $campaign_start_date    =  $postData['campaign_start_date'];
                $campaign_end_date      =  $postData['campaign_end_date'];
                $campaign_target_amount =  $postData['campaign_target_amount'];
                $campaign_image            =    $postData['campaign_image'];
                $area_lat            =    (isset($postData['area_lat'])) ? $postData['area_lat'] : '';
                $area_long            =    (isset($postData['area_long'])) ? $postData['area_long'] : '';
                $filter_by_type_id    =    (isset($postData['filter_by_type'])) ? $postData['filter_by_type'] : '';
                $campaign_target_qty  =    (isset($postData['campaign_target_qty'])) ? $postData['campaign_target_qty'] : '';
            }
            if ($campaign_name && $user_id && $donation_mode && $campaign_details && $campaign_start_date &&  $campaign_end_date  && $filter_by_type_id) {
                $data   =   [
                    'user_id'                   =>  $user_id,
                    'kind_id'                   =>  $kind_id,
                    'campaign_name'             =>  $campaign_name,
                    'donation_mode'             =>  $donation_mode,
                    'campaign_details'          =>  $campaign_details,
                    'zip'                       =>  $zip,
                    'campaign_start_date'       =>  $campaign_start_date,
                    'campaign_end_date'         =>  $campaign_end_date,
                    'campaign_image'            =>  $campaign_image,
                    'area_lat'                  =>  $area_lat,
                    'area_long'                 =>  $area_long,
                    'campaign_target_amount'    => ($campaign_target_amount != '') ? $campaign_target_amount : '0.00',
                    'campaign_target_qty'       => ($campaign_target_qty != '') ? $campaign_target_qty : '0',
                    'filter_by_type_id'         =>  $filter_by_type_id
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
                $zip                   =    $this->input->post('zip');
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
                $zip                   =  $postData['zip'];
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
                    'zip'                         =>  $zip,
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


    public function preference_list_post()
    {
        // if ($this->verify_token()['status]) {
        $data   =   $this->api_model->get_preference_list(array('status' => 1), TRUE);
        if (!empty($data)) {
            //$data = (array)$data;
            $data[] = array(
                "pref_id" => "1010",
                "pref_name" => "Money"
            );
            //$data = (object)$data;

            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no preference found'];
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

                $user_id   = xss_clean($this->input->post('user_id'));
                $campaign_id   = xss_clean($this->input->post('campaign_id'));
                //$status   = xss_clean($this->input->post('status'));
                $kind_id   = xss_clean($this->input->post('kind_id'));
                $quantity   = xss_clean($this->input->post('quantity'));
                $amountpaid   = xss_clean($this->input->post('amountpaid'));
                //$notification_title   = xss_clean(($this->input->post('notification_title') != '') ? $this->input->post('notification_title') : '');
                //$notification_body   = xss_clean(($this->input->post('notification_body') != '') ? $this->input->post('notification_body') : '');
                $click_action   = xss_clean(($this->input->post('click_action') != '') ? $this->input->post('click_action') : '');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean($postData['user_id']);
                $campaign_id   = xss_clean($postData['campaign_id']);
                //$status   = xss_clean($postData['status']);
                $kind_id   = xss_clean($postData['kind_id']);
                $quantity   = xss_clean($postData['quantity']);
                $amountpaid   = xss_clean($postData['amountpaid']);
                //$notification_title   = xss_clean((isset($postData['notification_title'])) ? $postData['notification_title'] : '');
                //$notification_body   = xss_clean((isset($postData['notification_body'])) ? $postData['notification_body'] : '');
                $click_action   = xss_clean((isset($postData['click_action'])) ? $postData['click_action'] : '');
            }
            if ($user_id && $campaign_id) {
                $data   =   [
                    'user_id'       =>  $user_id,
                    'campaign_id'   =>  $campaign_id,
                    'kind_id'       =>  $kind_id,
                    'quantity'      =>  $quantity,
                    'amountpaid'    =>  $amountpaid,
                    'status'        =>  '1'
                ];
                $checkUser  =   $this->api_model->is_user_verified($user_id);
                if ($checkUser) {
                    $inserted   =   $this->api_model->insert_new_payment($data);
                    if ($inserted) {

                        //send notification starts
                        $userDetails  =   $this->api_model->getCampaignUsers(array('campaign_master.campaign_id' => $campaign_id));
                        //print_obj($userDetails);
                        if (!empty($userDetails) && $userDetails['fcm_token'] != '') {
                            $fcm = $userDetails['fcm_token'];
                            $name = $userDetails['first_name']. ' ' .$userDetails['last_name'];
                            //$fcm = 'cNf2---6Vs9';
                            $icon = NOTIFICATION_ICON;
                            $notification_title = 'You have got a new Donation';
                            $notification_body = 'Hello, You have got a new donation from ' . $name;
                            $click_action = CLICK_ACTION;

                            $data = array(
                                "to" => $fcm,
                                "notification" => array(
                                    "title" => $notification_title,
                                    "body" => $notification_body,
                                    "icon" => $icon,
                                    "click_action" => $click_action
                                )
                            );
                            $data_string = json_encode($data);

                            //echo "The Json Data : " ;

                            //print_obj($data_string);

                            $headers = array(
                                'Authorization: key=' . API_ACCESS_KEY,
                                'Content-Type: application/json'
                            );

                            $ch = curl_init();

                            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

                            $result = curl_exec($ch);

                            curl_close($ch);

                            $result_ar = json_decode($result);
                            //print_obj($result_ar);
                        }
                        //die;
                        //send notification ends

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


    public function donation_list_post()
    {
        //snigdho
        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {
            $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
            $search   = xss_clean(($this->input->post('search') != '') ? $this->input->post('search') : '');
            $filter_by_type_id   = xss_clean(($this->input->post('filter_by_type') != '') ? $this->input->post('filter_by_type') : '');
        } else {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
            $search   = xss_clean((isset($postData['search'])) ? $postData['search'] : '');
            $filter_by_type_id   = xss_clean((isset($postData['filter_by_type'])) ? $postData['filter_by_type'] : '');
        }

        if (isset($postData) && $user_id != '') {
            if ($filter_by_type_id != '') {
                $params = array(
                    //'campaign_master.user_id' => $user_id,
                    'campaign_master.state' => '1',
                    'campaign_master.status' => '1',
                    'campaign_master.filter_by_type_id' => $filter_by_type_id
                );
            } else {
                $params = array(
                    //'campaign_master.user_id' => $user_id,
                    'campaign_master.state' => '1',
                    'campaign_master.status' => '1'
                );
            }

            if ($search != '') {
                $campaignData   =   $this->api_model->getcampaignList($params, $search);
            } else {
                $campaignData   =   $this->api_model->getcampaignList($params);
            }



            if (!empty($campaignData)) {
                //print_obj($campaignData);die;

                foreach ($campaignData as $key => $value) {
                    $paramsLikes = array(
                        'user_id' => $user_id,
                        'campaign_id' => $value['campaign_id']
                    );

                    $likesData   =   $this->api_model->getLikes($paramsLikes);
                    //print_obj($likesData);die;
                    if (!empty($likesData)) {
                        $campaignData[$key]['like_status'] = $likesData->like_status;
                    } else {
                        $campaignData[$key]['like_status'] = '';
                    }

                    $paramsCount = array(
                        'campaign_id' => $value['campaign_id']
                    );

                    $likesCount   =   $this->api_model->getLikesCount($paramsCount);
                    //print_obj($likesCount);die;
                    if (!empty($likesCount)) {
                        $campaignData[$key]['total_likes'] = $likesCount->count_likes;
                    } else {
                        $campaignData[$key]['total_likes'] = '';
                    }

                    $commentsCount   =   $this->api_model->getCommentsCount($paramsCount);
                    //print_obj($commentsCount);die;
                    if (!empty($commentsCount)) {
                        $campaignData[$key]['total_comments'] = $commentsCount->count_comments;
                    } else {
                        $campaignData[$key]['total_comments'] = '';
                    }
                }

                $data['campaign_data'] = $campaignData;
            } else {
                $data['campaign_data'] = '';
            }
        } else {
            $params = array(
                'campaign_master.state' => '1',
                'campaign_master.status' => '1'
            );

            if ($search != '') {
                $campaignData   =   $this->api_model->getcampaignList($params, $search);
            } else {
                $campaignData   =   $this->api_model->getcampaignList($params);
            }

            if (!empty($campaignData)) {
                //print_obj($campaignData);die;

                foreach ($campaignData as $key => $value) {

                    $paramsCount = array(
                        'campaign_id' => $value['campaign_id']
                    );

                    $likesCount   =   $this->api_model->getLikesCount($paramsCount);
                    //print_obj($likesCount);die;
                    if (!empty($likesCount)) {
                        $campaignData[$key]['total_likes'] = $likesCount->count_likes;
                    } else {
                        $campaignData[$key]['total_likes'] = '';
                    }

                    $commentsCount   =   $this->api_model->getCommentsCount($paramsCount);
                    //print_obj($commentsCount);die;
                    if (!empty($commentsCount)) {
                        $campaignData[$key]['total_comments'] = $commentsCount->count_comments;
                    } else {
                        $campaignData[$key]['total_comments'] = '';
                    }
                }

                $data['campaign_data'] = $campaignData;
            } else {
                $data['campaign_data'] = '';
            }
        }



        if (count($data)) {
            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
        }

        $this->response($data);
    }

    public function donation_list_by_preference_post()
    {
        //snigdho
        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {
            $user_id   = xss_clean($this->input->post('user_id'));
        } else {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $user_id   = xss_clean($postData['user_id']);
        }

        if (isset($postData) && $user_id != '') {


            $param = array(
                'user_id' => $user_id
            );

            $getUserPref   =   $this->api_model->get_user_preferences($param, TRUE);

            if (!empty($getUserPref)) {
                $money = 0;
                foreach ($getUserPref as $row) {
                    if ($row['selected_pref_id'] == '1010') {
                        $money = 1;
                    } else {
                        $arr_list_id[] = "'" . $row['selected_pref_id'] . "'";
                    }
                }
                $kind_mon_ids = implode(",", $arr_list_id);
                //print_obj($kind_mon_ids);die;

                $params = array(
                    //'campaign_master.user_id' => $user_id,
                    'campaign_master.state' => '1',
                    'campaign_master.status' => '1'
                );

                $campaignData   =   $this->api_model->getcampaignListByPref($params, $kind_mon_ids, $money);
            } else {
                $params = array(
                    //'campaign_master.user_id' => $user_id,
                    'campaign_master.state' => '1',
                    'campaign_master.status' => '1'
                );
                $campaignData   =   $this->api_model->getcampaignList($params);
            }

            if (!empty($campaignData)) {
                //print_obj($campaignData);die;

                foreach ($campaignData as $key => $value) {
                    $paramsLikes = array(
                        'user_id' => $user_id,
                        'campaign_id' => $value['campaign_id']
                    );

                    $likesData   =   $this->api_model->getLikes($paramsLikes);
                    //print_obj($likesData);die;
                    if (!empty($likesData)) {
                        $campaignData[$key]['like_status'] = $likesData->like_status;
                    } else {
                        $campaignData[$key]['like_status'] = '';
                    }

                    $paramsCount = array(
                        'campaign_id' => $value['campaign_id']
                    );

                    $likesCount   =   $this->api_model->getLikesCount($paramsCount);
                    //print_obj($likesCount);die;
                    if (!empty($likesCount)) {
                        $campaignData[$key]['total_likes'] = $likesCount->count_likes;
                    } else {
                        $campaignData[$key]['total_likes'] = '';
                    }

                    $commentsCount   =   $this->api_model->getCommentsCount($paramsCount);
                    //print_obj($commentsCount);die;
                    if (!empty($commentsCount)) {
                        $campaignData[$key]['total_comments'] = $commentsCount->count_comments;
                    } else {
                        $campaignData[$key]['total_comments'] = '';
                    }
                }

                $data['campaign_data'] = $campaignData;
            } else {
                $data['campaign_data'] = '';
            }
        } else {
            $params = array(
                'campaign_master.state' => '1',
                'campaign_master.status' => '1'
            );
            $campaignData   =   $this->api_model->getcampaignList($params);

            if (!empty($campaignData)) {
                //print_obj($campaignData);die;

                foreach ($campaignData as $key => $value) {

                    $paramsCount = array(
                        'campaign_id' => $value['campaign_id']
                    );

                    $likesCount   =   $this->api_model->getLikesCount($paramsCount);
                    //print_obj($likesCount);die;
                    if (!empty($likesCount)) {
                        $campaignData[$key]['total_likes'] = $likesCount->count_likes;
                    } else {
                        $campaignData[$key]['total_likes'] = '';
                    }

                    $commentsCount   =   $this->api_model->getCommentsCount($paramsCount);
                    //print_obj($commentsCount);die;
                    if (!empty($commentsCount)) {
                        $campaignData[$key]['total_comments'] = $commentsCount->count_comments;
                    } else {
                        $campaignData[$key]['total_comments'] = '';
                    }
                }

                $data['campaign_data'] = $campaignData;
            } else {
                $data['campaign_data'] = '';
            }
        }



        if (count($data)) {
            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no campaign found'];
        }

        $this->response($data);
    }


    public function preference_list_by_user_post()
    {
        //snigdho
        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {
            $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
        } else {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
        }

        if (isset($postData) && $user_id != '') {

            $param = array(
                'user_id' => $user_id
            );

            $getUserPref   =   $this->api_model->get_user_preferences($param, TRUE);

            if (!empty($getUserPref)) {
                $data = ['statuscode' => '200', 'status' => 'success', 'data' => $getUserPref];
            } else {
                $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no preference found'];
            }
        } else {
            $data = ['statuscode' => '404', 'status' => 'warning', 'message' => 'invalid params'];
        }

        $this->response($data);
    }

    //*** Donation Like Method Starts ***//
    public function campaign_like_dislike_post()
    {
        //snigdho
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {
                $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $campaign_id   = xss_clean(($this->input->post('campaign_id') != '') ? $this->input->post('campaign_id') : '');
            } else {
                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $campaign_id = xss_clean((isset($postData['campaign_id'])) ? $postData['campaign_id'] : '');
            }

            if ($user_id != '' && $campaign_id != '') {
                $result = $this->api_model->campaignLikeDislike($user_id, $campaign_id);

                if ($result == '1') {
                    $data = ['statuscode' => '200', 'status' => 'success', 'like_type' => $result, 'message' => 'You have liked the donation'];
                } else {
                    $data = ['statuscode' => '200', 'status' => 'success', 'like_type' => $result, 'message' => 'You have disliked the donation'];
                }
            } else {
                $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Required fields are missing!'];
            }
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
        }

        $this->response($data);
    }
    //*** Donation Like Method Ends ***//
    public function campaign_comment_post()
    {
        //snigdho
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {
                $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $campaign_id   = xss_clean(($this->input->post('campaign_id') != '') ? $this->input->post('campaign_id') : '');
                $comment   = xss_clean(($this->input->post('comment') != '') ? $this->input->post('comment') : '');
            } else {
                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $campaign_id = xss_clean((isset($postData['campaign_id'])) ? $postData['campaign_id'] : '');
                $comment      = xss_clean((isset($postData['comment'])) ? $postData['comment'] : '');
            }

            if ($user_id != '' && $campaign_id != '' && $comment != '') {
                $result = $this->api_model->campaignAddComment($user_id, $campaign_id, $comment);

                $params = array(
                    'user_id' => $user_id,
                    'campaign_id' => $campaign_id
                );

                $CommentsData   =   $this->api_model->getComments($params, TRUE);

                if (!empty($CommentsData)) {
                    $comments_data = $CommentsData;
                } else {
                    $comments_data = '';
                }

                if ($result == TRUE) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Comment added!', 'comments_data' => $comments_data];
                } else {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Comment not added!', 'comments_data' => ''];
                }
            } else {
                $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Required fields are missing!'];
            }
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
        }

        $this->response($data);
    }

    public function campaign_comments_list_post()
    {
        //snigdho
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {
                $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $campaign_id   = xss_clean(($this->input->post('campaign_id') != '') ? $this->input->post('campaign_id') : '');
            } else {
                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $campaign_id = xss_clean((isset($postData['campaign_id'])) ? $postData['campaign_id'] : '');
            }

            if ($campaign_id != '') {
                if ($user_id != '') {
                    $params = array(
                        'user_id' => $user_id,
                        'campaign_id' => $campaign_id
                    );
                } else {
                    $params = array(
                        'campaign_id' => $campaign_id
                    );
                }


                $CommentsData   =   $this->api_model->getComments($params, TRUE);

                if (!empty($CommentsData)) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Comment added!', 'comments_data' => $CommentsData];
                } else {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Comment not added!', 'comments_data' => ''];
                }
            } else {
                $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Required fields are missing!'];
            }
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
        }

        $this->response($data);
    }

    public function donee_approval_post()
    {
        //snigdho
        if ($this->verify_token()) {

            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {
                $donation_id   = xss_clean(($this->input->post('donation_id') != '') ? $this->input->post('donation_id') : '');
                $donee_approved   = xss_clean(($this->input->post('donee_approved') != '') ? $this->input->post('donee_approved') : '');
                $approved_donee_id   = xss_clean(($this->input->post('approved_donee_id') != '') ? $this->input->post('approved_donee_id') : '');
                $donor_id   = xss_clean(($this->input->post('donor_id') != '') ? $this->input->post('donor_id') : '');
            } else {
                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $donation_id   = xss_clean((isset($postData['donation_id'])) ? $postData['donation_id'] : '');
                $donee_approved   = xss_clean((isset($postData['donee_approved'])) ? $postData['donee_approved'] : '');
                $approved_donee_id = xss_clean((isset($postData['approved_donee_id'])) ? $postData['approved_donee_id'] : '');
                $donor_id = xss_clean((isset($postData['donor_id'])) ? $postData['donor_id'] : '');
            }

            if ($donation_id != '' && $donee_approved != '' && $approved_donee_id != '' && $donor_id != '') {
                $params = array(
                    'donation_id' => $donation_id
                );


                $donationData   =   $this->api_model->getDonations($params, FALSE);

                if (!empty($donationData)) {

                    $params_upd = array(
                        'donee_approved' => $donee_approved,
                        'approved_donee_id' => $approved_donee_id
                    );
                    $donationUpd   =   $this->api_model->updateCampaign($donation_id, $params_upd);

                    if ($donationUpd) {

                        //send notification starts
                        $userDetails  =   $this->api_model->getUsers(array('user_id' => $donor_id));

                        //print_obj($userDetails);die;
                        if (!empty($userDetails) && $userDetails['fcm_token'] != '') {
                            $fcm = $userDetails['fcm_token'];
                            $name = $userDetails['first_name']. ' ' .$userDetails['last_name'];
                            //$fcm = 'cNf2---6Vs9';
                            $icon = NOTIFICATION_ICON;
                            $notification_title = 'Your donation has been approved.';
                            $notification_body = 'Congrats!! Your donation has been approved by Donee.';
                            $click_action = CLICK_ACTION;

                            $data = array(
                                "to" => $fcm,
                                "notification" => array(
                                    "title" => $notification_title,
                                    "body" => $notification_body,
                                    "icon" => $icon,
                                    "click_action" => $click_action
                                )
                            );
                            $data_string = json_encode($data);

                            //echo "The Json Data : " . $data_string;

                            $headers = array(
                                'Authorization: key=' . API_ACCESS_KEY,
                                'Content-Type: application/json'
                            );

                            $ch = curl_init();

                            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

                            $result = curl_exec($ch);

                            curl_close($ch);

                            $result_ar = json_decode($result);
                            //print_obj($result_ar);
                        }
                        //send notification ends

                        $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Donation Approved!', 'donation_data' => $donationData];
                    } else {
                        $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Donation not Approved!', 'donation_data' => ''];
                    }
                } else {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Donor not found!', 'donation_data' => ''];
                }
            } else {
                $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Required fields are missing!'];
            }
        } else {
            $data   =   ['successcode' => '404', 'status' => 'failed', 'message' => 'token not found'];
        }

        $this->response($data);
    }

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

    public function add_preference_post()
    {
        //snigdho
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $preference_array   = xss_clean(($this->input->post('preferences') != '') ? $this->input->post('preferences') : '');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $preference_array   = xss_clean((isset($postData['preferences'])) ? $postData['preferences'] : '');
            }
            //print_obj($postData);die;
            if ($user_id != '' && $preference_array != '') {

                $param = array(
                    'user_id' => $user_id
                );

                $getUserPref   =   $this->api_model->get_user_preferences($param);

                if (!empty($getUserPref)) {
                    //remove 
                    $deleted   =   $this->api_model->delete_user_preferences($param);
                }

                foreach ($preference_array as $key => $value) {
                    $data   =   [
                        'user_id'       =>  $user_id,
                        'selected_pref_id'   =>  $value
                    ];
                    $inserted   =   $this->api_model->add_user_preferences($data);
                }

                if ($inserted) {
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Preferences added'];
                } else {
                    $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Preference not added'];
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

    public function delete_preference_post()
    {
        //snigdho
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                //$user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $pref_id   = xss_clean(($this->input->post('pref_id') != '') ? $this->input->post('pref_id') : '');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                //$user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $pref_id   = xss_clean((isset($postData['pref_id'])) ? $postData['pref_id'] : '');
            }
            //print_obj($postData);die;
            if ($pref_id != '') {

                $param = array(
                    //'user_id' => $user_id,
                    'id' => $pref_id
                );

                $getUserPref   =   $this->api_model->get_user_preferences($param);

                if (!empty($getUserPref)) {
                    //remove 
                    $deleted   =   $this->api_model->delete_user_preferences($param);

                    if ($deleted) {
                        $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Preferences deleted'];
                    } else {
                        $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Preference not deleted'];
                    }
                } else {
                    $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Preference not found'];
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

    public function filter_by_type_list_post()
    {
        //snigdho
        $postData = $this->input->post();

        // if (isset($postData) && !empty($postData)) {
        //     $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
        // } else {
        //     $jsonData = file_get_contents('php://input');
        //     $postData = json_decode($jsonData, true);

        //     $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
        // }

        // if (isset($postData) && $user_id != '') {

        $param = array(
            'status' => 1
        );

        $getUserPref   =   $this->api_model->getFilterByType($param, TRUE);

        if (!empty($getUserPref)) {
            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $getUserPref];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'no preference found'];
        }
        // } else {
        //     $data = ['statuscode' => '404', 'status' => 'warning', 'message' => 'invalid params'];
        // }

        $this->response($data);
    }

    public function change_password_post()
    {
        //snigdho
        if ($this->verify_token()) {
            $postData = $this->input->post();

            if (isset($postData) && !empty($postData)) {

                $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
                $new_password   = xss_clean(($this->input->post('new_password') != '') ? $this->input->post('new_password') : '');
            } else {

                $jsonData = file_get_contents('php://input');
                $postData = json_decode($jsonData, true);

                $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
                $new_password   = xss_clean((isset($postData['new_password'])) ? $postData['new_password'] : '');
            }
            //print_obj($postData);die;
            if ($user_id != '' && $new_password != '') {

                $getUserDet   =   $this->api_model->get_user_profile_info($user_id);
                // print_obj($getUserDet);die;

                if (!empty($getUserDet)) {
                    $dataU   =   [
                        'password'  =>  md5($new_password)
                    ];
                    $update =   $this->api_model->update_user_profile_info($user_id, $dataU);
                    if ($update) {
                        $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Password changed successfully'];
                    } else {
                        $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Password not changed!'];
                    }
                } else {
                    $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'User not found!'];
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


    public function user_likes_post()
    {
        //snigdho
        $postData = $this->input->post();

        if (isset($postData) && !empty($postData)) {
            $user_id   = xss_clean(($this->input->post('user_id') != '') ? $this->input->post('user_id') : '');
        } else {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            $user_id   = xss_clean((isset($postData['user_id'])) ? $postData['user_id'] : '');
        }

        if (isset($postData) && $user_id != '') {

            $params = array(
                'liked_campaign.user_id' => $user_id,
                'campaign_master.state' => '1',
                'campaign_master.status' => '1',
                'liked_campaign.like_status' => '1'
            );

            $campaignData   =   $this->api_model->getUserLikes($params, TRUE);

            if (!empty($campaignData)) {
                //print_obj($campaignData);die;

                $data['campaign_data'] = $campaignData;
            } else {
                $data['campaign_data'] = '';
            }
        } else {
            $data['campaign_data'] = '';
        }

        if (!empty($data['campaign_data'])) {
            $data = ['statuscode' => '200', 'status' => 'success', 'data' => $data];
        } else {
            $data = ['statuscode' => '405', 'status' => 'warning', 'message' => 'No likes found!'];
        }

        $this->response($data);
    }

    public function firebase_test_post()
    {
        //snigdho
        $fcm = 'eR5CaSUQSr--MBbIZ61aqA:APA91bFRVkb-BZlFQQWLojasc6po_evMGeQlFrUY25B25UEPwunwtmu3Zl9l1L4H6Fdz8Sn6LvQ2z0xodL2IwGENL3q-uFbwbCkylv0QQtbBycKPWjRpdL3PoSOLD1YC1cG7AFrjex5Y';
        $title = 'ABC.com';
        $body = 'A Code Sharing Blog!';
        $icon = NOTIFICATION_ICON;
        $click_action = 'http://abc.com';

        $data = array(
            "to" => $fcm,
            "notification" => array(
                "title" => $title,
                "body" => $body,
                "icon" => $icon,
                "click_action" => $click_action
            )
        );
        $data_string = json_encode($data);

        //echo "The Json Data : " . $data_string;

        $headers = array(
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

        $result = curl_exec($ch);

        curl_close($ch);

        $result_ar = json_decode($result);
        print_obj($result_ar);
    }
}
