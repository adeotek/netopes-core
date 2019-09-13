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
use NETopes\Core\Validators\Validator;

/**
 * Class DataHelpers
 *
 * @package NETopes\Core
 */
class DataHelpers {
    /**
     * SQL-like coalesce
     *
     * @return  bool Returns first non-null argument
     */
    public static function coalesce() {
        $params=func_get_args();
        foreach($params as $p) {
            if(isset($p)) {
                return $p;
            }
        }
        return NULL;
    }//END public static function coalesce

    /**
     * SQL-like coalesce for strings
     * (empty string is considered null)
     *
     * @param mixed [ $arg ] Any number of arguments to be coalesced
     * Obs. Each argument will be checked after trim
     * @return  bool Returns first non-null, non-empty argument
     */
    public static function stringCoalesce() {
        $params=func_get_args();
        foreach($params as $p) {
            if(isset($p) && !is_string($p)) {
                continue;
            }
            $val=isset($p) ? trim($p) : '';
            if(strlen($val)) {
                return $p;
            }
        }//END foreach
        return NULL;
    }//END public static function stringCoalesce

    /**
     * Check if a string contains one or more strings.
     *
     * @param string  $haystack  The string to be searched.
     * @param mixed   $needle    The string to be searched for.
     *                           To search for multiple strings, needle can be an array containing this strings.
     * @param integer $offset    The offset from which the search to begin (default 0, the begining of the string).
     * @param bool    $all_array Used only if the needle param is an array, sets the search type:
     *                           * if is set TRUE the function will return TRUE only if all the strings contained in needle are found in haystack,
     *                           * if is set FALSE (default) the function will return TRUE if any (one, several or all)
     *                           of the strings in the needle are found in haystack.
     * @return  bool Returns TRUE if needle is found in haystack or FALSE otherwise.
     */
    public static function stringContains($haystack,$needle,$offset=0,$all_array=FALSE) {
        if(is_array($needle)) {
            if(!$haystack || count($needle)==0) {
                return FALSE;
            }
            foreach($needle as $n) {
                $tr=strpos($haystack,$n,$offset);
                if(!$all_array && $tr!==FALSE) {
                    return TRUE;
                }
                if($all_array && $tr===FALSE) {
                    return FALSE;
                }
            }//foreach($needle as $n)
            return $all_array;
        }//if(is_array($needle))
        return strpos($haystack,$needle,$offset)!==FALSE;
    }//END public static function stringContains

    /**
     * Remove all instances of white-spaces from both ends of the string,
     * as well as remove duplicate white-space characters inside the string
     *
     * @param string $input Subject string
     * @param null   $what  Optional undesired characters to be replaced
     * @param string $with  Undesired characters replacement
     * @return string Returns processed string
     */
    public static function trimAll($input,$what=NULL,$with=' ') {
        if($what===NULL) {
            //  Character      Decimal      Use
            //  "\0"            0           Null Character
            //  "\t"            9           Tab
            //  "\n"           10           New line
            //  "\x0B"         11           Vertical Tab
            //  "\r"           13           New Line in Mac
            //  " "            32           Space
            $what="\\x00-\\x20"; // all white-spaces and control chars
        }//if($what===NULL)
        return trim(preg_replace("/[".$what."]+/",$with,$input),$what);
    }//END public static function trimAll

    /**
     * @param $string
     * @return null|string
     */
    public static function nl2br($string) {
        if(!is_string($string)) {
            return NULL;
        }
        return nl2br(str_replace("\t",'&nbsp;&nbsp;&nbsp;',$string));
    }//END public static function nl2br

    /**
     * @param $string
     * @return mixed|null
     */
    public static function br2nl($string) {
        if(!is_string($string)) {
            return NULL;
        }
        return str_replace('&nbsp;&nbsp;&nbsp;',"\t",str_replace(['<br/>','<br />','<br>'],"\n",$string));
    }//END public static function br2nl

    /**
     * Replace last occurrence of a substring
     *
     * @param string $search  Substring to be replaced
     * @param string $replace Replacement string
     * @param string $str     String to be processed
     * @return string Returns processed string
     */
    public static function stringReplaceLast($search,$replace,$str) {
        if(($pos=strrpos($str,$search))===FALSE) {
            return $str;
        }
        return substr_replace($str,$replace,$pos,strlen($search));
    }//END public static function stringReplaceLast

    /**
     * Normalize string
     *
     * @param string $input                String to be normalized
     * @param bool   $replace_diacritics   Replace diacritics with ANSI corespondent (default TRUE)
     * @param bool   $html_entities_decode Decode html entities (default TRUE)
     * @param bool   $trim                 Trim beginning/ending white spaces (default TRUE)
     * @return string Returns processed string
     */
    public static function normalizeString($input,$replace_diacritics=TRUE,$html_entities_decode=TRUE,$trim=TRUE) {
        if(!is_string($input)) {
            return NULL;
        }
        if(!strlen($input)) {
            return $input;
        }
        $diacritics=[
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t','Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ț'=>'T',
            'á'=>'a','Á'=>'A','à'=>'a','À'=>'A','å'=>'a','Å'=>'A','ã'=>'a','Ã'=>'A','ą'=>'a','Ą'=>'A','ā'=>'a','Ā'=>'A','ä'=>'ae','Ä'=>'AE','æ'=>'ae','Æ'=>'AE','ḃ'=>'b','Ḃ'=>'B','ć'=>'c','Ć'=>'C','ĉ'=>'c','Ĉ'=>'C','č'=>'c','Č'=>'C','ċ'=>'c','Ċ'=>'C','ç'=>'c','Ç'=>'C','ď'=>'d','Ď'=>'D','ḋ'=>'d','Ḋ'=>'D','đ'=>'d','Đ'=>'D','ð'=>'dh','Ð'=>'Dh','é'=>'e','É'=>'E','è'=>'e','È'=>'E','ĕ'=>'e','Ĕ'=>'E','ê'=>'e','Ê'=>'E','ě'=>'e','Ě'=>'E','ë'=>'e','Ë'=>'E','ė'=>'e','Ė'=>'E','ę'=>'e','Ę'=>'E','ē'=>'e','Ē'=>'E','ḟ'=>'f','Ḟ'=>'F','ƒ'=>'f','Ƒ'=>'F','ğ'=>'g','Ğ'=>'G','ĝ'=>'g','Ĝ'=>'G','ġ'=>'g','Ġ'=>'G','ģ'=>'g','Ģ'=>'G','ĥ'=>'h','Ĥ'=>'H','ħ'=>'h','Ħ'=>'H','í'=>'i','Í'=>'I','ì'=>'i','Ì'=>'I','ï'=>'i','Ï'=>'I','ĩ'=>'i','Ĩ'=>'I','į'=>'i','Į'=>'I','ī'=>'i','Ī'=>'I','ĵ'=>'j','Ĵ'=>'J','ķ'=>'k','Ķ'=>'K','ĺ'=>'l','Ĺ'=>'L','ľ'=>'l','Ľ'=>'L','ļ'=>'l','Ļ'=>'L','ł'=>'l','Ł'=>'L','ṁ'=>'m','Ṁ'=>'M','ń'=>'n','Ń'=>'N','ň'=>'n','Ň'=>'N','ñ'=>'n','Ñ'=>'N','ņ'=>'n','Ņ'=>'N','ó'=>'o','Ó'=>'O','ò'=>'o','Ò'=>'O','ô'=>'o','Ô'=>'O','ő'=>'o','Ő'=>'O','õ'=>'o','Õ'=>'O','ø'=>'oe','Ø'=>'OE','ō'=>'o','Ō'=>'O','ơ'=>'o','Ơ'=>'O','ö'=>'oe','Ö'=>'OE','ṗ'=>'p','Ṗ'=>'P','ŕ'=>'r','Ŕ'=>'R','ř'=>'r','Ř'=>'R','ŗ'=>'r','Ŗ'=>'R','ś'=>'s','Ś'=>'S','ŝ'=>'s','Ŝ'=>'S','š'=>'s','Š'=>'S','ṡ'=>'s','Ṡ'=>'S','ş'=>'s','Ş'=>'S','ß'=>'SS','ť'=>'t','Ť'=>'T','ṫ'=>'t','Ṫ'=>'T','ţ'=>'t','Ţ'=>'T','ŧ'=>'t','Ŧ'=>'T','ú'=>'u','Ú'=>'U','ù'=>'u','Ù'=>'U','ŭ'=>'u','Ŭ'=>'U','û'=>'u','Û'=>'U','ů'=>'u','Ů'=>'U','ű'=>'u','Ű'=>'U','ũ'=>'u','Ũ'=>'U','ų'=>'u','Ų'=>'U','ū'=>'u','Ū'=>'U','ư'=>'u','Ư'=>'U','ü'=>'ue','Ü'=>'UE','ẃ'=>'w','Ẃ'=>'W','ẁ'=>'w','Ẁ'=>'W','ŵ'=>'w','Ŵ'=>'W','ẅ'=>'w','Ẅ'=>'W','ý'=>'y','Ý'=>'Y','ỳ'=>'y','Ỳ'=>'Y','ŷ'=>'y','Ŷ'=>'Y','ÿ'=>'y','Ÿ'=>'Y','ź'=>'z','Ź'=>'Z','ž'=>'z','Ž'=>'Z','ż'=>'z','Ż'=>'Z','þ'=>'th','Þ'=>'Th','µ'=>'u','а'=>'a','А'=>'a','б'=>'b','Б'=>'b','в'=>'v','В'=>'v','г'=>'g','Г'=>'g','д'=>'d','Д'=>'d','е'=>'e','Е'=>'E','ё'=>'e','Ё'=>'E','ж'=>'zh','Ж'=>'zh','з'=>'z','З'=>'z','и'=>'i','И'=>'i','й'=>'j','Й'=>'j','к'=>'k','К'=>'k','л'=>'l','Л'=>'l','м'=>'m','М'=>'m','н'=>'n','Н'=>'n','о'=>'o','О'=>'o','п'=>'p','П'=>'p','р'=>'r','Р'=>'r','с'=>'s','С'=>'s','т'=>'t','Т'=>'t','у'=>'u','У'=>'u','ф'=>'f','Ф'=>'f','х'=>'h','Х'=>'h','ц'=>'c','Ц'=>'c','ч'=>'ch','Ч'=>'ch','ш'=>'sh','Ш'=>'sh','щ'=>'sch','Щ'=>'sch','ъ'=>'','Ъ'=>'','ы'=>'y','Ы'=>'y','ь'=>'','Ь'=>'','э'=>'e','Э'=>'e','ю'=>'ju','Ю'=>'ju','я'=>'ja','Я'=>'ja',
        ];
        $result=$input;
        if($html_entities_decode) {
            $result=html_entity_decode($result,ENT_QUOTES | ENT_HTML5);
        }
        if($replace_diacritics) {
            $result=str_replace(array_keys($diacritics),array_values($diacritics),$result);
        }
        if($trim) {
            $result=trim($result);
        }
        return $result;
    }//END public static function normalizeString

    /**
     * Convert string from unknown character set to UTF-8
     *
     * @param string $value The string to be converted
     * @return     string Returns the converted string
     * @throws \Exception
     */
    public static function utf8Encode($value) {
        if(!function_exists('mb_detect_encoding')) {
            throw new Exception('Function mb_detect_encoding() not found!');
        }
        $enc=mb_detect_encoding($value,mb_detect_order(),TRUE);
        if(strtoupper($enc)=='UTF-8' || !function_exists('iconv')) {
            return $value;
        }
        return iconv($enc,'UTF-8',$value);
    }//END public static function utf8Encode

    /**
     * Returns a string containing a formatted number
     *
     * @param float  $value  The number to be formatted
     * @param string $format The format string in NETopes style
     *                       (NETopes format: "[number of decimals]|[decimal separator|[group separator]|[sufix]"
     * @return string Returns the formatted number or NULL in case of errors
     */
    public static function numberFormat(float $value,string $format='0|||'): ?string {
        if(!strlen($format)) {
            return NULL;
        }
        $f_arr=explode('|',$format);
        if(!is_array($f_arr) || count($f_arr)!=4) {
            return NULL;
        }
        return number_format($value,$f_arr[0],$f_arr[1],$f_arr[2]).$f_arr[3];
    }//END public static function numberFormat

    /**
     * Convert number (integer part) to words representation
     *
     * @param float  $value    Number to be converted (only integer part will be processed)
     * @param string $langcode Language code
     * @return null|string
     */
    public static function convertNumberToWords($value,$langcode) {
        if(!is_numeric($value) || !is_string($langcode) || !strlen($langcode)) {
            return NULL;
        }
        $words_list=[
            'en'=>[
                'and'=>'-',
                '0-20'=>['0'=>'zero','1'=>'one','2'=>'two','2f'=>'two','3'=>'three','4'=>'four','5'=>'five','6'=>'six','7'=>'seven','8'=>'eight','9'=>'nine','10'=>'ten','11'=>'eleven','12'=>'twelve','13'=>'thirteen','14'=>'fourteen','15'=>'fifteen','16'=>'sixteen','17'=>'seventeen','18'=>'eighteen','19'=>'nineteen'],
                '2z-9z'=>['2'=>'twentieth','3'=>'thirtieth','4'=>'forty','5'=>'fifty','6'=>'sixty','7'=>'seventy','8'=>'eighty','9'=>'ninety'],
                'um'=>['c'=>'hundred','oc'=>'one hundred','2'=>'thousand','o2'=>'one thousand','3'=>'million','o3'=>'one million','4'=>'billion','o4'=>'one billion'],
            ],
            'ro'=>[
                'and'=>' și ',
                '0-20'=>['0'=>'zero','1'=>'unu','2'=>'doi','2f'=>'două','3'=>'trei','4'=>'patru','5'=>'cinci','6'=>'șase','7'=>'șapte','8'=>'opt','9'=>'nouă','10'=>'zece','11'=>'unsprezece','12'=>'doisprezece','13'=>'treisprezece','14'=>'paisprezece','15'=>'cincisprezece','16'=>'șaisprezece','17'=>'șaptesprezece','18'=>'optsprezece','19'=>'nouăsprezece'],
                '2z-9z'=>['2'=>'douăzeci','3'=>'treizeci','4'=>'patruzeci','5'=>'cincizeci','6'=>'șaizeci','7'=>'șaptezeci','8'=>'optzeci','9'=>'nouăzeci'],
                'um'=>['c'=>'sute','oc'=>'o sută','2'=>'mii','o2'=>'o mie','3'=>'milioane','o3'=>'un milion','4'=>'miliarde','o4'=>'un miliard'],
            ],
        ];
        if(!array_key_exists($langcode,$words_list)) {
            return NULL;
        }
        $value=intval($value);
        if($value==0) {
            return $words_list[$langcode]['0-20']['0'];
        }
        $words=[];
        $groups=str_split(str_pad($value,ceil(strlen($value) / 3) * 3,'0',STR_PAD_LEFT),3);
        foreach($groups as $k=>$group) {
            if($group=='000') {
                continue;
            }
            if($group=='001') {
                if((count($groups) - $k)==1) {
                    $words[]=$words_list[$langcode]['0-20']['1'];
                } else {
                    $words[]=$words_list[$langcode]['um']['o'.(count($groups) - $k)];
                }//if((count($groups)-$k)==1)
            } elseif($group=='002') {
                if((count($groups) - $k)==1) {
                    $words[]=$words_list[$langcode]['0-20']['2'];
                } else {
                    $words[]=$words_list[$langcode]['0-20']['2f'].' '.$words_list[$langcode]['um'][(count($groups) - $k)];
                }//if((count($groups)-$k)==1)
            } else {
                $val=str_split($group);
                if($val[0]!='0') {
                    if($val[0]==1) {
                        $words[]=$words_list[$langcode]['um']['oc'];
                    } elseif($val[0]==2) {
                        $words[]=$words_list[$langcode]['0-20']['2f'].' '.$words_list[$langcode]['um']['c'];
                    } else {
                        $words[]=$words_list[$langcode]['0-20'][$val[0]].' '.$words_list[$langcode]['um']['c'];
                    }//if($val[0]==1)
                }//if($val[0]!='0')
                if($val[1]!='0') {
                    if($val[1]==1) {
                        $words[]=$words_list[$langcode]['0-20'][$val[1].$val[2]];
                    } else {
                        $tmpw=$words_list[$langcode]['2z-9z'][$val[1]];
                        if($val[2]>0) {
                            $tmpw.=$words_list[$langcode]['and'];
                            $tmpw.=$words_list[$langcode]['0-20'][$val[2]];
                        }//if($val[2]>0)
                        $words[]=$tmpw;
                    }//if($val[1]==1)
                } elseif($val[2]>0) {
                    $words[]=$words_list[$langcode]['0-20'][$val[2]];
                }//if($val[1]!='0')
                if((count($groups) - $k)!=1) {
                    $words[]=$words_list[$langcode]['um'][(count($groups) - $k)];
                }
            }//if($group=='001')
        }//END foreach
        return implode(' ',$words);
    }//END public static function convertNumberToWords

    /**
     * String explode public static function based on standard php explode function.
     * Explode on two levels to generate a table-like array.
     *
     * @param string $str  The string to be exploded.
     * @param string $rsep The string used as row separator.
     * @param string $csep The string used as column separator.
     * @param string $ksep The string used as column key-value separator.
     * @param int    $keys_case
     * @return  array The exploded multi-level array.
     */
    public static function explodeToTable($str,$rsep='|:|',$csep=']#[',$ksep=NULL,$keys_case=CASE_LOWER) {
        $result=[];
        if(!is_string($str) || !strlen($str) || !is_string($rsep) || !strlen($rsep)
            || (isset($csep) && (!is_string($csep) || !strlen($csep)))
        ) {
            return $result;
        }
        foreach(explode($rsep,$str) as $row) {
            if(!strlen($row)) {
                continue;
            }
            if(!$csep) {
                $result[]=$row;
                continue;
            }//if(!$csep)
            $r_arr=[];
            foreach(explode($csep,$row) as $col) {
                if(!strlen($col)) {
                    continue;
                }
                if(!is_string($ksep) || !strlen($ksep)) {
                    $r_arr[]=$col;
                    continue;
                }//if(!is_string($ksep) || !strlen($ksep))
                $c_kv=explode($ksep,$col);
                if(count($c_kv)!=2) {
                    $r_arr[]=$col;
                } else {
                    if(is_numeric($keys_case)) {
                        $r_arr[($keys_case==CASE_UPPER ? strtoupper($c_kv[0]) : strtolower($c_kv[0]))]=$c_kv[1];
                    } else {
                        $r_arr[$c_kv[0]]=$c_kv[1];
                    }//if(is_numeric($keys_case))
                }//if(count($c_kv)!=2)
            }//END foreach
            $result[]=$r_arr;
        }//END foreach
        return $result;
    }//END public static function explodeToTable

    /**
     * Array merge with overwrite option (the 2 input arrays remains untouched).
     * The second array will overwrite the first.
     *
     * @param array $arr1      First array to merge
     * @param array $arr2      Second array to merge
     * @param bool  $overwrite Overwrite sitch: TRUE with overwrite (default), FALSE without overwrite
     * @param array $initial_arr2
     * @return  array|bool Returns the merged array or FALSE if one of the arr arguments is not an array
     */
    public static function customArrayMerge($arr1,$arr2,$overwrite=TRUE,$initial_arr2=NULL) {
        if(!is_array($arr1) || !is_array($arr2)) {
            return NULL;
        }
        if(!is_array($arr1)) {
            return $arr2;
        }
        if(!is_array($arr2)) {
            return $arr1;
        }
        $result=$arr1;
        foreach($arr2 as $k=>$v) {
            $i_arr=is_array($initial_arr2) && array_key_exists($k,$initial_arr2) ? $initial_arr2[$k] : NULL;
            if($i_arr && $v===$i_arr) {
                continue;
            }
            if(array_key_exists($k,$result)) {
                if(is_array($result[$k]) && is_array($v)) {
                    $result[$k]=self::customArrayMerge($result[$k],$v,$overwrite,$i_arr);
                } else {
                    if($overwrite===TRUE) {
                        $result[$k]=$v;
                    }
                }//if(is_array($result[$k]) && is_array($v))
            } else {
                $result[$k]=$v;
            }//if(array_key_exists($k,$result))
        }//END foreach
        if(is_array($initial_arr2) && count($initial_arr2)) {
            foreach(array_diff_key($initial_arr2,$arr2) as $k=>$v) {
                unset($result[$k]);
            }
        }//if(is_array($initial_arr2) && count($initial_arr2))
        return $result;
    }//END public static function customArrayMerge

    /**
     * Read a CSV file and convert content to an associative array
     *
     * @param string     $file        Input file full name (including path)
     * @param int        $line_length Line length in characters (default 1000)
     * @param bool|array $with_header If TRUE first row will be considered table header, if FALSE no header
     *                                or an array containing table header
     * @param string     $delimiter   Delimiter character (default [,])
     * @param string     $enclosure   Enclosure character (default ["])
     * @return null|array Returns resulted data array or NULL on error
     */
    public static function csvFileToArray($file,$line_length=1000,$with_header=TRUE,$delimiter=',',$enclosure='"') {
        if(!is_string($file) || !strlen($file) || !file_exists($file)) {
            return NULL;
        }
        if(($handle=fopen($file,"r"))===FALSE) {
            return NULL;
        }
        $result=[];
        $header=is_array($with_header) ? $with_header : [];
        while(($row=fgetcsv($handle,$line_length,$delimiter,$enclosure))!==FALSE) {
            if($with_header===TRUE) {
                if(!count($header)) {
                    $header=$row;
                } else {
                    $drow=[];
                    foreach($header as $i=>$k) {
                        $drow[$k]=count($row)>=($i + 1) ? $row[$i] : NULL;
                    }
                    $result[]=$drow;
                }//if(!count($header))
            } else {
                $result[]=$row;
            }//if($with_header===TRUE)
        }//END while
        fclose($handle);
        return $result;
    }//END public static function csvFileToArray

    /**
     * @param array|null $data
     * @return string|null
     * @throws \Exception
     */
    public static function array2csv(?array $data): ?string {
        if(!is_array($data) || !count($data)) {
            return NULL;
        }
        $f=fopen('php://memory','r+');
        foreach($data as $row) {
            if(!is_array($row) || !count($row)) {
                throw new Exception('Invalid row data (empty or not an array)');
            }
            if(fputcsv($f,$row)===FALSE) {
                throw new Exception('Unable to convert row to CSV: '.print_r($row,1));
            }
        }//END foreach
        rewind($f);
        $result=stream_get_contents($f);
        return rtrim($result);
    }//END public static function array2csv

    /**
     * description
     *
     * @param array|null $input
     * @param bool       $recursive
     * @param int        $case
     * @return array|null
     */
    public static function changeArrayValuesCase(?array $input,bool $recursive=FALSE,int $case=CASE_LOWER): ?array {
        if(is_null($input)) {
            return NULL;
        }
        if(!in_array($case,[CASE_LOWER,CASE_UPPER])) {
            return $input;
        }
        $result=[];
        foreach($input as $k=>$v) {
            if(is_array($v) && $recursive===TRUE) {
                $result[$k]=self::changeArrayValuesCase($v,TRUE,CASE_LOWER);
            } else {
                $result[$k]=is_string($v) ? ($case==CASE_UPPER ? strtoupper($v) : strtolower($v)) : $v;
            }//if(is_array($v))
        }//foreach ($input as $k=>$v)
        return $result;
    }//END public static function changeArrayValuesCase

    /**
     * description
     *
     * @param      $array
     * @param      $structure
     * @param null $uppercasekeys
     * @return array|null
     */
    public static function convertDbArrayToTree($array,$structure,$uppercasekeys=NULL) {
        if(!is_array($array) || count($array)==0) {
            return NULL;
        }
        if((!is_array($structure) && strlen($structure)==0) || (is_array($structure) && count($structure)==0)) {
            return $array;
        }
        $lstructure=$structure;
        if(!is_array($structure)) {
            $lstructure=[$structure];
        }
        $valkey=array_pop($lstructure);
        if(count($lstructure)>0) {
            $keystr='';
            if($uppercasekeys===TRUE) {
                foreach($lstructure as $key) {
                    $keystr.='[strtoupper($row["'.strtoupper($key).'"])]';
                }
            } elseif($uppercasekeys===FALSE) {
                foreach($lstructure as $key) {
                    $keystr.='[strtolower($row["'.strtolower($key).'"])]';
                }
            } else {
                foreach($lstructure as $key) {
                    $keystr.='[$row["'.$key.'"]]';
                }
            }//if($uppercasekeys===TRUE)
        } else {
            $keystr='[]';
        }//if(count($lstructure)>0)
        $result=[];
        foreach($array as $row) {
            eval('$result'.$keystr.' = $row["'.$valkey.'"];');
        }
        return $result;
    }//END public static function convertDbArrayToTree

    /**
     * description
     *
     * @param      $array
     * @param bool $return
     * @return array|null
     */
    public static function customShuffle(&$array,$return=TRUE) {
        if(!is_array($array)) {
            if($return) {
                return $array;
            } else {
                return NULL;
            }
        }
        $keys=array_keys($array);
        shuffle($keys);
        $random=[];
        foreach($keys as $key) {
            $random[$key]=$array[$key];
        }
        if($return) {
            return $random;
        }
        $array=$random;
        return $array;
    }//public static function customShuffle

    /**
     * @param string|\DateTime $startDate
     * @param string|\DateTime $endDate
     * @param string           $returnType
     * @return \DateInterval|float|int|null
     * @throws \NETopes\Core\AppException
     */
    public static function getDateDiff($startDate,$endDate,string $returnType) {
        $sDt=Validator::ValidateValue($startDate,NULL,'is_datetime');
        $eDt=Validator::ValidateValue($endDate,NULL,'is_datetime');
        if(!$sDt || !$eDt) {
            return NULL;
        }
        /** @var \DateInterval $di */
        $di=$eDt->diff($sDt);
        switch(strtolower($returnType)) {
            case 'd':
            case 'day':
                return $di->days;
            case 'h':
            case 'hour':
                return ($di->days * 24 + $di->h);
            case 'm':
            case 'minute':
                return (($di->days * 24 + $di->h) * 60 + $di->i);
            case 's':
            case 'second':
                return ((($di->days * 24 + $di->h) * 60 + $di->i) * 60 + $di->s);
            case 'ms':
            case 'microsecond':
                return ((($di->days * 24 + $di->h) * 60 + $di->i) * 60 + $di->s + $di->f);
            default:
                return $di;
        }//END switch
    }//END public static function getDateDiff
}//END class DataHelpers