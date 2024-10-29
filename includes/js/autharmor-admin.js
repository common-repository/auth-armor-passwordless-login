jQuery(document).ready(function($)
{  
    $(".login_type").on('change', function (e) {
        
        $('.login_type_cls').hide();
        var datatype = $('option:selected', $(this)).attr('data-type');
        $('.'+datatype).show();        
    });  
    
    var currentObj = $('.user-pass1-wrap').closest('tr');
    var loginType = $('.login_method').clone();
    $( currentObj ).before(loginType);
    


    $(document).on('click', '.default_pass_login', function(e){
        var objval = $(this).val();
        check_display_method(objval);        
    }); 
    
    var checkedValue = $(".form-table").find("input[name=login_method]:checked").val();
    check_display_method(checkedValue);

    
    $(document).on('click', '.verify_details', function(e){      
        
        $('.verify-details-msg').removeClass('success');
        $('.verify-details-msg').removeClass('error');
        var obj = $(this);
        obj.addClass('disabledsection');
        var method = obj.attr('data-method');
        var api_key = $('#auth_plugin_setting_api_key').val();
        var api_secret = $('#auth_plugin_setting_api_secret').val();
        $.ajax({
            type: 'POST',
            url: adminobj.ajax_url,
            dataType:'json',
            data: {'action' :'verify_api_details','jsondata':'1','method': method,'api_key' : api_key,'api_secret' : api_secret},
            beforeSend : function()
            {
                $('.loader').show();
            },
            success: function(response){
                $('.loader').hide();
                obj.removeClass('disabledsection');
                if(response.success == 1){
                    if(method == 1 && response.access_token != ''){
                        $('.access_token').val(response.access_token);
                    }
                    $('.setup_skip_wrap').attr('data-skip','1');
                    $('.verify-details-msg').addClass('success');
                    $('.verify-details-msg').html('API is verify successfully');
                } else {
                    $('.verify-details-msg').addClass('error');
                    $('.verify-details-msg').html('API is not verify, please try again!');                    
                }
            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log('error');                    
            }
        });        
    });
    function check_display_method(checkObj){
        console.log(checkObj);
        if(checkObj == '0'){
            $('.user-pass1-wrap').show();
            $('.autharmor_login').hide();
            $('#send_user_notification').closest('tr').show();
            $('.user-generate-reset-link-wrap').show();       
        } else if(checkObj == '1'){
            $('.autharmor_login').show();
            $('#send_user_notification').closest('tr').hide();
            $('.user-generate-reset-link-wrap').hide();            
            $('.user-pass1-wrap').hide();
        }
    }   
 });

 