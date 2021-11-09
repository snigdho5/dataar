<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

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
	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function testmail(){
		$this->load->library('aws_ses_smtp');
        // echo $this->amazon_ses_smtp->show_hello_world();
        //echo $this->amazon_ses_smtp->test_aws_mail();
        $sendToEmail	=	'soumyajeet.lnsel@gmail.com';
        $mailSubject	=	'This is a test Email';
        $bodyHtml 		=	'<h1>Email Test</h1>
						    <p>This email was sent through the
						    <a href="https://aws.amazon.com/ses">Amazon SES</a> SMTP
						    interface using the <a href="https://github.com/PHPMailer/PHPMailer">
						    PHPMailer</a> class.</p>';

		echo $this->aws_ses_smtp->send_aws_ses_mail($sendToEmail,$mailSubject,$bodyHtml);
	}
}
