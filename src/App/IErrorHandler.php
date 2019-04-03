<?php
/**
 * Errors handler interface file
 * Must be implemented by the ErrorHandler class that will be registered as application error handler
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
/**
 * Errors handler interface
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
     * @param int         $errorNo      Error code
     * @param string      $errorMessage Error location (file)
     * @param string|null $errorFile
     * @param int|null    $errorLine    Error location (line)
     * @param array       $errcontext   Error context
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function ErrorHandlerFunction(int $errorNo=-1,string $errorMessage='Unknown error',?string $errorFile=NULL,?int $errorLine=NULL,array $errcontext=[]);

    /**
     * Method called through set_exception_handler() on exception thrown
     *
     * @param object $exception The thrown exception
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function ExceptionHandlerFunction($exception);

    /**
     * Method called through register_shutdown_function() on shutdown
     *
     * @param bool $output Flag to allow or restrict output
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function ShutDownHandlerFunction(bool $output=TRUE);
}//END interface IErrorHandler