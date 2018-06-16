<?php
/**
 * Base Doctrine data source file
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use PAF\AppException;

/**
 * Base Base Doctrine data adapter class
 *
 * @package  NETopes\Core\Data
 * @access   public
 */
class DoctrineDataSource extends DataSource {
	/**
	 * Gets new project blank object
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function CreateItem($params = [],$extra_params = []) {
		if(!strlen($this->entityName) || !class_exists($this->entityName)) { throw new AppException('Invalid entity ['.$this->entityName.']!'); }
		$project = new $this->entityName();
		return $project;
	}//END public function CreateItem
	/**
	 * Gets projects list
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function GetItems($params = [],$extra_params = []) {
		if(!strlen($this->entityName) || !class_exists($this->entityName)) { throw new AppException('Invalid entity ['.$this->entityName.']!'); }
		if(!is_array($extra_params)) { $extra_params = []; }
		if(!isset($extra_params['filters']) || !is_array($extra_params['filters'])) { $extra_params['filters'] = []; }
		$qsearch = get_array_param($params,'for_text','','is_string');
		if(!strlen($qsearch) || strtolower($qsearch)!=='null') { $extra_params['filters'][] = ['field'=>['name','comments'],'condition_type'=>'contains','value'=>$qsearch]; }
		$result = $this->adapter->em->getRepository($this->entityName)->findFiltered($extra_params);
		return $result;
	}//END public function GetItems
	/**
	 * Gets projects list
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function GetObjects($params = [],$extra_params = []) {
		if(!strlen($this->entityName) || !class_exists($this->entityName)) { throw new AppException('Invalid entity ['.$this->entityName.']!'); }
		$result = $this->adapter->em->getRepository($this->entityName)->findBy($params);
		return $result;
	}//END public function GetObjects
	/**
	 * Gets projects list
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function GetItem($params = [],$extra_params = []) {
		if(!strlen($this->entityName) || !class_exists($this->entityName)) { throw new AppException('Invalid entity ['.$this->entityName.']!'); }
		$id = get_array_param($params,'for_id','null','is_integer');
		if(!$id) { throw new AppException('Invalid record identifier!'); }
		$obj = $this->adapter->em->getRepository($this->entityName)->find($id);
		return $obj;
	}//END public function GetItem
	/**
	 * Gets projects list
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function Search($params = [],$extra_params = []) {
		if(!strlen($this->entityName) || !class_exists($this->entityName)) { throw new AppException('Invalid entity ['.$this->entityName.']!'); }
		$term = get_array_param($extra_params,'text','','is_string');
		$targets = get_array_param($extra_params,'targets',[],'is_array');
		$filters = get_array_param($extra_params,'filters',[],'is_array');
		$maxrows = get_array_param($extra_params,'maxrows',NULL,'is_integer');
		$obj = $this->adapter->em->getRepository($this->entityName)->getSearchResults($term,$targets,$filters,$maxrows);
		return $obj;
	}//END public function GetItem
	/**
	 * Sets new project
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function SetItem($params = [],$extra_params = []) {
		if(!is_object($params)) { throw new AppException('Invalid entity instance!'); }
		$exceptFlush = get_array_param($extra_params,'transaction',FALSE,'bool');
		try {
			$this->adapter->em->persist($params);
			if(!$exceptFlush) { $this->adapter->em->flush(); }
		} catch(\Doctrine\ORM\ORMException $e) {
			throw new AppException($e->getMessage(),$e->getCode(),1,$e->getFile(),$e->getLine());
		}//END try
		return $params;
	}//END public function SetItem
	/**
	 * Sets new project
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function SetNewItem($params = [],$extra_params = []) {
		return $this->SetItem($params,$extra_params);
	}//END public function GetNewItem
	/**
	 * Unsets a project
	 *
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function UnsetItem($params = [],$extra_params = []) {
		if(is_array($params)) {
			if(!strlen($this->entityName) || !class_exists($this->entityName)) { throw new AppException('Invalid entity ['.$this->entityName.']!'); }
			$id = get_array_param($params,'for_id',0,'is_integer');
			if(!$id) { throw new AppException('Invalid record identifier!'); }
			$obj = $this->adapter->em->getRepository($this->entityName)->find($id);
		} else {
			$obj = $params;
		}//if(is_array($params))
		if(!is_object($obj)) { throw new AppException('Invalid entity instance!'); }
		$exceptFlush = get_array_param($extra_params,'transaction',FALSE,'bool');
		try {
            $this->adapter->em->remove($obj);
			if(!$exceptFlush) { $this->adapter->em->flush(); }
		} catch(\Doctrine\ORM\ORMException $e) {
			throw new AppException($e->getMessage(),$e->getCode(),1,$e->getFile(),$e->getLine());
		}//END try
		return TRUE;
	}//END public function UnsetItem
}//END class System extends DataSource
?>