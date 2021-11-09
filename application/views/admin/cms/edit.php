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
                    Edit CMS
                </h1>
                <ol class="breadcrumb">
                    <li><a href="<?= base_url('admin') ?>"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Edit CMS</li>
                </ol>
            </section>    
            <div class="container">
                <div class="col-md-11">
                    <h2></h2>
                    <form method="POST" action="<?= base_url('admin/updatecms') ?>" enctype="multipart/form-data">
                        <div class="form-group col-md-6 col-md-offset-3"></div>
                        <div class="form-group col-md-6">
                            <label for="email">Page Title : </label>
                            <input type="text" class="form-control" placeholder="Title" name="page_title" value="<?= $cms[0]->page_title ?>" required/>
                        </div>
                        <div class="form-group col-md-6 col-md-offset-6">
                            <input name="id" type="hidden" value="<?= $cms[0]->id ?>" />
                        </div>
                        <div class="form-group col-md-12">
                            <label for="email">CMS Content : </label>
                            <textarea id="page_text" class="form-control" name="page_text" rows="12" style="resize:none;">
                                <?= html_entity_decode($cms[0]->page_text) ?>
                            </textarea>
                        </div>
                        <div class="form-group col-md-12" style="margin-bottom:15px">
                            <button type="submit" class="btn btn-warning">update cms</button>
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