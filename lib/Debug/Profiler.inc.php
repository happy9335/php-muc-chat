<?php
	class Debug_Profiler
	{
		protected $stopWatches;
		public $begin;
		public $end;
		
		function __construct()
		{
			if (DEBUG_ENABLED) {
				$this->begin();
			}
		}
		
		public function begin()
		{
			if (DEBUG_ENABLED) {
				$this->begin = microtime(true);
				$this->stopWatch("begin",$this->begin);
			}
		}
		
		public function stopWatch($str,$timestamp=false)
		{
			if (DEBUG_ENABLED) {
				$this->stopWatches[] = ($timestamp)?Array($str,$timestamp):Array($str,microtime(true));
			}
		}
		
		public function popoutstring($str)
		{return $str;
			if (strlen($str)>40) {
				return substr($str,0,40) . "...";
			} else {
				return $str;
			}
		}
		
		public function end()
		{
			if (DEBUG_ENABLED) {
			
				$this->stopWatch("end");
				$str = "<table class=\"profiler\"><tr><th>Checkpoint</th><th>Time</th></tr>";
				$class = "";
				foreach ($this->stopWatches as $stopwatch) {
					$class=($class==" alternate")?"":" alternate";
					$str.= "<tr class=\"row $class\"><td align=\"right\">" . (round(($stopwatch[1]-$this->begin)*100000)/100000) . "<br />".strlen($stopwatch[0])."</td><td>" . $this->popoutString($stopwatch[0]) . "</td></tr>";
				}
				$str .= "</table>";
				return $str;
			}
		}
		
		public static function __do($str,$objName="profiler")
		{
			if (DEBUG_ENABLED) {
			
				global $$objName;
				if (!isset($$objName)) {
					$$objName = new Debug_Profiler;
				}
				$$objName->stopWatch($str);
			}
		}
	}
