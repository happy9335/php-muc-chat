<?php

	class Socket_Client
	{
		protected $socket;
		protected $socketError;

		protected $serverStream;
		protected $clientStream;
		protected $status = "not connected";
		protected $response_terminator;
		
		public $host;
		public $port;
		
		public $timeout = "7";
		public $messageId = 0;
				
		public function __construct($host,$port="")
		{
			$this->response_terminator = chr(0x00);
			
			$this->host = $host;
			$this->port = $port;
			
			$this->createConnection();
		}

		public function __destruct()
		{
			$this->closeConnection();
		}

		private function createConnection()
		{
			// if socket hasnt been established, try to create one
			if (!is_resource($this->socket)) {

				$this->socket = @fsockopen($this->host, $this->port,$errno, $errstr, $this->timeout);
				
				if (is_resource($this->socket)) {
				
					stream_set_timeout($this->socket, $this->timeout);
					$this->status = "connected";

					return true;
				
				} else {
					// connection failed.
					$this->socketError = Array('errorNumber' => $errno, 'errorString' => $errstr);
					$this->status = "not connected";
					throw new Exception("Error: could not open socket connection; " . $this->socketError['errorNumber'] . ":" . $this->socketError['errorString']);
				}
			} else {
				// socket already established, return that it is ok
				return true;
			}
		}

		public function closeConnection()
		{
			if (is_resource($this->socket)) {
				fclose($this->socket);	
			}
		}

		private function checkConnection()
		{
			Debug_Profiler::__do("checkConnection");
			if (!is_resource($this->socket)) {
				throw new Exception("socket is not a live resource");
			}
			return true;
		}
				
		public function send($query,$returnResponse=false)
		{
			$this->checkConnection();
			Debug_Profiler::__do("send");
			fwrite($this->socket, $query);
			Debug_Profiler::__do("sent: " . htmlentities($query));
			
			$this->clientStream[$this->messageId++] = $query;
								
			return ($returnResponse)?$this->receive():true;
		}

		public function getStatus()
		{
			return $this->status;
		}
			
		public function receive()
		{
			$this->checkConnection();
			Debug_Profiler::__do("receive");
			
			$rcvd = "";
			$rcvd = fread($this->socket,4096);
			
			Debug_Profiler::__do("received: " . htmlentities($rcvd)	);
			
			$this->serverStream[$this->messageId++] = $rcvd;

			if (strlen($rcvd)>0) {
				return $rcvd;
			} else {
				return false;
			}
		}
	}
