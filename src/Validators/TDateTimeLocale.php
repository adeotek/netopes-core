<?php
/**
 * NETopes DateTime localization helpers trait file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.1.0
 * @filesource
 */
namespace NETopes\Core\Validators;
use DateTime;
use DateTimeImmutable;

/**
 * Trait TDateTimeLocale
 *
 * @package NETopes\Core\Validators
 */
trait TDateTimeLocale {
    /**
     * @param string $format
     * @return string
     */
    public function ConvertDateTimeFormat(string $format): string {
        return preg_replace('/([a-zA-Z]{1})/','%$1',str_replace(['D','M','y','i','s','h'],['A','B','Y','M','S','l'],$format));
    }//END public function ConvertDateTimeFormat

    /**
     * @param             $input
     * @param string      $format
     * @param string|null $locale
     * @return string|null
     */
    public function DateTimeFormat($input,string $format,?string $locale=NULL): ?string {
        $timestamp=NULL;
        if($input instanceof DateTime || $input instanceof DateTimeImmutable) {
            $timestamp=$input->getTimestamp();
        } elseif(is_numeric($input)) {
            $timestamp=$input;
        } elseif(is_string($input) && strlen($input)) {
            $timestamp=strtotime($input);
        }//if($input instanceof DateTime)
        $result=NULL;
        if(isset($timestamp)) {
            if($locale) {
                $currentLocale=setlocale(LC_TIME,0);
                setlocale(LC_TIME,$locale);
                $result=strftime($this->ConvertDateTimeFormat($format),(int)$timestamp);
                setlocale(LC_TIME,$currentLocale);
            } else {
                $result=strftime($this->ConvertDateTimeFormat($format),(int)$timestamp);
            }//if($locale)
        }//if(isset($timestamp))
        return $result;
    }//END public function DateTimeFormat
}//END trait TDateTimeLocale