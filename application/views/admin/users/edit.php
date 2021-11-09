<?php
defined('BASEPATH') or exit('No direct script access allowed');
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
            <?php
            $kycWithPath    =   base_url('uploads/kyc/') . $user->kyc_file;
            $kycFileCheck   =   $this->input->server('DOCUMENT_ROOT') . '/donationapp/uploads/kyc/' . $user->kyc_file;

            $images = ['gif', 'png', 'jpg'];
            $ext = pathinfo($user->kyc_file, PATHINFO_EXTENSION);
            if (in_array($ext, $images)) {
                $kyc    =   '<img src="' . $kycWithPath . '"/>';
            } else {
                $kyc    =   '<a href="' . $kycWithPath . '" target="_blank"/>View Document</a>';
            }
            ?>
            <section class="content-header">
                <h1>
                    Update User
                </h1>
                <ol class="breadcrumb">
                    <li><a href="<?= base_url('admin') ?>"><i class="fa fa-dashboard"></i> Home </a></li>
                    <li><a href="<?= base_url('admin/users') ?>"><i class="fa fa-dashboard"></i> All Users </a></li>
                    <li class="active">View Campaign</li>
                </ol>
            </section>
            <div class="container">
                <div class="col-md-11">
                    <h2></h2>
                    <form method="POST" action="<?= base_url('admin/updateuser') ?>" enctype="multipart/form-data">
                        <div class="form-group col-md-6 col-md-offset-3"></div>
                        <div class="form-group col-md-6">
                            <label for="firstname">First Name : </label>
                            <input type="text" class="form-control" value="<?= $user->first_name ?>" readonly />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="lastname">Last Name : </label>
                            <input type="text" class="form-control" value="<?= $user->last_name ?>" readonly />
                            <input type="hidden" name="id" value="<?= $user->user_id ?>" />
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email : </label>
                            <input type="text" class="form-control" value="<?= $user->email ?>" readonly />
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="phone">Phone : </label>
                            <input type="text" class="form-control" value="<?= $user->phone ?>" readonly />
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="lastlogin">Last Login : </label>
                            <input type="text" class="form-control" value="<?= $user->last_login_time ?>" readonly />
                        </div>
                        <div class="form-group col-md-6 ">
                            <?php
                            $userType = ($user->user_type == 0) ? 'Donor' : 'Donee';
                            ?>
                            <label for="lastlogin">User Type : </label>
                            <input type="text" class="form-control" value="<?= $userType ?>" readonly />
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="status">Status : </label>
                            <select name="status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if ($user->status == 1) {
                                                        echo 'selected';
                                                    } ?>>Active</option>
                                <option value="0" <?php if ($user->status == 0) {
                                                        echo 'selected';
                                                    } ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="status">KYC Verified : </label> <span> View KYC File:
                                <?php if ($user->kyc_file != '') {
                                ?><a href="#" class="btn-view-kyc" data-kyc-file="<?php echo  $user->kyc_file; ?>"><img style="max-height: 20px; max-width:30px;" src="data:image/png;base64,<?php echo  $user->kyc_file; ?>" alt="Kyc" /></a>
                                <?php
                                } else {
                                    echo 'Not uploaded!';
                                }
                                ?></span>
                            <select name="kyc_verified" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if ($user->kyc_verified == 1) {
                                                        echo 'selected';
                                                    } ?>>Yes</option>
                                <option value="0" <?php if ($user->kyc_verified == 0) {
                                                        echo 'selected';
                                                    } ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="status">Can Create Campaign : </label>
                            <select name="camp_auth" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if ($user->camp_auth == 1) {
                                                        echo 'selected';
                                                    } ?>>Yes</option>
                                <option value="0" <?php if ($user->camp_auth == 0) {
                                                        echo 'selected';
                                                    } ?>>No</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6 ">
                            <label for="status">Can Give Donation : </label>
                            <select name="donation_auth" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if ($user->donation_auth == 1) {
                                                        echo 'selected';
                                                    } ?>>Yes</option>
                                <option value="0" <?php if ($user->donation_auth == 0) {
                                                        echo 'selected';
                                                    } ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group col-md-12" style="margin-bottom:15px">
                            <?php if (file_exists($kycFileCheck)) { ?>
                                <a href="javascript:void(0)" data-toggle="modal" data-target="#myModal" class="btn btn-primary">view KYC</a>
                            <?php } ?>
                            <button type="submit" class="btn btn-warning">update user</button>
                        </div>
                    </form>
                    <!-- Modal -->
                    <div class="modal fade" id="myModal" role="dialog">
                        <div class="modal-dialog">
                            <!-- Modal content-->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title"><?= $user->first_name . ' ' . $user->last_name . "'s KYC Details" ?></h4>
                                </div>
                                <div class="modal-body">
                                    <?= $kyc ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="base64-img">

                        </p>
                    </div>
                    <!-- <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save changes</button>
                    </div> -->
                </div>
            </div>
        </div>
        <?php
        $this->load->view('admin/include/footer-content');
        ?>


        <!-- Control Sidebar -->

        <!-- /.control-sidebar -->
        <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
        <div class="control-sidebar-bg"></div>
    </div>

    <?php
    $this->load->view('admin/include/footer');
    ?>

    <script>
        $(document).ready(function() {
            $('.btn-view-kyc').on('click', function() {
                var viewkyc = $(this).attr('data-kyc-file');
                $('#exampleModal').modal('toggle');
                $('#exampleModal').modal('show');
                //$('#exampleModal').modal('hide');
                $('.base64-img').html('<img style="" src="data:image/png;base64,' + viewkyc + '" alt="Kyc" />');
            });
        });
    </script>