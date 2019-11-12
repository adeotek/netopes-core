<?php
/**
 * IEntity interface file
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.9.2
 * @filesource
 */
namespace NETopes\Core\Data;

/**
 * Interface IEntity
 *
 * @package NETopes\Core\Data
 */
interface IEntity {
    /**
     * Check if property exists
     *
     * @param string|null $name The name of the property
     * @param bool        $notNull
     * @return bool Returns TRUE if property exists
     */
    public function hasProperty(?string $name,bool $notNull=FALSE): bool;

    /**
     * Get property value by name
     *
     * @param string|null $name
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param bool        $strict
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function getProperty(?string $name,$defaultValue=NULL,?string $validation=NULL,bool $strict=FALSE);

    /**
     * Get data array
     *
     * @param bool $originalNames
     * @return array
     */
    public function toArray(bool $originalNames=FALSE): array;
}//END interface IEntity