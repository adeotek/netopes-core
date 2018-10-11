<?php
namespace NETopes\Core\Data;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class DoctrineBaseRepository extends EntityRepository {
	use DoctrineRepositoryStandardTrait;

	public function getSearchResults(string $term,array $targets = [],array $params = [],int $rowNum = 10) {
        $qb = $this->createQueryBuilder('e');
		$words = str_word_count($term, 1, '1234567890');

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

        foreach($words as $k=>$word) {
            $xor = $qb->expr()->orX();
            foreach($targets as $target) {
                $xor->add($qb->expr()->like('e.'.$target,':in_'.$k));
            }//END foreach
            $qb->andWhere($xor);
            $qb->setParameter('in_'.$k,'%'.$word.'%');
        }//END foreach

        foreach($targets as $target) { $qb->addOrderBy('e.'.$target,'ASC'); }
        $qb->setMaxResults($rowNum);
        $this->DbDebug($qb->getQuery(),'getSearchResults');
        return $qb->getQuery()->getResult();
    }//END public function getSearchResults
}//END class DoctrineBaseRepository extends EntityRepository
?>