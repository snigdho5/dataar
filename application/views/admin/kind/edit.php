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
                    Edit Kind
                </h1>
                <ol class="breadcrumb">
                    <li><a href="<?= base_url('admin') ?>"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Edit Kind</li>
                </ol>
            </section>    
            <div class="container">
                <div class="col-md-11">
                    <h2></h2>
                    <form method="POST" action="<?= base_url('admin/updatekind') ?>" enctype="multipart/form-data">
                        <div class="form-group col-md-6 col-md-offset-3"></div>
                        <div class="form-group col-md-6">
                            <label for="email">Kind Name : </label>
                            <input type="text" class="form-control" placeholder="Kind" name="kind_name" value="<?= $kind->kind_name ?>" required/>
                        </div>
                        <div class="form-group col-md-6 col-md-offset-6">
                            <input type="hidden" name="id" value="<?= $kind->kind_id ?>"/>
                        </div>                        
                        <div class="form-group col-md-6 ">
                            <label for="email">Status : </label>
                            <select name="status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="1" <?php if($kind->status == 1){echo 'selected';} ?>>Active</option>
                                <option value="0" <?php if($kind->status == 0){echo 'selected';} ?>>Inactive</option>
                            </select>
                        </div>                     
                        <div class="form-group col-md-12" style="margin-bottom:15px">
                            <button type="submit" class="btn btn-warning">update kind</button>
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
        <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
        <div class="control-sidebar-bg"></div>
    </div>
<?php
    $this->load->view('admin/include/footer');
?>