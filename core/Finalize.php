<?php

	namespace TeedCss;

	class Finalize
	{
		public static function PutInFiles()
		{

			# Busca dados css no array
			foreach(Initialize::$CssTextArray as $Item => $Value)
			{
				if( empty($Value)) continue;

				# compressado e não compressdo
				$ContentResponse = null;
				$ContentResponseCompressed = null;

				foreach($Value as $AttrName => $AttrValue)
				{
					$ContentResponse .= "\t{$AttrName}: {$AttrValue};\r";
					$ContentResponseCompressed .= "{$AttrName}:{$AttrValue};";
				}

				$Response = sprintf("%s\r{\r%s}\r", $Item, $ContentResponse);
				$ResponseCompressed = sprintf("%s{%s}", $Item, $ContentResponseCompressed);

				Initialize::$CssResponse .= $Response;
				Initialize::$CssCompressed .= $ResponseCompressed;
			}

			#

			self::PutInTemplate();
		}

		# colocar conteúdo em arquivos
		public static function PutInTemplate($Minify=null)
		{
			try
			{
				# arquivo teedcss
				$FileToWrite = Initialize::$CssResponse;

				# na segunda chamada seta para o arquivo minificado
				if($Minify) $FileToWrite = Initialize::$CssCompressed;

				$FileName = sprintf("%s/%s%s.css", Initialize::$Paths['css'], Initialize::$PathToFile, $Minify);
				$FileOpen = fopen($FileName, "w+");
				fwrite($FileOpen, $FileToWrite);
				fclose($FileOpen);

			} catch(Exception $e)
			{
				exit( $e->getMessage());
			}

			# se for a primeira volta, setar minifyer
			if( !$Minify)
			{
				self::PutInTemplate(".min");
			}
		}
	}