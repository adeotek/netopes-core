<?php
namespace NETopes\Core\Data;
use PAF\AppConfig;
use NApp;
use Doctrine\ORM\Query;

/**
 * Trait DoctrineRepositoryBaseTrait
 *
 * @package NETopes\Core\Data
 */
trait DoctrineRepositoryBaseTrait {
	/**
	 * @return array
	 */
	public function getOperators(): array
	{
        return [
            '=='=>'eq',
            '>'=>'gt',
            '<'=>'lt',
            '<='=>'lte',
            '>='=>'gte',
            '<>'=>'neq',
            'null'=>'isNull',
            'in'=>'in',
            'notin'=>'notIn',
            'like'=>'like',
            'notlike'=>'notLike',
            '=%'=>'startsWith',
            '%='=>'endWith',
            'between'=>'between',
        ];
    }
	/**
	 * @param             $query
	 * @param null|string $label
	 * @param float|null  $time
	 * @param bool        $forced
	 */
	protected function DbDebug($query,?string $label = NULL,?float $time = NULL,bool $forced = FALSE) {
		if(!AppConfig::db_debug() && !$forced) { return; }
		$llabel = strlen($label) ? $label : 'DbDebug';
		if(is_object($query)) {
			$lparams = '';
			foreach($query->getParameters()->toArray() as $p) {
			    if(is_object($p->getValue())) {
                    if($p->getValue() instanceof \DateTime) {
                        $pValue = $p->getValue()->format('Y-m-d H:i:s');
                    } else {
                        $pValue = $p->getValue()->getId();
                    }//if($p->getValue() instanceof \DateTime)
                } else {
			        $pValue = $p->getValue();
                }//if(is_object($p->getValue()))
				$lparams .= (strlen($lparams) ? ', ' : '').$p->getName().' => '.$pValue;
			}//END foreach
			$lquery = $query->getSql().' ['.$lparams.']';
		} else {
			$lquery = $query;
		}//if(is_object($query))
		$lquery .= ($time ? '   =>   Duration: '.number_format((microtime(TRUE)-$time),3,'.','').' sec' : '');
		NApp::_Dlog($lquery,$llabel);
		if(AppConfig::db_debug2file()) { NApp::_Write2LogFile($llabel.': '.$lquery,'debug'); }
	}//END protected function DbDebug
	/**
     * Finds entities by a set of criteria.
     *
     * @param array      $criteria
     * @property $_em
     *
     * @return int The objects.
     */
    public function countBy(array $criteria) {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);
        return $persister->count($criteria);
    }//END public function countBy

    /**
     * Adds where conditions to the Query for searching all words in $searchTerm
     *
     * @param \Doctrine\ORM\Query $qb
     * @param string              $searchTerm
     * @param array               $searchFields
     * @return \Doctrine\ORM\Query The objects.
     */
    public function wordsSearchConditionsGenerator(Query $qb,string $searchTerm,array $searchFields): Query {
        $words = str_word_count($searchTerm,1,'1234567890');
        foreach($words as $k=>$word) {
            $xor = $qb->expr()->orX();
            foreach($searchFields as $target) { $xor->add($qb->expr()->like('e.'.$target,':in_'.$k)); }
            $qb->andWhere($xor);
            $qb->setParameter('in_'.$k,'%'.$word.'%');
        }//END foreach
        return $qb;
    }//END public function countBy
}//END trait DoctrineRepositoryBaseTrait