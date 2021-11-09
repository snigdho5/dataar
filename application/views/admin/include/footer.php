    <input id="baseUrl" type="hidden" value="<?= base_url() ?>">
    <!-- jQuery 3 -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/fastclick/lib/fastclick.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('assets/admin/') ?>dist/js/adminlte.min.js"></script>
    <!-- Sparkline -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
    <!-- jvectormap  -->
    <script src="<?= base_url('assets/admin/') ?>plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="<?= base_url('assets/admin/') ?>plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <!-- SlimScroll -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
    <!-- ChartJS -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/chart.js/Chart.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="<?= base_url('assets/admin/') ?>dist/js/pages/dashboard2.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="<?= base_url('assets/admin/') ?>dist/js/demo.js"></script>
    <!-- AdminLTE for Datatables -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="<?= base_url('assets/admin/') ?>bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <!-- SlimScroll -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
    <!-- CK Editor -->
    <script src="<?= base_url('assets/admin/') ?>bower_components/ckeditor/ckeditor.js"></script>
    <!-- Bootstrap WYSIHTML5 -->
    <script src="<?= base_url('assets/admin/') ?>plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
    <!-- iCheck -->
    <script src="<?= base_url('assets/admin/') ?>plugins/iCheck/icheck.min.js"></script>

    <script src="<?= base_url('assets/admin/') ?>dist/js/jquery.toaster.js"></script>

    <script src="<?= base_url('assets/admin/') ?>dist/js/sj_script.js"></script>
    <script>
      $(function () {
        $('input').iCheck({
          checkboxClass: 'icheckbox_square-blue',
          radioClass: 'iradio_square-blue',
          increaseArea: '20%' /* optional */
        });
      });
    </script>
    </body>
</html>