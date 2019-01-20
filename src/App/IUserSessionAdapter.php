<?php
/**
 * NETopes application user session adapter interface file
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;

/**
 * Interface IUserSessionAdapter
 *
 * @package NETopes\Core\App
 */
interface IUserSessionAdapter {
    /**
     * Loads application settings from database or from request parameters
     *
     * @param bool        $notFromDb
     * @param array|null  $params
     * @param string|null $appAccessKey
     * @return void
     * @throws \NETopes\Core\AppException
     */
	public static function LoadAppSettings(bool $notFromDb = FALSE,?array $params = NULL,?string &$appAccessKey = NULL): void;
    /**
     * This function checks the authenticity
     * of the login information in the database
     * and creates the session effectively logging in the user.
     *
     * @param string      $username
     * @param string      $password
     * @param int         $remember
     * @param string|null $loginNamespace
     * @param bool        $allowNullCompany
     * @return bool|null Returns TRUE if login is successful or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
	public static function Login(string $username,string $password,int $remember = 0,?string $loginNamespace = NULL,bool $allowNullCompany = FALSE): ?bool;
	/**
	 * Method called on user logout action for clearing the session
	 * and the login cookie
	 *
	 * @param  string $namespace If passed, logs out the specified namespace
	 * else logs out the current namespace
	 * @return void
	 * @throws \NETopes\Core\AppException
	 */
	public static function Logout(?string $namespace = NULL): void;
}//END interface IUserSessionAdapter