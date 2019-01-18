<?php
/**
 * NETopes data source helpers class file
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Data;

/**
 * Class DataSourceHelpers
 *
 * @package NETopes\Core\Data
 */
class DataSourceHelpers {
    /**
     * Replaces the keys (first level only) of the $results array
     * with the values of a specified key in the second level of the array
     * Obs. The $results array is usualy an results array from a database query.
     *
     * @param  array  $results The array to be converted
     * @param  string $keyField The key for the second level value to be
     * set as the new main key.
     * If $keyfield is empty or NULL, the 'id' key will be used.
     * @param bool    $convertToDataSet
     * @param int|null $case
     * @return array Returns the converted array
     * @access public
     * @static
     */
	public static function ConvertResultsToKeyValue($results,?string $keyField = NULL,bool $convertToDataSet = FALSE,?int $case = NULL) {
	    if(!is_iterable($results)) { return $results; }
	    $key = strlen($keyField) ? $keyField : 'id';
	    if(is_object($results)) {
            $tempResults = new DataSet();
            foreach($results as $v) {
                if(is_object($v)) {
                    $tempResults->set(change_case($v->getProperty($key),$case),$v);
                } else {
                    $tempResults->set(change_case(get_array_value($v,$key,NULL,'isset'),$case),$v);
                }//if(is_object($v))
            }//END foreach
	    } else {
	        $tempResults = [];
            foreach($results as $v) {
                if(is_object($v)) {
                    $tempResults[change_case($v->getProperty($key),$case)] = $v;
                } else {
                    $tempResults[change_case(get_array_value($v,$key,NULL,'isset'),$case)] = $v;
                }//if(is_object($v))
            }//END foreach
            if($convertToDataSet) { $tempResults = new DataSet($tempResults); }
	    }//if(is_object($results))
		return $tempResults;
	}//END public static function ConvertResultsToKeyValue
    /**
     * Convert array to a DataSet of entities or row arrays
     *
     * @param  array       $data The array to be converted
     * @param  string|null $entityClass Name of the entity class
     * @param null|string  $fieldToUseAsKey
     * @param int          $case
     * @return mixed Returns the DataSet or NULL on error
     * @access public
     * @static
     */
	public static function ConvertArrayToDataSet($data = [],$entityClass = NULL,?string $fieldToUseAsKey = NULL,?int $case = CASE_LOWER) {
		if(!is_array($data)) {
		    if($fieldToUseAsKey===NULL) { return $data; }
		    return static::ConvertResultsToKeyValue($data,$fieldToUseAsKey,FALSE,$case);
		}//if(!is_array($data))
		if(!is_string($entityClass) || !strlen($entityClass) || !class_exists($entityClass)) {
		    $result = static::ConvertResultsToKeyValue($data,$fieldToUseAsKey,TRUE,$case);
		} else {
			if(count($data)) {
			    $fElement = reset($data);
                if(is_null($fElement) || is_scalar($fElement)) {
                    $result = new $entityClass($data);
                } else {
					if(is_object($fElement)) {
					    if(isset($fieldToUseAsKey)) {
				        	$result = static::ConvertResultsToKeyValue($data,$fieldToUseAsKey,TRUE,$case);
					    } else {
					        $result = new DataSet($data);
					    }//if(isset($fieldToUseAsKey))
			        } else {
				        $result = new DataSet();
				        foreach($data as $k=>$v) {
				            if(strlen($fieldToUseAsKey)) {
				                $result->set(change_case(get_array_value($v,$fieldToUseAsKey,$k,'isset'),$case),new $entityClass($v));
				            } else {
				                $result->set(change_case($k,$case),new $entityClass($v));
				            }//if(strlen($fieldToUseAsKey))
				        }//END foreach
					}//if(is_object($fElement))
				}//if(is_scalar($fElement))
			} else {
				$result = new DataSet([]);
			}//if(count($data))
		}//if(!is_string($entityClass) || !strlen($entityClass) || !class_exists($entityClass))
		return $result;
	}//END public static function ConvertArrayToDataSet
	/**
	 * Convert results array to a DataSet of entities or row arrays
	 *
	 * @param  array $results The array to be converted
	 * @param  string|null $entityClass Name of the entity class
	 * @return mixed Returns the DataSet or unprocessed data if input is not an array
	 * @access public
	 * @static
	 */
	public static function ConvertResultsToDataSet($results = [],$entityClass = NULL) {
		if(!is_array($results)) { return $results; }
		if(array_key_exists('data',$results)) {
			if(!is_array($results['data'])) {
				$result = self::ConvertArrayToDataSet([],$entityClass);
			} else {
				$result = self::ConvertArrayToDataSet($results['data'],$entityClass);
			}//if(!is_array($results['data']))
			if(isset($results['count']) && $results['count']>=0) { $result->setTotalCount($results['count']); }
		} else {
			$result = self::ConvertArrayToDataSet($results,$entityClass);
		}//if(isset($results['data']) && is_array($results['data']))
		return $result;
	}//END public static function ConvertResultsToDataSet
}//END class DataSourceHelpers