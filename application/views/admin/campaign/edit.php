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
            <?php if ($this->session->flashdata('error')){ ?>
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php } ?>
            <?php if ($this->session->flashdata('success')){ ?>
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <?= $this->session->flashdata('success'); ?>
                </div>
        	<?php } ?>
            <section class="content-header">
                <h1>
                    View Campaign
                </h1>
                <ol class="breadcrumb">
                    <li><a href="<?= base_url('admin') ?>"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li><a href="<?= base_url('admin/campaigns') ?>"><i class="fa fa-dashboard"></i> All Campaigns</a></li>
                    <li class="active">View Campaign</li>
                </ol>
            </section>    
            <div class="container">
                <div class="col-md-11">
                    <h2></h2>
                    <?php
                        if(!empty($campaign)){
                    ?>
                    <form method="POST" action="<?= base_url('admin/updatecampaign') ?>" enctype="multipart/form-data">
                        <div class="form-group col-md-6 col-md-offset-3"></div>
                        <div class="form-group col-md-6">
                            <label for="email">Campaign Name : </label>
                            <input type="text" class="form-control" placeholder="Kind" value="<?php  echo $campaign->campaign_name; ?>" readonly/>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Campaign Start : </label>
                            <input type="text" class="form-control" placeholder="Kind" value="<?= $campaign->campaign_start_date ?>" readonly/>
                            <input type="hidden" name="id" value="<?= $campaign->campaign_id ?>"/>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Campaign End : </label>
                            <input type="text" class="form-control" placeholder="Kind" value="<?= $campaign->campaign_end_date ?>" readonly/>
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="email">Approval Status : </label>
                            <select name="status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if($campaign->state == 1){echo 'selected';} ?>>Active</option>
                                <option value="0" <?php if($campaign->state == 0){echo 'selected';} ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- <div class="form-group col-md-6 ">
                            <label for="email">Campaign Approval : </label>
                            <select name="appr_status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if($campaign->status == 1){echo 'selected';} ?>>Approve</option>
                                <option value="2" <?php if($campaign->status == 2){echo 'selected';} ?>>Reject</option>
                            </select>
                        </div> -->
                        <div class="form-group col-md-6 ">
                            <label for="email">User Type : </label>
                            <?php
                                $userType = ($campaign->user_type == 0) ? 'Donor' : 'Donee';
                            ?>
                            <input type="text" class="form-control" value="<?= $userType ?>" readonly/>
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="email">Name of Initiator : </label>
                            <input type="text" class="form-control" value="<?= $campaign->first_name.' '.$campaign->last_name ?>" readonly/>
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="email">Initiator Phone : </label>
                            <input type="text" class="form-control" value="<?= $campaign->phone ?>" readonly/>
                        </div>
                        <div class="form-group col-md-6 ">
                            <label for="email">Initiator email : </label>
                            <input type="text" class="form-control" value="<?= $campaign->email ?>" readonly/>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="email">Campaign Details : </label>
                            <textarea class="form-control" style="resize:none;" readonly><?= $campaign->campaign_details ?></textarea>
                        </div>
                        <div class="form-group col-md-12" style="margin-bottom:15px">
                            <button type="submit" class="btn btn-warning">Approve Campaign</button>
                        </div>
                    </form> 
                    <?php
                        }else{
                            echo '<label>Campaign not found!</label> ';
                        }
                    ?>                   
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