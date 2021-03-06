<?php
/**
 * NETopes helpers class file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core;
use Exception;

/**
 * Class Helpers
 *
 * @package NETopes\Core
 */
class Helpers {
    /**
     * File unlink with check if file exists
     *
     * @param string $file File to unlink
     * @return  bool Returns TRUE on success or FALSE on error or if the file doesn't exist
     */
    public static function safeUnlink($file) {
        if(!is_string($file) || !strlen($file) || !file_exists($file)) {
            return FALSE;
        }
        try {
            unlink($file);
            return TRUE;
        } catch(Exception $e) {
            return FALSE;
        }
    }//END public static function safeUnlink

    /**
     * Eliminate last N folders from a path.
     *
     * @param string  $path The path to be processed.
     * @param integer $no   The number of folders to be removed from the end of the path (default 1).
     * @return  string The processed path.
     */
    public static function upInPath($path,$no=1) {
        $result=$path;
        for($i=0; $i<$no; $i++) {
            $result=str_replace('/'.basename($result),'',$result);
        }//for($i=0; $i<$no; $i++)
        return $result;
    }//END public static function upInPath

    /**
     * Replaces all url not accepted characters with minus character (-)
     *
     * @param string $string String to be processed.
     * @return  string The processed string.
     */
    public static function stringToUrl($string) {
        return trim(str_replace(['--','~~','~',','],'-',preg_replace('/(\W)/','-',trim($string))),'-');
    }//END public static function stringToUrl

    /**
     * Converts a hex color to RGB
     *
     * @param string $hex Color hex code
     * @param number $r   B code by reference (for output)
     * @param null   $g
     * @param null   $b
     * @return array Returns an array containing the RGB values - array(R,G,B)
     */
    public static function hex2rgb($hex,&$r=NULL,&$g=NULL,&$b=NULL) {
        $hex=str_replace('#','',$hex);
        if(strlen($hex)==3) {
            $r=hexdec(substr($hex,0,1).substr($hex,0,1));
            $g=hexdec(substr($hex,1,1).substr($hex,1,1));
            $b=hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r=hexdec(substr($hex,0,2));
            $g=hexdec(substr($hex,2,2));
            $b=hexdec(substr($hex,4,2));
        }//if(strlen($hex)==3)
        return [$r,$g,$b];
    }//END public static function hex2rgb

    /**
     * Returns an array of files from the provided path and all its sub folders.
     * For each file the value is an array with the following structure:
     * array(
     *        'name'=>(string) File name (with extension),
     *        'path'=>(string) Full path of the file (without file name),
     *        'ext'=>(string) File extension (without "." character)
     * )
     *
     * @param string $path        The starting path for the search
     * @param array  $extensions  An array of accepted file extensions (without the "." character)
     *                            or NULL for all
     * @param string $exclude     A regex string for filtering files and folders names with preg_match function
     *                            or NULL for all
     * @param int    $sort        Sort type in php scandir() format (default SCANDIR_SORT_ASCENDING)
     * @param array  $dir_exclude An array of folders to be excluded (at any level of the tree)
     * @return array|bool  Returns an array of found files
     */
    public static function getFilesRecursive($path,$extensions=NULL,$exclude=NULL,$sort=SCANDIR_SORT_ASCENDING,$dir_exclude=NULL) {
        if(!$path || !file_exists($path)) {
            return FALSE;
        }
        $result=[];
        foreach(scandir($path,$sort) as $v) {
            if($v=='.' || $v=='..' || (strlen($exclude) && preg_match($exclude,$v))) {
                continue;
            }
            if(is_dir($path.'/'.$v)) {
                if(is_array($dir_exclude) && count($dir_exclude) && in_array($v,$dir_exclude)) {
                    continue;
                }
                $tmp_result=self::getFilesRecursive($path.'/'.$v,$extensions,$exclude,$sort,$dir_exclude);
                if(is_array($tmp_result)) {
                    $result=array_merge($result,$tmp_result);
                }
            } else {
                $ext=strrpos($v,'.')===FALSE || strrpos($v,'.')==0 ? '' : substr($v,strrpos($v,'.') + 1);
                if(is_array($extensions) && !in_array($ext,$extensions)) {
                    continue;
                }
                $result[]=['name'=>$v,'path'=>$path,'ext'=>$ext];
            }//if(is_dir($path.'/'.$v))
        }//END foreach
        return $result;
    }//END public static function getFilesRecursive

    /**
     * Get file mime type by extension
     *
     * @param string $filename Target file name (with or without path)
     * @return string Returns the mime type identified by file extension
     */
    public static function getFileMimeTypeByExtension($filename) {
        $standard_mime_types=[
            'pdf'=>'application/pdf',
            'txt'=>'text/plain',
            'html'=>'text/html',
            'htm'=>'text/html',
            'exe'=>'application/octet-stream',
            'zip'=>'application/zip',
            'doc'=>'application/msword',
            'xls'=>'application/vnd.ms-excel',
            'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'=>'application/vnd.ms-powerpoint',
            'dbf'=>'application/x-dbf',
            'gif'=>'image/gif',
            'png'=>'image/png',
            'jpeg'=>'image/jpg',
            'jpg'=>'image/jpg',
            'php'=>'text/plain',
            'apk'=>'application/octet-stream',
            'log'=>'text/plain',
        ];
        $fileext=substr($filename,strrpos($filename,'.') + 1);
        return (array_key_exists($fileext,$standard_mime_types) ? $standard_mime_types[$fileext] : 'application/force-download');
    }//END public static function getFileMimeTypeByExtension

    /**
     * Get file extension by mime type
     *
     * @param string $mime_type Target mime type
     * @return string Returns the file extension identified by mime type
     */
    public static function getFileExtensionByMimeType($mime_type) {
        if(!is_string($mime_type) || !strlen(trim($mime_type))) {
            return FALSE;
        }
        $standard_extensions=[
            'application/pdf'=>'pdf',
            'text/html'=>'html',
            'image/jpg'=>'jpg',
            'image/png'=>'png',
            'image/gif'=>'gif',
            'application/msword'=>'doc',
            'application/vnd.ms-excel'=>'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx',
            'application/vnd.ms-powerpoint'=>'ppt',
            'application/zip'=>'zip',
            'application/octet-stream'=>'exe',
            'text/plain'=>'txt',
        ];
        foreach($standard_extensions as $k=>$v) {
            if(strpos(strtolower($mime_type),$k)!==FALSE) {
                return $v;
            }
        }
        return NULL;
    }//END public static function getFileExtensionByMimeType

    /**
     * Windows to Unix path conversion
     *
     * @param $path
     * @return string
     */
    public static function win2unixPath(string $path): string {
        return DIRECTORY_SEPARATOR=='\\' ? str_replace('\\','/',$path) : $path;
    }//END public static function win2unixPath

    /**
     * Get remote client IP address
     *
     * @return string|null
     */
    public static function getClientIpAddr(): ?string {
        if(isset($_SERVER['HTTP_CLIENT_IP']) && strlen($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strlen($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif(isset($_SERVER['REMOTE_ADDR']) && strlen($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return NULL;
    }//END public static function getClientIpAddr

    /**
     * @param      $file
     * @param null $ext
     * @return bool|int
     */
    public static function checkFile404($file,$ext=NULL) {
        $file=preg_replace('{ +}','%20',trim($file));
        if(substr($file,0,7)!=="http://") {
            $file="http://".$file;
        }
        if($ext) {
            $file_arr=explode('.',$file);
            $file_ext=strtolower(array_pop($file_arr));
            if($file_ext!==$ext) {
                return 1;
            }
        }//if($ext)
        try {
            $file_headers=@get_headers($file);
        } catch(Exception $e) {
            $file_headers=NULL;
        }//END try
        if(!$file_headers) {
            return 2;
        }
        if($file_headers[0]=='HTTP/1.1 404 Not Found') {
            return 404;
        }
        return TRUE;
    }//END public static function checkFile404

    /**
     * description
     *
     * @param array $params Parameters object (instance of [Params])
     * @param null  $info
     * @return bool|string
     * @throws \Exception
     */
    public static function curlCall($params=[],&$info=NULL) {
        if(!is_array($params) || !count($params)) {
            return FALSE;
        }
        if(!isset($params['url']) || !strlen($params['url'])) {
            return FALSE;
        }
        if(isset($params['user_agent']) && strlen($params['user_agent'])) {
            $req_user_agent=$params['user_agent']=='auto' ? 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36' : $params['user_agent'];
        } else {
            $req_user_agent='PHP_CURL_CALL';
        }//if(isset($params['user_agent']) && strlen($params['user_agent']))
        $cUrl=curl_init();
        @$options=[
            CURLOPT_URL=>$params['url'],
            CURLOPT_SSL_VERIFYPEER=>FALSE,
            CURLOPT_RETURNTRANSFER=>TRUE,
            CURLOPT_FOLLOWLOCATION=>TRUE,
            CURLOPT_CONNECTTIMEOUT=>60,
            CURLOPT_TIMEOUT=>300,
            // CURLOPT_MUTE=>TRUE,
            // CURLOPT_FRESH_CONNECT=>TRUE,
            // CURLOPT_HEADER=>FALSE,
            CURLOPT_USERAGENT=>$req_user_agent,
            // This is what solved the issue (Accepting gzip encoding)
            CURLOPT_ENCODING=>'gzip, deflate',
        ];
        @curl_setopt_array($cUrl,$options);
        if(isset($params['headers']) && is_array($params['headers']) && count($params['headers'])) {
            curl_setopt($cUrl,CURLOPT_HTTPHEADER,$params['headers']);
        }
        if(isset($params['post_params']) && $params['post_params']) {
            curl_setopt($cUrl,CURLOPT_POST,TRUE);
            curl_setopt($cUrl,CURLOPT_POSTFIELDS,$params['post_params']);
        }//if(isset($params['post_params']) && $params['post_params'])
        if(isset($params['auth_username']) && $params['auth_username']) {
            curl_setopt($cUrl,CURLOPT_USERPWD,$params['auth_username'].':'.(isset($params['auth_password']) ? $params['auth_password'] : ''));
            curl_setopt($cUrl,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
        }//if(isset($params['auth_username']) && $params['auth_username'])
        $result=curl_exec($cUrl);
        $error=curl_error($cUrl);
        $info=curl_getinfo($cUrl);
        curl_close($cUrl);
        if($error) {
            throw new Exception($error);
        }
        return $result;
    }//END public static function curlCall

    /**
     * description
     *
     * @param array $params Parameters object
     * @return mixed
     */
    public static function asyncCurlCall($params=NULL) {
        if(!is_array($params) || !count($params)) {
            return FALSE;
        }
        if(!isset($params['url']) || !$params['url']) {
            return FALSE;
        }
        $req_user_agent='PHP_ASYNC_CURL_CALL';
        $url=$params['url'];
        $options=[
            CURLOPT_URL=>$url,
            CURLOPT_FAILONERROR=>TRUE,
            CURLOPT_FRESH_CONNECT=>TRUE,
            CURLOPT_RETURNTRANSFER=>TRUE,
            CURLOPT_NOSIGNAL=>1, //to timeout immediately if the value is < 1000 ms
            CURLOPT_TIMEOUT_MS=>50, //The maximum number of mseconds to allow cURL functions to execute
            CURLOPT_CONNECTTIMEOUT=>60,
            CURLOPT_TIMEOUT=>36000,
            CURLOPT_USERAGENT=>$req_user_agent,
            CURLOPT_VERBOSE=>1,
            CURLOPT_HEADER=>1,
            // This is what solved the issue (Accepting gzip encoding)
            CURLOPT_ENCODING=>'gzip, deflate',
        ];
        $cUrl=curl_init();
        curl_setopt_array($cUrl,$options);
        if(isset($params['headers']) && is_array($params['headers']) && count($params['headers'])) {
            curl_setopt($cUrl,CURLOPT_HTTPHEADER,$params['headers']);
        }
        if(isset($params['post_params']) && $params['post_params']) {
            curl_setopt($cUrl,CURLOPT_POST,TRUE);
            curl_setopt($cUrl,CURLOPT_POSTFIELDS,$params['post_params']);
        }//if(isset($params['post_params']) && $params['post_params'])
        $result=curl_exec($cUrl);
        $error=curl_error($cUrl);
        curl_close($cUrl);
        if($error) {
            return $error;
        }
        return $result;
    }//END public static function asyncCurlCall

    /**
     * Emulate ping command
     *
     * @param     $host
     * @param int $port
     * @param int $timeout
     * @return string
     */
    public static function ping($host,$port=80,$timeout=10) {
        $ts=microtime(TRUE);
        $errorNo=$errorMessage=NULL;
        try {
            $sconn=fSockOpen($host,$port,$errorNo,$errorMessage,$timeout);
            if(!$sconn || $errorNo) {
                return 'Timeout/Error: ['.$errorNo.'] '.$errorMessage;
            }
            return round(((microtime(TRUE) - $ts) * 1000),0).' ms';
        } catch(Exception $e) {
            return 'Exception: '.$e->getMessage();
        }//END try
    }//END public static function ping

    /**
     * @brief Generates a Universally Unique IDentifier, version 4.
     *
     * This function generates a truly random UUID. The built in CakePHP String::uuid() function
     * is not cryptographically secure. You should uses this function instead.
     *
     * @see   http://tools.ietf.org/html/rfc4122#section-4.4
     * @see   http://en.wikipedia.org/wiki/UUID
     * @return string A UUID, made up of 32 hex digits and 4 hyphens.
     */
    public static function new_uuid(): string {
        $pr_bits=NULL;
        try {
            $fp=@fopen('/dev/urandom','rb');
            if($fp!==FALSE) {
                $pr_bits=@fread($fp,16);
                @fclose($fp);
            }
        } catch(Exception $e) {
            $fp=FALSE;
        }

        if($fp==FALSE) {
            // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
            $pr_bits='';
            for($cnt=0; $cnt<16; $cnt++) {
                $pr_bits.=chr(mt_rand(0,255));
            }
        }

        $time_low=bin2hex(substr($pr_bits,0,4));
        $time_mid=bin2hex(substr($pr_bits,4,2));
        $time_hi_and_version=bin2hex(substr($pr_bits,6,2));
        $clock_seq_hi_and_reserved=bin2hex(substr($pr_bits,8,2));
        $node=bin2hex(substr($pr_bits,10,6));

        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * time_hi_and_version field to the 4-bit version number from
         * Section 4.1.3.
         *
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $time_hi_and_version=hexdec($time_hi_and_version);
        $time_hi_and_version=$time_hi_and_version >> 4;
        $time_hi_and_version=$time_hi_and_version | 0x4000;

        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clock_seq_hi_and_reserved=hexdec($clock_seq_hi_and_reserved);
        $clock_seq_hi_and_reserved=$clock_seq_hi_and_reserved >> 2;
        $clock_seq_hi_and_reserved=$clock_seq_hi_and_reserved | 0x8000;

        return sprintf('%08s-%04s-%04x-%04x-%012s',$time_low,$time_mid,$time_hi_and_version,$clock_seq_hi_and_reserved,$node);
    }//END public static function new_uuid
}//END class Helpers