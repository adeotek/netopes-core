<?php
namespace NETopes\Core\Data\Doctrine;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use NApp;
use NETopes\Core\AppException;

/**
 * Trait RepositoryStandardTrait
 *
 * @package NETopes\Core\Data
 */
trait RepositoryStandardTrait {
    use RepositoryBaseTrait;

    /**
     * @param array $params
     * @return array|null
     * @throws \NETopes\Core\AppException
     */
    public function findFiltered(?array $params=[]): ?array {
        try {
            $sQuery=get_array_value($params,'query',NULL,'is_object');
            if($sQuery instanceof Query) {
                return $sQuery->getResult();
            }
            $firstRow=get_array_value($params,'first_row',0,'is_integer');
            $lastRow=get_array_value($params,'last_row',0,'is_integer');
            $sort=get_array_value($params,'sort',[],'is_array');
            $relations=get_array_value($params,'relations',[],'is_array');
            $filters=get_array_value($params,'filters',[],'is_array');
            $tCount=0;
            /** @var QueryBuilder $qb */
            $qb=$this->createQueryBuilder('e');
            if(count($relations)) {
                foreach($relations as $k=>$r) {
                    if(!is_string($r) || !strlen($r) || !is_string($k) || !strlen($k)) {
                        continue;
                    }
                    if(strpos($r,'.')!==FALSE) {
                        $qb->leftJoin($r,$k);
                    } else {
                        $qb->leftJoin('e.'.$r,$k);
                    }//if(strpos($r,'.')!==FALSE)
                }//END foreach
            }//if(count($relations))
            if(count($filters)) {
                $groupedFilters=array_group_by_hierarchical('group_id',$filters,TRUE,'_');
                // NApp::Dlog($groupedFilters,'$groupedFilters');
                $parameters=[];
                $expressions=$this->processQueryFilters($qb,$groupedFilters,$parameters);
                // NApp::Dlog($expressions,'$expressions');
                if($expressions) {
                    $qb->where($expressions);
                    if(count($parameters)) {
                        $qb->setParameters($parameters);
                    }
                    // NApp::Dlog($qb->getQuery()->getSql(),'findFiltered>>SQL');
                    // NApp::Dlog($qb->getParameters()->toArray(),'findFiltered>>parameters');
                }
            }//if(count($filters))
            if($firstRow>0 && $lastRow>0) {
                $qb->select('count(e)');
                $tCount=$qb->getQuery()->getSingleScalarResult();
            }//if($firstRow>0 && $lastRow>0)
            if(count($sort)) {
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
            }//if(count($sort))
            if($firstRow>0) {
                if($lastRow>0) {
                    $qb->setFirstResult($firstRow - 1);
                    $qb->setMaxResults(($lastRow - $firstRow + 1));
                } else {
                    $qb->setMaxResults($firstRow);
                }//if($lastrow>0)
            }//if($firstrow>0)
            $qb->select('e');
            if(count($relations)) {
                foreach($relations as $k=>$r) {
                    if(!is_string($r) || !strlen($r) || !is_string($k) || !strlen($k)) {
                        continue;
                    }
                    $qb->addSelect($k);
                }//END foreach
            }//if(count($relations))
            $data=$qb->getQuery()->getResult();
            if(get_array_value($params,'collection',FALSE,'bool')) {
                return $data;
            }
            return ['data'=>$data,'count'=>$tCount];
        } catch(QueryException $qe) {
            // \NApp::Dlog($qe->getTrace());
            throw new AppException('#'.get_class($qe).'# '.$qe->getMessage(),$qe->getCode(),1);
        } catch(ORMException $oe) {
            throw new AppException('#'.get_class($oe).'# '.$oe->getMessage(),$oe->getCode(),1);
        } catch(Exception $e) {
            throw new AppException($e->getMessage(),$e->getCode(),1);
        }//END try
    }//END public function findFiltered
}//END trait RepositoryStandardTrait