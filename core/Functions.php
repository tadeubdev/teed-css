<?php

	namespace TeedCss;

	class Functions
	{

		# soma um ou mais valores
		public static function _sum($AttrValues, $Result, $FunctionValue)
		{
			for($xNumber=1; $xNumber<=count($AttrValues)-1; $xNumber++)
			{
				$OperatorName = Initialize::GetOperatorName($AttrValues[$xNumber]);
				$Result += $AttrValues[$xNumber];
				$Result .= $OperatorName;
			}

			return $Result;
		}

		# subtrai um ou mais valores
		public static function _subtract($AttrValues, $Result, $FunctionValue)
		{
			for($xNumber=1; $xNumber<=count($AttrValues)-1; $xNumber++)
			{
				$OperatorName = Initialize::GetOperatorName($AttrValues[$xNumber]);
				$Result -= $AttrValues[$xNumber];
				$Result .= $OperatorName;
			}

			return $Result;
		}

		# divide um ou mais valores
		public static function _divide($AttrValues, $Result, $FunctionValue)
		{
			for($xNumber=1; $xNumber<=count($AttrValues)-1; $xNumber++)
			{
				$OperatorName = Initialize::GetOperatorName($AttrValues[$xNumber]);
				$Result /= $AttrValues[$xNumber];
				$Result .= $OperatorName;
			}

			return $Result;
		}

		# multiplica um ou mais valores
		public static function _multiply($AttrValues, $Result, $FunctionValue)
		{
			for($xNumber=1; $xNumber<=count($AttrValues)-1; $xNumber++)
			{
				$OperatorName = Initialize::GetOperatorName($AttrValues[$xNumber]);
				$Result *= $AttrValues[$xNumber];
				$Result .= $OperatorName;
			}

			return $Result;
		}

		# extends: inclui atributos de um elemento em outro
		public static function _extends($AttrName, $AttrValue)
		{
			Initialize::$ExtendsArray[$AttrName] = $AttrValue;
		}

		# printa dados na tela
		public static function _dump($AttrValues, $Result, $FunctionValue)
		{
			dump("Dump");
			dump("File: {$PathToFile}");
			dump("Line: {$y}");
			dump($FunctionValue);

			exit();
		}

	}