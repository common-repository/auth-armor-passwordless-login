jQuery(document).ready(function($)
{  
    if(typeof scanner != 'undefined' && scanner.qr_code_url != ''){
        
        autharmorInviteQrScanner(scanner.qr_code_url);
    }
    $(document).on('click', '.setup_skip_wrap', function(e){
        console.log($(this).attr('data-skip'));
         if($(this).attr('data-skip') == ''){
            if(confirm('API key amd API Secret key is not setup. Do you want to really skip setup?')){
                 location.replace(adminobj.setting_link); 
            }
            return false;
         } else {
             location.replace(adminobj.setting_link);
         }
    });
    $(document).find('.prev').css('opacity','0');
    $(document).on('click', '#prev', function(e){
       
        if($(".step.active").index() > 0)
            $(".step.active").removeClass("active").prev().addClass("active");
        if($(".step.active").index() < $(".step").length-1){ 
            $('.next').show();
            $('.submit').hide();
        }
        $('.proceed_login').hide();    
        if($(".step.active").index() == 0)
            $('.prev').css('opacity','0');
    });
    $(document).on('click', '#next', function(e){ 
        console.log($(".step.active").index());
        if($(".step.active").index() < $(".step").length-1){
            $(".step.active").removeClass("active").next().addClass("active");
            
        }
        if($(this).attr('data-page') == 'front-setup' && ($(".step.active").index() == 1)){     
            $('#qr-code-scanner').html(''); 
            $('#revoke').trigger('click');         
        }
        console.log($(".step.active").index());
        if($(this).attr('data-page') == 'admin-setup' && ($(".step.active").index() == 3)){
            $('#revoke').trigger('click');
        }
        if($(".step.active").index() > 0)
            $('.prev').css('opacity','1');
        if($(".step.active").index() == $(".step").length-1){ 
            $('.next,.login_url_step').hide();
            //$('.submit').show();
            $('#qr_loader, .commonstep-wrap').show();
            $('.qr_confirmation_msg').html('Waiting for confirmation...');
            loginWithUsername(e);      /* last step for verification of auth armor account*/   
        }
        
    });
    $(document).on("click",".verify_acc_detail",function(e) {
        loginWithUsername(e);
    });
    $('.copy_invite_link').click(function(e)
    {
        e.preventDefault();
        var copyText = document.getElementById("invite_link");
        
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        document.execCommand("copy");

        $('.copy_invite_link').text('Copied!');
        window.setTimeout(function () {
            $('.copy_invite_link').text('Copy');
         }, 2000);
    });
    $('#generate_invite,#regenerate_invite,#revoke').click(function(e)
    {
        $('.status-msg').remove();  
        $('.qr-error-msg').remove();  
         
        $this = $(this);
        var buttonText = $this.attr('value');
        var data_come = $this.attr('data-type');
        var accesstoken = $('.access_token').val();
        if(accesstoken == '' || typeof accesstoken === 'undefined'){
            accesstoken = scanner.token;
        }
        var fd = new FormData();    
        fd.append( 'action', 'generate_user_invite_code' );
        fd.append( 'nickname', jQuery('#user_login').val() );
        fd.append( 'refrenceID', jQuery('#autharmor_refid').val() );
        fd.append( 'access_token',accesstoken);

        if($this.attr('id') == 'regenerate_invite')
        {
            fd.append( 'type', 'reset' );
        }

        if($this.attr('id') == 'revoke')
        {
            fd.append( 'type', 'revoke' );
        }
    
        $.ajax({
            type: 'POST', 
            url: scanner.ajax_url,
            processData: false,
            contentType: false,
            data: fd,
            beforeSend : function()
            {
                $('.qr_loader_verification').show();
                $('.invite_code_form').addClass('disabledsection');
            },
            success: function(data){
                var obj = jQuery.parseJSON( data );
                $('.status-msg').remove();
                if(typeof obj.body !== 'undefined')
                {   
                    $('.invite-msg').show();
                    var qr_code_data = obj.body.qr_code_data;
                    console.log(qr_code_data);
                    autharmorInviteQrScanner(qr_code_data);
                    
                    $('#invite_link').val('https://invite.autharmor.com/?i='+obj.body.invite_code+'&aa_sig='+obj.body.aa_sig);
                    $('b.expire-date').text(obj.date_expires);
                    $(".step.active").find('.qr-step-msg').prepend('<p class="qr-error-msg" style="color:green">Invite Code Generate Sucessfully!</p>');
                }
                else if(obj.status == 'fail')
                {
                    if(data_come == '')
                    $(".step.active").find('.qr-step-msg').prepend('<p class="status-msg" style="color:red">'+obj.msg+'</p>');
                    if(data_come == 'setup')
                    $(".step.active").find('.qr-step-msg').prepend('<p class="status-msg" style="color:red">'+obj.msg+'</p>');
                    $('.qr_loader_verification').hide();
                }
                console.log(obj);
                $('.invite_code_form').removeClass('disabledsection');
                $this.val(buttonText);
            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log('generate_invite'); 
            }
        });
    });

    /**
     * Generate QR code using invite code
     * @param {*} invite_code 
     */
    function autharmorInviteQrScanner(invite_code)
    {
        $('#qr-code-scanner').html('');
        $('.qr_loader_verification').hide();
        console.log(invite_code);
        var options = {
            text: invite_code, // Content                
            width: 210, // Widht
            height: 210, // Height
            colorDark: scanner.fore_color,
            logo:scanner.autharmor_logo,
            quietZone: 15,
            quietZoneColor:scanner.back_color,
            correctLevel: QRCode.CorrectLevel.H, // L, M, Q, H                        
        };
        var t = new QRCode(document.getElementById("qr-code-scanner"), options);
        $('.invite_code_form').show(); 
    }

    /**
     * Get Qr code and request id     
     */
    function show_QR_code() {
        var accesstoken = $('.access_token').val();
        if(accesstoken == '' || typeof accesstoken === 'undefined'){
            accesstoken = scanner.token;
        }
        var fd = new FormData();
        fd.append( 'action', 'get_auth_request_data' );
        fd.append( 'access_token', accesstoken );
        $('.status-msg').remove();
        $.ajax({
            type: 'POST',
            url: scanner.ajax_url,
            processData: false,
            contentType: false,
            data: fd,
            success: function(data){
                var obj = jQuery.parseJSON( data );
                if(obj.success == '1'){
                    $('#auth_request_id').val(obj.auth_request_id);
                    autharmorAsyncRequest(obj.qr_code);                    
                } else if(obj.success == '0'){
                    $(".step.active").find('.qr-msg').prepend('<p class="status-msg" style="color:red">'+obj.msg+'</p>');
                    $('#qr_loader').hide();
                } else {
                    $(".step.active").find('.qr-msg').prepend('<p class="status-msg" style="color:red">Something went wrong, Please reload page!</p>');
                    $('#qr_loader').hide();
                }
            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log('show_scanner_code');
                show_QR_code();
            }
        });
    }
    /**
     * Display Qr code using Easy QRCode library
     * @param {*} qr_url          
     */
    function autharmorAsyncRequest(qr_url)
    {
        $('#qr_loader').hide();
        $('#qr-code').html('');
        $('.qr_confirmation_msg').html('');
        var options = {
            text: qr_url, // Content                
            width: 210, // Widht
            height: 210, // Height
            colorDark: scanner.fore_color,
            logo:scanner.autharmor_logo,
            quietZone: 15,
            quietZoneColor:scanner.back_color,
            correctLevel: QRCode.CorrectLevel.H, // L, M, Q, H                        
        };
        var t = new QRCode(document.getElementById("qr-code"), options);
        getStatus();
    }

    /**
     * Check access token status
     */
    function getStatus()
    {
        
        var auth_request_id = jQuery('#auth_request_id').val();
        $(".step.active").find('.qr-error-msg').remove();
        if(auth_request_id != '')
        { 
            var accesstoken = $('.access_token').val();
            if(accesstoken == '' || typeof accesstoken === 'undefined'){
                accesstoken = scanner.token;
            }
            var fd = new FormData();
            fd.append( 'action', 'get_token_state' );
            fd.append( 'auth_request_id', auth_request_id );
            fd.append( 'access_token', accesstoken );
            fd.append( 'is_front', '1' );

            $.ajax({
                type: 'POST',
                url: scanner.ajax_url,
                processData: false,
                contentType: false,
                data: fd,
                success: function(data){
                    var obj = jQuery.parseJSON( data );
                        
                    if(obj.status == "success")
                    {
                        $(".login_url_step, .proceed_login").show();        
                        $('.submit').show();
                        $('.commonstep-wrap').hide();                         
                    }
                    else if(obj.status == "declined" || obj.status == "timeout")
                    {
                        
                    }
                    else {
                        getStatus();
                    }
                },
                error: function(MLHttpRequest, textStatus, errorThrown){
                    console.log('getStatus');
                    getStatus();
                }
            });
        }
    }

    /**
     * Check login with username
     * @param {*} e 
     */
    function loginWithUsername(e)
    {
        
        var user_login = jQuery('.user_login').val();
        $('.status-msg').remove();
        var fd = new FormData();
        var accesstoken = $('.access_token').val();
        if(accesstoken == '' || typeof accesstoken === 'undefined'){
            accesstoken = scanner.token;
        }
        fd.append( 'action', 'get_login_via_username' );
        fd.append( 'nickname', user_login );
        fd.append( 'method', 'setup' );
        fd.append( 'access_token', accesstoken );
        $.ajax({
            type: 'POST',
            url: scanner.ajax_url,
            processData: false,
            contentType: false,
            data: fd,
            beforeSend : function()
            {
                $('.verify_acc_detail').addClass('disabledsection');
            },
            success: function(data){
                var obj = jQuery.parseJSON( data );
                if(obj.status=='success')
                { 
                    if(obj.auth_request_id == null && obj.qr_code == null){
                        $(".step.active").find('.qrdesc').after('<p class="status-msg" style="color:red">Please try again auth armor user and wordpress user different.</p>');
                        $('#qr_loader').hide();
                    } else {
                        $('#auth_request_id').val(obj.auth_request_id);
                        $('#qr_loader').show();
                        autharmorAsyncRequest(obj.qr_code);
                    }
                }
                else
                {
                    $(".step.active").find('.qr-msg').prepend('<p class="status-msg" style="color:red">Please enter valid auth armor username.</p>')
                    $('#qr_loader').hide();
                }                
            },
            complete : function()
            {
                $('.verify_acc_detail').removeClass('disabledsection');                
            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log('loginWithUsername');
                loginWithUsername();
            }
        });
    }
 });