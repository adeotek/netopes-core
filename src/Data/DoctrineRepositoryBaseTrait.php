<?php
namespace NETopes\Core\Data;
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
		if(!NApp::$db_debug && !$forced) { return; }
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
		if(NApp::$db_debug2file) { NApp::_Write2LogFile($llabel.': '.$lquery,'debug'); }
	}//END protected function DbDebug
}//END trait DoctrineRepositoryBaseTrait
?>