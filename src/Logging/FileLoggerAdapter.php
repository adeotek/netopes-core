<?php
/**
 * NETopes FileLoggerAdapter class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging;
use Exception;
use NETopes\Core\App\Params;
use NETopes\Core\AppException;

/**
 * Class FileLoggerAdapter
 *
 * @package  NETopes\Core\Logger
 */
class FileLoggerAdapter implements ILoggerAdapter {
    /**
     * @var int Minimal log level
     */
    protected $minLogLevel=LogEvent::LEVEL_INFO;
    /**
     * @var string Relative path to the logs folder
     */
    protected $logsPath='.logs';
    /**
     * @var string Name of the main log file
     */
    protected $logFile='application.log';
    /**
     * @var bool Include exceptions stack trace into the log message
     */
    protected $includeExceptionsTrace=TRUE;
    /**
     * @var bool Include extra labels into the log message
     */
    protected $includeExtraLabels=TRUE;
    /**
     * @var array Global extra labels array
     */
    protected $globalExtraLabels=[];
    /**
     * @var bool Buffered run (write buffer to file only when flush method is called)
     */
    protected $buffered=FALSE;
    /**
     * @var \NETopes\Core\Logging\LogEventsCollection|null Log events buffer collection
     */
    protected $logEventsBuffer=NULL;

    /**
     * FileLoggerAdapter constructor.
     *
     * @param array $params
     */
    public function __construct(array $params) {
        $this->minLogLevel=get_array_value($params,'min_log_level',$this->minLogLevel,'is_integer');
        $this->logFile=get_array_value($params,'log_file',$this->logFile,'is_notempty_string');
        $this->logsPath=get_array_value($params,'logs_path',$this->logsPath,'is_notempty_string');
        $this->includeExceptionsTrace=get_array_value($params,'include_exceptions_trace',$this->includeExceptionsTrace,'is_bool');
        $this->includeExtraLabels=get_array_value($params,'include_extra_labels',$this->includeExtraLabels,'is_bool');
        $this->globalExtraLabels=get_array_value($params,'global_extra_labels',$this->globalExtraLabels,'is_bool');
        $this->buffered=get_array_value($params,'buffered',$this->buffered,'is_bool');
    }//END public function __construct

    /**
     * Get javascript dependencies list
     *
     * @return array
     */
    public function GetScripts(): array {
        return [];
    }//END public function GetScripts

    /**
     * Add new log event (to buffer if buffered=TRUE or directly to log otherwise)
     *
     * @param \NETopes\Core\Logging\LogEvent $entry
     */
    public function AddEvent(LogEvent $entry): void {
        try {
            if($entry->level<$this->minLogLevel) {
                return;
            }
            if($this->buffered) {
                if(!$this->logEventsBuffer instanceof LogEventsCollection) {
                    $this->logEventsBuffer=new LogEventsCollection();
                }
                $this->logEventsBuffer->add($entry);
                return;
            }//if($this->buffered)
            $data=self::FileLogEntryFormatter($entry,$this->includeExceptionsTrace,$this->includeExtraLabels,$this->globalExtraLabels);
        } catch(Exception $e) {
            $data=self::FileLogFormatter($e,LogEvent::LEVEL_ERROR,__FILE__);
        }//END try
        try {
            self::WriteToFile($data,$this->logFile,$this->logsPath);
        } catch(AppException $e) {
            unset($e);
        }//END try
    }//END public function AddEvent

    /**
     * Flush log events buffer
     */
    public function FlushEvents(): void {
        if($this->buffered && !$this->logEventsBuffer instanceof LogEventsCollection || !$this->logEventsBuffer->count()) {
            return;
        }
        try {
            $data='';
            foreach($this->logEventsBuffer as $entry) {
                $data.=self::FileLogEntryFormatter($entry,$this->includeExceptionsTrace,$this->includeExtraLabels,$this->globalExtraLabels);
            }//END foreach
            $clear=TRUE;
        } catch(AppException $e) {
            $clear=FALSE;
            $data=self::FileLogFormatter($e,LogEvent::LEVEL_ERROR,__FILE__);
        }//END try
        try {
            self::WriteToFile($data,$this->logFile,$this->logsPath);
            if($clear) {
                $this->logEventsBuffer->clear();
            }
        } catch(AppException $e) {
            unset($e);
        }//END try
    }//END public function FlushEvents

    /**
     * @param \NETopes\Core\Logging\LogEvent $entry
     * @param bool                           $includeExceptionsTrace
     * @param bool                           $includeExtraLabels
     * @param array                          $globalExtraLabels
     * @return string
     */
    public static function FileLogEntryFormatter(LogEvent $entry,bool $includeExceptionsTrace=TRUE,bool $includeExtraLabels=TRUE,array $globalExtraLabels=[]): string {
        $data='#'.date('Y-m-d H:i:s.u').'# ['.strtoupper($entry->getLevelAsString()).(strlen($entry->mainLabel) ? '|'.$entry->mainLabel : '').'] ';
        $data.='<'.$entry->sourceFile.($entry->sourceLine ? ':'.$entry->sourceLine : '').'> ';
        if($entry->isException()) {
            $data.=$entry->message->getMessage();
            if($includeExceptionsTrace) {
                $data.=PHP_EOL.'<<<STACK TRACE:';
                $data.=PHP_EOL.print_r($entry->backtrace,1);
                $data.=PHP_EOL.'STACK TRACE>>> ';
            }//if($includeExceptionsTrace)
        } else {
            $data.=is_null($entry->message) || is_scalar($entry->message) ? $entry->message ?? 'NULL' : PHP_EOL.print_r($entry->message,1);
        }//if($entry->isException())
        if($includeExtraLabels) {
            $data.=PHP_EOL.'<<<LABELS:';
            $data.=PHP_EOL.json_encode(array_merge($globalExtraLabels,$entry->extraLabels));
            $data.=PHP_EOL.'LABELS>>>';
        }//if($includeExtraLabels)
        $data.=PHP_EOL;
        return $data;
    }//END public static function FileLogEntryFormatter

    /**
     * @param             $message
     * @param int         $level
     * @param string|null $scriptName
     * @param int|null    $scriptLine
     * @return string
     */
    public static function FileLogFormatter($message,int $level,?string $scriptName=NULL,?int $scriptLine=NULL): string {
        if(is_array($message) && count($message)) {
            $entry=LogEvent::GetNewInstance()
                ->setLevel(is_integer($message['level']) && $message['level']>0 && $message['level']<5 ? $message['level'] : $level)
                ->setSourceFile(isset($message['file']) ? $message['file'] : $scriptName)
                ->setSourceLine(isset($message['line']) ? $message['line'] : $scriptLine)
                ->setExtraLabels(isset($message['labels']) && is_array($message['labels']) ? $message['labels'] : [])
                ->setMessage(isset($message['message']) ? $message['message'] : NULL);
        } else {
            $entry=LogEvent::GetNewInstance()
                ->setLevel($level)
                ->setSourceFile($scriptName)
                ->setSourceLine($scriptLine)
                ->setMessage($message);
        }//if(is_array($message) && count($message))
        return self::FileLogEntryFormatter($entry);
    }//END public static function FileLogFormatter

    /**
     * Add entry to log file
     *
     * @param mixed       $message    Data to be written to log
     * @param string      $file       Log file
     * @param string|null $path       Log file path (optional)
     * @param string|null $scriptName Source script file (optional)
     * @param int|null    $scriptLine Source script line (optional)
     * @param int         $level      Log level (optional)
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function LogToFile($message,string $file,?string $path=NULL,?string $scriptName=NULL,?int $scriptLine=NULL,int $level=LogEvent::LEVEL_INFO) {
        self::WriteToFile(self::FileLogFormatter($message,$level,$scriptName),$file,$path);
    }//END public static function LogToFile

    /**
     * Write string data to file
     *
     * @param string      $message Data to be written to file
     * @param string      $file    File name
     * @param string|null $path    File path (optional)
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function WriteToFile(string $message,string $file,?string $path=NULL) {
        $logFile=(strlen($path) ? rtrim($path,'/').'/' : '').$file;
        $fileHandler=fopen($logFile,'a');
        if(!$fileHandler) {
            throw new AppException("Unable to open log file [{$file}]!",E_WARNING,1);
        }
        fwrite($fileHandler,$message);
        fclose($fileHandler);
    }//END public static function WriteToFile
}//END class PhpConsoleAdapter implements ILoggerAdapter