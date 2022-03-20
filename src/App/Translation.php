<?php
/**
 * Class Translation file
 * Helper class for translating application resources
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\App;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\DataHelpers;
use NETopes\Core\Logging\LogEvent;

/**
 * Translation class
 */
class Translation {
    /**
     * @var    array An array containing loaded translations data
     */
    protected static $_LANGUAGES_STRINGS=[];

    /**
     * @param string $langCode
     * @param bool   $loop
     * @return array
     * @throws \NETopes\Core\AppException
     */
    protected static function GetTranslationCacheFile(?string $langCode,bool $loop=TRUE): ?array {
        if(!strlen($langCode)) {
            return NULL;
        }
        if(NApp::$currentNamespace=='web') {
            $idSection=NApp::GetParam('id_section') ? NApp::GetParam('id_section') : 0;
            $idZone=NApp::GetParam('id_zone') ? NApp::GetParam('id_zone') : 0;
            $cNamespace='site_strings';
            $cacheFileName='lang_'.$langCode.'_'.NApp::$currentNamespace.'_'.$idSection.'_'.$idZone.'.cache';
        } else {
            $idSection=NULL;
            $idZone=NULL;
            $cNamespace=NApp::$currentNamespace.'_strings';
            $cacheFileName='lang_'.$langCode.'_'.NApp::$currentNamespace.'.cache';
        }//if(NApp::current_namespace=='web')
        if(NApp::GetParam('translation_cache_is_dirty')==1 && file_exists(AppHelpers::GetCachePath().$cacheFileName)) {
            unlink(AppHelpers::GetCachePath().$cacheFileName);
        }
        if(!file_exists(AppHelpers::GetCachePath().$cacheFileName)) {
            $tArray=NULL;
            try {
                $items=DataProvider::GetArray('System\Translations','GetTranslationsResources',[
                    'section_id'=>$idSection,
                    'zone_id'=>$idZone,
                    'for_label'=>$cNamespace,
                    'lang_code'=>$langCode,
                    'reset_dirty'=>NApp::GetParam('translation_cache_is_dirty'),
                ]);
                $tArray=DataHelpers::convertDbArrayToTree($items,['module','method','name','value'],FALSE);
            } catch(AppException $e) {
                $tArray=NULL;
            }//END try
            if(!is_array($tArray) || count($tArray)==0) {
                return NULL;
            }
            $fContent='<?php function GET_'.strtoupper($langCode).'_STRINGS() {'."\n";
            $fContent.='return '.var_export($tArray,TRUE);
            $fContent.='; } ?>';
            if(file_put_contents(AppHelpers::GetCachePath().$cacheFileName,$fContent)===FALSE) {
                return NULL;
            }
        }//if(!file_exists(AppHelpers::GetCachePath().$cacheFileName))
        require_once(AppHelpers::GetCachePath().$cacheFileName);
        if(!function_exists('GET_'.strtoupper($langCode).'_STRINGS')) {
            if(!$loop) {
                return NULL;
            }
            unlink(AppHelpers::GetCachePath().$cacheFileName);
            return static::GetTranslationCacheFile($langCode,FALSE);
        }//if(!function_exists('GET_TRANSLATIONS_'.strtoupper($langCode)))
        if(!is_array(static::$_LANGUAGES_STRINGS)) {
            static::$_LANGUAGES_STRINGS=[];
        }
        static::$_LANGUAGES_STRINGS[$langCode]=call_user_func('GET_'.strtoupper($langCode).'_STRINGS');
        return static::$_LANGUAGES_STRINGS[$langCode] ?? [];
    }//END protected static function GetTranslationCacheFile

    /**
     * Get resource translation value
     *
     * @param string|array $key
     * @param string|null  $langCode
     * @param bool         $echo
     * @return string Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function Get($key,?string $langCode=NULL,bool $echo=FALSE): string {
        if(is_array($key)) {
            if(!array_key_exists('key',$key) || strlen($key['key'])==0) {
                return '';
            }
            $lKey=$key['key'];
            $lModule=(array_key_exists('module',$key) && strlen($key['module'])>0) ? $key['module'] : '';
            $lMethod=(array_key_exists('method',$key) && strlen($key['method'])>0) ? $key['method'] : '';
        } else {
            $lKey=strtolower($key);
            $lModule='';
            $lMethod='';
        }//if(is_array($key))
        $llang_code=(is_string($langCode) && strlen($langCode)) ? $langCode : NApp::GetLanguageCode();
        if(!is_array(static::$_LANGUAGES_STRINGS) || !array_key_exists($llang_code,static::$_LANGUAGES_STRINGS) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code])) {
            static::GetTranslationCacheFile($llang_code);
        }
        if(!is_array(static::$_LANGUAGES_STRINGS) || !count(static::$_LANGUAGES_STRINGS) || !array_key_exists($llang_code,static::$_LANGUAGES_STRINGS) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code]) || !count(static::$_LANGUAGES_STRINGS[$llang_code]) || !array_key_exists($lModule,static::$_LANGUAGES_STRINGS[$llang_code]) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code][$lModule]) || !array_key_exists($lMethod,static::$_LANGUAGES_STRINGS[$llang_code][$lModule]) || !is_array(static::$_LANGUAGES_STRINGS[$llang_code][$lModule][$lMethod]) || !array_key_exists($lKey,static::$_LANGUAGES_STRINGS[$llang_code][$lModule][$lMethod])) {
            if(AppConfig::GetValue('auto_insert_missing_translations')) {
                try {
                    if(NApp::$currentNamespace=='web') {
                        $idSection=NApp::GetParam('id_section') ? NApp::GetParam('id_section') : NULL;
                        $idZone=NApp::GetParam('id_zone') ? NApp::GetParam('id_zone') : NULL;
                        $cNamespace='site_strings';
                    } else {
                        $idSection=NULL;
                        $idZone=NULL;
                        $cNamespace=NApp::$currentNamespace.'_strings';
                    }//if(NApp::$currentNamespace=='web')
                    DataProvider::GetArray('System\Translations','SetBlankTranslationsResource',[
                        'in_name'=>$lKey,
                        'in_config_label'=>$cNamespace,
                        'language_code'=>$llang_code,
                        'section_id'=>$idSection,
                        'zone_id'=>$idZone,
                        'in_module'=>strlen($lModule) ? $lModule : NULL,
                        'in_method'=>strlen($lMethod) ? $lMethod : NULL,
                    ]);
                } catch(AppException $e) {
                    NApp::Elog($e);
                }//END try
            } else {
                NApp::LogToFile("|Module[{$lModule}]|Method[{$lMethod}]|Key[{$lKey}]",NULL,NULL,LogEvent::LEVEL_WARNING,NApp::$appPath.AppConfig::GetValue('logs_path')."/missing_translations_".NApp::$currentNamespace."_{$llang_code}.log");
            }//if(AppConfig::GetValue('auto_insert_missing_translations'))
            return "[{$lKey}]";
        }//if(...
        if($echo) {
            echo static::$_LANGUAGES_STRINGS[$llang_code][$lModule][$lMethod][$lKey];
        }
        return static::$_LANGUAGES_STRINGS[$llang_code][$lModule][$lMethod][$lKey];
    }//END public static function Get

    /**
     * Get label translation
     *
     * @param string      $key
     * @param string|null $langCode
     * @param bool        $echo
     * @return string|null Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function GetLabel(string $key,?string $langCode=NULL,bool $echo=FALSE): ?string {
        if(!strlen($key)) {
            return NULL;
        }
        return static::Get('label_'.$key,$langCode,$echo);
    }//END public static function GetLabel

    /**
     * Get button translation
     *
     * @param string      $key
     * @param string|null $langCode
     * @param bool        $echo
     * @return string|null Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function GetButton(string $key,?string $langCode=NULL,bool $echo=FALSE): ?string {
        if(!strlen($key)) {
            return NULL;
        }
        return static::Get('button_'.$key,$langCode,$echo);
    }//END public static function GetButton

    /**
     * Get title translation
     *
     * @param string      $key
     * @param string|null $langCode
     * @param bool        $echo
     * @return string|null Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function GetTitle(string $key,?string $langCode=NULL,bool $echo=FALSE): ?string {
        if(!strlen($key)) {
            return NULL;
        }
        return static::Get('title_'.$key,$langCode,$echo);
    }//END public static function GetTitle

    /**
     * Get message translation
     *
     * @param string      $key
     * @param string|null $langCode
     * @param bool        $echo
     * @return string|null Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function GetMessage(string $key,?string $langCode=NULL,bool $echo=FALSE): ?string {
        if(!strlen($key)) {
            return NULL;
        }
        return static::Get('msg_'.$key,$langCode,$echo);
    }//END public static function GetMessage

    /**
     * Get error translation
     *
     * @param string      $key
     * @param string|null $langCode
     * @param bool        $echo
     * @return string|null Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function GetError(string $key,?string $langCode=NULL,bool $echo=FALSE): ?string {
        if(!strlen($key)) {
            return NULL;
        }
        return static::Get('error_'.$key,$langCode,$echo);
    }//END public static function GetError

    /**
     * Get URLID translation
     *
     * @param string      $key
     * @param string|null $langCode
     * @param bool        $echo
     * @return string|null Translated value
     * @throws \NETopes\Core\AppException
     */
    public static function GetUrlId(string $key,?string $langCode=NULL,bool $echo=FALSE): ?string {
        if(!strlen($key)) {
            return NULL;
        }
        return static::Get('urlid_'.$key,$langCode,$echo);
    }//END public static function GetUrlId
}//END class Translation