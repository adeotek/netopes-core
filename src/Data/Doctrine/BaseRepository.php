<?php
/**
 * Doctrine base repository class
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;
use Doctrine\ORM\EntityRepository;
use NETopes\Core\AppException;

/**
 * BaseRepository class
 */
class BaseRepository extends EntityRepository {
    use RepositoryStandardTrait;

    /**
     * @param $id
     * @return object
     * @throws \NETopes\Core\AppException
     */
    public function findRecord($id) {
        if(!is_scalar($id)) {
            throw new AppException('Invalid PK search value for ['.$this->_entityName.']: '.print_r($id,1));
        }
        $entity=$this->find($id);
        if(!$entity instanceof $this->_entityName) {
            throw new AppException('Record not found for PK ['.$id.'] in ['.$this->_entityName.']');
        }
        return $entity;
    }//END public function findRecord

    /**
     * @param string     $term
     * @param array      $targets
     * @param array      $params
     * @param int        $rowNum
     * @param array|null $sort
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function getSearchResults(string $term,array $targets=[],array $params=[],int $rowNum=10,?array $sort=NULL) {
        $qb=$this->createQueryBuilder('e');
        foreach($params as $pn=>$pv) {
            if(is_array($pv)) {
                foreach($pv as $pvc=>$pvv) {
                    $operators=$this->getOperators();
                    $operator=get_array_value($operators,strtolower($pvc),'','is_string');
                    if(!strlen($operator)) {
                        continue;
                    }
                    $qb->andWhere($qb->expr()->$operator('e.'.$pn,$pvv));
                }//END foreach
            } else {
                $qb->andWhere($qb->expr()->eq('e.'.$pn,$pv));
            }//if(is_array($pv))
        }//END foreach
        $qb=$this->wordsSearchConditionsGenerator($qb,$term,array_map(function($v) { return 'e.'.$v; },$targets));
        if(is_array($sort) && count($sort)) {
            $first=TRUE;
            foreach($sort as $c=>$d) {
                $field=$this->getFieldName($c,'e');
                if($first) {
                    $first=FALSE;
                    $qb->orderBy($field,strtoupper($d));
                } else {
                    $qb->addOrderBy($field,strtoupper($d));
                }//if($first)
            }//END foreach
        }//if(is_array($sort) && count($sort))
        $qb->setMaxResults($rowNum);
        return $qb->getQuery()->getResult();
    }//END public function getSearchResults
}//END class BaseRepository extends EntityRepository