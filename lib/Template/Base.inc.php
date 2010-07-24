<?php

	class Template_Base
	{
		protected $className = "Template_Base";
		protected $readyStatus = false;
		protected $templateContents;
		protected $tplFile;
		
		protected $templatePath = "tpl/";
			
		public function __construct($tplFile)
		{
			if ($tplFile) {
				$this->tplFile = $tplFile;
				$this->getTemplate($tplFile);
			}
					
		}
		
		protected function getTemplate($tplFile)
		{
			if ($tplFile) {
				$this->tplFile = $tplFile;
			}
			
			if (!$this->tplFile) {
				throw new Exception("No tplfile specified!");
			}
		
			if (!$this->templatePath) {
				throw new Exception("No template path specified.");
			}
			
			global $conf;
			
			$templateContents = file($this->templatePath . $this->tplFile . ".tpl");
			$this->templateContents = implode("\n",$templateContents);
			$this->readyStatus = true;
		}
		
		public function setSubTemplate($tag,$tplFile)
		{
			if (!$this->readyStatus) {
				throw new Exception("template not ready for population");
			}
			
			$subTemplate = new $this->className($tplFile);
			$this->templateContents = str_replace("{\$$tag}",$subTemplate->output(),$this->templateContents);
			
		}
		
		public function populate($tag,$value)
		{
			if (!$this->readyStatus) {
				throw new Exception("template not ready for population!");
			}
			
			$this->templateContents = str_replace("{\$$tag}",$value,$this->templateContents);
		}
		
		public function output()
		{
			return $this->templateContents;
		}
	}
	
?>