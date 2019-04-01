<?php
/**
 * NETopes application user session class file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use DateInterval;
use DateTime;
use GibberishAES;
use NETopes\Core\AppConfig;
use NETopes\Core\Data\DataProvider;
use NApp;
use Translate;

/**
 * Class UserSession
 *
 * @package  NETopes\Core\App
 */
class UserSessionAdapter implements IUserSessionAdapter {
    /**
     * Get user ID key
     *
     * @return string Returns the key of the user ID
     */
    public static function GetUserIdKey(): string {
        return 'id_user';
    }//END public static function GetUserIdKey

    /**
     * Get current user ID
     *
     * @return int|null Returns current user ID
     * @throws \NETopes\Core\AppException
     */
    public static function GetCurrentUserId(): ?int {
        return is_integer(NApp::GetParam(static::GetUserIdKey())) ? NApp::GetParam(static::GetUserIdKey()) : NULL;
    }//END public static function GetCurrentUserId

    /**
     * Loads application settings from database or from request parameters
     *
     * @param bool        $notFromDb
     * @param array|null  $params
     * @param string|null $appAccessKey
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function LoadAppSettings(bool $notFromDb=FALSE,?array $params=NULL,?string &$appAccessKey=NULL): void {
        $cookieHash=UserSession::GetCookieHash();
        $auto_login=1;
        $user_hash=NApp::Url()->GetParam('uhash');
        if(!strlen($user_hash) && array_key_exists($cookieHash,$_COOKIE) && strlen($_COOKIE[$cookieHash])) {
            $user_hash=GibberishAES::dec($_COOKIE[$cookieHash],AppConfig::GetValue('app_encryption_key'));
        }//if(!strlen($user_hash) && array_key_exists($cookieHash,$_COOKIE) && strlen($_COOKIE[$cookieHash]))
        if(!strlen($user_hash)) {
            $auto_login=0;
            $user_hash=NApp::GetParam('user_hash');
        }//if(!strlen($user_hash))
        $idsection=NApp::Url()->GetParam('section');
        $idzone=NApp::Url()->GetParam('zone');
        $langCode=NApp::Url()->GetParam('language');
        if(NApp::IsAjax() || !is_string($langCode) || !strlen($langCode)) {
            $langCode=NApp::GetLanguageCode();
        }
        if($notFromDb) {
            UserSession::$userStatus=-1;
            UserSession::$loginStatus=FALSE;
            NApp::SetParam('login_status',UserSession::$loginStatus);
            NApp::SetParam('id_section',$idsection);
            NApp::SetParam('id_zone',$idzone);
            NApp::SetParam('user_hash',$user_hash);
            NApp::$currentSectionFolder='';
            NApp::SetParam('account_timezone',AppConfig::GetValue('server_timezone'));
            NApp::SetParam('timezone',AppConfig::GetValue('server_timezone'));
            if(strlen(AppConfig::GetValue('server_timezone'))) {
                date_default_timezone_set(AppConfig::GetValue('server_timezone'));
            }
            NApp::SetParam('website_name',AppConfig::GetValue('website_name'));
            NApp::SetParam('rows_per_page',20);
            NApp::SetParam('decimal_separator','.');
            NApp::SetParam('group_separator',',');
            NApp::SetParam('date_separator','.');
            NApp::SetParam('time_separator',':');
            NApp::SetPageParam('language_code',strtolower($langCode));
            NApp::Url()->SetParam('language',$langCode);
        } else {
            $appdata=DataProvider::Get('System\System','GetAppSettings',[
                'for_domain'=>NApp::Url()->GetAppDomain(),
                'for_namespace'=>NApp::$currentNamespace,
                'for_lang_code'=>$langCode,
                'for_user_hash'=>$user_hash,
                'login_namespace'=>(strlen(NApp::$loginNamespace) ? NApp::$loginNamespace : NULL),
                'section_id'=>((is_numeric($idsection) && $idsection>0) ? $idsection : NULL),
                'zone_id'=>((is_numeric($idzone) && $idzone>0) ? $idzone : NULL),
                'validity'=>UserSession::GetLoginTimeout(),
                'keep_alive'=>(NApp::$keepAlive ? 1 : 0),
                'auto_login'=>$auto_login,
                'for_user_ip'=>(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'),
            ],['mode'=>'native']);
            if(!is_object($appdata)) {
                die('Invalid application settings!');
            }
            $login_msg=$appdata->safeGetLoginMsg('','is_string');
            if(!is_object($appdata) || !$appdata->safeGetIdAccount(0,'is_integer') || !$appdata->safeGetIdSection(0,'is_integer') || !$appdata->safeGetIdZone(0,'is_integer') || !$appdata->safeGetIdLanguage(0,'is_integer') || $login_msg=='incorect_namespace') {
                die('Wrong domain or application settings !!!');
            }
            UserSession::$userStatus=$appdata->safeGetState(-1,'is_integer');
            UserSession::$loginStatus=($login_msg=='1' && (UserSession::$userStatus==1 || UserSession::$userStatus==2));
            if(UserSession::$loginStatus && isset($_COOKIE[$cookieHash]) && strlen($appdata->getProperty('user_hash'))) {
                UserSession::SetLoginCookie($appdata->getProperty('user_hash'),NULL,$cookieHash);
            }//if(UserSession::$loginStatus && isset($_COOKIE[$cookieHash]) && strlen($appdata->getProperty('user_hash')))
            NApp::SetParam('login_status',UserSession::$loginStatus);
            NApp::SetParam('id_registry',$appdata->getProperty('id_registry'));
            NApp::SetParam('id_section',$appdata->getProperty('id_section'));
            NApp::SetParam('section_folder',$appdata->getProperty('section_folder'));
            NApp::$currentSectionFolder='';
            $cSectionDir=$appdata->getProperty('section_folder','','is_string');
            if(NApp::$withSections && strlen($cSectionDir)) {
                NApp::$currentSectionFolder='/'.$cSectionDir;
            }
            NApp::SetParam('id_zone',$appdata->getProperty('id_zone'));
            NApp::SetParam('zone_code',$appdata->getProperty('zone_code'));
            NApp::SetParam('id_account',$appdata->getProperty('id_account'));
            NApp::SetPageParam('id_account',$appdata->getProperty('id_account'));
            NApp::SetParam('account_type',$appdata->getProperty('account_type'));
            NApp::SetParam('account_name',$appdata->getProperty('account_name'));
            $appAccessKey=$appdata->getProperty('access_key');
            NApp::SetParam('account_timezone',$appdata->getProperty('account_timezone',AppConfig::GetValue('server_timezone'),'is_notempty_string'));
            NApp::SetParam('id_entity',$appdata->getProperty('id_entity'));
            NApp::SetParam('id_location',$appdata->getProperty('id_location'));
            NApp::SetParam('website_name',$appdata->getProperty('website_name'));
            NApp::SetParam('rows_per_page',$appdata->getProperty('rows_per_page'));
            $timezone=$appdata->getProperty('timezone',NApp::GetParam('account_timezone'),'is_notempty_string');
            NApp::SetParam('timezone',$timezone);
            if(strlen($timezone)) {
                date_default_timezone_set($timezone);
            }
            NApp::SetParam('translation_cache_is_dirty',$appdata->getProperty('is_dirty'));
            NApp::SetParam('decimal_separator',$appdata->getProperty('decimal_separator'));
            NApp::SetParam('group_separator',$appdata->getProperty('group_separator'));
            NApp::SetParam('date_separator',$appdata->getProperty('date_separator'));
            NApp::SetParam('time_separator',$appdata->getProperty('time_separator'));
            NApp::SetParam(static::GetUserIdKey(),$appdata->getProperty('id_user'));
            NApp::SetParam('id_users_group',$appdata->getProperty('id_users_group'));
            NApp::SetParam('restrict_access',$appdata->getProperty('restrict_access'));
            NApp::SetParam('id_country',$appdata->getProperty('id_country'));
            NApp::SetParam('id_company',$appdata->getProperty('id_company'));
            NApp::SetParam('company_name',$appdata->getProperty('company_name'));
            NApp::SetParam('user_hash',$appdata->getProperty('user_hash'));
            NApp::SetParam('user_email',$appdata->getProperty('email'));
            NApp::SetParam('username',$appdata->getProperty('username'));
            NApp::SetParam('user_full_name',$appdata->getProperty('surname').' '.$appdata->getProperty('name'));
            NApp::SetParam('user_phone',$appdata->getProperty('phone'));
            NApp::SetParam('confirmed_user',$appdata->getProperty('confirmed'));
            NApp::SetParam('sadmin',$appdata->getProperty('sadmin'));
            $appTheme=$appdata->getProperty('app_theme',NULL,'is_string');
            if(strlen($appTheme)) {
                AppConfig::SetValue('app_theme',$appTheme=='_default' ? NULL : $appTheme);
            }
            NApp::SetPageParam('menu_state',$appdata->getProperty('menu_state'));
            NApp::SetPageParam('id_language',$appdata->getProperty('id_language'));
            NApp::SetPageParam('language_code',strtolower($appdata->getProperty('lang_code')));
            NApp::Url()->SetParam('language',strtolower($appdata->getProperty('lang_code')));
        }//if($notFromDb)
        if(NApp::$currentNamespace=='web') {
            return;
        }
        //Load user rights
        if(UserSession::$loginStatus && !$notFromDb) {
            $ur_ts=NApp::GetParam('user_rights_revoked_ts');
            $dt_ur_ts=strlen($ur_ts) ? new DateTime($ur_ts) : new DateTime('1900-01-01 01:00:00');
            if($dt_ur_ts->add(new DateInterval('PT30M'))<(new DateTime('now'))) {
                $rightsrevoked=DataProvider::GetArray('System\Users','GetUserRightsRevoked',['user_id'=>NApp::GetParam(static::GetUserIdKey())],['results_keys_case'=>CASE_LOWER]);
                NApp::SetParam('user_rights_revoked',AppHelpers::ConvertRightsRevokedArray($rightsrevoked));
                NApp::SetParam('user_rights_revoked_ts',date('Y-m-d H:i:s'));
            }//if($dt_ur_ts->add(new DateInterval('PT30M'))<(new DateTime('now')))
        } else {
            NApp::SetParam('user_rights_revoked_ts',NULL);
            NApp::SetParam('user_rights_revoked',NULL);
        }//if(UserSession::$loginStatus && !$notFromDb)
    }//END public static function LoadAppSettings

    /**
     * This function authenticates an user and updates the session data
     *
     * @param string                              $username
     * @param string                              $password
     * @param bool                                $remember
     * @param \NETopes\Core\App\Params|array|null $extraParams
     * @return bool|null Returns TRUE if login is successful or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function Login(string $username,string $password,bool $remember=FALSE,$extraParams=NULL): ?bool {
        $params=$extraParams instanceof Params ? $extraParams : new Params(is_array($extraParams) ? $extraParams : []);
        UserSession::$loginStatus=FALSE;
        $tries=NApp::GetParam('login_tries');
        if(is_numeric($tries) && $tries>=0) {
            $tries+=1;
        } else {
            $tries=1;
        }//if(is_numeric($tries) && $tries>=0)
        NApp::SetParam('login_tries',$tries);
        if($tries>50) {
            NApp::Redirect(NApp::$appBaseUrl.'/bruteforce.php');
            return UserSession::$loginStatus;
        }//if($tries>50)
        if(!is_string($password) || !strlen($password)) {
            return FALSE;
        }
        $namespace=$params->safeGet('login_namespace',NApp::$loginNamespace,'is_notempty_string');
        switch($namespace) {
            case 'web':
                $userData=DataProvider::Get('Cms\Users','GetLogin',[
                    'section_id'=>(NApp::GetParam('id_section') ? NApp::GetParam('id_section') : NULL),
                    'zone_id'=>(NApp::GetParam('id_zone') ? NApp::GetParam('id_zone') : NULL),
                    'for_username'=>$username,
                    'allow_null_company'=>intval($params->safeGet('allow_null_company',FALSE,'bool')),
                    'web_session'=>UserSession::GetHashFromCookie('websession'),
                ]);
                break;
            default:
                $userData=DataProvider::Get('System\Users','GetLogin',['for_username'=>$username]);
        }//END switch
        if(!is_object($userData)) {
            return Translate::Get('msg_unknown_error');
        }
        $login_msg=$userData->getProperty('login_msg','','is_string');
        if(!strlen($login_msg)) {
            return Translate::Get('msg_unknown_error');
        }
        if($login_msg!='1') {
            return Translate::Get('msg_'.$login_msg);
        }
        UserSession::$loginStatus=password_verify($password,$userData->getProperty('password_hash'));
        if(!UserSession::$loginStatus) {
            return Translate::Get('msg_invalid_password');
        }
        NApp::SetParam('login_tries',NULL);
        UserSession::$userStatus=$userData->getProperty('active',0,'is_integer');
        if(UserSession::$userStatus<>1 && UserSession::$userStatus<>2) {
            return Translate::Get('msg_inactive_user');
        }
        NApp::SetParam(static::GetUserIdKey(),$userData->getProperty('id',NULL,'is_integer'));
        NApp::SetParam('confirmed_user',$userData->getProperty('confirmed',NULL,'is_integer'));
        NApp::SetParam('user_hash',$userData->getProperty('hash',NULL,'is_string'));
        NApp::SetParam('id_users_group',$userData->getProperty('id_users_group',NULL,'is_integer'));
        NApp::SetParam('id_company',$userData->getProperty('id_company',NApp::GetParam('id_company'),'is_integer'));
        NApp::SetParam('company_name',$userData->getProperty('company_name',NULL,'is_string'));
        NApp::SetParam('id_country',$userData->getProperty('id_country',NApp::GetParam('id_country'),'is_integer'));
        NApp::SetParam('id_element',$userData->getProperty('id_element',NApp::GetParam('id_element'),'is_integer'));
        NApp::SetParam('user_email',$userData->getProperty('email',NULL,'is_string'));
        NApp::SetParam('username',$userData->getProperty('username',NULL,'is_string'));
        $user_full_name=trim($userData->getProperty('surname','','is_string').' '.$userData->getProperty('name','','is_string'));
        NApp::SetParam('user_full_name',$user_full_name);
        NApp::SetParam('phone',$userData->getProperty('phone',NULL,'is_string'));
        NApp::SetParam('sadmin',$userData->getProperty('sadmin',0,'is_integer'));
        NApp::SetPageParam('menu_state',$userData->getProperty('menu_state',0,'is_integer'));
        NApp::SetParam('rows_per_page',$userData->getProperty('rows_per_page',NApp::GetParam('rows_per_page'),'is_integer'));
        NApp::SetParam('timezone',$userData->getProperty('timezone',NApp::GetParam('timezone'),'is_notempty_string'));
        NApp::SetParam('decimal_separator',$userData->getProperty('decimal_separator',NApp::GetParam('decimal_separator'),'is_notempty_string'));
        NApp::SetParam('group_separator',$userData->getProperty('group_separator',NApp::GetParam('group_separator'),'is_notempty_string'));
        NApp::SetParam('date_separator',$userData->getProperty('date_separator',NApp::GetParam('date_separator'),'is_notempty_string'));
        NApp::SetParam('time_separator',$userData->getProperty('time_separator',NApp::GetParam('time_separator'),'is_notempty_string'));
        if($userData->getProperty('id_language_def',0,'is_integer')>0 && strlen($userData->getProperty('lang_code','','is_string'))) {
            NApp::SetPageParam('id_language',$userData->getProperty('id_language_def'));
            NApp::SetPageParam('language_code',$userData->getProperty('lang_code'));
            NApp::Url()->SetParam('language',$userData->getProperty('lang_code'));
        }//if($userData->getProperty('id_language_def',0,'is_integer')>0 && strlen($userData->getProperty('lang_code','','is_string')))
        if($remember && strlen($userData->getProperty('hash','','is_string'))) {
            UserSession::SetLoginCookie($userData->getProperty('hash','','is_string'));
        } else {
            UserSession::SetLoginCookie('',-4200);
        }//if($remember && strlen($userData->getProperty('hash','','is_string')))
        //DataProvider::GetArray('System\Users','SetUserLoginLog',array('id_user'=>NApp::GetParam(static::GetUserIdKey()),'id_account'=>NApp::GetParam('id_account')));
        return UserSession::$loginStatus;
    }//END public static function Login

    /**
     * Method called on user logout action for clearing the session
     * and the login cookie
     *
     * @param  string $namespace If passed, logs out the specified namespace
     *                           else logs out the current namespace
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function Logout(?string $namespace=NULL): void {
        $namespace=$namespace ? $namespace : NApp::$currentNamespace;
        UserSession::SetLoginCookie('',-4200,NULL,$namespace);
        switch($namespace) {
            case 'web':
                $idUser=NApp::GetParam(static::GetUserIdKey());
                if(is_numeric($idUser) && $idUser>0) {
                    DataProvider::Get('Cms\Users','SetLastRequest',['user_id'=>$idUser]);
                }
                break;
            default:
                $idUser=NApp::GetParam(static::GetUserIdKey());
                if(is_numeric($idUser) && $idUser>0) {
                    DataProvider::Get('System\Users','SetLastRequest',['user_id'=>$idUser]);
                }
                break;
        }//END switch
        UserSession::$loginStatus=FALSE;
        NApp::NamespaceSessionCommit(TRUE,FALSE,TRUE,$namespace);
    }//END public static function Logout
}//END class UserSessionAdapter