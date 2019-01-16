<?php
/**
 * NETopes application user session class file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\AppConfig;
use NETopes\Core\AppHelpers;
use NApp;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataProvider;

/**
 * Class UserSession
 *
 * @package  NETopes\Core\App
 * @access   public
 */
class UserSession {
    /**
     * Gets the login timeout in minutes
     *
     * @return int Returns login timeout
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetLoginTimeout() {
		$cookieHash = static::GetCookieHash();
		if(array_key_exists($cookieHash,$_COOKIE) && strlen($_COOKIE[$cookieHash]) && $_COOKIE[$cookieHash]==NApp::GetParam('user_hash')) { return intval(AppConfig::GetValue('cookie_login_lifetime') / 24 / 60); }
		return intval(AppConfig::GetValue('session_timeout') / 60);
	}//END public static function GetLoginTimeout
    /**
     * Gets the login cookie hash
     *
     * @param  string     $namespace The namespace for the cookie or NULL for current namespace
     * @param string|null $salt
     * @return string The name (hash) of the login cookie
     * @throws \NETopes\Core\AppException
     * @access public
     */
	public static function GetCookieHash(?string $namespace = NULL,?string $salt = NULL) {
		$namespace = $namespace ? $namespace : NApp::$currentNamespace;
		$salt = strlen($salt) ? $salt : 'loggedIn';
		return AppSession::GetNewUID(AppConfig::GetValue('app_encryption_key').NApp::Url()->GetAppDomain().NApp::Url()->GetUrlFolder().$namespace.$salt,'sha256',TRUE);
	}//END public static function GetCookieHash
    /**
     * description
     *
     * @param string|null $name
     * @param string|null $namespace
     * @param bool        $setIfMissing
     * @param int|null    $validity
     * @return string|null
     * @throws \NETopes\Core\AppException
     * @access public
     */
	public static function GetHashFromCookie(?string $name,?string $namespace = NULL,bool $setIfMissing = TRUE,?int $validity = NULL): ?string {
		$cHash = static::GetCookieHash($namespace,$name);
		$cCookieHash = NULL;
		if(array_key_exists($cHash,$_COOKIE) && strlen($_COOKIE[$cHash])) {
			$cCookieHash = \GibberishAES::dec($_COOKIE[$cHash],AppConfig::GetValue('app_encryption_key'));
		} elseif($setIfMissing) {
			$cCookieHash = AppSession::GetNewUID();
			$validity = (is_numeric($validity) && $validity>0 ? $validity : 180)*24*3600;
			$_COOKIE[$cHash] = \GibberishAES::enc($cCookieHash,AppConfig::GetValue('app_encryption_key'));
			setcookie($cHash,$_COOKIE[$cHash],time()+$validity,'/',NApp::Url()->GetAppDomain());
		}//if(array_key_exists($sc_hash,$_COOKIE) && strlen($_COOKIE[$sc_hash]))
		return $cCookieHash;
	}//END public static function GetHashFromCookie
    /**
     * Set the login cookie
     *
     * @param  string  $uHash The user hash
     * @param  integer $validity The cookie lifetime or NULL for default
     * @param  string  $cookieHash The name (hash) of the login cookie
     * @param  string  $namespace The namespace for the cookie or NULL for current namespace
     * @return bool True on success or false
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function SetLoginCookie(string $uHash,?int $validity = NULL,?string $cookieHash = NULL,?string $namespace = NULL): bool {
		if(!is_string($uHash)) { return FALSE; }
		$validity = is_numeric($validity) && $validity>0 ? $validity : AppConfig::GetValue('cookie_login_lifetime')*24*3600;
		$cookieHash = $cookieHash ? $cookieHash : static::GetCookieHash($namespace);
		if(!$uHash) {
			unset($_COOKIE[$cookieHash]);
			setcookie($cookieHash,'',time()+$validity,'/',NApp::Url()->GetAppDomain());
			return TRUE;
		}//if(!$uHash)
		$_COOKIE[$cookieHash] = \GibberishAES::enc($uHash,AppConfig::GetValue('app_encryption_key'));
		setcookie($cookieHash,$_COOKIE[$cookieHash],time()+$validity,'/',NApp::Url()->GetAppDomain());
		return TRUE;
	}//END public static function SetLoginCookie
    /**
     * Loads application settings from database or from request parameters
     *
     * @param bool        $notFromDb
     * @param array|null  $params
     * @param string|null $appAccessKey
     * @return void
     * @throws \NETopes\Core\AppException
     * @access public
     */
	public static function LoadAppSettings(bool $notFromDb = FALSE,?array $params = NULL,?string &$appAccessKey = NULL): void {
		$cookieHash = static::GetCookieHash();
		$auto_login = 1;
		$user_hash = NApp::Url()->GetParam('uhash');
		if(!strlen($user_hash) && array_key_exists($cookieHash,$_COOKIE) && strlen($_COOKIE[$cookieHash])) {
			$user_hash = \GibberishAES::dec($_COOKIE[$cookieHash],AppConfig::GetValue('app_encryption_key'));
		}//if(!strlen($user_hash) && array_key_exists($cookieHash,$_COOKIE) && strlen($_COOKIE[$cookieHash]))
		if(!strlen($user_hash)) {
			$auto_login = 0;
			$user_hash = NApp::GetParam('user_hash');
		}//if(!strlen($user_hash))
		$idsection = NApp::Url()->GetParam('section');
		$idzone = NApp::Url()->GetParam('zone');
		$langCode = NApp::Url()->GetParam('language');
		if(NApp::IsAjax() || !is_string($langCode) || !strlen($langCode)) { $langCode = NApp::GetLanguageCode(); }
		if($notFromDb) {
		    NApp::$userStatus = -1;
            NApp::$loginStatus = FALSE;
            NApp::SetParam('login_status',NApp::$loginStatus);
            NApp::SetParam('id_section',$idsection);
            NApp::SetParam('id_zone',$idzone);
            NApp::SetParam('user_hash',$user_hash);
            NApp::$currentSectionFolder = '';
            NApp::SetParam('account_timezone',AppConfig::GetValue('server_timezone'));
            NApp::SetParam('timezone',AppConfig::GetValue('server_timezone'));
            if(strlen(AppConfig::GetValue('server_timezone'))) { date_default_timezone_set(AppConfig::GetValue('server_timezone')); }
            NApp::SetParam('website_name',AppConfig::GetValue('website_name'));
            NApp::SetParam('rows_per_page',20);
            NApp::SetParam('decimal_separator','.');
            NApp::SetParam('group_separator',',');
            NApp::SetParam('date_separator','.');
            NApp::SetParam('time_separator',':');
            NApp::SetPageParam('language_code',strtolower($langCode));
            NApp::Url()->SetParam('language',$langCode);
        } else {
            $appdata = DataProvider::Get('System\System','GetAppSettings',[
                'for_domain'=>NApp::Url()->GetAppDomain(),
                'for_namespace'=>NApp::$currentNamespace,
                'for_lang_code'=>$langCode,
                'for_user_hash'=>$user_hash,
                'login_namespace'=>(strlen(NApp::$loginNamespace) ? NApp::$loginNamespace : NULL),
                'section_id'=>((is_numeric($idsection) && $idsection>0) ? $idsection : NULL),
                'zone_id'=>((is_numeric($idzone) && $idzone>0) ? $idzone : NULL),
                'validity'=>static::GetLoginTimeout(),
                'keep_alive'=>(NApp::$keepAlive ? 1 : 0),
                'auto_login'=>$auto_login,
                'for_user_ip'=>(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'),
            ],['mode'=>'native']);
            if(!is_object($appdata)) { die('Invalid application settings!'); }
            $login_msg = $appdata->safeGetLoginMsg('','is_string');
            if(!is_object($appdata) || !$appdata->safeGetIdAccount(0,'is_integer')  || !$appdata->safeGetIdSection(0,'is_integer') || !$appdata->safeGetIdZone(0,'is_integer') || !$appdata->safeGetIdLanguage(0,'is_integer') || $login_msg=='incorect_namespace') { die('Wrong domain or application settings !!!'); }
            NApp::$userStatus = $appdata->safeGetState(-1,'is_integer');
            NApp::$loginStatus = ($login_msg=='1' && (NApp::$userStatus==1 || NApp::$userStatus==2));
            if(NApp::$loginStatus && isset($_COOKIE[$cookieHash]) && strlen($appdata->getProperty('user_hash'))) {
                static::SetLoginCookie($appdata->getProperty('user_hash'),NULL,$cookieHash);
            }//if(NApp::$loginStatus && isset($_COOKIE[$cookieHash]) && strlen($appdata->getProperty('user_hash')))
            NApp::SetParam('login_status',NApp::$loginStatus);
            NApp::SetParam('id_registry',$appdata->getProperty('id_registry'));
            NApp::SetParam('id_section',$appdata->getProperty('id_section'));
            NApp::SetParam('section_folder',$appdata->getProperty('section_folder'));
            NApp::$currentSectionFolder = '';
            $cSectionDir = $appdata->getProperty('section_folder','','is_string');
            if(NApp::$withSections && strlen($cSectionDir)) { NApp::$currentSectionFolder = '/'.$cSectionDir; }
            NApp::SetParam('id_zone',$appdata->getProperty('id_zone'));
            NApp::SetParam('zone_code',$appdata->getProperty('zone_code'));
            NApp::SetParam('id_account',$appdata->getProperty('id_account'));
            NApp::SetPageParam('id_account',$appdata->getProperty('id_account'));
            NApp::SetParam('account_type',$appdata->getProperty('account_type'));
            NApp::SetParam('account_name',$appdata->getProperty('account_name'));
            NApp::SetParam('access_key',$appdata->getProperty('access_key'));
            $appAccessKey = $appdata->getProperty('access_key');
            NApp::SetParam('account_timezone',$appdata->getProperty('account_timezone',AppConfig::GetValue('server_timezone'),'is_notempty_string'));
            NApp::SetParam('id_entity',$appdata->getProperty('id_entity'));
            NApp::SetParam('id_location',$appdata->getProperty('id_location'));
            NApp::SetParam('website_name',$appdata->getProperty('website_name'));
            NApp::SetParam('rows_per_page',$appdata->getProperty('rows_per_page'));
            $timezone = $appdata->getProperty('timezone',NApp::GetParam('account_timezone'),'is_notempty_string');
            NApp::SetParam('timezone',$timezone);
            if(strlen($timezone)) { date_default_timezone_set($timezone); }
            NApp::SetParam('translation_cache_is_dirty',$appdata->getProperty('is_dirty'));
            NApp::SetParam('decimal_separator',$appdata->getProperty('decimal_separator'));
            NApp::SetParam('group_separator',$appdata->getProperty('group_separator'));
            NApp::SetParam('date_separator',$appdata->getProperty('date_separator'));
            NApp::SetParam('time_separator',$appdata->getProperty('time_separator'));
            NApp::SetParam('id_user',$appdata->getProperty('id_user'));
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
            $appTheme = $appdata->getProperty('app_theme',NULL,'is_string');
            if(strlen($appTheme)) { AppConfig::SetValue('app_theme',$appTheme=='_default' ? NULL : $appTheme); }
            NApp::SetPageParam('menu_state',$appdata->getProperty('menu_state'));
            NApp::SetPageParam('id_language',$appdata->getProperty('id_language'));
            NApp::SetPageParam('language_code',strtolower($appdata->getProperty('lang_code')));
            NApp::Url()->SetParam('language',strtolower($appdata->getProperty('lang_code')));
		}//if($notFromDb)
		if(static::$currentNamespace=='web') { return; }
		//Load user rights
		if(NApp::$loginStatus && !$notFromDb) {
			$ur_ts = NApp::GetParam('user_rights_revoked_ts');
			$dt_ur_ts = strlen($ur_ts) ? new \DateTime($ur_ts) : new \DateTime('1900-01-01 01:00:00');
			if($dt_ur_ts->add(new \DateInterval('PT30M'))<(new \DateTime('now'))) {
				$rightsrevoked = DataProvider::GetArray('System\Users','GetUserRightsRevoked',array('user_id'=>NApp::GetParam('id_user')),array('results_keys_case'=>CASE_LOWER));
				NApp::SetParam('user_rights_revoked',Module::ConvertRightsRevokedArray($rightsrevoked));
				NApp::SetParam('user_rights_revoked_ts',date('Y-m-d H:i:s'));
			}//if($dt_ur_ts->add(new DateInterval('PT30M'))<(new DateTime('now')))
		} else {
			NApp::SetParam('user_rights_revoked_ts',NULL);
			NApp::SetParam('user_rights_revoked',NULL);
		}//if(NApp::$loginStatus && !$notFromDb)
	}//END public static function LoadAppSettings
    /**
     * This function checks the authenticity
     * of the login information in the database
     * and creates the session effectively logging in the user.
     *
     * @param             $username
     * @param             $password
     * @param int         $remember
     * @param string|null $loginNamespace
     * @param bool        $allowNullCompany
     * @return bool Returns TRUE if login is successful or FALSE otherwise
     * @throws \NETopes\Core\AppException
     * @access public
     */
	public static function Login($username,$password,$remember = 0,?string $loginNamespace = NULL,bool $allowNullCompany = FALSE) {
		NApp::$loginStatus = FALSE;
		$tries = NApp::GetParam('login_tries');
        if(is_numeric($tries) && $tries>=0) {
            $tries += 1;
        } else {
            $tries = 1;
        }//if(is_numeric($tries) && $tries>=0)
        NApp::SetParam('login_tries',$tries);
		if($tries>50) {
            NApp::Redirect(NApp::$appBaseUrl.'/bruteforce.php');
            return NApp::$loginStatus;
        }//if($tries>50)
        if(!is_string($password) || !strlen($password)) { return FALSE; }
        $namespace = (strlen($loginNamespace) ? $loginNamespace : (strlen(NApp::$loginNamespace) ? NApp::$loginNamespace : NApp::$currentNamespace));
		switch($namespace) {
			case 'web':
				$userdata = DataProvider::Get('Cms\Users','GetLogin',[
					'section_id'=>(NApp::GetParam('id_section') ? NApp::GetParam('id_section') : NULL),
					'zone_id'=>(NApp::GetParam('id_zone') ? NApp::GetParam('id_zone') : NULL),
					'for_username'=>$username,
					'allow_null_company'=>intval($allowNullCompany),
					'web_session'=>static::GetHashFromCookie('websession'),
				]);
				break;
			default:
				$userdata = DataProvider::Get('System\Users','GetLogin',['for_username'=>$username]);
		}//END switch
		if(!is_object($userdata)) { return \Translate::Get('msg_unknown_error'); }
		$login_msg = $userdata->getProperty('login_msg','','is_string');
		if(!strlen($login_msg)) { return \Translate::Get('msg_unknown_error'); }
		if($login_msg!='1') { return \Translate::Get('msg_'.$login_msg); }
		NApp::$loginStatus = password_verify($password,$userdata->getProperty('password_hash'));
		if(!NApp::$loginStatus) { return \Translate::Get('msg_invalid_password'); }
		NApp::SetParam('login_tries',NULL);
		NApp::$userStatus = $userdata->getProperty('active',0,'is_integer');
		if(NApp::$userStatus<>1 && NApp::$userStatus<>2) { return \Translate::Get('msg_inactive_user'); }
		NApp::SetParam('id_user',$userdata->getProperty('id',NULL,'is_integer'));
		NApp::SetParam('confirmed_user',$userdata->getProperty('confirmed',NULL,'is_integer'));
		NApp::SetParam('user_hash',$userdata->getProperty('hash',NULL,'is_string'));
		NApp::SetParam('id_users_group',$userdata->getProperty('id_users_group',NULL,'is_integer'));
		NApp::SetParam('id_company',$userdata->getProperty('id_company',NApp::GetParam('id_company'),'is_integer'));
		NApp::SetParam('company_name',$userdata->getProperty('company_name',NULL,'is_string'));
		NApp::SetParam('id_country',$userdata->getProperty('id_country',NApp::GetParam('id_country'),'is_integer'));
		NApp::SetParam('id_element',$userdata->getProperty('id_element',NApp::GetParam('id_element'),'is_integer'));
		NApp::SetParam('user_email',$userdata->getProperty('email',NULL,'is_string'));
		NApp::SetParam('username',$userdata->getProperty('username',NULL,'is_string'));
		$user_full_name = trim($userdata->getProperty('surname','','is_string').' '.$userdata->getProperty('name','','is_string'));
		NApp::SetParam('user_full_name',$user_full_name);
		NApp::SetParam('phone',$userdata->getProperty('phone',NULL,'is_string'));
		NApp::SetParam('sadmin',$userdata->getProperty('sadmin',0,'is_integer'));
		NApp::SetPageParam('menu_state',$userdata->getProperty('menu_state',0,'is_integer'));
		NApp::SetParam('rows_per_page',$userdata->getProperty('rows_per_page',NApp::GetParam('rows_per_page'),'is_integer'));
		NApp::SetParam('timezone',$userdata->getProperty('timezone',NApp::GetParam('timezone'),'is_notempty_string'));
		NApp::SetParam('decimal_separator',$userdata->getProperty('decimal_separator',NApp::GetParam('decimal_separator'),'is_notempty_string'));
		NApp::SetParam('group_separator',$userdata->getProperty('group_separator',NApp::GetParam('group_separator'),'is_notempty_string'));
		NApp::SetParam('date_separator',$userdata->getProperty('date_separator',NApp::GetParam('date_separator'),'is_notempty_string'));
		NApp::SetParam('time_separator',$userdata->getProperty('time_separator',NApp::GetParam('time_separator'),'is_notempty_string'));
		if($userdata->getProperty('id_language_def',0,'is_integer')>0 && strlen($userdata->getProperty('lang_code','','is_string'))) {
			NApp::SetPageParam('id_language',$userdata->getProperty('id_language_def'));
			NApp::SetPageParam('language_code',$userdata->getProperty('lang_code'));
			NApp::Url()->SetParam('language',$userdata->getProperty('lang_code'));
		}//if($userdata->getProperty('id_language_def',0,'is_integer')>0 && strlen($userdata->getProperty('lang_code','','is_string')))
		if($remember && strlen($userdata->getProperty('hash','','is_string'))) {
			static::SetLoginCookie($userdata->getProperty('hash','','is_string'));
		} else {
			static::SetLoginCookie('',-4200);
		}//if($remember && strlen($userdata->getProperty('hash','','is_string')))
		//DataProvider::GetArray('System\Users','SetUserLoginLog',array('id_user'=>NApp::GetParam('id_user'),'id_account'=>NApp::GetParam('id_account')));
		return NApp::$loginStatus;
	}//END public static function Login
	/**
	 * Method called on user logout action for clearing the session
	 * and the login cookie
	 *
	 * @param  string $namespace If passed, logs out the specified namespace
	 * else logs out the current namespace
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public static function Logout(?string $namespace = NULL) {
		$namespace = $namespace ? $namespace : NApp::$currentNamespace;
		static::SetLoginCookie('',-4200,NULL,$namespace);
		switch($namespace) {
			case 'web':
				$idUser = NApp::GetParam('id_user');
				if(is_numeric($idUser) && $idUser>0) { DataProvider::Get('Cms\Users','SetLastRequest',['user_id'=>$idUser]); }
				break;
			default:
				$idUser = NApp::GetParam('id_user');
				if(is_numeric($idUser) && $idUser>0) { DataProvider::Get('System\Users','SetLastRequest',['user_id'=>$idUser]); }
				break;
		}//END switch
		NApp::$loginStatus = FALSE;
		NApp::NamespaceSessionCommit(TRUE,NULL,NULL,$namespace);
	}//END public static function Logout
}//END class UserSession