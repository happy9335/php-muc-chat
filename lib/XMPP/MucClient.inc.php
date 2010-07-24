<?php
class XMPP_MucClient extends XMPP_Base
{
	public function __construct($host,$port,$realm,$username,$password,$digestURI)
	{
		parent::__construct($host,$port,$realm,
							$username,$password,$digestURI);


		// additional stuff for handling MUC specifics goes in here. if we get to this point we have a successfully authenticated xmpp session open, otherwise an exception will have been thrown.

		// Trailing stream features stuff
		//$r = $this->socket->receive();
//var_dump('Trailing:'.$r);
		//* Bind resource
		$xml = "<iq id='bind_1' type='set'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'/></iq>";
		$this->socket->send($xml);
		$r = $this->socket->receive();

		$r = strip_tags($r);// get jit like xxxx@talk.google.com/f8c32d16
		$this->username = $r;
		//*/
//var_dump($r);
		// Init session
		$xml = "<iq to='{$host}' type='set' id='sess_1'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>";
		$this->socket->send($xml);
		$r = $this->socket->receive();
//var_dump($r);
		// join room
		$xml = "<presence to='syy@app.pub.okooo.com/xxxxx0' />";
		$this->socket->send($xml);
		$r = $this->socket->receive();
//var_dump('presence'.$r);

//* say a word
		$xml ="<message to='syy@app.pub.okooo.com' type='groupchat'><body>-</body></message>";
		//$this->socket->send($xml);
		$r = $this->socket->receive();
		$r = $this->socket->receive();
		$r = $this->socket->receive();
		$r = $this->socket->receive();
		$r = $this->socket->receive();
		$r = $this->socket->receive();
echo strlen($r);
//		var_dump($this);//die;
//sleep(10);
	}
}
