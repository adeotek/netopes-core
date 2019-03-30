<?php
/**
 * NETopes AppException class file
 * Definition of the custom exception class
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core;
use Error;
use Exception;
use Throwable;

/**
 * Class AppException
 * Extends Exception and must be the only exception class
 * used in the application
 *
 * @package  NETopes\Core
 */
final class AppException extends Exception {
    /**
     * @var int Exception severity (1 non-blocking, <=0 blocking)
     */
    protected $severity = 1;
    /**
     * @var  string Exception type (default: app)
     * posible values: app, firebird, mysql, pdo, mssql
     */
    protected $type = NULL;
    /**
     * @var  string Stores the original exception message
     */
    protected $originalMessage = NULL;
    /**
     * @var  mixed External error code
     * (used generally for database exceptions)
     */
    protected $externalCode = NULL;
    /**
     * @var  array More exception information
     * (inherited from PDOException)
     */
    public $errorInfo = [];

    /**
     * Get AppException instance from another exception instance
     * @param \Throwable  $e
     * @param string|null $type
     * @param int         $severity
     * @return \NETopes\Core\AppException
     */
    public static function GetInstance(Throwable $e,?string $type=NULL,int $severity=1): AppException {
        if(!($e instanceof Throwable)) {
            return new AppException('Unknown exception!');
        }
        $type=isset($type) ? $type : ($e instanceof Error ? 'php' : 'other');
        return new AppException($e->getMessage(),$e->getCode(),$severity,$e->getFile(),$e->getLine(),$type,NULL,[],$e->getPrevious());
    }//END public static function GetInstance

    /**
     * Class constructor method
     * @param  string          $message      Exception message
     * @param  int             $code         Exception message
     * @param  int             $severity     Exception severity
     * <= 0 - stops the execution
     * > 0 continues execution
     * @param  string|null     $file         Exception location (file)
     * @param  int|null        $line         Exception location (line)
     * @param  string          $type         Exception type
     * @param  mixed           $externalCode Extra error code
     * @param  array           $errorInfo    PDO specific error information
     * @param  \Throwable|null $previous
     */
    public function __construct(string $message,int $code=-1,int $severity=1,?string $file=NULL,?int $line=NULL,string $type='app',$externalCode=NULL,array $errorInfo=[],?Throwable $previous=NULL) {
        $this->severity = $severity;
        $this->type = strtolower($type);
        $this->externalCode = $externalCode;
        $this->errorInfo = $errorInfo;
        $this->originalMessage = $message;
        $this->file = $file;
        $this->line = $line;
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
        parent::__construct($message,$code,$previous);
    }//END public function __construct

    /**
     * Gets the exception severity
     * @return int The severity level of the exception.
     */
    final public function getSeverity(): int {
        return $this->severity;
    }//END final public function getSeverity

    /**
     * Gets the external error code
     * @return int Returns external error code
     */
    final public function getExtCode() {
        return $this->externalCode;
    }//END public function getExtCode

    /**
     * Gets the original exception message
     * @return string Returns original exception message
     */
    final public function getOriginalMessage() {
        return $this->originalMessage;
    }//END public function getOriginalMessage

    /**
     * Sets the original exception message
     * @param  string $message New message to be stored in the
     * original message property
     * @return void
     */
    final public function setOriginalMessage($message) {
        $this->originalMessage = $message;
    }//END public function setOriginalMessage

    /**
     * Gets the full exception message
     * @return string Returns full exception message
     */
    final public function getFullMessage() {
        $result = $this->message;
        if($this->file) { $result .= ' in file ['.$this->file.']'; }
        if($this->line) { $result .= ' at line ['.$this->line.']'; }
        return $result;
    }//END public function getFullMessage
}//END class AppException extends \Exception