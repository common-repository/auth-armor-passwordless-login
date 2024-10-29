jQuery(document).ready(function($) {
    var time_interval;
    var isActive = false;   /* check current tab qr active or not */
    var userTabActive = false;
    var usertime_interval;
    var i = 0;
    var xhrObj = null;

    $('#loginform').each(function(j) {
        scanner_inject_code_into_form(this, i + 1);
        i++;
    });
    $(".login_with").on('change', function (e) {

        var datatype = $(this).attr('data-type');
        if(datatype == 'login_with_username'){
            $('.'+datatype).show();
            $('.login_with_qr').hide(); 
            $('.login_with_user_qr').show();
            $('.timer').hide();
            $('.refresh_btn_wrap').hide();
            $('.usertimer').show();  
            $('.status-msg').remove();
            
            clearInterval(usertime_interval);
            loginWithUsername(e);   
        } else {
            $('.'+datatype).show();
            $('.login_with_user_qr').hide();
            $('.timer').show();
            $('.usertimer').hide();
            $('.login_with_username').hide();
            $('.user-status-msg').remove();
            /** generate new qr code on selection of tab */
            $('.timer').html('');
            isActive = false;
            clearInterval(time_interval);
            show_scanner_code();
            
        }
    });
    $(document).on("click",".resend_request",function() {
        $('.timer').html('');
        isActive = false;
        show_scanner_code();
    });
    $(document).on("click","#wp-submit-user",function(e) {
        clearInterval(usertime_interval);
        loginWithUsername(e);         
    });
    
    /**
     * Check access token status
     */
    function getStatus()
    {
        
        var auth_request_id = jQuery('#auth_request_id').val();
        /** check if first tab not actived then not send request  */
        if(!isActive){
            if(auth_request_id != '')
            {

                var fd = new FormData();
                fd.append( 'action', 'get_token_state' );
                fd.append( 'auth_request_id', auth_request_id );
                fd.append( 'access_token', scanner.token );

                $.ajax({
                    type: 'POST',
                    url: scanner.ajax_url,
                    processData: false,
                    contentType: false,
                    data: fd,
                    success: function(data){
                        var obj = jQuery.parseJSON( data );
                        $('.status-msg').remove();
                        console.log('qrstatus'+obj.status);
                        if(obj.status == "success")
                        {
                            location.replace(obj.url);
                        }
                        else if(obj.status == "declined")
                        {
                            $('.scanner_qrcode_container').find('.timer').html('');
                            $('.scanner-login-wrapper').prepend('<div class="status-msg-main-wrap"><p class="status-msg" style="color:red">'+obj.msg+'</p><div class="refresh_btn_wrap"><img src='+scanner.refresh_token+' class="resend_request"/></div></div>');                                            
                        }
                        else if(obj.status == "timeout")
                        {
                            $('.scanner_qrcode_container').find('.timer').html('');
                            $('.scanner-login-wrapper').prepend('<div class="status-msg-main-wrap"><p class="status-msg" style="color:red">'+obj.msg+'</p><div class="refresh_btn_wrap"><img src='+scanner.refresh_token+' class="resend_request"/></div></div>');                                                                
                            isActive = true;
                        } else {
                            setTimeout(function(){ getStatus(); }, 2000);                        
                        }
                    },
                    error: function(MLHttpRequest, textStatus, errorThrown){
                        getStatus();
                    }
                });
            }
        }
    }
    function check_another_request(){
        var auth_request_id = jQuery('#auth_request_id').val();
        isActive = true;    /* For stop first tab request to set this varibale true  */
        
        if(auth_request_id != '')
        {

            var fd = new FormData();
            fd.append( 'action', 'get_token_state' );
            fd.append( 'auth_request_id', auth_request_id );
            fd.append( 'access_token', scanner.token );

            $.ajax({
                type: 'POST',
                url: scanner.ajax_url,
                processData: false,
                contentType: false,
                data: fd,
                success: function(data){
                    var obj = jQuery.parseJSON( data );
                    $('.user-status-msg').remove();
                    console.log('userqr'+obj.status);
                    if(obj.status == "success")
                    {
                        location.replace(obj.url);
                    }
                    else if(obj.status == "declined")
                    {
                        $('.scanner_qrcode_container').find('.usertimer').html('');
                        $('.scanner-login-wrapper').prepend('<div class="status-msg-main-wrap"><p class="user-status-msg" style="color:red">'+obj.msg+'</p></div>');                                                                    
                    }
                    else if(obj.status == "timeout")
                    {
                        $('.scanner_qrcode_container').find('.usertimer').html('');
                        $('.scanner-login-wrapper').prepend('<div class="status-msg-main-wrap"><p class="user-status-msg" style="color:red">'+obj.msg+'</p></div>');                                            
                    } else {
                        setTimeout(function(){ check_another_request(); }, 2000);             
                    }
                },
                error: function(MLHttpRequest, textStatus, errorThrown){
                    check_another_request();
                }
            });
        }
        
    }
    /**
     * Get Qr code and request id for login
     * @param {*} selector
     * @param {*} context
     */
    function show_scanner_code(selector, context) {
        var fd = new FormData();
        fd.append( 'action', 'get_auth_request_data' );
        fd.append( 'access_token', scanner.token );

        $.ajax({
            type: 'POST',
            url: scanner.ajax_url,
            processData: false,
            contentType: false,
            data: fd,
            beforeSend : function()
            {
                $('#loginform').addClass('disabledsection');
            },
            success: function(data){
                var obj = jQuery.parseJSON( data );
                if(obj.success == '1'){
                    $('#auth_request_id').val(obj.auth_request_id);
                    autharmorAsyncRequest(obj.qr_code,selector,$);
                } else {
                    $('#qr_loader').hide();
                    $('.scanner-login-wrapper').prepend('<p class="status-msg" style="color:red">Something went wrong, Please reload page!</p>');
                }
            },
            complete : function()
            {
                $('#loginform').removeClass('disabledsection');
            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log('show_scanner_code');
                show_scanner_code(selector, context);
            }
        });
    }

    /**
     * Get Qr code and request id for login
     * @param {*} selector
     * @param {*} context
     */
     function show_scanner_code_user(selector, context) {
        var fd = new FormData();
        fd.append( 'action', 'get_auth_request_data' );
        fd.append( 'access_token', scanner.token );
        $('.login_with_user_qr').show();
        $.ajax({
            type: 'POST',
            url: scanner.ajax_url,
            processData: false,
            contentType: false,
            data: fd,
            beforeSend : function()
            {
                $('#loginform').addClass('disabledsection');
            },
            success: function(data){
                var obj = jQuery.parseJSON( data );
                if(obj.success == '1'){
                    $('#auth_request_id').val(obj.auth_request_id);
                    autharmorAsyncUserRequest(obj.qr_code,selector,$);
                } else {
                    $('#qr_loader_user').hide();
                    $('.scanner-login-wrapper').prepend('<p class="user-status-msg" style="color:red">Something went wrong, Please reload page!</p>');
                }
            },
            complete : function()
            {
                $('#loginform').removeClass('disabledsection');
            },
            error: function(MLHttpRequest, textStatus, errorThrown){
                console.log('show_scanner_code');
                show_scanner_code_user(selector, context);
            }
        });
    }
    /**
     * Display Qr code using QRious library
     * @param {*} qr_url
     * @param {*} selector
     * @param {*} $
     */
    function autharmorAsyncRequest(qr_url,selector,$)
    {
        $('#qr-code').html('');
        /* generate qr code using EasyQRCodeJS library */
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
        $(selector).addClass('scanner_qrcode').data('qrcode', qr_url).empty();
        $('#qr_loader').hide();
        $('#qr-code').show();
        $('.qr_code_login').show();
        // Code for add timer based on setting start here
        var timer2 = timerval = $('.timer').html();
        $('.timer').removeClass('timeout-wrap');
        
        if(timerval == ''){
            var min = 0;
            var sec = scanner.timeout;
            if(scanner.timeout > 60){
                min = Math.floor(scanner.timeout / 60);
                var secArr = scanner.timeout / 60;
                secArr = secArr.toFixed(2);
                sec = secArr.toString().split(".")[1];             
            }
            timer2 = min+":"+sec;            
        }
        console.log(timer2);
        time_interval = setInterval(function() {
            var timer = timer2.split(':');
            var minutes = parseInt(timer[0], 10);
            var seconds = parseInt(timer[1], 10);
            if(minutes >= 0){
                --seconds;
                minutes = (seconds < 0) ? --minutes : minutes;
                seconds = (seconds < 0) ? 59 : seconds;
                seconds = (seconds < 10) ? '0' + seconds : seconds;
                if(seconds < 10 && minutes < 1){
                    $('.timer').addClass('timeout-wrap');
                }
                if (minutes < 0) clearInterval(time_interval);
                //check if both minutes and seconds are 0
                if ((seconds <= 0) && (minutes <= 0)) clearInterval(time_interval);

                if (minutes < 0) {
                    clearInterval(time_interval);
                } else {
                    $('.timer').html(minutes + ':' + seconds);
                    timer2 = minutes + ':' + seconds;
                } 
            }
        }, 1000);
        // Code for add timer based on setting end here

        getStatus();
    }

    /**
     * Display Qr code using QRious library
     * @param {*} qr_url
     * @param {*} selector
     * @param {*} $
     */
     function autharmorAsyncUserRequest(qr_url,selector,$)
     {
         $('#user-qr-code').html('');
         /* generate qr code using EasyQRCodeJS library */
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
         var t = new QRCode(document.getElementById("user-qr-code"), options);
         $(selector).addClass('scanner_qrcode').data('user-qr-code', qr_url).empty();
         $('#qr_loader_user').hide();
         $('#user-qr-code').show();
         $('.qr_code_login_user').show();
         $('.refresh_btn_wrap').hide();
         $('.usertimer').removeClass('timeout-wrap');
         $('.scanner_qrcode_container').find('.usertimer').html('');
         // Code for add timer based on setting start here
        var timer3 = usertimerval = $('.usertimer').html();
        if(usertimerval == ''){
            var min = 0;
            var sec = scanner.timeout;
            if(scanner.timeout > 60){
                min = Math.floor(scanner.timeout / 60);
                var secArr = scanner.timeout / 60;
                secArr = secArr.toFixed(2);
                sec = secArr.toString().split(".")[1];             
            }
            timer3 = min+":"+sec;     
        }   
        
        usertime_interval = setInterval(function() {
            var utimer = timer3.split(':');
            var uminutes = parseInt(utimer[0], 10);
            var useconds = parseInt(utimer[1], 10);
            if(uminutes >= 0){
                --useconds;
                uminutes = (useconds < 0) ? --uminutes : uminutes;
                useconds = (useconds < 0) ? 59 : useconds;
                useconds = (useconds < 10) ? '0' + useconds : useconds;
                if(useconds < 10 && uminutes < 1){
                    $('.usertimer').addClass('timeout-wrap');
                }
                
                if (uminutes < 0) clearInterval(usertime_interval);
                //check if both minutes and seconds are 0
                if ((useconds <= 0) && (uminutes <= 0)) clearInterval(usertime_interval);
                
                if (uminutes < 0) {
                    clearInterval(usertime_interval);
                } else {
                    $('.usertimer').html(uminutes + ':' + useconds);     
                    timer3 = uminutes + ':' + useconds;  
                } 
            }
        }, 1000);        
        // // Code for add timer based on setting end here
        check_another_request();
    }
    /**
     * Login using username
     * @param {*} e
     */
    function loginWithUsername(e)
    {
        e.preventDefault();
        
        var user_login = jQuery('#user_login').val();
        if(user_login != ''){
            var fd = new FormData();
            fd.append( 'action', 'get_login_via_username' );
            fd.append( 'nickname', user_login );
            fd.append( 'method', 'login' );
            fd.append( 'access_token', scanner.token );

            $.ajax({
                type: 'POST',
                url: scanner.ajax_url,
                processData: false,
                contentType: false,
                data: fd,
                beforeSend : function()
                {
                    $('#wp-submit').val('Wait...');
                    $('#loginform').addClass('disabledsection');
                },
                success: function(data){
                    var obj = jQuery.parseJSON( data );
                    $('#auth_request_id').val(obj.auth_request_id);
                    $('.user-status-msg').remove();
                    console.log('obj.auth_request_id'+obj.auth_request_id);
                    if(obj.status=='success' && (obj.auth_request_id != '' && obj.auth_request_id != null))
                    {
                        $('.default-wp-form-status').prepend('<p class="user-status-msg" style="color:green">The push notification has been sent.</p>');
                        $('#qr_loader_user').show();
                        autharmorAsyncUserRequest(obj.qr_code,'',$);
                    }
                    else
                    {
                        $('.default-wp-form-status').prepend('<p class="user-status-msg" style="color:red">Please enter valid auth armor username.</p>')
                        $('.login_with_user_qr').hide();
                    }
                    console.log(obj);
                },
                complete : function()
                {
                    $('#loginform').removeClass('disabledsection');
                    $('#wp-submit').val('Log In');
                },
                error: function(MLHttpRequest, textStatus, errorThrown){
                    console.log('loginWithUsername');
                    loginWithUsername();
                }
            });
        }
    }
    /**
     * Add QR code in login form based on selected setting type from backend
     * @param {*} form_selector
     * @param {*} form_identifier
     * @param {*} selector
     */
    function scanner_inject_code_into_form(form_selector, form_identifier, selector) {

        var $ = jQuery;

        if ('undefined' === typeof selector) {

            var login_type = scanner.login_type;
            switch(login_type) {
                case '1':
                    username_pass_authArmor($,form_selector, form_identifier);
                    break;                
                case '2':
                    only_authArmor($,form_selector, form_identifier);
                break;
                default:
            }
        }

        var qr_selector = $(form_selector).find('.scanner_qrcode');
        show_scanner_code(qr_selector, 'login');
    }
    /**
     * Customization of login form to display QR code
     * @param {*} $
     * @param {*} form_selector
     * @param {*} form_identifier
     */
    function username_pass_authArmor($,form_selector, form_identifier)
    {
        var prepend_this = '<div class="scanner_qrcode_container">';

        prepend_this += '<input type="hidden" id="auth_request_id"><div id="qr_loader" class="align-center"><img alt="" src="'+scanner.loader_src+'"></div>';
        if(scanner.login_type == 1)
        {
            prepend_this += '<div class="default-wp-form login_with_username" style="display:none;"><p><label class="user_login">Login With Username</label></p><p><label for="user_login">Username</label><input type="text" id="user_login" aria-describedby="login_error" class="input" value="" size="20" autocapitalize="off"></p><p class="submit"><input type="submit" id="wp-submit-user" class="button button-primary button-large" value="Log In"></p></div>';
            $(document).ready(function()
            {                
            });
        }             
        prepend_this += '<div class="align-center login_with_qr"><p><label class="qr_code_login" style="display:none;">Login With Auth Armor</label></p><div id="qr-code" style="display:none"></div></div>';
        prepend_this += '<div id="qr_loader_user" class="align-center" style="display:none;"><img alt="" src="'+scanner.loader_src+'"></div>';
        prepend_this += '<div class="align-center login_with_user_qr"><p><label class="qr_code_login_user" style="display:none;">Login With User Auth Armor</label></p><div id="user-qr-code" style="display:none"></div></div>';
        prepend_this += '<div class="scanner_qrcode" style="text-align: center;" data-scanner_form_identifier="'+form_identifier+'"></div>';

        prepend_this += '<div class="default-wp-form-status"></div>';
        
        prepend_this += '<div class="timer"></div>';
        prepend_this += '<div class="usertimer"></div>';
        if(scanner.login_type == 1)
        {
            prepend_this += '<div class="align-center switch switch--horizontal login-with-button"><input type="radio" id="login_with_qr" name="login_with" data-type="login_with_qr" class="login_with" value="Login with QR" checked><label for="login_with_qr">Login With QR code</label><input type="radio" name="login_with" class="login_with" data-type="login_with_username" id="login_with_username" value="Login with Username"><label for="login_with_username">Login with Username</label><span class="toggle-outside"><span class="toggle-inside"></span></span></div>';
        }
        prepend_this += (scanner.login_type == 1) ? '<div class="align-center separator-div-wrap"><h2>OR</h2></div><br><label class="default_login">Login With Username and Password</label>' : '';
        $(form_selector).wrapInner('<div class="default-wp-form"></div>');
        $(form_selector).prepend(prepend_this);

        $(form_selector).wrapInner('<div class="scanner-login-wrapper"></div>');
        $(form_selector).find('.learn-more-slide-container').hide();
    }
    /**
     * Display username and QRcode with no password type2 setting
     * @param {*} $
     * @param {*} form_selector
     * @param {*} form_identifier
     */
    function username_nopass_authArmor($,form_selector, form_identifier)
    {
        username_pass_authArmor($,form_selector, form_identifier);
        $('.user-pass-wrap').remove();
        $(document).ready(function()
        {
            $('#wp-submit').click(loginWithUsername);
        });
    }
    /**
     * Display QRcode with no password type3 setting
     * @param {*} $
     * @param {*} form_selector
     * @param {*} form_identifier
     */
    function only_authArmor($,form_selector, form_identifier)
    {
        username_pass_authArmor($,form_selector, form_identifier);
        $('.default-wp-form').remove();
    }
});