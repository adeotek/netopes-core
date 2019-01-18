<?php
namespace NETopes\Core\Data\Doctrine;
use NETopes\Core\AppConfig;
use NApp;
use Doctrine\ORM\QueryBuilder;
use NETopes\Core\AppException;

/**
 * Trait RepositoryBaseTrait
 *
 * @package NETopes\Core\Data
 */
trait RepositoryBaseTrait {
	/**
	 * @return array
	 */
	public function getOperators(): array {
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
     * @throws \NETopes\Core\AppException
     */
	protected function DbDebug($query,?string $label = NULL,?float $time = NULL,bool $forced = FALSE) {
		if(!AppConfig::GetValue('db_debug') && !$forced) { return; }
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
		NApp::Dlog($lquery,$llabel);
		if(AppConfig::GetValue('db_debug2file')) { NApp::Write2LogFile($llabel.': '.$lquery,'debug'); }
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
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string              $searchTerm
     * @param array               $searchFields
     * @return \Doctrine\ORM\QueryBuilder The objects.
     */
    public function wordsSearchConditionsGenerator(QueryBuilder $qb,string $searchTerm,array $searchFields): QueryBuilder {
        $words = str_word_count($searchTerm,1,'1234567890');
        foreach($words as $k=>$word) {
            $xor = $qb->expr()->orX();
            foreach($searchFields as $target) { $xor->add($qb->expr()->like($target,':in_'.$k)); }
            $qb->andWhere($xor);
            $qb->setParameter('in_'.$k,'%'.$word.'%');
        }//END foreach
        return $qb;
    }//END public function countBy
    /**
     * @param string      $rawName
     * @param string|null $alias
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public function getFieldName(string $rawName,?string $alias = NULL): string {
        if(!strlen($rawName)) { throw new AppException('Invalid field name!'); }
        $rawName = str_replace(['"',"'",'`','[',']'],'',$rawName);
        if(strpos($rawName,'.')===FALSE) {
            $name = (strlen($alias) ? trim($alias,'. ').'.' : '').convert_to_camel_case($rawName,TRUE);
        } else {
            $nameArr = explode('.',$rawName);
            $name = trim($nameArr[0],'. ').'.'.convert_to_camel_case($nameArr[1],TRUE);
        }//if(strpos($rawName,'.')===FALSE)
        return $name;
    }//END public function getFieldName
}//END trait RepositoryBaseTrait