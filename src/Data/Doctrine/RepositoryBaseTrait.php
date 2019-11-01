<?php
namespace NETopes\Core\Data\Doctrine;
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
            '=%'=>'like',
            '%='=>'like',
            'strictlike'=>'like',
            'between'=>'between',
        ];
    }//END public function getOperators

    /**
     * @param string      $rawName
     * @param string|null $alias
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public function getFieldName(string $rawName,?string $alias=NULL): string {
        if(!strlen($rawName)) {
            throw new AppException('Invalid field name!');
        }
        $rawName=str_replace(['"',"'",'`','[',']'],'',$rawName);
        if(strpos($rawName,'.')===FALSE) {
            $name=(strlen($alias) ? trim($alias,'. ').'.' : '').convert_to_camel_case($rawName,TRUE);
        } else {
            $nameArr=explode('.',$rawName);
            $name=trim($nameArr[0],'. ').'.'.convert_to_camel_case($nameArr[1],TRUE);
        }//if(strpos($rawName,'.')===FALSE)
        return $name;
    }//END public function getFieldName

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array|null                 $filter
     * @param string                     $key
     * @param array                      $parameters
     * @param string|null                $logicalSeparator
     * @return \Doctrine\ORM\Query\Expr|null
     * @throws \NETopes\Core\AppException
     */
    protected function getFilterExpression(QueryBuilder $qb,?array $filter,string $key,array &$parameters,?string &$logicalSeparator=NULL) {
        $field=get_array_value($filter,'field',NULL,'isset');
        $value=get_array_value($filter,'value',NULL,'isset');
        if(is_null($field) || is_null($value)) {
            return NULL;
        }
        $operators=$this->getOperators();
        $operatorType=strtolower(get_array_value($filter,'condition_type','==','is_string'));
        $operator=get_array_value($operators,$operatorType,'','is_string');
        if(!strlen($operator)) {
            return NULL;
        }
        $logicalSeparator=get_array_value($filter,'logical_separator','AND','is_notempty_string');
        $expression=NULL;
        if(is_array($field) && count($field)) {
            $expression=$qb->expr()->orX();
            $fieldParams=[];
            foreach($field as $mfi) {
                $mfi=$this->getFieldName($mfi,'e');
                $paramName='in'.$key.'_'.str_replace('.','_',$mfi);
                $expression->add($qb->expr()->$operator($mfi,':'.$paramName));
                $fieldParams[]=$paramName;
            }//END foreach
            foreach($fieldParams as $paramName) {
                if(array_key_exists($paramName,$parameters)) {
                    continue;
                }
                switch($operatorType) {
                    case 'like':
                    case 'notlike':
                        $parameters[$paramName]='%'.$value.'%';
                        break;
                    case '=%':
                        $parameters[$paramName]=$value.'%';
                        break;
                    case '%=':
                        $parameters[$paramName]='%'.$value;
                        break;
                    default:
                        $parameters[$paramName]=$value;
                        break;
                }//END switch
            }//END foreach
        } elseif(is_string($field) && strlen($field)) {
            $field=$this->getFieldName($field,'e');
            $paramName='in'.$key.'_'.str_replace('.','_',$field);
            $expression=$qb->expr()->$operator($field,':'.$paramName);
            if(!array_key_exists($paramName,$parameters)) {
                switch($operatorType) {
                    case 'like':
                    case 'notlike':
                        $parameters[$paramName]='%'.$value.'%';
                        break;
                    case '=%':
                        $parameters[$paramName]=$value.'%';
                        break;
                    case '%=':
                        $parameters[$paramName]='%'.$value;
                        break;
                    default:
                        $parameters[$paramName]=$value;
                        break;
                }//END switch
            }//if(!array_key_exists($paramName,$parameters))
        }//if(is_array($field) && count($field))
        return $expression;
    }//END protected function getFilterExpression

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array                      $filters
     * @param array                      $parameters
     * @param string|null                $logicalSeparator
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function processQueryFilters(QueryBuilder &$qb,array $filters,array &$parameters,?string &$logicalSeparator=NULL) {
        if(!count($filters)) {
            return NULL;
        }
        $result='';
        foreach($filters as $k=>$f) {
            $lSeparator=NULL;
            if(substr($k,0,1)=='_') {
                if(!is_array($f)) {
                    continue;
                }
                $expr=$this->processQueryFilters($qb,$f,$parameters,$lSeparator);
                if(strlen($result) && strlen($lSeparator)) {
                    $result.=' '.strtoupper($lSeparator).' ';
                }
                $result.='('.(string)$expr.')';
            } else {
                $expr=$this->getFilterExpression($qb,$f,$k,$parameters,$lSeparator);
                if(!is_object($expr)) {
                    continue;
                }
                $result.=(strlen($result) && strlen($lSeparator) ? ' '.strtoupper($lSeparator).' ' : '').(string)$expr;
            }//if(substr($k,0,1)=='_')
            if(is_null($logicalSeparator)) {
                $logicalSeparator=strtoupper($lSeparator);
            }
        }//END foreach
        return $result;
    }//END public function processQueryFilters

    /**
     * Finds entities by a set of criteria.
     *
     * @param array $criteria
     * @return int The objects.
     * @property    $_em
     */
    public function countBy(array $criteria) {
        $persister=$this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);
        return $persister->count($criteria);
    }//END public function countBy

    /**
     * Adds where conditions to the Query for searching all words in $searchTerm
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string                     $searchTerm
     * @param array                      $searchFields
     * @return \Doctrine\ORM\QueryBuilder The objects.
     */
    public function wordsSearchConditionsGenerator(QueryBuilder $qb,string $searchTerm,array $searchFields): QueryBuilder {
        if(!strlen($searchTerm)) {
            return $qb;
        }
        $words=str_word_count($searchTerm,1,'1234567890');
        foreach($words as $k=>$word) {
            $xor=$qb->expr()->orX();
            foreach($searchFields as $target) {
                $xor->add($qb->expr()->like($target,':in_'.$k));
            }
            $qb->andWhere($xor);
            $qb->setParameter('in_'.$k,'%'.$word.'%');
        }//END foreach
        return $qb;
    }//END public function countBy
}//END trait RepositoryBaseTrait