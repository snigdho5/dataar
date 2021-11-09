<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

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
        $this->load->library(['form_validation','encryption', 'encrypt','session','javascript','image_lib','pagination']);
        $this->load->helper(['url', 'form', 'date','admin_helper']);
        $this->encryption->create_key(16);
        $this->load->model(['admin_model','login_model']);
        $this->gallery_path = realpath(APPPATH . '../uploads');
    }
    
    /********************************************   VIEW  ********************************************/
    
    public function index($page = 'dashboard')
    {
        //print_r($_SESSION);
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else
        {
            if(!file_exists(APPPATH . 'views/admin/' . $page . '.php')){
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
            
                //print_r($this->session->all_userdata());
                $this->load->view('admin/' . $page, $data);
            }
        }
    }
        
    public function login($page = 'login')
    {
        if(!file_exists(APPPATH . 'views/admin/' . $page . '.php'))
        {
            show_404();
        }
        else{
            $data['title'] = ucfirst($page);
            //$this->load->view('admin/login');
            $this->load->view('admin/' . $page, $data);
        }
    }
    
    public function register($page = 'register')
    {
        if($this->session->userdata('logged_in'))
        {
            return redirect('admin');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/' . $page . '.php'))
            {
                show_404();
            }
            else{
                //$this->load->view('admin/register');
                $data['title'] = ucfirst($page);
                $this->load->view('admin/' . $page, $data);
            }
        }
    }
    
    /********************************************   VIEW  ********************************************/
    
    /********************************************   PROFILE  ********************************************/
    
    public function adminlogin()
    {        
        $this->form_validation->set_rules('email_address', 'Username', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        if ($this->form_validation->run() == FALSE){
            $this->session->set_flashdata('error', validation_errors());
            return redirect('admin/login');
        }
        else{                        
            $data = [
                        'email_address' =>  $this->input->post('email_address'),
                        'status'        =>  1
                    ];            
            $loginUser   =   $this->login_model->login($data);
            //print_obj($loginUser);die;
            if(!empty($loginUser)){
                if (password_verify($this->input->post('password', false), $loginUser[0]->password)){
                    $this->session->set_userdata('logged_in', $loginUser[0]->email_address);
                    $this->session->set_userdata('firstName', $loginUser[0]->first_name);
                    $this->session->set_userdata('lastName', $loginUser[0]->last_name);
                    $this->session->set_userdata('prof_img', $loginUser[0]->profile_image);
                    $user_data = $this->session->userdata('logged_in');
                    return redirect('admin');
                }  else{
                    $this->session->set_flashdata('error', 'User does not exists!');
                    return redirect('admin/login');
                }
            }else{
                $this->session->set_flashdata('error', 'Authentication Failed');
                return redirect('admin/login');
            }
            
          
        }
    }
    
    public function fileregister()
    {
        $this->form_validation->set_rules('username', 'Username', 'required');
        $this->form_validation->set_rules('email_address', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[5]');
        $this->form_validation->set_rules('conpassword', 'Confirm Password', 'required|matches[password]');
            
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('error', validation_errors());
            return redirect('admin/register');
        }
        else
        {            
            $data   =   [];
            
            $data['username']               =   $this->input->post('username', TRUE);
            //$data['password']               =   password_hash($this->input->post('password', false), PASSWORD_BCRYPT);
            $data['password']               =   md5($this->input->post('password', true));
            $data['is_admin']               =   1;
            $data['email_address']          =   $this->input->post('email_address', TRUE);
            $data['status']                 =   1;
                        
            $registerUser   =   $this->login_model->registerUser($data);
            
            if($registerUser > 0){
                $this->session->set_flashdata('success', 'Please Login!!!');
                return redirect('admin/login');
            }
            else{
                $this->session->set_flashdata('error', 'Something went wrong');
                return redirect('admin/register');
            }
        }
    }
    
    public function profile()
    {
        if(!$this->session->userdata('logged_in'))
        {
            return redirect('admin/login');
        }
        else{
            $sessionEmail   =   $this->session->userdata('logged_in');
            $data['profileData']   =   $this->admin_model->get_profile_data($sessionEmail);
            $this->load->view('admin/profile', $data);
        }
    }

    public function updateprofile()
    {
        $data   =   [];
        $sessionEmail           =   $this->session->userdata('logged_in');
        $data['first_name']     =   $this->input->post('first_name', TRUE);
        $data['last_name']      =   $this->input->post('last_name', TRUE);        
        $data['phone']          =   $this->input->post('phone', TRUE);

        /****************************** Single Image Upload ******************************/
            
        if(!empty($_FILES["profile_image"]['name'])){
            $data['profile_image']       =      time().'-'.$_FILES["profile_image"]['name'];
            $getImageName   =   $this->admin_model->getReplacedSingleImgName($sessionEmail);
            if(!empty($getImageName)){
                $deleteFile     =   './uploads/admin/'.$getImageName;
                if(is_readable($deleteFile) && unlink($deleteFile)){
                    //echo "The file has been deleted";                    
                    $this->session->set_userdata('prof_img', $data['profile_image']);
                }
            }
            
            $this->load->library('image_lib');
        
            $config['upload_path']       =   './uploads/admin/';
            $config['allowed_types']     =   'gif|jpg|png';
            $config['file_name']         =    $data['profile_image'];
            
            $this->load->library('upload', $config);
            $this->upload->do_upload('profile_image');
            $image_data = $this->upload->data();
            
            // Resize image to the given format
            $imageResize =  [
                                'image_library'   => 'gd2',
                                'source_image'    =>  $image_data['full_path'],
                                'maintain_ratio'  =>  TRUE,
                                'width'           =>  160,
                                'height'          =>  160,
                            ];

            $this->image_lib->clear();
            $this->image_lib->initialize($imageResize);
            $this->image_lib->resize();
        }
        
        /****************************** Single Image Upload ******************************/

        $updateProfile     =   $this->admin_model->updateprofile($sessionEmail, $data);

        if(count($updateProfile)){
            $this->session->set_userdata('firstName', $data['first_name']);
            $this->session->set_userdata('lastName', $data['last_name']);
            $this->session->set_flashdata('success', 'Profile updated successfully');
            echo redirectPreviousPage();
            exit;
        }
        else{
            $this->session->set_flashdata('error', 'Something went wrong');
            echo redirectPreviousPage();
            exit;
        }
    }
    
    public function changepassword()
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            $this->load->view('admin/change-password');
        }
    }

    public function changepass()
    {
        $data                       =   [];
        $data['email_address']      =   $this->session->userdata('logged_in');
        $password                   =   $this->input->post('new_password', TRUE);
        $confirmPassword            =   $this->input->post('confirm_password', TRUE);
        
        $checkUser   =   $this->login_model->login($data);
        
        if(password_verify($this->input->post('old_password'), $checkUser[0]->password)){
            if($password == $confirmPassword){
                $changePassword     =   $this->admin_model->changePassword($data,$confirmPassword);
                if($changePassword){
                    $this->session->set_flashdata('success', 'Password changed successfully');
                    echo redirectPreviousPage();
                    exit;
                }
                else{
                    $this->session->set_flashdata('error', 'Incorrect Old Password');
                    echo redirectPreviousPage();
                    exit;
                }            
            }
            else{
                $this->session->set_flashdata('error', 'Password Mismatch');
                echo redirectPreviousPage();
                exit;
            }
        }
        else{            
            $this->session->set_flashdata('error', 'Incorrect Old password');
            echo redirectPreviousPage();
            exit;
        }
    }
    
    /********************************************   PROFILE  ********************************************/
    
    /********************************************   BANNER   ********************************************/
    
    public function addbanner($page = 'add-new-banner')
    {
        if(!$this->session->userdata('logged_in'))
        {
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/' . $page . '.php'))
            {
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/' . $page);
            }
        }
    }
    
    public function insertBanner()
    {
        $this->form_validation->set_rules('bannerPage', 'Banner Page Name', 'trim|required');
        $this->form_validation->set_rules('status', 'Status', 'required');
        
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('error', validation_errors());
            //return redirect('admin/adddestination');
            echo redirectPreviousPage();
            exit;
        }
        else{
            $data['image']		=	time().$_FILES["image"]['name'];
		
            if(!is_dir('uploads/banners'))
            mkdir('uploads/banners');

            $this->load->library('image_lib');

            $config['upload_path']          = './uploads/banners/';
            $config['allowed_types']        = 'gif|jpg|png';
            $config['file_name']            =  $data['image'];

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('image')){
                $error = array('error' => $this->upload->display_errors());                
                $this->session->set_flashdata('error', 'Image format not supported');
                echo redirectPreviousPage();
                exit;
            }
            else{
                $image_data = $this->upload->data();
                // Resize image to the given format
                $imageResize =  [
                        'image_library'   => 'gd2',
                        'source_image'    =>  $image_data['full_path'],
                        'maintain_ratio'  =>  TRUE,
                        'width'           =>  1600,
                        'height'          =>  600,
                    ];
                $this->image_lib->clear();
                $this->image_lib->initialize($imageResize);
                $this->image_lib->resize();

                $insertStatus		=	$this->admin_model->add_new_banner($data);

                if($insertStatus == 1){
                    $this->session->set_flashdata('success', 'Banner added Successfully');
                    echo redirectPreviousPage();
                    exit;
                }
                else{
                    $this->session->set_flashdata('error', 'Banner already Exists');
                    echo redirectPreviousPage();
                    exit;
                }
            }
        }
    }

    /********************************************   BANNER   ********************************************/
    
    /********************************************   KIND   ********************************************/
    
    
    public function addkind($page = 'add')
    {
        if(!$this->session->userdata('logged_in'))
        {
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/kind/' . $page . '.php'))
            {
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/kind/' . $page);
            }
        }
    }
    
    public function insertkind()
    {
        if($this->input->post('kind_name', TRUE)){
            $data   =   [
                            'kind_name'     =>  $this->input->post('kind_name', TRUE),
                            'status'        =>  $this->input->post('status', TRUE)
                        ];
            $insert =   $this->admin_model->insert_kind($data);
            if($insert){
                $this->session->set_flashdata('success', 'kind added successfully');
                echo redirectPreviousPage();
                exit;
            }
            else{
                $this->session->set_flashdata('error', 'something went wrong');
                echo redirectPreviousPage();
                exit;
            }
        }
        else{            
            $this->session->set_flashdata('error', 'validation failed');
            echo redirectPreviousPage();
            exit;
        }
    }
    
    public function kindlist()
    {
        $draw   =   intval($this->input->get("draw"));
        $start  =   intval($this->input->get("start"));
        $length =   intval($this->input->get("length"));
        
        $query  =   $this->admin_model->fetch_dtb_data('kind_master');
        
        $data   =   [];
        foreach($query->result() as $r){
            $r->status      =   ($r->status == 0) ? 'Inactive' : 'Active';
            $data[] =   [
                            $r->kind_id,
                            $r->kind_name,
                            $r->status,
                            $r->edit = '<a href="'.base_url('/admin/editkind/').$r->kind_id.'">Edit</a>',
                        ];
        }

        $result =   [
                        "draw"              =>  $draw,
                        "recordsTotal"      =>  $query->num_rows(),
                        "recordsFiltered"   =>  $query->num_rows(),
                        "data"              =>  $data
                    ];
        echo json_encode($result);
        exit();
    }
    
    public function kinds($page = 'index')
    {
        if(!$this->session->userdata('logged_in'))
        {
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/kind/' . $page . '.php'))
            {
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/kind/' . $page);
            }
        }
    }
    
    public function editkind($id)
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            $data['kind']   =   $this->admin_model->edit_kind($id)[0];
            $this->load->view('admin/kind/edit', $data);
        }
    }
    
    public function updatekind()
    {
        $id                 =   $this->input->post('id', TRUE);
        $data['kind_name']  =   $this->input->post('kind_name', TRUE);
        $data['status']     =   $this->input->post('status', TRUE);
        
        $updatekind   =   $this->admin_model->update_kind($id, $data);

        if($updatekind == 1){
            $this->session->set_flashdata('success', 'Kind data updated successfully');
            echo redirectPreviousPage();
            exit;
        }
        else{
            $this->session->set_flashdata('error', 'Something went wrong');
            echo redirectPreviousPage();
            exit;
        }
    }


    /********************************************   KIND   ********************************************/
    
    /********************************************   CAMPAIGN   ********************************************/
    
    public function campaignlist()
    {
        $draw   =   intval($this->input->get("draw"));
        $start  =   intval($this->input->get("start"));
        $length =   intval($this->input->get("length"));
        
        //$query  =   $this->admin_model->fetch_dtb_data('campaign_master');
        $query  =   $this->admin_model->fetch_dtb_campaign();
        $data   =   [];
        foreach($query->result() as $r){
            $r->state      =   ($r->state == 0) ? 'Inactive' : 'Active';
            $r->username   =   $r->first_name.' '.$r->last_name;
            $data[] =   [
                            $r->campaign_id,
                            //$r->username,
                            $r->campaign_name,
                            $r->campaign_start_date,
                            $r->campaign_end_date,
                            $r->campaign_target_amount,
                            $r->state,
                            $r->edit = '<a href="'.base_url('/admin/viewcampaign/').$r->campaign_id.'">View Details</a>',
                        ];
        }

        $result =   [
                        "draw"              =>  $draw,
                        "recordsTotal"      =>  $query->num_rows(),
                        "recordsFiltered"   =>  $query->num_rows(),
                        "data"              =>  $data
                    ];
        echo json_encode($result);
        exit();
    }
    
    public function viewcampaign($id)
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            $data['campaign']   =   $this->admin_model->edit_campaign($id)[0];
            $this->load->view('admin/campaign/edit', $data);
        }
    }
    
    public function updatecampaign()
    {
        $id             =   $this->input->post('id', TRUE);
        $data['state']  =   $this->input->post('status', TRUE);
        
        $updatestatus   =   $this->admin_model->update_campaign($id, $data);

        if($updatestatus == 1){
            $this->session->set_flashdata('success', 'Campaign approved successfully');
            echo redirectPreviousPage();
            exit;
        }
        else{
            $this->session->set_flashdata('error', 'Something went wrong');
            echo redirectPreviousPage();
            exit;
        }
    }
    
    public function campaigns($page = 'index')
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/campaign/' . $page . '.php')){
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/campaign/' . $page);
            }
        }
    }
    
    /********************************************   CAMPAIGN   ********************************************/
    
    /********************************************   USERS   ********************************************/
    
    public function userlist()
    {
        $draw   =   intval($this->input->get("draw"));
        $start  =   intval($this->input->get("start"));
        $length =   intval($this->input->get("length"));
        
        $query  =   $this->admin_model->fetch_dtb_data('users');
        
        $data   =   [];
        foreach($query->result() as $r){
            $r->status      =   ($r->status == 0) ? 'Inactive' : 'Active';
            $r->user_type   =   ($r->user_type == 0) ? 'Donor' : 'Donee';
            $data[] =   [
                            $r->user_id,
                            $r->first_name,
                            $r->last_name,
                            $r->email,
                            $r->phone,
                            $r->user_type,
                            $r->status,
                            $r->edit = '<a href="'.base_url('/admin/edituser/').$r->user_id.'">Edit</a>',
                        ];
        }
        $result =   [
                        "draw"              =>  $draw,
                        "recordsTotal"      =>  $query->num_rows(),
                        "recordsFiltered"   =>  $query->num_rows(),
                        "data"              =>  $data
                    ];
        echo json_encode($result);
        exit();
    }
    
    public function users($page = 'index')
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/users/' . $page . '.php')){
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/users/' . $page);
            }
        }
    }
    
    public function edituser($id)
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            $data['user']   =   $this->admin_model->edit_user($id)[0];
            $this->load->view('admin/users/edit', $data);
        }
    }
    
    public function updateuser()
    {
        $id             =   $this->input->post('id', TRUE);
        $data['status']         =   $this->input->post('status', TRUE);
        $data['kyc_verified']   =   $this->input->post('kyc_verified', TRUE);
        $data['camp_auth']      =   $this->input->post('camp_auth', TRUE);
        
        $updatestatus   =   $this->admin_model->update_user($id, $data);

        if($updatestatus == 1){
            $this->session->set_flashdata('success', 'User approved successfully');
            echo redirectPreviousPage();
            exit;
        }
        else{
            $this->session->set_flashdata('error', 'Something went wrong');
            echo redirectPreviousPage();
            exit;
        }
    }
    
    /********************************************   USERS   ********************************************/
    
    /********************************************   DONATIONS   ********************************************/
    
    public function donationkinds()
    {
        $draw   =   intval($this->input->get("draw"));
        $start  =   intval($this->input->get("start"));
        $length =   intval($this->input->get("length"));
        
        $query  =   $this->admin_model->fetch_data_by_kind();
        
        $data   =   [];
        $k  =   1;
        foreach($query->result() as $r){
            $r->id  =   $k;
            $data[] =   [
                            $r->id,
                            $r->user,
                            $r->phone,
                            $r->kind,
                            $r->quantity,
                            $r->campaign,
                            $r->start,
                            $r->expiry,
                        ];
            $k++;
        }
        $result =   [
                        "draw"              =>  $draw,
                        "recordsTotal"      =>  $query->num_rows(),
                        "recordsFiltered"   =>  $query->num_rows(),
                        "data"              =>  $data
                    ];
        echo json_encode($result);
        exit();
    }

    public function kindslist($page = 'kind')
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/donation/' . $page . '.php')){
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/donation/' . $page);
            }
        }
    }
    
    public function donationcash()
    {
        $draw   =   intval($this->input->get("draw"));
        $start  =   intval($this->input->get("start"));
        $length =   intval($this->input->get("length"));
        
        $query  =   $this->admin_model->fetch_data_by_cash();
        
        $data   =   [];
        $k  =   1;
        foreach($query->result() as $r){
            $r->id  =   $k;
            $data[] =   [
                            $r->id,
                            $r->user,
                            $r->phone,
                            $r->campaign,
                            $r->donation_amount,
                            $r->start,
                            $r->expiry,
                        ];
            $k++;
        }
        $result =   [
                        "draw"              =>  $draw,
                        "recordsTotal"      =>  $query->num_rows(),
                        "recordsFiltered"   =>  $query->num_rows(),
                        "data"              =>  $data
                    ];
        echo json_encode($result);
        exit();
    }
    
    public function cashlist($page = 'cash')
    {
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            if(!file_exists(APPPATH . 'views/admin/donation/' . $page . '.php')){
                show_404();
            }
            else{
                $data['title'] = ucfirst($page);
                $this->load->view('admin/donation/' . $page);
            }
        }
    }
    
    /********************************************   DONATIONS   ********************************************/


    /********************************************   CMS   ********************************************/

    public function cms()
    {
        // echo 'xaxa'; die();
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            $this->load->view('admin/cms/index');
        }
    }
    
    public function cml_list_data()
    {
        $draw   =   intval($this->input->get("draw"));
        $start  =   intval($this->input->get("start"));
        $length =   intval($this->input->get("length"));
        
        $query  =   $this->admin_model->fetch_dtb_data('cms_master');
        
        $data = [];
        foreach($query->result() as $r){
            $data[] = [
                $r->id,
                $r->page_title,
                $r->edit = '<a href="'.base_url('/admin/editcms/').$r->id.'">Edit</a>',
            ];
        }

        $result = [
            "draw"              =>  $draw,
            "recordsTotal"      =>  $query->num_rows(),
            "recordsFiltered"   =>  $query->num_rows(),
            "data"              =>  $data
        ];
        echo json_encode($result);
        exit();
    }

    public function editcms($id)
    {
        $page = 'edit-cms';
        if(!$this->session->userdata('logged_in')){
            return redirect('admin/login');
        }
        else{
            $data['title']  =   ucfirst($page);
            $data['cms']    =   $this->admin_model->fetch_cms_data($id);
            $this->load->view('admin/cms/edit', $data);
        }
    }
    
    public function updatecms()
    {
        $data   =   [];     
        $id                 =   $this->input->post('id', TRUE);
        $data['page_title'] =   $this->input->post('page_title', TRUE);
        $data['page_text']  =   htmlentities($this->input->post('page_text', TRUE));
        
        $updatebanner   =   $this->admin_model->update_cms($id, $data);

        if($updatebanner == 1){
            $this->session->set_flashdata('success', 'CMS updated successfully');
            echo redirectPreviousPage();
            exit;
        }
        else{
            $this->session->set_flashdata('error', 'Something went wrong');
            echo redirectPreviousPage();
            exit;
        }        
    }

    /********************************************   CMS   ********************************************/
    
    // Logout from admin page
    public function logout()
    {
        $this->session->unset_userdata(['logged_in','firstName','lastName','prof_img']);
        $data['message_display'] = 'Successfully Logout';
        redirect('admin/login');
    }
}