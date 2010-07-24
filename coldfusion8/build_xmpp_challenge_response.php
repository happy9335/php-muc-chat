<?php 

function _getResponseValue($authcid, $pass, $realm, $nonce, $cnonce, $digest_uri, $nc, $authzid = '') {
	if ($authzid == '') {
		$A1 = sprintf('%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $authcid, $realm, $pass))), $nonce, $cnonce);
	} else {
		$A1 = sprintf('%s:%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $authcid, $realm, $pass))), $nonce, $cnonce, $authzid);
	}
	$A2 = 'AUTHENTICATE:' . $digest_uri;
	return md5(sprintf('%s:%s:%s:%s:auth:%s', md5($A1), $nonce, $nc, $cnonce, md5($A2)));
}

echo(_getResponseValue($_GET['username'],$_GET['password'],$_GET['domain'],$_GET['nonce'],$_GET['cnonce'],$_GET['digesturi'],$_GET['nc']));

?>
