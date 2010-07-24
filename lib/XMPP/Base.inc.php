<?php
	class XMPP_Base
	{
		protected $socket;
		protected $connectionId;
		protected $authentication;
		
		protected $realm;
		
		public function __construct($host,$port,$realm,$username,$password,$digestURI)
		{
			
			$this->socket = new Socket_Client($host,$port);
			Debug_Profiler::__do("connectionEstablished");			
			
			$this->realm = $realm;
			$this->handshake();
			Debug_Profiler::__do("handshakeComplete. connID={$this->connectionId}");
			
			$this->authenticate($username, $password, $digestURI);
			
			if (!$this->isAuthenticated()) {
				throw new Exception("Authentication failed");
			}
			
			Debug_Profiler::__do("authed ok");
			
			$this->bindService();
			Debug_Profiler::__do("service bound");
		}

		public function __destruct()
		{
			unset($this->socket);
		}
		
		protected function handshake()
		{
			$xml = "<" . "?xml version='1.0'?" . "><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' to='".$this->realm."' version='1.0'>";
			$this->socket->send($xml);
			$welcome = $this->socket->receive();
			
			// this particular hack is required because simplexml doesnt like parsing incomplete xml
			$welcome .= "</stream:stream>"; // required for simplexml
			$welcomeXML = new SimpleXMLElement($welcome);
			$welcome = str_replace("</stream:stream>","",$welcome);

			// repeated receive because the code is waitng for a \n to return.
			$mechanisms = $this->socket->receive();
			
			$this->connectionId = $welcomeXML['id'];

		}
	
		protected function authenticate($username, $password, $digestURI)
		{
			$params = Array (
				'username'	=> $username,
				'password'	=> $password,
				'realm'		=> $this->realm,
				'digestURI'	=> $digestURI
			);
				
			$this->authentication = new XMPP_AuthMD5($this->socket,$params);
		}
		
		protected function bindService()
		{
			$xml = "<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' to='".$this->realm."' version='1.0'>";
			$this->socket->send($xml);
			$features = $this->socket->receive();
			//var_dump('bindService: '.$features);
		}
		
		public function isAuthenticated()
		{
			if (isset($this->authentication)) {
				return ($this->authentication->getStatus()=="authenticated")?true:false;
			} else {
				return false;
			}
		}
	}	