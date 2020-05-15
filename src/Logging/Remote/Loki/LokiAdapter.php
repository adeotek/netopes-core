<?php
/**
 * NETopes LokiAdapter class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging\Remote\Loki;
use Exception;
use NETopes\Core\AppException;
use NETopes\Core\Logging\FileLoggerAdapter;
use NETopes\Core\Logging\ILoggerAdapter;
use NETopes\Core\Logging\LogEvent;
use NETopes\Core\Logging\LogEventsCollection;
use NETopes\Core\Logging\Logger;

/**
 * Class LokiAdapter
 *
 * @package  NETopes\Core\Logger
 */
class LokiAdapter implements ILoggerAdapter {
    /**
     * @var array Global labels array
     */
    protected $labels=[];
    /**
     * @var \NETopes\Core\Logging\LogEventsCollection|null Log events buffer collection
     */
    protected $logEventsBuffer=NULL;
    /**
     * @var bool Buffered run (write buffer to file only when flush method is called)
     */
    protected $buffered=FALSE;
    /**
     * @var bool Do not write labels to the log file
     */
    protected $ignoreLabels=FALSE;

    /**
     * GrafanaLokiAdapter constructor.
     *
     * @param array $params
     */
    public function __construct(array $params) {
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
            if($this->buffered) {
                if(!$this->logEventsBuffer instanceof LogEventsCollection) {
                    $this->logEventsBuffer=new LogEventsCollection();
                }
                $this->logEventsBuffer->add($entry);
                return;
            }//if($this->buffered)
            // $data=self::FileLogEntryFormatter($entry,$this->includeExceptionsTrace,$this->includeExtraLabels,$this->globalExtraLabels);
        } catch(Exception $e) {
            try {
                FileLoggerAdapter::LogToFile($e,NULL,NULL,__FILE__,__LINE__,LogEvent::LEVEL_ERROR);
            } catch(AppException $e) {
                unset($e);
            }//END try
        }//END try
    }//END public function AddEvent

    public function FlushEvents(): void {
    }//END public function FlushEvents
}//END class LokiAdapter implements ILoggerAdapter