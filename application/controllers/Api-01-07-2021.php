<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'/libraries/REST_Controller.php');
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
        $this->load->library(['form_validation','encryption', 'encrypt','session','javascript','image_lib','pagination','Authorization_Token']);
        $this->load->helper(['url', 'form', 'date','admin_helper']);
        // $this->encryption->create_key(16);?
        $this->load->model(['api_model']);
        $this->gallery_path = realpath(APPPATH . '../uploads');
    }
    /*************************************************  REGISTRATION & LOGIN    *************************************************/
    
    public function verify_token()
    {
        $headers        =   $this->input->request_headers();
        @$decodeToken   =   $this->authorization_token->validateToken($headers['Authorization']);
        return $decodeToken;
    }

    //user register module
    public function register_post()
    {
        if($this->input->post('firstname',TRUE) && $this->input->post('lastname',TRUE) && $this->input->post('email',TRUE) && $this->input->post('phone',TRUE) && $this->input->post('password',TRUE)){

            $fourDigitOtp = rand(1000,9999);
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
            $this->email->to($this->input->post('email',TRUE));
            $this->email->subject('Dataar OTP'); 
            $this->email->message('Hello '.$this->input->post('firstname',TRUE).', your OTP is '.$fourDigitOtp);
            $this->email->send();

            $checkUserExists    =   [
                                        'email'     =>  $this->input->post('email',TRUE),
                                        'phone'     =>  $this->input->post('phone',TRUE)
                                    ];
            
            $validateUser   =   $this->api_model->check_user_exists($checkUserExists);
            if($validateUser == true){
                $data   =   [
                                'statuscode'    =>  '405',
                                'status'        =>  'warning',
                                'message'       =>  'User already exist!!',
                                'usrMobileNo'   =>  $this->input->post('phone'),
                                'usrEmail'      =>  $this->input->post('email')
                            ];
            }
            else{
                $data   =   [
                                'first_name'=>  ucfirst($this->input->post('firstname',TRUE)),
                                'last_name' =>  ucfirst($this->input->post('lastname',TRUE)),
                                'email'     =>  $this->input->post('email',TRUE),
                                'phone'     =>  $this->input->post('phone',TRUE),
                                'password'  =>  md5($this->input->post('password',TRUE)),
                                'user_type' =>  $this->input->post('usertype',TRUE),
                                'status'    =>  '1',
                                'otp'       =>  $fourDigitOtp
                            ];
                $insertNewUser      =   $this->api_model->insert_new_user($data);
                if($insertNewUser > 0){
                    //register device for push notification
                    $device['user_id']		=   $insertNewUser;
                    $device['firebase_token']	=   $this->input->post('device_id',TRUE);
                    $device['user_type']        =   $this->input->post('device_type',TRUE);
                    $registeredDevice           =   $this->api_model->register_device_notification($device);
                    $data = [
                                'statuscode'	=>  '200',									
                                'status'        =>  'success',
                                'message'       =>  'User Registered Sucessfully',
                            ];
                }
                else{
                    $data   =   ['statuscode' => '200', 'status' => 'failure', 'message' => 'something went wrong'];
                }
            }
        }
        else{
            $data   =   ['statuscode' => '200', 'status' => 'failure', 'message' => 'form validation failed'];
        }
        $this->response($data);
    }
    //otp verification
    public function user_otp_verification_post()
    {
        header('Content-Type: application/json');
        if($this->input->post('phone',TRUE) && $this->input->post('otp',TRUE)){
            $params =   [
                            'phone'         =>  $this->input->post('phone'),
                            'otp'           =>  $this->input->post('otp')
                        ];

            $otp_status     =   $this->api_model->check_user_otp_status($params);
            if($otp_status == true){
                $return     =   $this->api_model->get_user_data($params)[0];

                $token['user_id']       =   $return->user_id;
                $token['first_name']    =   $return->first_name;
                $token['last_name']     =   $return->last_name;
                $token['email']         =   $return->email;

                $tokenData  =   $this->authorization_token->generateToken($token);

                $return->token          =   $tokenData;

                $data   = $return;
            }
            else{
                $data   =   ['status' => 'error','message'  => 'OTP Verification Failed'];   
            }
        }
        else{
            $data = ['status' => 'warning', 'message' => 'Some Parameter Missing'];
        }
        $this->response($data);
    }
    //resend otp
    public function resend_user_otp_post()
    {
        //$this->load->library('twilio');
        header('Content-Type: application/json');
        if($this->input->post('phone',TRUE)){
            $params =   ['phone' => $this->input->post('phone')];
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
            $this->email->message('Hello '.$this->input->post('firstname',TRUE).', your OTP is '.$otp);
            $this->email->send();

            $mobile_number_exist = $this->api_model->check_mobile_number_exist($params);
            if($mobile_number_exist == true){
                $params_update  =   ['otp' => $otp];
                $update_user    =   $this->api_model->update_user_otp($params, $params_update);
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'OTP Send Successfully'];
            }
            else{
                $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Mobile number not exist'];
            }
        }
        else{
            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Some Parameter Missing'];
        }
        $this->response($data);
    }
    //user login module
    public function login_post()
    {
        $username   =   $this->input->post('username');
        $password   =   $this->input->post('password');
        $login_ip   =   $this->input->ip_address();
        $login_time =   date('Y-m-d H:i:s');
        
        if(!empty($username) && !empty($password)){
            $return     =   $this->api_model->validate_login($username, $password, $login_ip, $login_time);
        
            if($return == 'not_found'){
                $data   =   ['statuscode' => '404','status' => 'error', 'message' => 'user not found', 'userdata' => ''];
            }
            else if($return == 'password_incorrect'){
                $data   =   ['statuscode' => '405','status' => 'warning', 'message' => 'password incorrect', 'userdata' => ''];
            }
            else if($return == 'inactive'){
                $data   =   ['statuscode' => '405','status' => 'warning', 'message' => 'user inactive', 'userdata' => ''];
            }
            else if($return == 'cannot_validate'){
                $data   =   ['statuscode' => '405','status' => 'warning', 'message' => 'cannot login now', 'userdata' => ''];
            }
            else{
                $token['user_id']       =   $return->user_id;
                $token['first_name']    =   $return->first_name;
                $token['last_name']     =   $return->last_name;
                $token['email']         =   $return->email;

                $tokenData  =   $this->authorization_token->generateToken($token);

                $return->token          =   $tokenData;

                $data   = $return;
            }
        }
        else{
            $data   =   ['statuscode' => '405', 'status' => 'warning', 'message' => 'Some Parameter Missing'];
        }
        $this->response($data);
    }
    //login with google
    public function login_with_google_post()
    {
        $social			=	[];
        $check 			=	[];
        $userInfo 		=	[];

        $social['first_name']       =	$this->input->post('firstName');
        $social['last_name']        =	$this->input->post('lastName');
        $social['email']            =	$this->input->post('email');
        $social['google_token']     =	$this->input->post('googleToken');
        $social['status']           =	"1";
        $social['last_login_ip']    =	$this->input->ip_address();		

        $check['email']             = 	$this->input->post('email');
        $check['google_token']      = 	$this->input->post('googleToken');
        
        //check google user exists
        if($this->api_model->check_google_user_exist($check) == false){

            $this->api_model->insert_new_user($social);

            $userData   =   $this->api_model->check_google_user_exist($check);

            //register device for push notification
            $device['user_id']          =   $userData[0]['user_id'];
            $device['firebase_token']   =   $this->input->post('device_id',TRUE);
            $device['user_type']        =   $this->input->post('device_type',TRUE);
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'userdata' => $userData[0]];
        }
        else{
            $userData   =   $this->api_model->check_google_user_exist($check);
            //register device for push notification
            $device['user_id']          =   $userData[0]['user_id'];
            $device['firebase_token']	=   $this->input->post('device_id',TRUE);
            $device['user_type']        =   $this->input->post('device_type',TRUE);
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'userdata' => $userData[0]];
        }
        $this->response($data);
    }
    //login with facebook
    public function login_with_facebook_post()
    {
        $social     =	[];
        $check      =	[];
        $userInfo   =   [];

        $fullName   =	$this->input->post('fullName');
        $splitName  =   explode(' ',$fullName);

        $social['first_name']       =	$splitName[0];
        $social['last_name']        =	$splitName[1];
        $social['email']            =	$this->input->post('email');
        $social['status']           =	"1";
        $social['fb_token']         =	$this->input->post('facebookToken');
        $social['last_login_ip']    =	$this->input->ip_address();

        $check['email']             = 	$this->input->post('email');
        $check['fb_token']          = 	$this->input->post('facebookToken');

        //check facebook user exists
        if($this->api_model->check_facebook_user_exist($check) == false){
            $this->api_model->insert_new_user($social);
            $userData   =   $this->api_model->check_facebook_user_exist($check);
            //register device for push notification
            $device['user_id']		=   $userData[0]['user_id'];
            $device['firebase_token']	=   $this->input->post('device_id',TRUE);
            $device['user_type']        =   $this->input->post('device_type',TRUE);
            $registeredDevice           =   $this->api_model->register_device_notification($device);

            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'userdata' => $userData[0]];
        }
        else{
            $userData  	= 	$this->api_model->check_facebook_user_exist($check);
            //register device for push notification
            $device['user_id']		=   $userData[0]['user_id'];
            $device['firebase_token']	=   $this->input->post('device_id',TRUE);
            $device['user_type']	=   $this->input->post('device_type',TRUE);
            $registeredDevice           =   $this->api_model->register_device_notification($device);
            
            $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'User logged in successfully', 'userdata' => $userData[0]];
        }
        $this->response($data);
    }
    
    public function validate_user_kyc_post()
    {
        if($this->verify_token()){
            $user_id    =   $this->input->post('user_id');
            if(!empty($user_id)){
                $validate   =   $this->api_model->validate_current_profile($user_id);
                if($validate){                
                    $data   =   ['successcode' => '405', 'status' =>  'warning', 'message' => 'Please get your KYC done'];
                }
                else{
                    $data   =   ['successcode' => '200','status' => 'success', 'message' => 'KYC Verified'];
                }
            }
            else{
                $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'params missing'];
            }
        }
        $this->response($data);
    }

    /*************************************************  REGISTRATION & LOGIN    *************************************************/
    
    /*************************************************  PROFILE DATA    *************************************************/
    
    public function fetch_profile_data_post()
    {
        if($this->verify_token()['status']){
            header('Content-Type: application/json');
            $user_id    =   $this->input->post('user_id');
            if(!empty($user_id)){
                $return = $this->api_model->my_profile($user_id);
                if($return == 'user_not_found') {
                    $data   =   ['successcode' => '405','status' => 'failed', 'message' => 'user not found'];
                }
                else if($return == 'user_inactive'){
                    $data   =   ['successcode' => '405','status' => 'warning', 'message' => 'user inactive'];
                }
                else{
                    $data   =   ['successcode' => '200','status' => 'success', 'data' => $return];
                }
            }
            else{
                $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'params missing'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }
    
    public function update_user_profile_info_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('usrId',TRUE) && 
                $this->input->post('firstname',TRUE) && 
                $this->input->post('lastname',TRUE) && 
                $this->input->post('email',TRUE) && 
                $this->input->post('phone',TRUE))
            {

                $params_email_or_mobile_number_exist    =   [
                                                                'phone' => 	$this->input->post('phone'),
                                                                'email' => 	$this->input->post('email'),
                                                                'usrId' =>  $this->input->post('usrId')
                                                            ];

                $checkBothValidation    =   $this->api_model->check_user_exists($params_email_or_mobile_number_exist);

                if($checkBothValidation){
                    $data   =   [
                                    'statuscode'    =>  '405',
                                    'status'        =>  'warning',
                                    'message'       =>  'Email or Phone already exist!!',
                                    'usrMobileNo'   =>  $this->input->post('phone'),
                                    'usrEmail'      =>  $this->input->post('email')
                                ];
                }
                else{
                    $data               =   [];
                    $data['first_name'] =   ucfirst($this->input->post('firstname', TRUE));
                    $data['last_name']  =   ucfirst($this->input->post('lastname', TRUE));
                    $data['email']      =   $this->input->post('email', TRUE);
                    $data['phone']      =   $this->input->post('phone', TRUE);

                    $update_user    =   $this->api_model->update_user_profile_info($this->input->post('usrId'), $data);
                    $userdata       =   $this->api_model->get_user_profile_info($this->input->post('usrId'));
                    if($userdata['profile_img'] ==  "" || $userdata['profile_img'] == null){
                        $user_profile_image =   base_url() .'uploads/profile_images/default.png';
                    }
                    else{
                        $user_profile_image =   base_url() .'uploads/profile_images/' .$userdata['profile_img'];
                    }
                    $data   =   [
                                    'statuscode' 	=>  '200',
                                    'status'        =>  'success',
                                    'message'       =>  'Profile Info Updated Successfully',
                                    'first_name' 	=>  $userdata['first_name'],
                                    'last_name' 	=>  $userdata['last_name'],
                                    'email'         =>  $userdata['email'],
                                    'phone'         =>  $userdata['phone'],
                                    'created_at' 	=>  $userdata['created_at'],
                                    'profile_img' 	=>  $user_profile_image
                                ];
                }
            }
            else{
                $data   =   ['statuscode' => '404','status' => 'warning','message' => 'Some Parameter Missing'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }
    
    public function update_kyc_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('user_id')){

                /************************************************* KYC UPLOAD *************************************************/

                if(!is_dir('uploads/kyc'))
                    mkdir('uploads/kyc');

                $new_name                  =   time().'-'.$_FILES["kycfile"]['name'];
                $config['file_name']       =   $new_name;
                $config['upload_path']     =   "uploads/kyc/";
                $config['allowed_types']   =   'gif|jpg|png|pdf';

                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if($this->upload->do_upload('kycfile')){
                    $uploadData          =     $this->upload->data();
                    $uploadedFile        =     $uploadData['file_name'];
                    $data   =   [
                                    'kyc_file'  =>  $uploadedFile
                                ];
                    $update =   $this->api_model->update_user_profile_info($this->input->post('user_id'), $data);
                    if($update){
                        $data   =   ['statuscode' => '200', 'status' => 'success', 'KYC uploaded successfully'];
                    }
                    else{
                        $data   =   ['statuscode' => '405', 'status' => 'warning', 'Something went wrong'];
                    }
                }
                else{
                    $data   =   ['statuscode' => '404', 'status' => 'failed', 'KYC upload failed'];
                }

                /************************************************* KYC UPLOAD *************************************************/

            }
            else{
                $data   =   ['statuscode' => '404', 'status' => 'failed', 'invalid user'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    /*************************************************  PROFILE DATA    *************************************************/
    
    /*************************************************  CAMPAIGN DATA    *************************************************/
    
    public function campaign_list_post()
    {
        if($this->verify_token()['status']){
            $data   =   $this->api_model->campaign_list();
            if(count($data)){
                $data = ['statuscode' => '200','status' => 'success','data' => $data];            
            }
            else{
                $data = ['statuscode' => '405', 'status' => 'warning','message' => 'no campaign found'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function add_campaign_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('campaign_name',TRUE) && 
                $this->input->post('user_id',TRUE) && 
                $this->input->post('donation_mode',TRUE) && 
                $this->input->post('campaign_details',TRUE) && 
                $this->input->post('campaign_start_date',TRUE) && 
                $this->input->post('campaign_end_date',TRUE) &&
                $this->input->post('campaign_target_amount',TRUE))
            {
                $data   =   [
                                'user_id'                   =>  $this->input->post('user_id',TRUE),
                                'campaign_name'             =>  $this->input->post('campaign_name',TRUE),
                                'donation_mode'             =>  $this->input->post('donation_mode',TRUE),
                                'campaign_details'          =>  $this->input->post('campaign_details',TRUE),
                                'campaign_start_date'       =>  $this->input->post('campaign_start_date',TRUE),
                                'campaign_end_date'         =>  $this->input->post('campaign_end_date',TRUE),
                                'campaign_image'            =>  $this->input->post('campaign_image',TRUE),
                                'area_lat'                  =>  $this->input->post('area_lat',TRUE),
                                'area_long'                 =>  $this->input->post('area_long',TRUE),
                                'campaign_target_amount'    =>  $this->input->post('campaign_target_amount',TRUE)
                            ];
                if($this->api_model->is_user_verified($this->input->post('user_id',TRUE))){
                    if($this->api_model->auth_to_create_camp($this->input->post('user_id',TRUE))){
                        $inserted   =   $this->api_model->insert_new_campaign($data);
                        if($inserted){
                            $data = ['statuscode' => '200','status' => 'success','message' => 'Campaign added sucessfully'];
                        }
                        else{
                            $data = ['statuscode' => '405','status' => 'warning','message' => 'Something went wrong'];
                        }
                    }
                    else{
                        $data = ['statuscode' => '405','status' => 'warning','message' => 'You are not authorised to create campaign'];
                    }
                }
                else{
                    $data = ['statuscode' => '405','status' => 'warning','message' => 'Contact us for KYC approval'];
                }
            }
            else{
                $data = ['statuscode' => '404','status' => 'error','message' => 'Some params missing'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    public function update_campaign_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('campaign_id',TRUE) && $this->input->post('user_id',TRUE)){
                $data   =   [
                                'user_id'                   =>  $this->input->post('user_id',TRUE),
                                'campaign_name'             =>  $this->input->post('campaign_name',TRUE),
                                'donation_mode'             =>  $this->input->post('donation_mode',TRUE),
                                'campaign_details'          =>  $this->input->post('campaign_details',TRUE),
                                'campaign_start_date'       =>  $this->input->post('campaign_start_date',TRUE),
                                'campaign_end_date'         =>  $this->input->post('campaign_end_date',TRUE),
                                'campaign_image'            =>  $this->input->post('campaign_image',TRUE),
                                'campaign_target_amount'    =>  $this->input->post('campaign_target_amount',TRUE)
                            ];
                $update_campaign    =   $this->api_model->update_single_campaign($this->input->post('campaign_id'), $data);
                $campaigndata       =   $this->api_model->get_single_campaign($this->input->post('campaign_id'));
                
                $data   =   ['statuscode' => '200','status' => 'success','message' => 'Campaign updated successfully','data' => $campaigndata];
            }
            else{
                $data   =   ['statuscode' => '404','status' => 'error','message' => 'Some params missing']; 
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }

    /*************************************************  CAMPAIGN DATA    *************************************************/
    
    /*************************************************  DONATION DATA    *************************************************/
    
    public function add_donation_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('user_id',TRUE) && 
                $this->input->post('campaign_id',TRUE) && 
                $this->input->post('status',TRUE))
            {
                $data   =   [
                                'user_id'       =>  $this->input->post('user_id',TRUE),
                                'campaign_id'   =>  $this->input->post('campaign_id',TRUE),
                                'kind_id'       =>  $this->input->post('kind_id',TRUE),
                                'quantity'      =>  $this->input->post('quantity',TRUE),
                                'amountpaid'    =>  $this->input->post('amountpaid',TRUE),
                                'status'        =>  $this->input->post('status',TRUE)
                            ];
                $checkUser  =   $this->api_model->is_user_verified($this->input->post('user_id',TRUE));
                if($checkUser){
                    $inserted   =   $this->api_model->insert_new_payment($data);
                    if($inserted){
                        $data   =   ['statuscode' => '200','status' => 'success','message' => 'Transanction completed successfully'];
                    }
                    else{
                        $data   =   ['statuscode' => '405','status' => 'warning','message' => 'Something went wrong'];
                    }
                }
                else{
                    $data   =   ['statuscode' => '405','status' => 'warning','message' => 'Contact us for KYC approval'];
                }
            }
            else{
                $data   =   ['statuscode' => '404','status' => 'error','message' => 'Some Parameter Missing'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }
    
    public function user_donation_data_list_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('user_id',TRUE) && $this->input->post('donation_type',TRUE)){            
                if($this->input->post('donation_type',TRUE) == 'kind'){
                    $data   =   $this->api_model->fetch_data_by_kind($this->input->post('user_id',TRUE));
                    $data = ['statuscode' => '200','status' => 'success','data' => $data];            
                }
                else if($this->input->post('donation_type',TRUE) == 'cash'){
                    $data   =   $this->api_model->fetch_data_by_cash($this->input->post('user_id',TRUE));
                    $data = ['statuscode'    =>  '200','status' => 'success','data' => $data];
                }
                else{
                    $data = ['statuscode' => '405','status' => 'warning','message' => 'no campaign found'];
                }
            }
            else{
                $data = ['statuscode' => '404','status' => 'warning','message' => 'invalid params'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }
    
    /*************************************************  DONATION DATA    *************************************************/
    
    /*************************************************  FAVOURITE DATA    *************************************************/
    
    public function add_to_favourite_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('user_id',TRUE) && 
                $this->input->post('campaign_id',TRUE)){
                $data   =   [
                                'user_id'       =>  $this->input->post('user_id',TRUE),
                                'campaign_id'   =>  $this->input->post('campaign_id',TRUE)
                            ];
                $inserted   =   $this->api_model->add_my_favourite($data);
                if($inserted){
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'Campaign added to favourites'];
                }
                else{
                    $data = ['statuscode' => '405', 'status' => 'error', 'message' => 'Campaign removed from favourites'];
                }
            }
            else{
                $data = ['statuscode' => '404', 'status' => 'error', 'message' => 'Invalid params'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
            $this->response($data);
        }
    }
    
    public function my_favourites_post()
    {
        if($this->verify_token()['status']){
            if($this->input->post('user_id',TRUE)){
                $favourites   =   $this->api_model->get_my_favourite($this->input->post('user_id',TRUE));
                if(count($favourites)){
                    $data = ['statuscode' => '200', 'status' => 'success', 'data' => $favourites];
                }
                else{
                    $data = ['statuscode' => '200', 'status' => 'success', 'message' => 'no favourites found'];
                }
            }
            else{
                $data = ['statuscode' => '404', 'status' => 'error', 'message' => 'Invalid params'];
            }
            $this->response($data);
        }
        else{
            $data   =   ['successcode' => '404','status' => 'failed', 'message' => 'token not found'];
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
        if(!$this->email->send()) {
            show_error($this->email->print_debugger()); }
        else {
            echo 'Your e-mail has been sent!';
        }
    }
}