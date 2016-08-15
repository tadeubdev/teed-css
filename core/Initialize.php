<?php

	namespace TeedCss;

	class Initialize
	{
		# nome do arquivo output
		public static $PathToFile = 'template';
		# tabulação estilo
		public static $Tabulation = "\t";
		# modo de chamada do elemento pai
		public static $ChamadaDoElementoPai = "&";
		# prefix das funções teed
		public static $FunctionsTeedCssBefore = "_";
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
		public static $CssTextArray = array();
		# nome do elemento css
		public static $ElementName = null;
		# adiciona itens ao array para estender mais tarde
		public static $ExtendsArray = array();

		public static function ConfigPaths()
		{
			# busca as variáveis globais
			$GlobalData = \Files::getData('globals.php');

			# seta os caminhos do projeto
			foreach($GlobalData['paths']->www as $Name => $Data)
			{
				self::$Paths[$Name] = \App::getUri() . $Data;
			}
		}

		# inicia o aplicativo
		public static function Start()
		{
			# if(\App::getEnv()->name == 'local')

			self::ConfigPaths();

			# Busca pelos arquivos teedcss
			$FilesTeedCss = glob(self::$Paths['teedcss'] . "/{**/*,*}.teedcss", GLOB_BRACE);

			# faz a leitura de um arquivo teedcss por vez
			foreach($FilesTeedCss as $PathToFile)
			{

				# busca o arquivo e coloca em $FileTeedCss
				$FileTeedCss = \Files::getData($PathToFile, true);
				$FileTeedCss = str_replace(array("\n","\r"), "", $FileTeedCss);

				for($IntFileTeedLoop=0; $IntFileTeedLoop<=count($FileTeedCss)-1; $IntFileTeedLoop++)
				{
					# linha do array Data
					$FileTeedCssLine = $FileTeedCss[$IntFileTeedLoop];

					# se a linha estiver vazia vai para a próxima
					if( strlen( trim( $FileTeedCssLine)) == 0 || substr($FileTeedCssLine, 0, 2) == '//') continue;

					# é uma variável, incluir
					$VerifyIfIsVariable = self::VerifyIfIsVariable($FileTeedCssLine);

					# se for uma variável passe para o próximo
					if( $VerifyIfIsVariable) continue;

					# se não houver espaço no inicio é um elemento
					# ler as próximas linhas
					if( substr_count($FileTeedCssLine,self::$Tabulation) == 0)
					{

						# adiciona um novo elemento ao array de css
						self::$CssTextArray[$FileTeedCssLine] = array();
						# seta o nome do elemento atual
						self::$ElementName = $FileTeedCssLine;

						# faz a leitura das próximas linhas quando é um novo elemento
						for($IntLineTeedLoop=$IntFileTeedLoop+1; $IntLineTeedLoop<=count($FileTeedCss)-1; $IntLineTeedLoop++)
						{
							$TeedCssLineChild = $FileTeedCss[$IntLineTeedLoop];

							# linha vazia, próximo
							if( strlen( trim( $TeedCssLineChild)) == 0) continue;

							# verifica se é um novo elemento, caso seja ele para o laço de repetição
							if( substr_count("/{$TeedCssLineChild}/", self::$Tabulation) == 0) break;

							# verifica se não é a última linha do arquivo
							if( $IntLineTeedLoop!=count($FileTeedCss)-1 &&

								# verifica se a próxima linha é um filho
								substr_count($FileTeedCss[$IntLineTeedLoop+1], self::$Tabulation) > substr_count($TeedCssLineChild, self::$Tabulation))
							{

								# verifica se é existe uma chamada para o pai
								if(preg_match("/".self::$ChamadaDoElementoPai."/", $TeedCssLineChild))
								{

									$TeedCssLineChild = trim($TeedCssLineChild);

									$Elements = explode(",", self::$ElementName);

									foreach($Elements as $Name => $Value)
									{
										$Elements[$Name] = str_replace(self::$ChamadaDoElementoPai, $Value, $TeedCssLineChild);
									}

									$SubElement = join($Elements,",");
								}
								else
								{
									$SubElement = str_replace(self::$Tabulation, "", $TeedCssLineChild);
									$SubElement = "{$FileTeedCssLine} {$SubElement}";
								}

								self::$CssTextArray[$SubElement] = array();
								self::$ElementName = $SubElement;
								// $IntLineTeedLoop++;
							}

							# limpa a variável
							$TeedCssLineChild = trim($TeedCssLineChild);

							# busca pelos itens da string
							$VariableMatches = explode(" ", $TeedCssLineChild, 2);

							if( !isset($VariableMatches[1]) ) continue;

							# nome e valor da string
							$AttrName = str_replace(self::$Tabulation, "", $VariableMatches[0]);
							$AttrValue = str_replace(self::$Tabulation, "", $VariableMatches[1]);

							# buscar variável do Nome
							$VerifyIfIsVariable = self::VerifyIfIsVariable($AttrName);

							# se for uma variável passe para o próximo
							if( $AttrName == "&" || $VerifyIfIsVariable) continue;

							# buscar variável do Valor e substitui
							$AttrValue = self::ReplaceVariableValue($AttrValue);

							# busca pelas funções disponíveis em `Funtions`
							$FunctionsTeedCss = get_class_methods(new Functions);
							$FunctionsTeedCssString = join($FunctionsTeedCss, "|");
							$FunctionsTeedCssString = str_replace(self::$FunctionsTeedCssBefore, "", $FunctionsTeedCssString);

							# verifica se existe AttrName uma das funções acima
							if( preg_match("/$FunctionsTeedCssString/", $AttrName, $AttrMatch))
							{
								$FunctionName = self::$FunctionsTeedCssBefore . "{$AttrName}";

								Functions::{"_{$AttrName}"}(self::$ElementName, $AttrValue);

								continue;
							}

							# verifica se existe AttrValue uma das funções acima
							if( preg_match("/($FunctionsTeedCssString)\((.*)\)/", $AttrValue, $AttrMatch))
							{
								# necessári para a class Function
								$AttrMatch[1] = self::$FunctionsTeedCssBefore . $AttrMatch[1];
								$AttrMatch[2] = explode(" ", $AttrMatch[2]);

								$Return = self::ReturnsFunctionsOptions($AttrMatch, $FileTeedCss[0]);
								# expressão encontrada e substituido valor
								$AttrValue = str_replace($AttrMatch[0], $Return, $AttrValue);
							}

							# adiciona o elemento a variável
							self::$CssTextArray[self::$ElementName][$AttrName] = $AttrValue;
						}
					}
				}
			}

			# faz uma varedura pelo array de extends
			foreach(self::$ExtendsArray as $ElementBefore => $ElementAfter)
			{

				# Faz uma varedura pelo array de css
				foreach(self::$CssTextArray as $Name => $Value)
				{

					if( preg_match("/{$ElementAfter}(,|:)/", $Name) ||
						preg_match("/{$ElementAfter}$/", $Name) )
					{

						$NewElementName = $Name;

						if( !preg_match("/{$ElementBefore}/", $Name))
						{
							$NewElementName = "{$Name},{$ElementBefore}";

							unset(self::$CssTextArray[$Name]);
						}

						if( preg_match("/:.*/", $Name, $Matches))
						{
							$NewElementName = str_replace($ElementBefore, "{$ElementBefore}{$Matches[0]}", $NewElementName);
						}

						self::$CssTextArray[$NewElementName] = $Value;
					}
				}
			}

			Finalize::PutInFiles();
		}

		# verifica se é uma variável e adiciona
		public static function VerifyIfIsVariable($String)
		{
			if( preg_match("/^(\\$[a-zA-Z0-9_-]{1,}) (.*)/", $String, $DataMatches))
			{
				self::$Variables[ $DataMatches[1] ] = $DataMatches[2];
				return true;
			}
			return false;
		}

		# verifica se é uma variável e troca valor
		public static function ReplaceVariableValue($String)
		{

			if( preg_match("/\\$[a-zA-Z0-9_-]{1,}/", $String, $Matches))
			{
				if(isset(self::$Variables[$Matches[0]]))
				{
					$String = str_replace($Matches[0], self::$Variables[$Matches[0]], $String);
				}
			}

			return $String;
		}

		public static function ReturnsFunctionsOptions($AttrExlode, $FunctionLine)
		{
			list($FunctionValue, $FunctionName, $AttrValues) = $AttrExlode;

			$OperatorName = self::GetOperatorName($AttrValues[0]);

			# primeiro valor do attr
			$Result = $AttrValues[0];

			$Result = Functions::{$FunctionName}($AttrValues, $Result, $FunctionValue);

			return $Result . $OperatorName;
		}

		# return: px, cm
		public static function GetOperatorName($String)
		{
			if( preg_match("/[a-zA-Z]{1,}/", $String, $OperatorMatch))
			{
				return $OperatorMatch[0];
			}

			return null;
		}

	}