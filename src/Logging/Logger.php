<?php
/**
 * NETopes application logger class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;

/**
 * Class Logger
 *
 * @package  NETopes\Core\Logger
 */
class Logger {
    /**
     * @var array Array containing started debug timers.
     */
    protected static $debugTimers=[];
    /**
     * @var array Array containing logging adapters objects.
     */
    protected $loggingObjects=[];
    /**
     * @var array Array containing logging adapters JavaScripts.
     */
    protected $loggingScripts=[];
    /**
     * @var bool Auto add caller to LogEntry
     */
    protected $autoAddCaller=TRUE;
    /**
     * @var bool Logger enabled state
     */
    protected $enabled=FALSE;
    /**
     * @var bool Logger adapters require output buffering
     */
    protected $requiresOutputBuffering=FALSE;

    /**
     * Logger class constructor
     *
     * @param array       $adapters List of enabled ILoggerAdapter adapters
     * @param string|null $logsPath Logs directory relative path
     * @param string|null $logFile  Default log file
     * @param string|null $tmpPath  Temp directory absolute path (must be outside document root)
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $adapters,?string $logsPath=NULL,?string $logFile=NULL,?string $tmpPath=NULL) {
        $this->autoAddCaller=AppConfig::GetValue('logger_auto_add_caller');
        $this->ConfigureAdapters($adapters,$logsPath,$logFile,$tmpPath);
    }//END protected function __construct

    /**
     * Configure adapters instances
     *
     * @param array       $adapters List of enabled ILoggerAdapter adapters
     * @param string|null $logsPath Logs directory relative path
     * @param string|null $logFile  Default log file
     * @param string|null $tmpPath  Temp directory absolute path (must be outside document root)
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function ConfigureAdapters(array $adapters,?string $logsPath=NULL,?string $logFile=NULL,?string $tmpPath=NULL) {
        $this->enabled=FALSE;
        $this->loggingObjects=[];
        $this->loggingScripts=[];
        if(!count($adapters)) {
            return;
        }
        $browser=self::GetBrowserType();
        foreach($adapters as $adapter) {
            if(!isset($adapter['class']) || !strlen($adapter['class']) || !class_exists($adapter['class'])) {
                continue;
            }
            try {
                $adapterClass=$adapter['class'];
                $adapterConfig=isset($adapter['config']) && is_array($adapter['config']) ? $adapter['config'] : [];
                /** @var \NETopes\Core\Logging\ILoggerAdapter $adapterInstance */
                $adapterInstance=new $adapterClass(array_merge([
                    'browser'=>$browser,
                    'logs_path'=>$logsPath,
                    'log_file'=>$logFile,
                    'tmp_path'=>$tmpPath,
                ],$adapterConfig));
                $this->loggingScripts=array_merge($this->loggingScripts,$adapterInstance->GetScripts());
                $this->requiresOutputBuffering=$this->requiresOutputBuffering || $adapterInstance->GetRequiresOutputBuffering();
                $this->loggingObjects[]=clone $adapterInstance;
                unset($adapterInstance);
            } catch(AppException $e) {
                if($e->getSeverity()!==0) {
                    throw $e;
                }
            }//END try
        }//END foreach
        $this->enabled=(is_array($this->loggingObjects) && count($this->loggingObjects));
    }//END public function ConfigureAdapters

    /**
     * Get debugging plug-ins JavaScripts to be loaded
     *
     * @return array Returns an array of debugging plug-ins JavaScripts to be loaded
     */
    public function GetScripts() {
        return ($this->enabled ? $this->loggingScripts : []);
    }//END public function GetScripts

    /**
     * Get output buffering requirement
     *
     * @return bool
     */
    public function RequiresOutputBuffering(): bool {
        return $this->requiresOutputBuffering;
    }//END public function RequiresOutputBuffering

    /**
     * Get enabled state
     *
     * @return bool Returns TRUE if enabled or FALSE otherwise
     */
    public function IsEnabled() {
        return $this->enabled;
    }//END public function IsEnabled

    /**
     * @param mixed       $message
     * @param int         $level
     * @param string|null $label
     * @param array       $extraLabels
     * @param array|null  $debugBacktrace
     */
    public function AddLogEntry($message,int $level=LogEvent::LEVEL_DEBUG,?string $label=NULL,array $extraLabels=[],?array $debugBacktrace=NULL) {
        if(!$this->enabled || !count($this->loggingObjects)) {
            return;
        }
        $entry=LogEvent::GetNewInstance()
            ->setLevel($level)
            ->setMainLabel($label)
            ->setExtraLabels($extraLabels)
            ->setBacktrace($debugBacktrace)
            ->setMessage($message);
        if(!$entry->isException() && $this->autoAddCaller===TRUE && is_array($debugBacktrace) && count($debugBacktrace)) {
            $entry
                ->setSourceFile(isset($debugBacktrace[0]['file']) ? $debugBacktrace[0]['file'] : NULL)
                ->setSourceLine(isset($debugBacktrace[0]['line']) ? $debugBacktrace[0]['line'] : NULL);
        }
        /** @var \NETopes\Core\Logging\ILoggerAdapter $lObj */
        foreach($this->loggingObjects as $lObj) {
            $lObj->AddEvent(clone $entry);
        }//END foreach
    }//END public function AddLogEntry

    /**
     * Send data to browser
     *
     * @param bool|null $onlyRequiringOutputBuffering
     * @return void
     */
    public function FlushLogs(?bool $onlyRequiringOutputBuffering=NULL) {
        if(!$this->enabled || !count($this->loggingObjects)) {
            return;
        }
        /** @var \NETopes\Core\Logging\ILoggerAdapter $lObj */
        foreach($this->loggingObjects as $lObj) {
            if((($onlyRequiringOutputBuffering ?? TRUE)===TRUE && $lObj->GetRequiresOutputBuffering())
                || (($onlyRequiringOutputBuffering ?? FALSE)===FALSE && !$lObj->GetRequiresOutputBuffering())) {
                $lObj->FlushEvents();
            }
        }//END foreach
    }//END public function FlushLogs

    /**
     * @return string
     */
    public static function GetBrowserType(): string {
        $userAgent=array_key_exists('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if(preg_match('/\sEdge\//',$userAgent)) {
            return 'Edge';
        }
        if(preg_match('/\sEdg\//',$userAgent)) {
            return 'EdgeChrome';
        }
        if(preg_match('/\sOPR\//',$userAgent)) {
            return 'Opera';
        }
        if(preg_match('/\sFirefox\//',$userAgent)) {
            return 'Firefox';
        }
        if(preg_match('/\sChrome\//',$userAgent)) {
            return 'Chrome';
        }
        return 'Other';
    }//END public static function GetBrowserType

    /**
     * Starts a debug timer
     *
     * @param string $name Name of the timer (required)
     * @return bool
     */
    public static function StartTimeTrack($name) {
        if(!$name) {
            return FALSE;
        }
        self::$debugTimers[$name]=microtime(TRUE);
        return TRUE;
    }//END public static function TimerStart

    /**
     * Displays a debug timer elapsed time
     *
     * @param string $name Name of the timer (required)
     * @param bool   $stop Flag for stopping and destroying the timer (default TRUE)
     * @return double|null
     */
    public static function ShowTimeTrack($name,$stop=TRUE) {
        if(!$name || !array_key_exists($name,self::$debugTimers)) {
            return NULL;
        }
        $time=self::$debugTimers[$name];
        if($stop) {
            unset(self::$debugTimers[$name]);
        }
        return (microtime(TRUE) - $time);
    }//END public static function TimerStart
}//END class Debugger