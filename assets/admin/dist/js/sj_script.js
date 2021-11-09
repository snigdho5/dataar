    /* 
    * To change this license header, choose License Headers in Project Properties.
    * To change this template file, choose Tools | Templates
    * and open the template in the editor.
    */
    let baseUrl = $('#baseUrl').val();
    $(function(){
        $('#users').DataTable({
            "ajax": {
                url: baseUrl+"admin/userlist",
                type: 'GET'
            }
        });
        $('#campaignlist').DataTable({
            "ajax": {
                url: baseUrl+"admin/campaignlist",
                type: 'GET'
            }
        });
        $('#kindlist').DataTable({
            "ajax": {
                url: baseUrl+"admin/kindlist",
                type: 'GET'
            }
        });
        $('#kinds').DataTable({
            "ajax": {
                url: baseUrl+"admin/donationkinds",
                type: 'GET'
            }
        });
        $('#cash').DataTable({
            "ajax": {
                url: baseUrl+"admin/donationcash",
                type: 'GET'
            }
        });
        $('#filterlist').DataTable({
            "ajax": {
                url: baseUrl+"admin/filter_list_data",
                type: 'GET'
            }
        });
        CKEDITOR.replace('page_text', {
            skin: 'moono',
            enterMode: CKEDITOR.ENTER_BR,
            shiftEnterMode:CKEDITOR.ENTER_P,
            toolbar: [{ name: 'basicstyles', groups: [ 'basicstyles' ], items: [ 'Bold', 'Italic', 'Underline', "-", 'TextColor', 'BGColor' ] },
                       { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
                       { name: 'scripts', items: [ 'Subscript', 'Superscript' ] },
                       { name: 'justify', groups: [ 'blocks', 'align' ], items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
                       { name: 'paragraph', groups: [ 'list', 'indent' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'] },
                       { name: 'links', items: [ 'Link', 'Unlink' ] },
                       { name: 'insert', items: [ 'Image'] },
                       { name: 'spell', items: [ 'jQuerySpellChecker' ] },
                       { name: 'table', items: [ 'Table' ] }
                       ],
        });
    });


