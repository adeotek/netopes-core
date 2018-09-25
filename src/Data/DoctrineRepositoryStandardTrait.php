<?php
namespace NETopes\Core\Data;
use Doctrine\ORM\Query;
use PAF\AppException;
/**
 * Trait DoctrineRepositoryStandardTrait
 *
 * @package NETopes\Core\Data
 */
trait DoctrineRepositoryStandardTrait {
	use DoctrineRepositoryBaseTrait;
	/**
	 * @param array  $params
	 * @return array|null
	 * @throws \PAF\AppException
	 */
	public function findFiltered(?array $params = []): ?array {
        try {
            $s_query = get_array_value($params,'query',NULL,'is_object');
			if($s_query instanceof Query) { return $s_query->getResult(); }
			$firstrow = get_array_value($params,'firstrow',0,'is_integer');
	        $lastrow = get_array_value($params,'lastrow',0,'is_integer');
	        $sort = get_array_value($params,'sort',[],'is_array');
	        $relations = get_array_value($params,'relations',[],'is_array');
	        $filters = get_array_value($params,'filters',[],'is_array');
			$tcount = 0;
			$qb = $this->createQueryBuilder('e');
			if(count($filters)) {
				foreach($filters as $k=>$f) {
					$field = get_array_value($f,'field',NULL,'isset');
                    $value = get_array_value($f,'value',NULL,'isset');
                    if(is_null($field) || is_null($value)) { continue; }
                    $operators = $this->getOperators();
                    $operator = get_array_value($operators,strtolower(get_array_value($f,'condition_type','==','is_string')),'','is_string');
                    if(!strlen($operator)) { continue; }
                    $logical_operator = get_array_value($f,'logical_separator','and','is_notempty_string');
                    if(is_array($field) && count($field)) {
                        $expression = $qb->expr()->orX();
                        foreach($field as $mfi) { $expression->add($qb->expr()->$operator('e.'.$mfi,':in'.$k.'_'.$mfi)); }
						if(strtolower($logical_operator)=='or') {
							$qb->orWhere($expression);
	                    } else {
							$qb->andWhere($expression);
	                    }//if($first)
	                    foreach($field as $mfi) {
							switch($operator) {
			                    case 'like':
			                    case 'notlike':
			                        $qb->setParameter('in'.$k.'_'.$mfi,'%'.$value.'%');
			                        break;
			                    case 'startsWith':
			                        $qb->setParameter('in'.$k.'_'.$mfi,$value.'%');
			                        break;
			                    case 'endWith':
			                        $qb->setParameter('in'.$k.'_'.$mfi,'%'.$value);
			                        break;
			                    default:
			                        $qb->setParameter('in'.$k.'_'.$mfi,$value);
			                        break;
		                    }//END switch
	                    }//END foreach
                    } elseif(is_string($field) && strlen($field)) {
						$expression = $qb->expr()->$operator('e.'.$field,':in'.$k.'_'.$field);
	                    if(strtolower($logical_operator)=='or') {
							$qb->orWhere($expression);
	                    } else {
							$qb->andWhere($expression);
	                    }//if($first)
	                    switch($operator) {
		                    case 'like':
		                    case 'notlike':
		                        $qb->setParameter('in'.$k.'_'.$field,'%'.$value.'%');
		                        break;
		                    case 'startsWith':
		                        $qb->setParameter('in'.$k.'_'.$field,$value.'%');
		                        break;
		                    case 'endWith':
		                        $qb->setParameter('in'.$k.'_'.$field,'%'.$value);
		                        break;
		                    default:
		                        $qb->setParameter('in'.$k.'_'.$field,$value);
		                        break;
	                    }//END switch
                    } else {
                        continue;
                    }//if(is_array($field) && count($field))
				}//END foreach
			}//if(count($filters))

			if($firstrow>0 && $lastrow>0) {
				$qb->select('count(e)');
				$stime = microtime(TRUE);
                $tcount = $qb->getQuery()->getSingleScalarResult();
                $this->DbDebug($qb->getQuery(),'findFiltered[count]',$stime);
			}//if($firstrow>0 && $lastrow>0)

	        if(count($sort)) {
	            $first = TRUE;
				foreach($sort as $c=>$d) {
					$field = 'e.'.strtolower(str_replace(['"',"'",'`','[',']'],'',$c));
					if($first) {
						$first = FALSE;
						$qb->orderBy($field,strtoupper($d));
					} else {
						$qb->addOrderBy($field,strtoupper($d));
					}//if($first)
				}//END foreach
	        }//if(count($sort))

	        if($firstrow>0) {
	            if($lastrow>0) {
	                $qb->setFirstResult($firstrow-1);
	                $qb->setMaxResults(($lastrow-$firstrow+1));
	            } else {
	                $qb->setMaxResults($firstrow);
	            }//if($lastrow>0)
	        }//if($firstrow>0)

			$qb->select('e');
			if(count($relations)) {
			    foreach($relations as $k=>$r) {
			        if(!is_string($r) || !strlen($r) || !is_string($k) || !strlen($k)) { continue; }
			        $qb->leftJoin('e.'.$r,$k);
                    $qb->addSelect($k);
                }//END foreach
			}//if(count($relations))

			$stime = microtime(TRUE);
			$data = $qb->getQuery()->getResult();
			$this->DbDebug($qb->getQuery(),'findFiltered',$stime);
			if(get_array_value($params,'collection',FALSE,'bool')) { return $data; }
			return ['data'=>$data,'count'=>$tcount];
		} catch(\Doctrine\ORM\Query\QueryException $qe) {
			// NApp::_Dlog($qe->getTrace());
			throw new AppException('#'.get_class($qe).'# '.$qe->getMessage(),$qe->getCode(),1);
		} catch(\Doctrine\ORM\ORMException $oe) {
			throw new AppException('#'.get_class($oe).'# '.$oe->getMessage(),$oe->getCode(),1);
        } catch(\Exception $e) {
            throw new AppException($e->getMessage(),$e->getCode(),1);
        }//END try
    }//END public function findFiltered
}//END trait DoctrineRepositoryStandardTrait
?>