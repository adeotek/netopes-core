<?php
/**
 * NETopes application user session class file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use GibberishAES;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NApp;

/**
 * Class UserSession
 *
 * @package  NETopes\Core\App
 */
class UserSession {
    /**
     * @var string User session adapter class name
     */
    protected static $adapterClass=UserSessionAdapter::class;
    /**
     * @var    bool|null Login status
     */
    public static $loginStatus=NULL;
    /**
     * @var    int User status
     */
    public static $userStatus=0;

    /**
     * @param string $className
     * @throws \NETopes\Core\AppException
     */
    public static function SetAdapterClass(string $className): void {
        $className='\\'.trim($className,'\\');
        if(!class_exists($className)) {
            throw new AppException('Invalid UserSession adapter class!');
        }
        static::$adapterClass=$className;
    }//END public static function SetAdapterClass

    /**
     * Gets the login timeout in minutes
     *
     * @return int Returns login timeout
     * @throws \NETopes\Core\AppException
     */
    public static function GetLoginTimeout() {
        $cookieHash=static::GetCookieHash();
        if(array_key_exists($cookieHash,$_COOKIE) && strlen($_COOKIE[$cookieHash]) && $_COOKIE[$cookieHash]==NApp::GetParam('user_hash')) {
            return intval(AppConfig::GetValue('cookie_login_lifetime') / 24 / 60);
        }
        return intval(AppConfig::GetValue('session_timeout') / 60);
    }//END public static function GetLoginTimeout

    /**
     * Gets the login cookie hash
     *
     * @param  string     $namespace The namespace for the cookie or NULL for current namespace
     * @param string|null $salt
     * @return string The name (hash) of the login cookie
     * @throws \NETopes\Core\AppException
     */
    public static function GetCookieHash(?string $namespace=NULL,?string $salt=NULL) {
        $namespace=$namespace ? $namespace : NApp::$currentNamespace;
        $salt=strlen($salt) ? $salt : 'loggedIn';
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
     */
    public static function GetHashFromCookie(?string $name,?string $namespace=NULL,bool $setIfMissing=TRUE,?int $validity=NULL): ?string {
        $cHash=static::GetCookieHash($namespace,$name);
        $cCookieHash=NULL;
        if(array_key_exists($cHash,$_COOKIE) && strlen($_COOKIE[$cHash])) {
            $cCookieHash=GibberishAES::dec($_COOKIE[$cHash],AppConfig::GetValue('app_encryption_key'));
        } elseif($setIfMissing) {
            $cCookieHash=AppSession::GetNewUID();
            $validity=(is_numeric($validity) && $validity>0 ? $validity : 180) * 24 * 3600;
            $_COOKIE[$cHash]=GibberishAES::enc($cCookieHash,AppConfig::GetValue('app_encryption_key'));
            setcookie($cHash,$_COOKIE[$cHash],time() + $validity,'/',NApp::Url()->GetAppDomain());
        }//if(array_key_exists($sc_hash,$_COOKIE) && strlen($_COOKIE[$sc_hash]))
        return $cCookieHash;
    }//END public static function GetHashFromCookie

    /**
     * Set the login cookie
     *
     * @param  string  $uHash      The user hash
     * @param  integer $validity   The cookie lifetime or NULL for default
     * @param  string  $cookieHash The name (hash) of the login cookie
     * @param  string  $namespace  The namespace for the cookie or NULL for current namespace
     * @return bool True on success or false
     * @throws \NETopes\Core\AppException
     */
    public static function SetLoginCookie(string $uHash,?int $validity=NULL,?string $cookieHash=NULL,?string $namespace=NULL): bool {
        if(!is_string($uHash)) {
            return FALSE;
        }
        $validity=is_numeric($validity) && $validity>0 ? $validity : AppConfig::GetValue('cookie_login_lifetime') * 24 * 3600;
        $cookieHash=$cookieHash ? $cookieHash : static::GetCookieHash($namespace);
        if(!$uHash) {
            unset($_COOKIE[$cookieHash]);
            setcookie($cookieHash,'',time() + $validity,'/',NApp::Url()->GetAppDomain());
            return TRUE;
        }//if(!$uHash)
        $_COOKIE[$cookieHash]=GibberishAES::enc($uHash,AppConfig::GetValue('app_encryption_key'));
        setcookie($cookieHash,$_COOKIE[$cookieHash],time() + $validity,'/',NApp::Url()->GetAppDomain());
        return TRUE;
    }//END public static function SetLoginCookie

    /**
     * Get user ID key
     *
     * @return string Returns the key of the user ID
     */
    public static function GetUserIdKey(): string {
        $adapter=static::$adapterClass;
        /** @var \NETopes\Core\App\UserSessionAdapter $adapter */
        return $adapter::GetUserIdKey();
    }//END public static function GetUserIdKey

    /**
     * Get current user ID
     *
     * @return int|null Returns current user ID
     * @throws \NETopes\Core\AppException
     */
    public static function GetCurrentUserId(): ?int {
        $adapter=static::$adapterClass;
        /** @var \NETopes\Core\App\UserSessionAdapter $adapter */
        return $adapter::GetCurrentUserId();
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
        $adapter=static::$adapterClass;
        /** @var \NETopes\Core\App\UserSessionAdapter $adapter */
        $adapter::LoadAppSettings($notFromDb,$params,$appAccessKey);
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
        $adapter=static::$adapterClass;
        /** @var \NETopes\Core\App\UserSessionAdapter $adapter */
        return $adapter::Login($username,$password,$remember,$extraParams);
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
        $adapter=static::$adapterClass;
        /** @var \NETopes\Core\App\UserSessionAdapter $adapter */
        $adapter::Logout($namespace);
    }//END public static function Logout
}//END class UserSession