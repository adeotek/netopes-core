<?php
namespace NETopes\Core\Data;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class DoctrineBaseRepository extends EntityRepository
{
	use DoctrineRepositoryStandardTrait;

	public function getSearchResults(string $term,array $targets = [],array $params = [],int $rowNum = 10)
    {
        $qb = $this->createQueryBuilder('e');
		$words = str_word_count($term, 1, '1234567890');
		foreach($params as $pn=>$pv) {
			$qb->andWhere($qb->expr()->eq('e.'.$pn,$pv));
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
        \NApp::_Dlog($qb->getDQL(),'getDQL');
        \NApp::_Dlog($qb->getQuery()->getSQL(),'getSQL');
        return $qb->getQuery()->getResult();
    }
}
?>