<?php
/**
 * Logger adapter interface file
 * All logging adapters must implement it
 *
 * @package    NETopes\Core\Logger
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
namespace NETopes\Core\Logging;

/**
 * Interface ILoggerAdapter
 *
 * @package NETopes\Core\Logger
 */
interface ILoggerAdapter {

    /**
     * ILoggerAdapter constructor.
     *
     * @param array $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $params);

    /**
     * Get javascript dependencies list
     *
     * @return array
     */
    public function GetScripts(): array;

    /**
     * Get output buffering requirement
     *
     * @return bool
     */
    public function GetRequiresOutputBuffering(): bool;

    /**
     * Add new log event (to buffer if buffered=TRUE or directly to log otherwise)
     *
     * @param \NETopes\Core\Logging\LogEvent $entry
     */
    public function AddEvent(LogEvent $entry): void;

    /**
     * Flush log events buffer.
     */
    public function FlushEvents(): void;
}//END interface ILoggerAdapter