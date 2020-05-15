<?php
/**
 * NETopes LogEvent class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging;

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
     * @return bool
     */
    public function isException(): bool {
        return $this->isException;
    }

    /**
     * @return string Log level string value
     */
    public function getLevelAsString(): string {
        return self::GetLogLevelString($this->level);
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