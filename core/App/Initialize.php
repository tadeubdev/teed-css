<?php

	namespace TeedCss;

	class Initialize
	{
		public static $Paths = array();
		public static $Config = array();
		public static $CssResponse;

		public static function Start()
		{

			if(\App::getEnv()->name != 'local')
			{
				return;
			}

			$GlobalData = \Files::getData('globals.php');

			foreach($GlobalData['paths']->www as $Name => $Data)
			{
				self::$Paths[$Name] = \App::getUri() . $Data;
			}

			$CssFiles = glob(self::$Paths['teedcss'] . "/{*,**/*}.teedcss", GLOB_BRACE);

			foreach($CssFiles as $PathToFile)
			{
				$Data = \Files::getData($PathToFile, true);
				$Data = join("", $Data);
				$Data = str_replace(array("\n","\r","\t"), "", $Data);
				$Data = str_replace(array(" : ",": ", " :"), ":", $Data);
				$Data = str_replace(array("{","}"), array(" {","} "), $Data);

				self::$CssResponse  .= $Data;
			}

			#

			$ConfigFiles = glob(self::$Paths['teedcss'] . "/{*,**/*}.conf", GLOB_BRACE);

			foreach($ConfigFiles as $PathToFile)
			{
				$Data = \Files::getData($PathToFile, true);

				foreach($Data as $Line)
				{
					$Item = explode("=", $Line);
					$Name = "$" . trim($Item[0]);
					$Value = trim($Item[1]);

					self::$CssResponse = str_replace($Name, $Value, self::$CssResponse);
				}
			}

			$FileName = self::$Paths['css'] . "/template.min.css";

			$FileOpen = fopen($FileName, 'w+');
			fwrite($FileOpen, self::$CssResponse);
			fclose($FileOpen);
		}
	}