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
	 * Get current user ID
	 *
	 * @return int|null Returns current user ID
	 * @access public
	 */
	public static function GetCurrentUserId(): ?int;
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
     * This function authenticates an user and updates the session data
     *
     * @param string                              $username
     * @param string                              $password
     * @param bool                                $remember
     * @param \NETopes\Core\App\Params|array|null $extraParams
     * @return bool|null Returns TRUE if login is successful or FALSE otherwise
     * @throws \NETopes\Core\AppException
     * @access public
     */
	public static function Login(string $username,string $password,bool $remember = FALSE,$extraParams = NULL): ?bool;
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