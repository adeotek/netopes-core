<?php
/**
 * NETopes FileLoggerAdapter class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.4.1.0
 * @filesource
 */
namespace NETopes\Core\Logging;
use Doctrine\DBAL\Driver\PDOException;
use Error;
use Exception;
use NETopes\Core\AppException;
use NETopes\Core\Data\Doctrine\BaseEntity;

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
     * @var string|null Relative path to the logs folder
     */
    protected $logsPath;
    /**
     * @var string|null Name of the main log file
     */
    protected $logFile;
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
        $this->logFile=get_array_value($params,'log_file',NULL,'is_string');
        $this->logsPath=get_array_value($params,'logs_path',NULL,'is_string');
        $this->includeExceptionsTrace=get_array_value($params,'include_exceptions_trace',$this->includeExceptionsTrace,'is_bool');
        $this->includeExtraLabels=get_array_value($params,'include_extra_labels',$this->includeExtraLabels,'is_bool');
        $this->globalExtraLabels=get_array_value($params,'global_extra_labels',$this->globalExtraLabels,'is_bool');
        $this->buffered=get_array_value($params,'buffered',$this->buffered,'is_bool');
        if($this->buffered) {
            $this->logEventsBuffer=new LogEventsCollection();
        }
    }//END public function __construct

    /**
     * Get adapter type
     *
     * @return string
     */
    public function GetType(): string {
        return Logger::FILE_ADAPTER;
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
        return FALSE;
    }//END public function GetRequiresOutputBuffering

    /**
     * @param string|null $rootPath
     * @return string
     */
    public function GetLogFile(?string $rootPath=NULL): string {
        if(is_absolute_path($this->logsPath)) {
            return rtrim($this->logsPath,'/').'/'.$this->logFile;
        }
        return rtrim((strlen($rootPath) ? $rootPath : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH),'/').(strlen($this->logsPath) ? trim($this->logsPath,'/') : '').'/'.$this->logFile;
    }//END public function GetLogFile

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
                $this->logEventsBuffer->add($entry);
                return;
            }//if($this->buffered)
            $data=self::FileLogEntryFormatter($entry,$this->includeExceptionsTrace,$this->includeExtraLabels,$this->globalExtraLabels);
        } catch(Exception $e) {
            $data=self::FileLogFormatter($e,LogEvent::LEVEL_ERROR,__FILE__,__LINE__);
        }//END try
        try {
            self::WriteToFile($data,$this->GetLogFile());
        } catch(AppException $e) {
            unset($e);
        }//END try
    }//END public function AddEvent

    /**
     * Flush log events buffer
     */
    public function FlushEvents(): void {
        if(!$this->logEventsBuffer instanceof LogEventsCollection || !$this->logEventsBuffer->count()) {
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
            $data=self::FileLogFormatter($e,LogEvent::LEVEL_ERROR,__FILE__,__LINE__);
        }//END try
        try {
            self::WriteToFile($data,$this->GetLogFile());
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
        $data='#'.$entry->getTimestamp()->format('Y-m-d H:i:s.u').'# ['.strtoupper($entry->getLevelAsString()).(strlen($entry->mainLabel) ? '|'.$entry->mainLabel : '').'] ';
        $data.='<'.$entry->sourceFile.($entry->sourceLine ? ':'.$entry->sourceLine : '').'> ';
        if($entry->isException()) {
            $data.=$entry->message->getMessage();
            if($includeExceptionsTrace && count($entry->backtrace)) {
                $data.=PHP_EOL.'<<<STACK TRACE:';
                $data.=PHP_EOL.static::BacktraceToString($entry->backtrace);
                $data.=PHP_EOL.'STACK TRACE>>> ';
            }//if($includeExceptionsTrace && count($entry->backtrace))
        } else {
            $data.=is_null($entry->message) || is_scalar($entry->message) ? $entry->message ?? 'NULL' : PHP_EOL.print_r($entry->message,1);
        }//if($entry->isException())
        if($includeExtraLabels) {
            $labels=array_merge($globalExtraLabels,$entry->extraLabels);
            if(count($labels)) {
                $data.=PHP_EOL.'<<<LABELS:';
                $data.=PHP_EOL.json_encode($labels);
                $data.=PHP_EOL.'LABELS>>>';
            }
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
     * @param string      $file       Log file (containing full path if $path is not provided)
     * @param string|null $path       Log file absolute path (not required if $file contains full path)
     * @param string|null $scriptName Source script file (optional)
     * @param int|null    $scriptLine Source script line (optional)
     * @param int         $level      Log level (optional)
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function LogToFile($message,string $file,?string $path=NULL,?string $scriptName=NULL,?int $scriptLine=NULL,int $level=LogEvent::LEVEL_INFO) {
        self::WriteToFile(self::FileLogFormatter($message,$level ?? LogEvent::LEVEL_INFO,$scriptName,$scriptLine),$file,$path);
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

    /**
     * @param array|null $input
     * @param int        $depth
     * @param int        $level
     * @return string|null
     */
    public static function BacktraceToString(?array $input,int $depth=128,int $level=0): ?string {
        if(!is_array($input)) {
            return NULL;
        }
        $result='Array('.PHP_EOL;
        foreach($input as $k=>$v) {
            $result.=str_repeat('    ',$level + 1).$k.'=>';
            if(is_array($v)) {
                if($level>=$depth) {
                    $result.='[Max depth reached!]'.PHP_EOL;
                } else {
                    $result.=static::BacktraceToString($v,$depth,++$level).PHP_EOL;
                }//if($level>$depth)
            } elseif(is_scalar($v)) {
                $result.=$v.PHP_EOL;
            } elseif($v instanceof Exception) {
                $result.='EXCEPTION: ['.$v->getCode().'] '.$v->getMessage().' in '.$v->getFile().':'.$v->getLine().PHP_EOL;
            } elseif($v instanceof PDOException || $v instanceof \Doctrine\DBAL\Driver\Exception) {
                $result.='\Doctrine\DBAL\Driver\Exception: ['.$v->getCode().'] '.$v->getMessage().' in '.$v->getFile().':'.$v->getLine().PHP_EOL;
            } elseif($v instanceof BaseEntity) {
                $result.='[Instance of \NETopes\Core\Data\Doctrine\BaseEntity]'.PHP_EOL;
            } elseif($v instanceof Error) {
                $result.='ERROR: ['.$v->getCode().'] '.$v->getMessage().' in '.$v->getFile().':'.$v->getLine().PHP_EOL;
            } else {
                $result.=print_r($v,1).PHP_EOL;
            }//if(is_array($v))
        }//END foreach
        $result.=')';
        return $result;
    }//END public static function BacktraceToString
}//END class PhpConsoleAdapter implements ILoggerAdapter