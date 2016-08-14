<?php

	namespace TeedCss;

	class Initialize
	{
		# Pasta onde se encontra os arquivos teedcss
		public static $Paths = array();
		# Configurações dos arquivos .conf
		public static $Config = array();
		# Variáveis dentro dos .conf
		public static $Variables = array();
		# Resposta do css
		public static $CssResponse;
		# Resposta do css compressado
		public static $CssCompressed;
		# Array com dados do css
		public static $ArrayCss = array();
		# nome do elemento css
		public static $ElementName = null;

		# inicia o aplicativo
		public static function Start()
		{

			# se não for local ele não continua
			if(\App::getEnv()->name != 'local') return;

			# busca as variáveis globais
			$GlobalData = \Files::getData('globals.php');

			# seta os caminhos do projeto
			foreach($GlobalData['paths']->www as $Name => $Data)
			{
				self::$Paths[$Name] = \App::getUri() . $Data;
			}

			# Busca arquivos de Configurações
			$ConfigFiles = glob(self::$Paths['teedcss'] . "/{*,**/*}.conf", GLOB_BRACE);

			foreach($ConfigFiles as $PathToFile)
			{
				# Busca pelos dados a partir do caminho
				$Data = \Files::getData($PathToFile, true);

				foreach($Data as $Line)
				{
					# Se começar por # é um comentário, passa para o próximo
					if( in_array(substr($Line, 0, 1), array("\r","#"))) continue;

					$Item = explode(" ", $Line, 2);
					$Name = trim($Item[0]);
					$Value = trim($Item[1]);
					self::$Variables[$Name] = $Value;
				}
			}

			# Busca pelos arquivos teedcss
			$FilesTeedCss = glob(self::$Paths['teedcss'] . "/{**/*,*}.teedcss", GLOB_BRACE);

			foreach($FilesTeedCss as $PathToFile)
			{
				# faz a leitura de um arquivo teedcss por vez

				# busca o arquivo e coloca em $Data
				$Data = \Files::getData($PathToFile, true);
				$Data = str_replace(array("\n","\r"), "", $Data);

				$VariableOldName = null;
				$VariableOldValue = null;

				for($DataNumber=0; $DataNumber<=count($Data)-1; $DataNumber++)
				{
					# linha do array Data
					$DataLine = $Data[$DataNumber];

					if( strlen( trim( $DataLine)) == 0) continue;

					if( substr_count($DataLine,"\t") == 0)
					{
						self::$ArrayCss[$DataLine] = array();
						self::$ElementName = $DataLine;

						for($y=$DataNumber+1; $y<=count($Data)-1; $y++)
						{
							if( strlen( trim( $Data[$y])) == 0) continue;

							if( substr_count($Data[$y], "\t") == 0)
							{
								$y = count($Data)-1;
								break;
							}

							if( $y!=count($Data)-1 && substr_count($Data[$y+1], "\t") > substr_count($Data[$y], "\t") )
							{

								if(preg_match("/(\&)/", $Data[$y]))
								{
									$SubElement = str_replace("&", self::$ElementName, $Data[$y]);
									$SubElement = str_replace("\t", "", $SubElement);
								} else {
									$SubElement = str_replace("\t", "", $Data[$y]);
									$SubElement = "{$DataLine} {$SubElement}";
								}

								self::$ArrayCss[$SubElement] = array();
								self::$ElementName = $SubElement;
								$y++;
							}

							$Matches = explode(" ", $Data[$y], 2);

							$AttrName = str_replace("\t", "", $Matches[0]);
							$AttrValue = str_replace("\t", "", $Matches[1]);

							# setar nova variável
							preg_match("/\\$([a-zA-Z0-9\-_]{1,})/", $AttrName, $Matches);

							if( isset($Matches[0]))
							{
								$Variable = $Matches[1];

								$VariableOldName = $Variable;
								$VariableOldValue = self::$Variables[$Variable];

								self::$Variables[$Variable] = $AttrValue;
							}
							else
							{

								# buscar variável do valor e usá-la
								preg_match("/\\$([a-zA-Z0-9\-_]{1,})/", $AttrValue, $Matches);

								if( isset($Matches[1]))
								{
									$Variable = $Matches[1];

									if( strlen($Variable) && isset(self::$Variables[$Variable]) )
									{
										$AttrValue = str_replace($Matches[0], self::$Variables[$Variable], $AttrValue);
									}
								}

								# https://github.com/tadeubarbosa/teed-css/wiki/Fun%C3%A7%C3%B5es
								if( preg_match("/(sum|subtract|divide|multiply|dump)\((.*)\)/", $AttrValue, $AttrMatch))
								{
									$Values = explode(" ", $AttrMatch[2]);
									$Function = null;
									$Result = 0;

									switch($AttrMatch[1])
									{
										#
										case 'sum':
											if( preg_match("/[a-zA-Z]{1,}/", $Values[0], $Matches))
											{
												$Function = $Matches[0];
											}

											foreach($Values as $Value)
											{

												if( preg_match("/[a-zA-Z]{1,}/", $Value, $Matches))
												{
													$Function = $Matches[0];
												}

												$Result += $Value;
											}
											break;
										#
										case 'subtract':
											$Result = $Values[0];

											if( preg_match("/[a-zA-Z]{1,}/", $Values[0], $Matches))
											{
												$Function = $Matches[0];
											}

											for($xNumber=1; $xNumber<=count($Values)-1; $xNumber++)
											{

												if( preg_match("/[a-zA-Z]{1,}/", $Values, $Matches))
												{
													$Function = $Matches[0];
												}

												$Result -= $Values[$xNumber];
											}
											break;
										#
										case 'divide':
											$Result = $Values[0];

											if( preg_match("/[a-zA-Z]{1,}/", $Values[0], $Matches))
											{
												$Function = $Matches[0];
											}

											for($xNumber=1; $xNumber<=count($Values)-1; $xNumber++)
											{

												if( preg_match("/[a-zA-Z]{1,}/", $Values[$xNumber], $Matches))
												{
													$Function = $Matches[0];
												}

												$Result /= $Values[$xNumber];
											}
											break;
										#
										case 'multiply':
											$Result = $Values[0];

											if( preg_match("/[a-zA-Z]{1,}/", $Values[0], $Matches))
											{
												$Function = $Matches[0];
											}

											for($xNumber=1; $xNumber<=count($Values)-1; $xNumber++)
											{

												if( preg_match("/[a-zA-Z]{1,}/", $Values[$xNumber], $Matches))
												{
													$Function = $Matches[0];
												}

												$Result *= $Values[$xNumber];
											}
											break;
										#
										case 'dump':
											dump("Dump");
											dump("File: {$PathToFile}");
											dump("Line: {$y}");
											dump($AttrMatch[2]);

											exit();
											break;
									}

									$AttrValue = str_replace($AttrMatch[0], $Result.$Function, $AttrValue);

								}

								if( $AttrName == "extends")
								{

									if(isset(self::$ArrayCss[$AttrValue]))
									{
										self::$ArrayCss[self::$ElementName] = array_merge(self::$ArrayCss[self::$ElementName], self::$ArrayCss[$AttrValue]);
									}
									else
									{
										self::ElementNotFound($AttrValue, $PathToFile, $y + 1);
										exit;
									}

								} else {
									self::$ArrayCss[self::$ElementName][$AttrName] = $AttrValue;
								}
							}

						}
					}

					if($VariableOldName)
					{
						self::$Variables[$VariableOldName] = $VariableOldValue;
					}
				}

			}

			foreach(self::$ArrayCss as $Item => $Value)
			{
				if( empty($Value)) continue;

				$Response = $Item;
				$ResponseCompressed = $Item;

				$Response .= "\r{\r";
				$ResponseCompressed .= "{";

				foreach($Value as $AttrName => $AttrValue)
				{
					$Response .= "\t{$AttrName}: {$AttrValue};\r";
					$ResponseCompressed .= "{$AttrName}:{$AttrValue};";
				}

				$Response .= "}\r\r";
				$ResponseCompressed .= "}";

				self::$CssResponse .= $Response;
				self::$CssCompressed .= $ResponseCompressed;
			}

			#

			self::PutInTemplate('template');
		}

		public static function PutInTemplate($PathToFile, $Minify=null)
		{
			try
			{
				$FileName = sprintf("%s/%s%s.css", self::$Paths['css'], $PathToFile, $Minify);
				$FileOpen = fopen($FileName, "w");
				fwrite($FileOpen, self::$CssResponse);
				fclose($FileOpen);
			} catch(Exception $e)
			{
				exit( dump($e->getMessage()));
			}

			if( !$Minify)
			{
				self::PutInTemplate($PathToFile, ".min");
			}
		}

		public static function ElementNotFound($Name, $File, $DataLine)
		{
			$Response = null;
			$Response .= "Element `{$Name}` not found! \n";
			$Response .= "File: {$File} \n";
			$Response .= "Line: {$DataLine} \n\n";

			$File = \Files::getFile($File);
			$LineElement = "";

			for($x=($DataLine-5); $x<=($DataLine+4); $x++)
			{
				if( !isset($File[$x])) continue;

				$Response .= sprintf("%s. %s", ($x+1), $File[$x]);
			}

			dump($Response);
		}

	}