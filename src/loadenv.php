<?php
$_LOCAL_ENV=[];

if(defined('_NAPP_ROOT_PATH') && file_exists(_NAPP_ROOT_PATH.'/.env')) {
    $_LOCAL_ENV=parse_ini_file(_NAPP_ROOT_PATH.'/.env',TRUE,INI_SCANNER_TYPED);
}

/**
 * @param string     $key
 * @param mixed|null $defaultValue
 * @return mixed|null
 */
function get_env_value(string $key,$defaultValue=NULL) {
    global $_LOCAL_ENV;
    return (!strlen($key) || !is_array($_LOCAL_ENV) || !isset($_LOCAL_ENV[$key]))
        ? $defaultValue
        : $_LOCAL_ENV[$key];
}//END function get_env_value