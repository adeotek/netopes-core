<?php
if(!defined('_VALID_NAPP_REQ') || _VALID_NAPP_REQ!==TRUE) { die('Invalid request!'); }
// Load helper functions
require_once(__DIR__.'/helpers.php');
// Load AppConfig
try {
    \NETopes\Core\AppConfig::LoadConfig((isset($_APP_CONFIG) && is_array($_APP_CONFIG) ? $_APP_CONFIG : []),(isset($_CUSTOM_CONFIG_STRUCTURE) && is_array($_CUSTOM_CONFIG_STRUCTURE) ? $_CUSTOM_CONFIG_STRUCTURE : []));
} catch(Exception $e) {
    die($e->getMessage());
}//END try
// START ErrorHandler Setup
$custom_error_handler = \NETopes\Core\AppConfig::error_handler_class();
if(strlen($custom_error_handler) && class_exists($custom_error_handler) && array_key_exists('NETopes\Core\App\IErrorHandler',class_implements($custom_error_handler))) {
    /** @var \NETopes\Core\App\IErrorHandler $custom_error_handler */
    $custom_error_handler::SetErrorLogFile(\NETopes\Core\AppConfig::errors_log_file());
    $custom_error_handler::SetErrorLogPath(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.\NETopes\Core\AppConfig::logs_path().'/');
    set_error_handler([$custom_error_handler,'ErrorHandlerFunction']);
    set_exception_handler([$custom_error_handler,'ExceptionHandlerFunction']);
    register_shutdown_function([$custom_error_handler,'ShutDownHandlerFunction']);
} else {
    require_once(__DIR__.'/App/ErrorHandler.php');
    ErrorHandler::SetErrorLogFile(\NETopes\Core\AppConfig::errors_log_file());
    ErrorHandler::SetErrorLogPath(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.\NETopes\Core\AppConfig::logs_path().'/');
    set_error_handler(['ErrorHandler','ErrorHandlerFunction']);
    set_exception_handler(['ErrorHandler','ExceptionHandlerFunction']);
    register_shutdown_function(['ErrorHandler','ShutDownHandlerFunction']);
}//if(strlen($custom_error_handler) && class_exists($custom_error_handler) && array_key_exists('NETopes\Core\App\IErrorHandler',class_implements($custom_error_handler)))
// END START ErrorHandler Setup
if(!\NETopes\Core\AppConfig::use_custom_autoloader()) {
    require_once(__DIR__.'/napp_autoload.php');
    spl_autoload_register('_napp_autoload');
}//if(!\NETopes\Core\AppConfig::use_custom_autoloader())