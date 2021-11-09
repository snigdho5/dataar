<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Frontend extends CI_Controller{

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
        $this->load->library('javascript');
        $this->load->library('form_validation');
        $this->load->library('email');
        $this->load->library('session');
        $this->load->library('pagination');
        //$this->load->library('user_agent');
        $this->load->helper('admin_helper');
        $this->load->model('frontend_model');
        $this->CI = & get_instance();
        $this->load->helper(array('form', 'url'));
        //$this->load->helper('frontend_helper');
        // Session
        $this->load->library('image_lib');
        $this->load->library("pagination");

        $this->gallery_path = realpath(APPPATH . '../uploads');
    }
    
    public function index()
    {
        $data['domesticData']               =   $this->frontend_model->domesticHomePackage();

        $data['internationalData']          =   $this->frontend_model->internationalHomePackage();
        
        $domesticFeatured                   =   $this->frontend_model->bestBuyFeaturedDomestic();
        
        $internationalFeatured              =   $this->frontend_model->bestBuyFeaturedInternational();
        
        if(!empty($domesticFeatured) && !empty($internationalFeatured)){
            $data['featuredData']               =   array_merge($domesticFeatured,$internationalFeatured);
        }
        else if(!empty($domesticFeatured)){
            $data['featuredData']               =   $domesticFeatured;
        }
        else{
            $data['featuredData']               =   $internationalFeatured;
        }
        /*echo '<pre>';
        print_r($data['featuredData']);
        die();*/
        $this->load->view('home', $data);
    }
    
    public function about()
    {
        $this->load->view('about');
    }
    
    public function domestic()
    {
        //$data['domesticData']               =   $this->frontend_model->domesticPackage();
        
        $table      =   'domestic_package';
        
        $config     =   [
                            'base_url'          =>      base_url('frontend/domestic'),
                            'per_page'          =>      3,
                            'total_rows'        =>      $this->frontend_model->numrowsPackage($table),
                            'full_tag_open'     =>      '<ul class="pagination justify-content-end w-100 mb-50 ">',
                            'full_tag_close'    =>      '</ul>',
                            'first_link'        =>      false,
                            'last_link'         =>      false,
                            'first_tag_open'    =>      '<li class="page-item">',
                            'first_tag_close'   =>      '</li>',
                            'next_tag_open'     =>      '<li class="page-item">',
                            'next_tag_close'    =>      '</li>',
                            'prev_tag_open'     =>      '<li class="page-item">',
                            'prev_tag_close'    =>      '</li>',
                            'num_tag_open'      =>      '<li class="page-item">',
                            'num_tag_close'     =>      '</li>',
                            'cur_tag_open'      =>      '<li class="page-item active"><a class="page-link">',
                            'cur_tag_close'     =>      '</a></li>',
                            'anchor_class'      =>      'class="number"'
                        ];
        
        $this->pagination->initialize($config);
        
        $data['domesticData']               =   $this->frontend_model->domesticPackage($config['per_page'],$this->uri->segment(3));
        
        $this->load->view('domestic', $data);
    }
    
    public function international()
    {
        $table      =   'international_package';
        
        $config     =   [
                            'base_url'          =>      base_url('frontend/international'),
                            'per_page'          =>      3,
                            'total_rows'        =>      $this->frontend_model->numrowsPackage($table),
                            'full_tag_open'     =>      '<ul class="pagination justify-content-end w-100 mb-50 ">',
                            'full_tag_close'    =>      '</ul>',
                            'first_link'        =>      false,
                            'last_link'         =>      false,
                            'first_tag_open'    =>      '<li class="page-item">',
                            'first_tag_close'   =>      '</li>',
                            'next_tag_open'     =>      '<li class="page-item">',
                            'next_tag_close'    =>      '</li>',
                            'prev_tag_open'     =>      '<li class="page-item">',
                            'prev_tag_close'    =>      '</li>',
                            'num_tag_open'      =>      '<li class="page-item">',
                            'num_tag_close'     =>      '</li>',
                            'cur_tag_open'      =>      '<li class="page-item active"><a class="page-link">',
                            'cur_tag_close'     =>      '</a></li>',
                            'anchor_class'      =>      'class="number"'
                        ];
        
        $this->pagination->initialize($config);
        
        $data['internationalData']               =   $this->frontend_model->internationalPackage($config['per_page'],$this->uri->segment(3));
        
        $this->load->view('international',$data);
    }
    
    public function contact()
    {
        $this->load->view('contact');
    }
    
    public function contactForm()
    {
        /*echo '<pre>';
        print_r($_POST);
        echo '</pre>';*/
        $this->form_validation->set_rules('firstName', 'Enter Your First Name', 'required');
        $this->form_validation->set_rules('lastName', 'Enter Your Last Name', 'trim|required');
        $this->form_validation->set_rules('mobileNo', 'Enter Your Mobile', 'trim|required');
        $this->form_validation->set_rules('emailId', 'Enter Your Email', 'trim|required');
        $this->form_validation->set_rules('message', 'Enter Your Message', 'trim|required');
        
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('error', validation_errors());
            //return redirect('admin/addpackage');
            echo redirectPreviousPage();
            exit;
        }
        else
        {
            $msgToemail	='<!doctype html>
            <html>
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                </head>
                <body style="padding:0; margin:0;">
                    <div style="width:600px; margin:0 auto; border:1px solid #e85a4a;">
                        <div style="width:100%; text-align:center; background-color:#ea6f61; padding:15px 0;"><img src="'.base_url('assets/frontend/images/logo.png').'" alt=""></div>
                        <div style="padding:20px 10px 0 20px;">
                            <h2 style="font-size:30px; font-family:">Hello Admin</h2>
                            <p style=" font-size:20px;">Someone has just filled out Contact form. The details are as follows:</p>
                            <p style="font-family:verdana; font-size:14px; margin:35px 0 0 0;"><strong>First Name : </strong>'.ucfirst($this->input->post('firstName', TRUE)).'</p>
                            <p style="font-family:verdana; font-size:14px; margin:35px 0 0 0;"><strong>Last Name : </strong>'.ucfirst($this->input->post('lastName', TRUE)).'</p>
                            <p style="font-family:verdana; font-size:14px; margin:35px 0 0 0;"><strong>Phone : </strong>'.$this->input->post('mobileNo', TRUE).'</p>
                            <p style="font-family:verdana; font-size:14px; margin:35px 0 0 0;"><strong>Email : </strong>'.$this->input->post('emailId', TRUE).'</p>
                            <p style="font-family:verdana; font-size:14px; margin:35px 0 30px 0;"><strong>Message : </strong>'.$this->input->post('message', TRUE).'</p>
                        </div>
                    </div>
                </body>
            </html>';
            
            //echo $msgToemail;
            
            $config['mailtype'] = 'html';
            $this->email->initialize($config);
            $this->email->to('soumyajeetseal@outlook.com');
            $this->email->from('noreply@etcsolutionsinc.com','Krish Travels');
            $this->email->subject('Contact Form Page');
            $this->email->message($msgToemail);
            $this->email->send();
            
            $this->session->set_flashdata('success', 'Your Enquiry sent to Krish Travels Successfully!!!');
            echo redirectPreviousPage();
            exit;
        }
    }
    
    public function packagedetails($id,$directory)
    {
        //$data['packageDetails']   =   $this->frontend_model->domesticPackageDetails($id);
        
        /*$data['packageDetails']     =   $this->frontend_model->domesticPackageDetails($id);
        $data['relatedImages']      =   $this->frontend_model->domesticPackageMultiImages($id);
        $data['directoryName']      =   'domestic/';*/
        
        if($directory   ==  0){
            $data['includes']           =   $this->frontend_model->getIncludeList();
            $data['excludes']           =   $this->frontend_model->getExcludeList();
            $data['packageDetails']     =   $this->frontend_model->domesticPackageDetails($id);
            $data['daysIternarys']      =   $this->frontend_model->domesticDaysIternary($id);
            $data['relatedImages']      =   $this->frontend_model->domesticPackageMultiImages($id);
            $data['directoryName']      =   'domestic/';
        }
        else{
            $data['includes']           =   $this->frontend_model->getIncludeList();
            $data['excludes']           =   $this->frontend_model->getExcludeList();
            $data['packageDetails']     =   $this->frontend_model->internationalPackageDetails($id);
            $data['daysIternarys']      =   $this->frontend_model->internationalDaysIternary($id);
            $data['relatedImages']      =   $this->frontend_model->internationalPackageMultiImages($id);
            $data['directoryName']      =   'international/';
        }
        
        $this->load->view('packagedetails', $data);
    }
    
    public function featuredetails($id,$directory)
    {
        if($directory   ==  0){
            $data['includes']           =   $this->frontend_model->getIncludeList();
            $data['excludes']           =   $this->frontend_model->getExcludeList();
            $data['daysIternarys']      =   $this->frontend_model->domesticDaysIternary($id);
            $data['packageDetails']     =   $this->frontend_model->domesticPackageDetails($id);
            $data['relatedImages']      =   $this->frontend_model->domesticPackageMultiImages($id);
            $data['directoryName']      =   'domestic/';
        }
        else{
            $data['includes']           =   $this->frontend_model->getIncludeList();
            $data['excludes']           =   $this->frontend_model->getExcludeList();
            $data['daysIternarys']      =   $this->frontend_model->internationalDaysIternary($id);
            $data['packageDetails']     =   $this->frontend_model->internationalPackageDetails($id);
            $data['relatedImages']      =   $this->frontend_model->internationalPackageMultiImages($id);
            $data['directoryName']      =   'international/';
        }
        $this->load->view('packagedetails', $data);
    }
    
    public function privacy_policy()
    {
        $this->load->view('privacy-policy');
    }
    
    public function terms_conditions()
    {
        $this->load->view('terms-conditions');
    }
    
}