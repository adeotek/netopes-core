<?php
/**
 * Doctrine Logger class file
 *
 * @package    NETopes\Core\Data\Doctrine
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.3.1
 * @filesource
 */
namespace NETopes\Core\Data\Doctrine;
use DateTime;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Query;
use NApp;
use NETopes\Core\AppConfig;

/**
 * A SQL logger that logs to PHP console.
 */
class Logger implements SQLLogger {
    /**
     * Query start time
     *
     * @var float|null
     */
    protected $startTime=NULL;
    /**
     * SQL Query string
     *
     * @var null
     */
    protected $query=NULL;

    /**
     * {@inheritdoc}
     * @throws \NETopes\Core\AppException
     */
    public function startQuery($sql,array $params=NULL,array $types=NULL) {
        if(!AppConfig::GetValue('db_debug')) {
            return;
        }
        $this->startTime=microtime(TRUE);
        $this->query=$sql;
        if(is_array($params) && count($params)) {
            $this->query.=' ['.print_r($params,1).']';
        }
    }//END public function startQuery

    /**
     * {@inheritdoc}
     * @throws \NETopes\Core\AppException
     */
    public function stopQuery() {
        if(!AppConfig::GetValue('db_debug')) {
            return;
        }
        if($this->startTime) {
            $this->query.='   =>   Duration: '.number_format((microtime(TRUE) - $this->startTime),3,'.','').' sec';
        }
        NApp::Dlog($this->query,'DbDebug');
        $this->startTime=$this->query=NULL;
    }//END public function stopQuery

    /**
     * @param             $query
     * @param null|string $label
     * @param float|null  $time
     * @param bool        $forced
     * @throws \NETopes\Core\AppException
     */
    public static function DbDebug(Query $query,?string $label=NULL,?float $time=NULL,bool $forced=FALSE) {
        if(!AppConfig::GetValue('db_debug') && !$forced) {
            return;
        }
        $lLabel=strlen($label) ? $label : 'DbDebug';
        if(is_object($query)) {
            $lParams='';
            foreach($query->getParameters()->toArray() as $p) {
                if(is_object($p->getValue())) {
                    if($p->getValue() instanceof DateTime) {
                        $pValue=$p->getValue()->format('Y-m-d H:i:s');
                    } else {
                        $pValue=$p->getValue()->getId();
                    }//if($p->getValue() instanceof \DateTime)
                } else {
                    $pValue=$p->getValue();
                }//if(is_object($p->getValue()))
                $lParams.=(strlen($lParams) ? ', ' : '').$p->getName().' => '.(is_scalar($pValue) ? $pValue : print_r($pValue,1));
            }//END foreach
            $lQuery=$query->getSql().' ['.$lParams.']';
        } else {
            $lQuery=$query;
        }//if(is_object($query))
        $lQuery.=($time ? '   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec' : '');
        NApp::Dlog($lQuery,$lLabel);
    }//END public static function DbDebug
}//END class Logger implements SQLLogger
