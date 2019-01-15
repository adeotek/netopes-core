<?php
/**
 * NETopes DateTime helpers trait file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
 * @filesource
 */
namespace NETopes\Core\Validators;

/**
 * Trait TDateTimeHelpers
 *
 * @package NETopes\Core
 */
trait TDateTimeHelpers {
    /**
     * Converts a date from unix timestamp to excel serial
     *
     * @param  mixed   $date The date to be converted in unix time stamp format
     * or in string format (if string the $ts_input param must be set to FALSE)
     * @param  string|null $timezone The time zone for the string data to be converted
     * @param  string|null $newTimezone User's time zone
     * @return int|null Returns the date in excel serial format
     */
    public static function datetimeToExcelTimestamp($date,?string $timezone = NULL,?string $newTimezone = NULL) {
        if(!$date) { return NULL; }
        try {
            if(is_numeric($date)) {
                $dt = strlen($timezone) ? new \DateTime('now',new \DateTimeZone($timezone)) : new \DateTime('now');
                $dt->setTimestamp($date);
            } elseif(is_object($date)) {
                $dt = $date;
            } else {
                $date = trim($date,' -.:/');
                if(!strlen($date)) { return NULL; }
                $dt = strlen($timezone) ? new \DateTime($date,new \DateTimeZone($timezone)) : new \DateTime($date);
            }//if(strlen($timezone))
            if(strlen($newTimezone) && $newTimezone!==$timezone) { $dt->setTimezone(new \DateTimeZone($newTimezone)); }
            $result = (25569.083333333 + ($dt->getTimestamp() + 3600) / 86400);
            return $result;
        } catch(\Exception $ne) {
            return NULL;
        }//END try
    }//END public static function datetimeToExcelTimestamp
    /**
     * Converts a date from excel serial to unix time stamp
     *
     * @param  float       $date The date to be converted from excel serial format
     * @param  string|null $timezone User's time zone
     * @param  string|null $newTimezone The time zone for the string data to be converted
     * If NULL or empty, numeric time stamp is returned
     * @param string|null  $format DateTime format in which result to be returned (NULL for object)
     * @return \DateTime|string|null Returns the date as string or or unix time stamp
     */
    public static function excelTimestampToDatetime($date,?string $timezone = NULL,?string $newTimezone = NULL,?string $format = NULL) {
        if(!is_numeric($date)) { return NULL; }
        try {
            $dt = strlen($timezone) ? new \DateTime('now',new \DateTimeZone($timezone)) : new \DateTime('now');
            $dt->setTimestamp((round(($date - 25569.083333333) * 86400) - 3600));
            if(strlen($newTimezone) && $newTimezone!==$timezone) { $dt->setTimezone(new \DateTimeZone($newTimezone)); }
            if(strlen($format)) { return $dt->format($format); }
            return $dt;
        } catch(\Exception $e) {
            return NULL;
        }//END try
    }//END public static function excelTimestampToDatetime
}//END trait TDateTimeHelpers