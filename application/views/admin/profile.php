<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
  $this->load->view('admin/include/header');
?>
<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
        <?php
            $this->load->view('admin/include/sidebar');
        ?>
        <div class="content-wrapper">
            <?php if ($this->session->flashdata('error')) { ?>
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php } ?>
            <?php if ($this->session->flashdata('success')) { ?>
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php } ?>            
            <section class="content-header">
                <h1>
                    Edit Profile
                </h1>
                <ol class="breadcrumb">
                    <li><a href="<?= base_url('admin') ?>"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Edit Profile</li>
                </ol>
            </section>
            <div class="container">
                <div class="col-md-11">
                    <h2></h2>
                    <form method="POST" action="<?= base_url('admin/updateprofile') ?>" enctype="multipart/form-data">
                        <div class="form-group col-md-6 col-md-offset-3"></div>
                        <div class="form-group col-md-6">
                            <label for="email">First Name : </label>
                            <input type="text" class="form-control" placeholder="First Name" name="first_name" value="<?= $profileData[0]->first_name ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Last Name : </label>
                            <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="<?= $profileData[0]->last_name ?>" required>
                        </div>                        
                        <div class="form-group col-md-6">
                            <label for="email">Email : </label>
                            <input type="text" class="form-control" placeholder="Email" value="<?= $profileData[0]->email_address ?>" disabled>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Phone : </label>
                            <input type="text" class="form-control" placeholder="Phone" name="phone" value="<?= $profileData[0]->phone ?>" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="email">Profile Image : </label>
                            <img src="<?= base_url('uploads/admin/') . $profileData[0]->profile_image ?>" alt="<?= $profileData[0]->profile_image ?>" style="height:75px; width:75px;">
                            <input type="file" class="custom-file-input" name="profile_image" accept="image/x-png,image/gif,image/jpeg">
                        </div>
                        <div class="form-group col-md-12" style="margin-bottom:15px">
                            <button type="submit" class="btn btn-warning">Update Profile</button>
                        </div>
                    </form>                    
                </div>
            </div>
        </div>
        <?php
            $this->load->view('admin/include/footer-content');
        ?>
        <!-- Control Sidebar -->        
        <!-- /.control-sidebar -->
        <!-- Add the sidebar's background. This div must be placed immediately after the control sidebar -->
        <div class="control-sidebar-bg"></div>
    </div>
<!-- ./wrapper -->
<?php
    $this->load->view('admin/include/footer');
?>