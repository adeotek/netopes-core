<?php
/**
 * NETopes AppException class file
 *
 * Definition of the custom exception class
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core;

/**
 * Class AppException
 *
 * Extends ErrorException and must be the only exception class
 * used in the application
 *
 * @package  NETopes\Core\App
 * @access   public
 * @final
 */
final class AppException extends \ErrorException {
	/**
	 * @param  string Exception type (default: app)
	 * posible values: app, firebird, mysql, pdo, mssql
	 * @access protected
	 */
	protected $type = NULL;
	/**
	 * @param  string Stores the original exception message
	 * @access protected
	 */
	protected $originalMessage = NULL;
	/**
	 * @param  mixed External error code
	 * (used generally for database exceptions)
	 * @access protected
	 */
	protected $externalCode = NULL;
	/**
	 * @param  array More exception information
	 * (inherited from PDOException)
	 * @access public
	 */
	public $errorInfo = [];
    /**
     * Get AppException instance from another exception instance
     *
     * @param \Exception $e
     * @return \NETopes\Core\AppException
     */
    public static function GetInstance($e): AppException {
	    if(!is_object($e) || !($e instanceof \Exception)) { return new AppException('Unknown exception!'); }
	    return new AppException($e->getMessage(),$e->getCode(),1,$e->getFile(),$e->getLine());
	}//END public static function GetInstance
    /**
     * Class constructor method
     *
     * @param  string $message Exception message
     * @param  int    $code Exception message
     * @param  int    $severity Exception severity
     * <= 0 - stops the execution
     * > 0 continues execution
     * @param  string $file Exception location (file)
     * @param  int    $line Exception location (line)
     * @param  string $type Exception type
     * @param  mixed  $externalCode Extra error code
     * @param  array  $errorInfo PDO specific error information
     * @param  \Exception|null $previous
     * @access public
     */
	public function __construct($message,$code = -1,$severity = 1,$file = NULL,$line = NULL,$type = 'app',$externalCode = NULL,$errorInfo = [],$previous = NULL) {
		$this->type = strtolower($type);
		$this->externalCode = $externalCode;
		$this->errorInfo = $errorInfo;
		$this->originalMessage = $message;
		switch($this->type) {
			case 'firebird':
				$this->externalCode = is_numeric($this->externalCode) ?  $this->externalCode*(-1) : $this->externalCode;
				break;
		  	case 'mysql':
		  	case 'mongodb':
			case 'sqlite':
			case 'sqlsrv':
				break;
			case 'pdo':
				switch($this->externalCode) {
					case 'HY000':
						if(is_array($this->errorInfo) && count($this->errorInfo)>2) {
							$this->externalCode = is_numeric($this->errorInfo[1]) ?  $this->errorInfo[1]*(-1) : $this->errorInfo[1];
							$message = 'SQL ERROR: '.$this->errorInfo[2];
						}//if(is_array($this->errorInfo) && count($this->errorInfo)>2)
						break;
					default:
						break;
				}//END switch
				break;
			default:
				$this->type = 'app';
				break;
		}//END switch
		parent::__construct($message,$code,$severity,$file,$line,$previous);
	}//END public function __construct
	/**
	 * Gets the external error code
	 *
	 * @return int Returns external error code
	 * @access public
	 * @final
	 */
	final public function getExtCode() {
		return $this->externalCode;
	}//END public function getExtCode
	/**
	 * Gets the original exception message
	 *
	 * @return string Returns original exception message
	 * @access public
	 * @final
	 */
	final public function getOriginalMessage() {
		return $this->originalMessage;
	}//END public function getOriginalMessage
	/**
	 * Sets the original exception message
	 *
	 * @param  string $message New message to be stored in the
	 * original message property
	 * @return void
	 * @access public
	 * @final
	 */
	final public function setOriginalMessage($message) {
		$this->originalMessage = $message;
	}//END public function setOriginalMessage
	/**
	 * Gets the full exception message
	 *
	 * @return string Returns full exception message
	 * @access public
	 * @final
	 */
	final public function getFullMessage() {
		$result = $this->message;
		if($this->file) { $result .= ' in file ['.$this->file.']'; }
		if($this->line) { $result .= ' at line ['.$this->line.']'; }
		return $result;
	}//END public function getFullMessage
}//END class AppException extends ErrorException