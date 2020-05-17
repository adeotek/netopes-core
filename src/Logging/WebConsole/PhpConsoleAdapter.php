<?php
/**
 * NETopes PhpConsoleAdapter class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging\WebConsole;
use Exception;
use NETopes\Core\AppException;
use NETopes\Core\Logging\FileLoggerAdapter;
use NETopes\Core\Logging\ILoggerAdapter;
use NETopes\Core\Logging\LogEvent;
use NETopes\Core\Logging\Logger;
use PhpConsole\Connector;
use PhpConsole\Storage\File;

/**
 * Class PhpConsoleAdapter
 *
 * @package  NETopes\Core\Logger
 */
class PhpConsoleAdapter implements ILoggerAdapter {
    /**
     * @var string Global labels array
     */
    protected $defaultLabel='';
    /**
     * @var bool Show source script file
     */
    protected $showSourceFile=TRUE;
    /**
     * @var bool Show exceptions stack trace
     */
    protected $showExceptionsTrace=TRUE;
    /**
     * @var object PhpConsole instance
     */
    protected $loggerObject=NULL;

    /**
     * PhpConsoleAdapter constructor.
     *
     * @param array $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $params) {
        $browserType=get_array_value($params,'browser',NULL,'?is_string');
        if(!in_array($browserType,['Chrome','EdgeChrome'])) {
            throw new AppException("Unsupported browser {$browserType}!",-1,0);
        }
        if(!class_exists('\PhpConsole\Connector')) {
            throw new AppException('PhpConsole\Connector class not found!');
        }
        $cachePath=get_array_value($params,'cache_path',NULL,'?is_string');
        if(strlen($cachePath)) {
            $tmpPath=(substr($cachePath,0,2)==='..' ? _NAPP_ROOT_PATH.'/' : '').$cachePath;
        } else {
            $tmpPath=get_array_value($params,'tmp_path',NULL,'?is_string');
        }
        try {
            Connector::setPostponeStorage(new File((strlen($tmpPath) ? rtrim($tmpPath,'/') : '').'/phpcons.data'));
            $this->loggerObject=Connector::getInstance();
            if(Connector::getInstance()->isActiveClient()) {
                $this->loggerObject->setServerEncoding('UTF-8');
                $password=get_array_value($params,'password',NULL,'?is_string');
                if(strlen($password)) {
                    $this->loggerObject->setPassword($password);
                }
                $this->defaultLabel=get_array_value($params,'default_label',$this->defaultLabel,'is_string');
                $this->showSourceFile=get_array_value($params,'show_source_file',$this->showSourceFile,'is_string');
                $this->showExceptionsTrace=get_array_value($params,'show_exceptions_trace',$this->showExceptionsTrace,'is_string');
            } else {
                $this->loggerObject=NULL;
            }//if(Connector::getInstance()->isActiveClient())
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public function __construct

    /**
     * Get adapter type
     *
     * @return string
     */
    public function GetType(): string {
        return Logger::WEB_CONSOLE_ADAPTER;
    }//END public function GetType

    /**
     * Get javascript dependencies list
     *
     * @return array
     */
    public function GetScripts(): array {
        return [];
    }//END public function GetScripts

    /**
     * Get output buffering requirement
     *
     * @return bool
     */
    public function GetRequiresOutputBuffering(): bool {
        return TRUE;
    }//END public function GetRequiresOutputBuffering

    /**
     * Add new log event
     *
     * @param \NETopes\Core\Logging\LogEvent $entry
     */
    public function AddEvent(LogEvent $entry): void {
        if(!is_object($this->loggerObject)) {
            return;
        }
        try {
            $label='['.strtoupper($entry->getLevelAsString());
            if($this->showSourceFile && strlen($entry->sourceFile)) {
                $label.='|'.basename($entry->sourceFile).($entry->sourceLine ? ':'.$entry->sourceLine : '').'] ';
            } else {
                $label.='] ';
            }
            $label.=$entry->mainLabel ?? $this->defaultLabel;
            if($entry->isException()) {
                $label.=' '.get_class($entry->message);
                if($this->showExceptionsTrace) {
                    $this->loggerObject->getDebugDispatcher()->dispatchDebug($entry->message->getMessage(),$label.':Message>>');
                    $this->loggerObject->getDebugDispatcher()->dispatchDebug($entry->backtrace,$label.':Trace>>');
                } else {
                    $this->loggerObject->getDebugDispatcher()->dispatchDebug($entry->message->getMessage(),$label);
                }
            } else {
                $this->loggerObject->getDebugDispatcher()->dispatchDebug($entry->message,$label);
            }
        } catch(Exception $e) {
            try {
                FileLoggerAdapter::LogToFile($e,NULL,NULL,__FILE__,__LINE__,LogEvent::LEVEL_ERROR);
            } catch(AppException $e) {
                unset($e);
            }//END try
        }
    }//END public function AddEvent

    /**
     * Flush log events buffer
     */
    public function FlushEvents(): void {
        // not used for PHPConsole
    }//END public function FlushEvents
}//END class PhpConsoleAdapter implements ILoggerAdapter