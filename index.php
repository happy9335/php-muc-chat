<?php

	function __autoload($className)
	{
		global $conf;
		$fileparts = explode("_",$className);
		$classPath = $conf['paths']['basePath'] . "lib/" . implode("/",$fileparts) .".inc.php";
		require($classPath);
	}

	// begin program execution

	define("DEBUG_ENABLED",true);
	set_time_limit(30);

	$profiler = new Debug_Profiler;
	$template = new Template_Base("wrapper");
	
	if (isset($_REQUEST['xmpp'])) {

		try {
			
			$_xmpp = new XMPP_MucClient(
				$_REQUEST['xmpp']['host'],$_REQUEST['xmpp']['port'],$_REQUEST['xmpp']['realm'],$_REQUEST['xmpp']['username'],$_REQUEST['xmpp']['password'], "xmpp/".$_REQUEST['xmpp']['realm']
			);
	
			if ($_xmpp->isAuthenticated()) {
				$template->populate("status","Authenticated ok");
			} else {
				$template->populate("status","Could not authenticate");
			}

			$template->populate("loginform","");
		
		} catch (Exception $e) {
			$template->setSubTemplate("loginform","login");
			
			$template->populate("status","<b>Something went wrong:</b><br />" . $e->getMessage()."<br /><br />");
		}
				
	} else {
		
		$template->setSubTemplate("loginform","login");
		$template->populate("status","");
	}

	$phtml = (DEBUG_ENABLED)?$profiler->end():"";
	
	$template->populate("profiler", $phtml);

	echo $template->output();
	
?>