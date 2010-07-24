<?php

	class XMPP_AuthMD5
	{
		private $socket;
		private $requiredParams = Array("username","password","realm","digestURI");
		private $params;
		
		private $challenge;
		private $response;
		
		private $status = "not authenticated";
		
		public function __construct(&$socket,$params)
		{
			$this->socket = &$socket;
			foreach($this->requiredParams as $param) {
				if (!isset($params[$param])) {
					throw new Exception("Required param $param not passed into xmppAuthMD5");
				}
				$this->params[$param] = $params[$param];
			}
			$this->authenticate();
		}

		private function authenticate()
		{
			$xml = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>";
			$this->socket->send($xml);
			$auth = $this->socket->receive();
			$auth = strip_tags($auth);
			//$auth = str_replace(Array("<challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>","</challenge>"),Array("",""),$auth);
			$authcode = base64_decode($auth);

			$authchallenge = explode(",",$authcode);

			if (!$authchallenge[0]) {
				throw new Exception("XMPP_AuthMD5; Server returned DIGEST-MD5 instead of the encoded challenge; speak to Dale.");
			}
			
			foreach($authchallenge as $chunk) {
				list($k,$v) = explode("=",$chunk);
				$this->challenge[$k] = $v;
			}

			$nonce = str_replace("\"","",$this->challenge['nonce']);
			$cnonce = $this->generateCnonce();

			$this->response = Array(
				'username'	=> $this->params['username'],
				'realm'		=> $this->params['realm'],
				'nonce'		=> $nonce,
				'cnonce'	=> $cnonce,
				'nc'		=> '00000001',
				'qop'		=> 'auth',
				'digest-uri'=> $this->params['digestURI'],
				'response'  => $this->getSASLResponseValue($this->params['username'],$this->params['password'],$this->params['realm'],$nonce,$cnonce,$this->params['digestURI']),
				'charset'	=> 'utf-8'
			);

			foreach($this->response as $k=>$v) {
				$chunks[] = "$k=\"$v\"";
			}
			$authResponse = implode(",",$chunks);
			
			$xml = "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>";
			$xml.= base64_encode($authResponse);
			$xml.= "</response>";

			$this->socket->send($xml);
			$reply = $this->socket->receive();

			$replyXML = new SimpleXMLElement($reply);
			//var_dump($reply,$replyXML);die;
			if ($replyXML->getName()=="failure") {
				throw new Exception("Authentication failed.");
			} elseif ($replyXML->getName()=="challenge") {
				$reply = strip_tags($reply);

				$this->rspAuth = base64_decode($reply);
				
				$xml = "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>";
				$this->socket->send($xml);
				$success = $this->socket->receive();
			} elseif($replyXML->getName()=="success")
			
			
			//var_dump($success);die;
			$this->status = "authenticated";			
		}
						
		public function generateCnonce()
		{
			return substr(base64_encode(microtime()),0,16);
		}

	    private function getSASLResponseValue($username, $pass, $realm, $nonce, $cnonce, $digest_uri)
 		{
			$A1 = sprintf('%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $username, $realm, $pass))), $nonce, $cnonce);
			$A2 = 'AUTHENTICATE:' . $digest_uri;
			return md5(sprintf('%s:%s:00000001:%s:auth:%s', md5($A1), $nonce, $cnonce, md5($A2)));
 		}
 		
 		public function getStatus()
 		{
 			return $this->status;
 		}
	}
