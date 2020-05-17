<?php
/**
 * NETopes LogEvent class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.4.1.0
 * @filesource
 */
namespace NETopes\Core\Logging;

use DateTime;
use Throwable;

/**
 * Class LogEvent
 *
 * @package  NETopes\Core\Logger
 */
class LogEvent {
    /**
     * LEVEL_DEBUG constant definition
     */
    const LEVEL_DEBUG=1;
    /**
     * DBG_INFO constant definition
     */
    const LEVEL_INFO=2;
    /**
     * LEVEL_WARNING constant definition
     */
    const LEVEL_WARNING=3;
    /**
     * LEVEL_ERROR constant definition
     */
    const LEVEL_ERROR=4;

    /**
     * @var float Event timestamp as micro-time
     */
    public $timestamp;
    /**
     * @var int Log level
     */
    public $level=self::LEVEL_INFO;
    /**
     * @var mixed Log message
     */
    public $message;
    /**
     * @var string|null Main label
     */
    public $mainLabel;
    /**
     * @var array Extra labels
     */
    public $extraLabels=[];
    /**
     * @var string|null Source script file
     */
    public $sourceFile;
    /**
     * @var string|null Source script line
     */
    public $sourceLine;
    /**
     * @var array|null Backtrace
     */
    public $backtrace;
    /**
     * @var bool Is Exception
     */
    protected $isException=FALSE;

    /**
     * @return \NETopes\Core\Logging\LogEvent
     */
    public static function GetNewInstance(): LogEvent {
        return new LogEvent();
    }

    /**
     * LogEvent constructor.
     */
    public function __construct() {
        $this->timestamp=microtime(TRUE);
    }

    /**
     * @return bool
     */
    public function isException(): bool {
        return $this->isException;
    }

    /**
     * @param float $timestamp
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setTimestamp(float $timestamp): LogEvent {
        $this->timestamp=$timestamp;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): DateTime {
        return DateTime::createFromFormat('U.u',$this->timestamp);
    }

    /**
     * @return int
     */
    public function getTsInNs(): int {
        return (intval(($this->timestamp * 10000)) * 100000);
    }

    /**
     * @param int $level
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setLevel(int $level): LogEvent {
        $this->level=$level;
        return $this;
    }

    /**
     * @return string Log level string value
     */
    public function getLevelAsString(): string {
        return self::GetLogLevelString($this->level);
    }

    /**
     * @param mixed $message
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setMessage($message): LogEvent {
        $this->message=$message;
        $this->isException=$message instanceof Throwable;
        if($this->isException) {
            $this->level=self::LEVEL_ERROR;
            /** @var \Exception $message */
            $this->sourceFile=$message->getFile();
            $this->sourceLine=$message->getLine();
            $this->backtrace=$message->getTrace();
        }
        return $this;
    }

    /**
     * @param string|null $label
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setMainLabel(?string $label): LogEvent {
        $this->mainLabel=$label;
        return $this;
    }

    /**
     * @param array $extraLabels
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setExtraLabels(array $extraLabels): LogEvent {
        $this->extraLabels=$extraLabels;
        return $this;
    }

    /**
     * @param array $globalLabels
     * @return array
     */
    public function getAllLabels(array $globalLabels): array {
        $result=$globalLabels;
        if(strlen($this->sourceFile)) {
            $result['SourceFile']=$this->sourceFile;
        }
        foreach($this->extraLabels as $k=>$v) {
            if(!is_string($k) || !is_string($v)) {
                continue;
            }
            $result[$k]=$v;
        }//END foreach
        if(strlen($this->mainLabel)) {
            $result['tag']=$this->mainLabel;
        }
        $result['level']=$this->getLevelAsString();
        return $result;
    }

    /**
     * @param string|null $file
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setSourceFile(?string $file): LogEvent {
        $this->sourceFile=$file;
        return $this;
    }

    /**
     * @param int|null $line
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setSourceLine(?int $line): LogEvent {
        $this->sourceLine=$line;
        return $this;
    }

    /**
     * @param array|null $backtrace
     * @return \NETopes\Core\Logging\LogEvent
     */
    public function setBacktrace(?array $backtrace): LogEvent {
        $this->backtrace=$backtrace;
        return $this;
    }

    /**
     * @param int $level Log level
     * @return string Log level string value
     */
    public static function GetLogLevelString(int $level): string {
        switch($level) {
            case self::LEVEL_DEBUG:
                return 'debug';
            case self::LEVEL_INFO:
                return 'info';
            case self::LEVEL_WARNING:
                return 'warning';
            case self::LEVEL_ERROR:
                return 'error';
            default:
                return '';
        }//END switch
    }//END public static function GetLogLevelString
}//END class LogEvent