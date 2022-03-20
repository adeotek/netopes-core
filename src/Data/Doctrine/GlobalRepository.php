<?php
/**
 * Global Doctrine repository class
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;
use Doctrine\ORM\ORMException;
use NETopes\Core\AppException;
use NETopes\Core\Data\DataSource;

/**
 * GlobalRepository class
 */
class GlobalRepository extends DataSource {

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function __call($name,$arguments) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $repository=$this->adapter->em->getRepository($this->entityName);
        if(!strlen($name) || !method_exists($repository,$name)) {
            throw new AppException('Invalid repository name ['.$name.'] for entity ['.$this->entityName.']!');
        }//if(!strlen($name) || !method_exists($repository,$name))
        $params=is_array($arguments) ? array_shift($arguments) : [];
        return $repository->$name(...array_values($params));
    }//END public function __call

    /**
     * Execute a repository method and get returned data
     *
     * @param array $params
     * @param array $extra_params
     * @return
     * @throws \NETopes\Core\AppException
     */
    public function ExecRepositoryMethod($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $method=get_array_value($extra_params,'method','','is_string');
        $repository=$this->adapter->em->getRepository($this->entityName);
        if(!strlen($method) || !method_exists($repository,$method)) {
            throw new AppException('Invalid repository name ['.$method.'] for entity ['.$this->entityName.']!');
        }//if(!strlen($method) || !method_exists($repository,$method))
        return $repository->$method(...$params);
    }//END public function ExecRepositoryMethod

    /**
     * Execute a repository method and get returned data
     *
     * @param array $params
     * @param array $extra_params
     * @return
     * @throws \NETopes\Core\AppException
     */
    public function GetFromRepositoryMethod($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $method=get_array_value($extra_params,'method','','is_string');
        $repository=$this->adapter->em->getRepository($this->entityName);
        if(!strlen($method) || !method_exists($repository,$method)) {
            throw new AppException('Invalid repository name ['.$method.'] for entity ['.$this->entityName.']!');
        }//if(!strlen($method) || !method_exists($repository,$method))
        return $repository->$method($params,$extra_params);
    }//END public function GetFromRepositoryMethod

    /**
     * Gets new project blank object
     *
     * @param array $params
     * @param array $extra_params
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function CreateItem($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $item=new $this->entityName();
        return $item;
    }//END public function CreateItem

    /**
     * Gets projects list
     *
     * @param array $params
     * @param array $extra_params
     * @return
     * @throws \NETopes\Core\AppException
     */
    public function GetItems($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        if(!is_array($extra_params)) {
            $extra_params=[];
        }
        if(!isset($extra_params['filters']) || !is_array($extra_params['filters'])) {
            $extra_params['filters']=[];
        }
        if(is_array($params) && count($params)) {
            foreach($params as $pn=>$pv) {
                switch($pn) {
                    case 'for_text':
                        if(is_string($pv) && strlen($pv)) {
                            $fieldsList=get_array_value($extra_params,'qs_fields',[],'is_array');
                            $extra_params['filters'][]=['field'=>(count($fieldsList) ? $fieldsList : ['name']),'condition_type'=>'like','value'=>$pv];
                        }
                        break;
                    default:
                        if(isset($pv) && is_scalar($pv)) {
                            $extra_params['filters'][]=['field'=>$pn,'condition_type'=>'==','value'=>$pv];
                        }
                        break;
                }//END switch
            }//END foreach
        }//if(is_array($params) && count($params))
        return $this->adapter->em->getRepository($this->entityName)->findFiltered($extra_params);
    }//END public function GetItems

    /**
     * Gets projects list
     *
     * @param array $params
     * @param array $extra_params
     * @return
     * @throws \NETopes\Core\AppException
     */
    public function GetObjects($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $result=$this->adapter->em->getRepository($this->entityName)->findBy($params);
        return $result;
    }//END public function GetObjects

    /**
     * Gets projects list
     *
     * @param array $params
     * @param array $extra_params
     * @return
     * @throws \NETopes\Core\AppException
     */
    public function GetItem($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $id=get_array_value($params,'for_id',NULL,'is_integer');
        if(!$id) {
            throw new AppException('Invalid record identifier!');
        }
        $obj=$this->adapter->em->getRepository($this->entityName)->find($id);
        return $obj;
    }//END public function GetItem

    /**
     * Gets projects list
     *
     * @param array $params
     * @param array $extra_params
     * @return
     * @throws \NETopes\Core\AppException
     */
    public function Search($params=[],$extra_params=[]) {
        if(!strlen($this->entityName) || !class_exists($this->entityName)) {
            throw new AppException('Invalid entity ['.$this->entityName.']!');
        }
        $term=get_array_value($extra_params,'text','','is_string');
        $targets=get_array_value($extra_params,'targets',[],'is_array');
        $filters=get_array_value($extra_params,'filters',[],'is_array');
        $maxrows=get_array_value($extra_params,'maxrows',NULL,'is_integer');
        $obj=$this->adapter->em->getRepository($this->entityName)->getSearchResults($term,$targets,$filters,$maxrows);
        return $obj;
    }//END public function GetItem

    /**
     * Sets new project
     *
     * @param array $params
     * @param array $extra_params
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public function SetNewItem($params=[],$extra_params=[]) {
        return $this->SetItem($params,$extra_params);
    }//END public function SetItem

    /**
     * Sets new project
     *
     * @param array $params
     * @param array $extra_params
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public function SetItem($params=[],$extra_params=[]) {
        if(!is_object($params)) {
            throw new AppException('Invalid entity instance!');
        }
        $exceptFlush=get_array_value($extra_params,'transaction',FALSE,'bool');
        try {
            $this->adapter->em->persist($params);
            if(!$exceptFlush) {
                $this->adapter->em->flush();
            }
        } catch(ORMException $e) {
            throw new AppException($e->getMessage(),$e->getCode(),1,$e->getFile(),$e->getLine());
        }//END try
        return $params;
    }//END public function GetNewItem

    /**
     * Unsets a project
     *
     * @param array $params
     * @param array $extra_params
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public function UnsetItem($params=[],$extra_params=[]) {
        if(is_array($params)) {
            if(!strlen($this->entityName) || !class_exists($this->entityName)) {
                throw new AppException('Invalid entity ['.$this->entityName.']!');
            }
            $id=get_array_value($params,'for_id',0,'is_integer');
            if(!$id) {
                throw new AppException('Invalid record identifier!');
            }
            $obj=$this->adapter->em->getRepository($this->entityName)->find($id);
        } else {
            $obj=$params;
        }//if(is_array($params))
        if(!is_object($obj)) {
            throw new AppException('Invalid entity instance!');
        }
        $exceptFlush=get_array_value($extra_params,'transaction',FALSE,'bool');
        try {
            $this->adapter->em->remove($obj);
            if(!$exceptFlush) {
                $this->adapter->em->flush();
            }
        } catch(ORMException $e) {
            throw new AppException($e->getMessage(),$e->getCode(),1,$e->getFile(),$e->getLine());
        }//END try
        return TRUE;
    }//END public function UnsetItem
}//END class DataSource extends \NETopes\Core\Data\DataSource