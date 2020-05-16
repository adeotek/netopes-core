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
namespace NETopes\Core\Logging\Remote;
use Exception;
use NETopes\Core\AppException;
use NETopes\Core\Logging\FileLoggerAdapter;
use NETopes\Core\Logging\ILoggerAdapter;
use NETopes\Core\Logging\LogEvent;
use NETopes\Core\Logging\LogEventsCollection;

/**
 * Class LokiAdapter
 *
 * @package  NETopes\Core\Logger
 */
class LokiAdapter implements ILoggerAdapter {
    /**
     * @var string Loki API route
     */
    protected $apiUri='/loki/api/v1/push';
    /**
     * @var int Minimal log level
     */
    protected $minLogLevel=LogEvent::LEVEL_INFO;
    /**
     * @var string Base URL for Loki API
     */
    protected $url='http://localhost:3100';
    /**
     * @var string|null User for Loki authentication
     */
    protected $user;
    /**
     * @var string|null Password for Loki authentication
     */
    protected $password;
    /**
     * @var bool Include exceptions stack trace into the log message
     */
    protected $includeExceptionsTrace=TRUE;
    /**
     * @var array Global labels array
     */
    protected $globalLabels=[];
    /**
     * @var bool Buffered run (write buffer to file only when flush method is called)
     */
    protected $buffered=TRUE;
    /**
     * @var \NETopes\Core\Logging\LogEventsCollection|null Log events buffer collection
     */
    protected $logEventsBuffer=NULL;

    /**
     * GrafanaLokiAdapter constructor.
     *
     * @param array $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $params) {
        $this->url=get_array_value($params,'url',NULL,'is_string');
        if(!strlen($this->url)) {
            throw new AppException('Invalid Loki URL!');
        }
        $this->minLogLevel=get_array_value($params,'min_log_level',$this->minLogLevel,'is_integer');
        $this->user=get_array_value($params,'user',NULL,'?is_string');
        $this->password=get_array_value($params,'password',NULL,'?is_string');
        $this->includeExceptionsTrace=get_array_value($params,'include_exceptions_trace',$this->includeExceptionsTrace,'is_bool');
        $this->globalLabels=get_array_value($params,'labels',$this->globalLabels,'is_bool');
        $this->buffered=get_array_value($params,'buffered',$this->buffered,'is_bool');
        $this->logEventsBuffer=new LogEventsCollection();
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
     * Get output buffering requirement
     *
     * @return bool
     */
    public function GetRequiresOutputBuffering(): bool {
        return FALSE;
    }//END public function GetRequiresOutputBuffering

    /**
     * Add new log event (to buffer if buffered=TRUE or directly to log otherwise)
     *
     * @param \NETopes\Core\Logging\LogEvent $entry
     */
    public function AddEvent(LogEvent $entry): void {
        try {
            $this->logEventsBuffer->add($entry);
            if(!$this->buffered) {
                $this->FlushEvents();
            }//if($this->buffered)
        } catch(Exception $e) {
            try {
                FileLoggerAdapter::LogToFile($e,NULL,NULL,__FILE__,__LINE__,LogEvent::LEVEL_ERROR);
            } catch(AppException $e) {
                unset($e);
            }//END try
        }//END try
    }//END public function AddEvent

    /**
     * Flush log events buffer
     */
    public function FlushEvents(): void {
        if(!$this->logEventsBuffer->count()) {
            return;
        }
        try {
            $this->SendDataAsync($this->FormatBatch());
            $this->logEventsBuffer->clear();
        } catch(Exception $e) {
            try {
                FileLoggerAdapter::LogToFile($e,NULL,NULL,__FILE__,__LINE__,LogEvent::LEVEL_ERROR);
            } catch(AppException $e) {
                unset($e);
            }//END try
        }//END try
    }//END public function FlushEvents

    /**
     * @param \NETopes\Core\Logging\LogEvent $entry
     * @return string
     */
    protected function FormatMessage(LogEvent $entry): string {
        if($entry->isException()) {
            $data=$entry->message->getMessage();
            if(strlen($entry->sourceFile)) {
                $data.=' in file ['.$entry->sourceFile.($entry->sourceLine ? ':'.$entry->sourceLine : '');
            }
            if($this->includeExceptionsTrace) {
                $data.=', Stack trace:';
                $data.=PHP_EOL.print_r($entry->backtrace,1);
            }//if($this->includeExceptionsTrace)
        } else {
            $data=is_null($entry->message) || is_scalar($entry->message) ? $entry->message ?? 'NULL' : PHP_EOL.print_r($entry->message,1);
            if(strlen($entry->sourceFile)) {
                $data.=' in file ['.$entry->sourceFile.($entry->sourceLine ? ':'.$entry->sourceLine : '');
            }
        }//if($entry->isException())
        return $data;
    }//protected function FormatMessage

    /**
     * @return string
     */
    protected function FormatBatch(): string {
        $data=['streams'=>[]];
        foreach($this->logEventsBuffer->uasort(function($a,$b) { return $a->timestamp==$b->timestamp ? 0 : ($a->timestamp>$b->timestamp ? 1 : -1); }) as $entry) {
            $data['streams'][]=[
                'stream'=>$entry->getAllLabels($this->globalLabels),
                'values'=>[[$entry->getTsInNs()=>$this->FormatMessage($entry)]],
            ];
        }//END foreach
        return json_encode($data);
    }//END protected function FormatBatch

    /**
     * @param string $data
     */
    protected function SendDataAsync(string $data) {
        if(!strlen($data) || !strlen($this->url)) {
            return;
        }
        $cUrl=curl_init(rtrim($this->url,'/').$this->apiUri);
        curl_setopt($cUrl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($cUrl,CURLOPT_HTTPHEADER,['Content-Type: application/json']);
        curl_setopt($cUrl,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($cUrl,CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($cUrl,CURLOPT_POST,1);
        curl_setopt($cUrl,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($cUrl,CURLOPT_TIMEOUT_MS,10);
        if(strlen($this->user)) {
            curl_setopt($cUrl,CURLOPT_USERPWD,$this->user.':'.$this->password);
            curl_setopt($cUrl,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
        }
        curl_setopt($cUrl,CURLOPT_POSTFIELDS,$data);
        curl_exec($cUrl);
        curl_close($cUrl);
    }//END protected function SendDataAsync
}//END class LokiAdapter implements ILoggerAdapter