<?php

/**
 * Add custom log for order status update
 */
function add_custom_auth_armor_log($msg, $screen, $path = ''){
    
    $upload_dir = wp_upload_dir();
    $day = date_i18n('d');
    $upload_dir = $upload_dir['basedir'];
    $logs_dir = $upload_dir . '/AuthArmor-Log/'.$day;
    if ( ! is_dir( $logs_dir ) ) {
        mkdir( $logs_dir, 0755, true );
    }
    $log_path = $logs_dir."/AuthArmor-".date_i18n('Y-m-d').".log";
    $message = " Message : ".$msg . ", PATH " .$path." , Screen : ".$screen;
    $file = fopen($log_path,"a"); 
    $data = fwrite($file, "\n" . date_i18n('Y-m-d h:i:s') . " :: " . $message); 
    fclose($file);
}