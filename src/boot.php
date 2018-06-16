<?php
	if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }
	require_once(__DIR__.'/NAppConfigStructure.php');
	if(isset($_CUSTOM_CONFIG_STRUCTURE) && is_array($_CUSTOM_CONFIG_STRUCTURE)) {
		$_CUSTOM_CONFIG_STRUCTURE = array_merge($_CUSTOM_CONFIG_STRUCTURE,(isset($_NAPP_CONFIG_STRUCTURE) && is_array($_NAPP_CONFIG_STRUCTURE) ? $_NAPP_CONFIG_STRUCTURE : []));
	} else {
		$_CUSTOM_CONFIG_STRUCTURE = (isset($_NAPP_CONFIG_STRUCTURE) && is_array($_NAPP_CONFIG_STRUCTURE) ? $_NAPP_CONFIG_STRUCTURE : []);
	}//if(isset($_CUSTOM_CONFIG_STRUCTURE) && is_array($_CUSTOM_CONFIG_STRUCTURE))
	require_once(PAF\AppPath::GetBootFile());
	require_once(__DIR__.'/helpers.php');
	// START ErrorHandler Setup
	$custom_error_handler = PAF\AppConfig::error_handler_class();
	if(strlen($custom_error_handler) && class_exists($custom_error_handler) && array_key_exists('NETopes\Core\App\IErrorHandler',class_implements($custom_error_handler))) {
	    $custom_error_handler::SetErrorLogFile(PAF\AppConfig::errors_log_file());
	    $custom_error_handler::SetErrorLogPath(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.PAF\AppConfig::logs_path().'/');
	    set_error_handler([$custom_error_handler,'ErrorHandlerFunction']);
		set_exception_handler([$custom_error_handler,'ExceptionHandlerFunction']);
		register_shutdown_function([$custom_error_handler,'ShutDownHandlerFunction']);
	} else {
		require_once(__DIR__.'/App/ErrorHandler.php');
		ErrorHandler::SetErrorLogFile(PAF\AppConfig::errors_log_file());
	    ErrorHandler::SetErrorLogPath(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.PAF\AppConfig::logs_path().'/');
	    set_error_handler(['ErrorHandler','ErrorHandlerFunction']);
		set_exception_handler(['ErrorHandler','ExceptionHandlerFunction']);
		register_shutdown_function(['ErrorHandler','ShutDownHandlerFunction']);
	}//if(strlen($custom_error_handler) && class_exists($custom_error_handler) && array_key_exists('NETopes\Core\App\IErrorHandler',class_implements($custom_error_handler)))
	// END START ErrorHandler Setup
	require_once(__DIR__.'/autoload_napp.php');
?>