<?php
class HgtReader{

	private static $htgFilesDestination;
	private static $resolution;
	private static $measPerDeg;
	private static $openedFiles=[];

	public static function init($htgFilesDestination, $resolution){
		self::$htgFilesDestination=$htgFilesDestination;
		self::$resolution=$resolution;
		switch ($resolution){
			case 1:self::$measPerDeg=3601;break;
			case 3:self::$measPerDeg=1201;break;
			default:
				throw new \Exception("bad resolution can be only one of 1,3");
		}
		register_shutdown_function(function (){
			HgtReader::closeAllFiles();
		});
	}

	public static function closeAllFiles(){
		foreach(self::$openedFiles as $file){
			fclose($file);
		}
		self::$openedFiles=[];
	}

	private static function getElevationAtPosition($fileName,$position){
		if (!array_key_exists($fileName,self::$openedFiles)){
			if (!file_exists(self::$htgFilesDestination.DIRECTORY_SEPARATOR.$fileName)){
				throw new \Exception("File '{$fileName}' not exists.");
			}
			$file=fopen(self::$htgFilesDestination.DIRECTORY_SEPARATOR.$fileName,"r");
			if ($file===false){
				throw new \Exception("Cant open file '{$fileName}' for reading.");
			}
			self::$openedFiles[$fileName]=$file;
		}else{
			$file=self::$openedFiles[$fileName];
		}
		fseek($file,$position);
		$short = fread($file,2);
		$shorts = reset(unpack("n*", $short));
		return $shorts;
	}

	public static function getElevation($lat, $lon){
		$N=self::getDeg($lat,2);
		$E=self::getDeg($lon,3);
		$fName="N{$N}E{$E}.hgt";
		$latSec = self::getSec($lat);
		$lonSec = self::getSec($lon);
		//TODO: calculate elevation from vertex

		$row = round($latSec/self::$resolution);
		$column = round($lonSec/self::$resolution);

		$aRow=self::$measPerDeg-$row;
		$position = (self::$measPerDeg * ($aRow-1) ) + $column;
		$position*=2;
		return self::getElevationAtPosition($fName,$position);
	}

	private static function getDeg($deg,$numPrefix){
		$deg = abs($deg);
		$d = round($deg, 0);     // round degrees
		if ($numPrefix>=3)
		if ($d<100) $d = '0' . $d; // pad with leading zeros
		if ($d<10) $d = '0' . $d;
		return $d;
	}

	private static function getSec($deg){
		$deg = abs($deg);
		$sec = round($deg*3600, 4);
		$m = fmod(floor($sec/60), 60);
		$s = round(fmod($sec, 60), 4);
		return ($m*60)+$s;
	}
}