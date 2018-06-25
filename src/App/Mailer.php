<?php
/**
 * Class Mailer file
 *
 * Helper class for sending emails trough SwiftMailer
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.2.0.1
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\Data\DataProvider;
/**
 * Class Mailer
 *
 * Helper class for sending emails trough SwiftMailer
 *
 * @package NETopes\Core\App
 */
class Mailer {
	/**
	 * Send email
	 *
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
	 * @throws \PAF\AppException
	 * @static
	 */
	public static function SendSMTPEmail($subject,$afrom,$ato,$msg,$settings = [],$abcc = NULL,$attachments = [],$params = [],$acc = NULL,$reply_to = NULL) {
		if(!is_array($settings) || !count($settings)) {
			$id_section = get_array_param($params,'id_section',\NApp::_GetParam('id_section'),'is_numeric');
			$id_zone = get_array_param($params,'id_zone',\NApp::_GetParam('id_zone'),'is_numeric');
			$items = DataProvider::GetArray('Email\Emailing','GetSettingsItem',array(
				'section_id'=>(is_numeric($id_section) ? $id_section : 'null'),
				'zone_id'=>(is_numeric($id_zone) ? $id_zone : 'null'),
				'for_stype'=>get_array_param($params,'nwl_stype',2,'is_numeric'),
				'for_active'=>1,
				'for_implicit'=>1,
			));
			$settings = get_array_param($items,0,array(),'is_array');
		}//if(!is_array($settings) || !count($settings))
		$sendmail = get_array_param($settings,'sendmail',0,'is_numeric');
		if($sendmail!=1) {
			$smtphost = get_array_param($settings,'smtp_server','localhost','is_string');
			$smtpport = get_array_param($settings,'smtp_port',25,'is_numeric');
			$smtpauth = get_array_param($settings,'smtp_auth',0,'is_numeric');
			$smtpuser = get_array_param($settings,'smtp_user','','is_string');
			$smtppass = get_array_param($settings,'smtp_password','','is_string');
			$smtpencrypt = get_array_param($settings,'smtp_encrypt',0,'is_numeric');
			$replyto = strlen($reply_to) ? $reply_to : get_array_param($settings,'reply_to','','is_string');
			$returnpath = get_array_param($settings,'return_path','','is_string');
			$exchangedomain = get_array_param($settings,'exchange_domain','','is_string');
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
				// NApp::_Dlog(array(
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
				// NApp::_Dlog($sendmail,'sendmail');
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
			// NApp::_Dlog(array(
			// 	'replyto'=>$replyto,
			// 	'afrom'=>$afrom,
			// 	'ato'=>$ato,
			// 	'acc'=>$acc,
			// 	'abcc'=>$abcc,
			// ),'recipients');
			$result = $mailer->send($message);
			// NApp::_Dlog($result,'$result');
			\NApp::Log2File('SendSMTPEmail result: '.print_r($result,1).'  >>  '.print_r([
				// 'replyto'=>$replyto,
				'afrom'=>$afrom,
				'subject'=>$subject,
				'ato'=>$ato,
				'acc'=>$acc,
				'abcc'=>$abcc,
			],1),\NApp::app_path().NApp::$logs_path.'/emails_debug.log');
			/* $result will be the no of emails sent successfully or 0 if there is an error */
			return $result;
		} catch(\Exception $e) {
			$result = strpos($e->getMessage(),'235 2.7.0 Authentication successful')!==FALSE ? 1 : 0;
			// NApp::_Dlog($e->getMessage(),'$mailer->send::Exception');
			\NApp::Log2File('SendSMTPEmail Error['.$result.']: '.$e->getMessage().'  >>  '.print_r([
				// 'replyto'=>$replyto,
				'afrom'=>$afrom,
				'subject'=>$subject,
				'ato'=>$ato,
				'acc'=>$acc,
				'abcc'=>$abcc,
			],1),\NApp::app_path().NApp::$logs_path.'/emails_debug.log');
			if($result) { return $result; }
			throw new \PAF\AppException($e->getMessage(),E_ERROR,0);
		}//try
	}//END public static function SendSMTPEmail
	/**
	 * Simple email send via SMTP
	 *
	 * @param      $subject string        email subject
	 * @param      $afrom array            sender email address (email => label)
	 * @param      $ato array            receiver email address (email => label)
	 * @param      $msg string            email content
	 * @param      $settings array        email content (optional)
	 * @param      $acc array            email CC adresses (email => label) (optional)
	 * @param      $abcc array            email BCC address (email => label) (optional)
	 * @param null $reply_to
	 * @return int                    will be the no of emails sent successfully or 0 if there is an error
	 * @throws \PAF\AppException
	 * @static
	 */
	public static function SimpleSendSMTPEmail($subject,$afrom,$ato,$msg,$settings = [],$acc = NULL,$abcc = NULL,$reply_to = NULL) {
		return self::SendSMTPEmail($subject,$afrom,$ato,$msg,$settings,$abcc,[],[],$acc,$reply_to);
	}//END public static function SimpleSendSMTPEmail
}//END class Mailer
?>