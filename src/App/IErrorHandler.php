<?php
/**
 * Errors handler interface file
 *
 * Must be implemented by the ErrorHandler class that will be registered as application error handler
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.2.0.1
 * @filesource
 */
namespace NETopes\Core\App;
/**
 * Errors handler interface file
 *
 * Must be implemented by the ErrorHandler class that will be registered as application error handler
 *
 * @package    NETopes\Core\App
 */
interface IErrorHandler {
	/**
	 * Sets error log file name
	 *
	 * @param string $errorLogFile
	 * @return void
	 */
	public static function SetErrorLogFile(string $errorLogFile);
	/**
	 * Sets error log file path
	 *
	 * @param string $errorLogPath
	 * @return void
	 */
	public static function SetErrorLogPath(string $errorLogPath);
	/**
	 * Method called through set_error_handler() on error
	 *
	 * @param  int         $errno Error code
	 * @param  string      $errstr Error location (file)
	 * @param  string|null $errfile
	 * @param  int|null    $errline Error location (line)
	 * @param  array       $errcontext Error context
	 * @return void
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function ErrorHandlerFunction(int $errno = -1,string $errstr = 'Unknown error',?string $errfile = NULL,?int $errline = NULL,array $errcontext = []);
	/**
	 * Method called through set_exception_handler() on exception thrown
	 *
	 * @param  object $exception The thrown exception
	 * @return void
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function ExceptionHandlerFunction($exception);
	/**
	 * Method called through register_shutdown_function() on shutdown
	 *
	 * @param  bool $output Flag to allow or restrict output
	 * @return void
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function ShutDownHandlerFunction(bool $output = TRUE);
}//END interface IErrorHandler
?>