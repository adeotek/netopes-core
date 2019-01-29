<?php
/**
 * Class Mailer file
 * Helper class for sending emails trough SwiftMailer
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\AppConfig;
use NApp;
/**
 * Class Mailer
 * Helper class for sending emails trough SwiftMailer
 * @package NETopes\Core\App
 */
class Mailer {
	/**
	 * @var bool Debug email sending
	 */
	public static $debug = FALSE;
	/**
	 * Send email
	 * @param $subject string		email subject
	 * @param $afrom array			sender email address (email => label)
	 * @param $ato array			receiver email address (email => label)
	 * @param $msg string			email content
	 * @param $settings array		email content (optional)
	 * @param $abcc array			email BCC address (email => label) (optional)
	 * @param $attachments array	email attachments (optional)
	 * @param $params array			extra parameters (optional)
	 * @param $acc array            email CC addresses (email => label) (optional)
	 * @param null $reply_to
	 * @return int 					will be the no of emails sent successfully or 0 if there is an error
	 * @throws \NETopes\Core\AppException
	 */
	public static function SendSMTPEmail($subject,$afrom,$ato,$msg,$settings = [],$abcc = NULL,$attachments = [],$params = [],$acc = NULL,$reply_to = NULL) {
		if(!is_array($settings) || !count($settings)) {
			$id_section = get_array_value($params,'id_section',NApp::GetParam('id_section'),'is_numeric');
			$id_zone = get_array_value($params,'id_zone',NApp::GetParam('id_zone'),'is_numeric');
			$items = DataProvider::GetArray('Email\Emailing','GetSettingsItem',array(
				'section_id'=>(is_numeric($id_section) ? $id_section : NULL),
				'zone_id'=>(is_numeric($id_zone) ? $id_zone : NULL),
				'for_stype'=>get_array_value($params,'nwl_stype',2,'is_numeric'),
				'for_active'=>1,
				'for_implicit'=>1,
			));
			$settings = get_array_value($items,0,[],'is_array');
		}//if(!is_array($settings) || !count($settings))
		$sendmail = get_array_value($settings,'sendmail',0,'is_numeric');
		if($sendmail!=1) {
			$smtphost = get_array_value($settings,'smtp_server','localhost','is_string');
			$smtpport = get_array_value($settings,'smtp_port',25,'is_numeric');
			$smtpauth = get_array_value($settings,'smtp_auth',0,'is_numeric');
			$smtpuser = get_array_value($settings,'smtp_user','','is_string');
			$smtppass = get_array_value($settings,'smtp_password','','is_string');
			$smtpencrypt = get_array_value($settings,'smtp_encrypt',0,'is_numeric');
			$replyto = strlen($reply_to) ? $reply_to : get_array_value($settings,'reply_to','','is_string');
			$returnpath = get_array_value($settings,'return_path','','is_string');
			$exchangedomain = get_array_value($settings,'exchange_domain','','is_string');
		}//if($sendmail!=1)
		try {
			if($sendmail!=1) {
				switch($smtpencrypt) {
					case 1:
						$encryption = 'tls';
						break;
					case 2:
						$encryption = 'ssl';
						break;
					default:
						$encryption = NULL;
						break;
				}//END switch
				// NApp::Dlog(array(
				// 	'1_smtpauth'=>$smtpauth,
				// 	'2_smtphost'=>$smtphost,
				// 	'3_smtpport'=>$smtpport,
				// 	'4_encryption'=>$encryption,
				// 	'5_smtpuser'=>$smtpuser,
				// 	'6_smtppass'=>$smtppass,
				// 	'7_exchangedomain'=>$exchangedomain,
				// ),'SMTP');
				if($smtpauth==0) {
					$transport = \Swift_SmtpTransport::newInstance($smtphost,$smtpport);
					$transport->setEncryption($encryption);
					$transport->setTimeout(10);
				} else {
					$transport = \Swift_SmtpTransport::newInstance($smtphost,$smtpport)
						->setEncryption($encryption)
						->setUsername($smtpuser)
						->setPassword($smtppass)
						->setTimeout(10)
						;
				}//if($smtpauth==0)
				if(strlen($exchangedomain)) { $transport->setLocalDomain($exchangedomain); }
			} else {
				// NApp::Dlog($sendmail,'sendmail');
				$transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -t -i');
			}//if($sendmail!=1)
			$mailer = \Swift_Mailer::newInstance($transport);
			$message = \Swift_Message::newInstance()
				->setSubject($subject)
				->setFrom($afrom)
				->setTo($ato)
				->setCc($acc)
				->setBcc($abcc)
				->setBody($msg,'text/html')
				;
			$message->setEncoder(\Swift_Encoding::get8BitEncoding());
			if(strlen($replyto)) { $message->setReplyTo($replyto); }
			if(strlen($returnpath)) { $message->setReturnPath($returnpath); }
			if(is_array($attachments) && count($attachments)) {
				foreach($attachments as $attach) {
					if(strlen($attach)>0 && file_exists($attach)) {
						$atachname = substr($attach,strrpos($attach,'/')+1);
						$message->attach(\Swift_Attachment::fromPath($attach)->setFilename($atachname));
					}//if(strlen($attach)>0 && file_exists($attach))
				}//END foreach
			} elseif(is_string($attachments) && strlen($attachments) && file_exists($attachments)) {
				$atachname = substr($attachments,strrpos($attachments,'/')+1);
				$message->attach(\Swift_Attachment::fromPath($attachments)->setFilename($atachname));
			}//if(is_array($attachments) && count($attachments))
			$result = $mailer->send($message);
			if(self::$debug) {
				NApp::Log2File('SendSMTPEmail result: '.print_r($result,1).'  >>  '.print_r([
					// 'replyto'=>$replyto,
					'afrom'=>$afrom,
					'subject'=>$subject,
					'ato'=>$ato,
					'acc'=>$acc,
					'abcc'=>$abcc,
				],1),NApp::$appPath.AppConfig::GetValue('logs_path').'/emails_debug.log');
			}//if(self::$debug)
			/* $result will be the no of emails sent successfully or 0 if there is an error */
			return $result;
		} catch(\Exception $e) {
			NApp::Elog($e);
			$result = strpos($e->getMessage(),'235 2.7.0 Authentication successful')!==FALSE ? 1 : 0;
			if(self::$debug) {
				NApp::Log2File('SendSMTPEmail Error['.$result.']: '.$e->getMessage().'  >>  '.print_r([
					// 'replyto'=>$replyto,
					'afrom'=>$afrom,
					'subject'=>$subject,
					'ato'=>$ato,
					'acc'=>$acc,
					'abcc'=>$abcc,
				],1),NApp::$appPath.AppConfig::GetValue('logs_path').'/emails_debug.log');
			}//if(self::$debug)
			if($result) { return $result; }
			throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,0);
		}//try
	}//END public static function SendSMTPEmail
	/**
	 * Simple email send via SMTP
	 * @param      $subject string        email subject
	 * @param      $afrom array            sender email address (email => label)
	 * @param      $ato array            receiver email address (email => label)
	 * @param      $msg string            email content
	 * @param      $settings array        email content (optional)
	 * @param      $acc array            email CC adresses (email => label) (optional)
	 * @param      $abcc array            email BCC address (email => label) (optional)
	 * @param null $reply_to
	 * @return int                    will be the no of emails sent successfully or 0 if there is an error
	 * @throws \NETopes\Core\AppException
	 */
	public static function SimpleSendSMTPEmail($subject,$afrom,$ato,$msg,$settings = [],$acc = NULL,$abcc = NULL,$reply_to = NULL) {
		return self::SendSMTPEmail($subject,$afrom,$ato,$msg,$settings,$abcc,[],[],$acc,$reply_to);
	}//END public static function SimpleSendSMTPEmail
}//END class Mailer