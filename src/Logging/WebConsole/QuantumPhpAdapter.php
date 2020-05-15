<?php
/**
 * NETopes QuantumPhpAdapter class file.
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging\WebConsole;
use NETopes\Core\AppException;
use NETopes\Core\Logging\ILoggerAdapter;
use NETopes\Core\Logging\LogEvent;
use QuantumPHP;

/**
 * Class QuantumPhpAdapter
 *
 * @package  NETopes\Core\Logger
 */
class QuantumPhpAdapter implements ILoggerAdapter {
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
     * @var array Javascript dependencies list
     */
    protected $loggerScripts=[];

    /**
     * QuantumPhpAdapter constructor.
     *
     * @param array $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $params) {
        if(!class_exists('\QuantumPHP')) {
            throw new AppException('QuantumPHP class not found!');
        }
        $browserType=get_array_value($params,'browser',NULL,'?is_string');
        switch($browserType) {
            case 'Chrome':
            case 'EdgeChrome':
                QuantumPHP::$MODE=3;
                break;
            case 'Firefox':
                QuantumPHP::$MODE=2;
                break;
            default:
                QuantumPHP::$MODE=1;
                $this->loggerScripts='QuantumPHP.min.js';
                break;
        }//END switch
        $this->defaultLabel=get_array_value($params,'default_label',$this->defaultLabel,'is_string');
        $this->showSourceFile=get_array_value($params,'show_source_file',$this->showSourceFile,'is_string');
        $this->showExceptionsTrace=get_array_value($params,'show_exceptions_trace',$this->showExceptionsTrace,'is_string');
    }//END public function __construct

    /**
     * Get javascript dependencies list
     *
     * @return array
     */
    public function GetScripts(): array {
        return $this->loggerScripts;
    }//END public function GetScripts

    /**
     * Add new log event to buffer
     *
     * @param \NETopes\Core\Logging\LogEvent $entry
     */
    public function AddEvent(LogEvent $entry): void {
        $level=$entry->getLevelAsString();
        if($this->showSourceFile && strlen($entry->sourceFile)) {
            $label='['.basename($entry->sourceFile).($entry->sourceLine ? ':'.$entry->sourceLine : '').']';
        } else {
            $label='';
        }
        $label.=$entry->mainLabel ?? $this->defaultLabel;
        if($entry->isException()) {
            $label=(strlen($label) ? $label.':' : '').get_class($entry->message);
            QuantumPHP::add($label,($level!=='debug' ? $level : 'status'),$entry->message);
        } else {
            if(is_null($entry->message)) {
                $isFunction=FALSE;
                $value=(is_string($label) ? $label.': ' : '').'[NULL]';
            } elseif(is_scalar($entry->message)) {
                $isFunction=FALSE;
                $value=(is_string($label) ? $label.': ' : '').$entry->message;
            } else {
                $isFunction=TRUE;
                $value=$entry->message;
            }
            QuantumPHP::add($value,($level!=='debug' ? $level : 'status'),FALSE,FALSE,FALSE,FALSE,$isFunction);
        }//if($entry->isException())
    }//END public function AddEvent

    public function FlushEvents(): void {
        QuantumPHP::send();
    }//END public function FlushEvents
}//END class QuantumPhpAdapter implements ILoggerAdapter