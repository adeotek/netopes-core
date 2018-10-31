<?php
namespace NETopes\Core\Data;
use PAF\AppConfig;
use NApp;
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
				$lparams .= (strlen($lparams) ? ', ' : '').$p->getName().' => '.$p->getValue();
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
     *
     * @return int The objects.
     */
    public function countBy(array $criteria)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);
        return $persister->count($criteria);
    }
}//END trait DoctrineRepositoryBaseTrait
?>