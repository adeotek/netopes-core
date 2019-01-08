<?php
namespace NETopes\Core\Data;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
class DoctrineBaseRepository extends EntityRepository {
	use DoctrineRepositoryStandardTrait;
	public function getSearchResults(string $term,array $targets = [],array $params = [],int $rowNum = 10,?array $sort = NULL) {
        $qb = $this->createQueryBuilder('e');
		foreach($params as $pn=>$pv) {
            if(is_array($pv)) {
               foreach($pv as $pvc=>$pvv) {
                   $operators = $this->getOperators();
                   $operator = get_array_value($operators,strtolower($pvc),'','is_string');
                   if(!strlen($operator)) { continue; }
                   $qb->andWhere($qb->expr()->$operator('e.'.$pn,$pvv));
               }//END foreach
           } else {
               $qb->andWhere($qb->expr()->eq('e.'.$pn,$pv));
           }//if(is_array($pv))
        }//END foreach
        $qb = $this->wordsSearchConditionsGenerator($qb,$term,array_map(function($v){return 'e.'.$v;},$targets));
        if(is_array($sort) && count($sort)) {
            $first = TRUE;
            foreach($sort as $c=>$d) {
                $field = $this->getFieldName($c,'e');
                if($first) {
                    $first = FALSE;
                    $qb->orderBy($field,strtoupper($d));
                } else {
                    $qb->addOrderBy($field,strtoupper($d));
                }//if($first)
            }//END foreach
        }//if(is_array($sort) && count($sort))
        $qb->setMaxResults($rowNum);
        $this->DbDebug($qb->getQuery(),'getSearchResults');
        return $qb->getQuery()->getResult();
    }//END public function getSearchResults
}//END class DoctrineBaseRepository extends EntityRepository