<?php

/*
 * BlueDBProperties.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Configuration;

class BlueDBProperties
{
	const HOST="host";
	const DB="db";
	const USER="user";
	const PASS="pass";
	const FORMAT_DATE="format_date";
	const FORMAT_TIME="format_time";
	const FORMAT_DATETIME="format_datetime";
	
	/**
	 * @var BlueDBProperties
	 */
	private static $instance;
	
	/**
	 * @return BlueDBProperties
	 */
	public static function Instance()
	{
		if(self::$instance===null)
			throw new Exception("The BlueDBProperties instance was not initialized.");
		return self::$instance;
	}
	
	/**
	 * @var string
	 */
	public $MySQL_host;
	/**
	 * @var string
	 */
	public $MySQL_databaseName;
	/**
	 * @var string
	 */
	public $MySQL_username;
	/**
	 * @var string
	 */
	public $MySQL_password;
	
	/**
	 * @var string
	 */
	public $Format_Date="Y-m-d";
	/**
	 * @var string
	 */
	public $Format_Time="H:i:s";
	/**
	 * @var string
	 */
	public $Format_DateTime="Y-m-d H:i:s";
	
	/**
	 * @param array $config
	 */
	private function __construct($config)
	{
		$mandatoryValues=[self::HOST,self::DB,self::USER,self::PASS];
		foreach($mandatoryValues as $mandatoryValue){
			if(!array_key_exists($mandatoryValue, $config))
				throw new Exception("The configuration file has to specify a '$mandatoryValue' value.");
		}
		
		$this->MySQL_host=$config[self::HOST];
		$this->MySQL_databaseName=$config[self::DB];
		$this->MySQL_username=$config[self::USER];
		$this->MySQL_password=$config[self::PASS];
		
		if(array_key_exists(self::FORMAT_DATE, $config))
			$this->Format_Date=$config[self::FORMAT_DATE];
		if(array_key_exists(self::FORMAT_TIME, $config))
			$this->Format_Time=$config[self::FORMAT_TIME];
		if(array_key_exists(self::FORMAT_DATETIME, $config))
			$this->Format_DateTime=$config[self::FORMAT_DATETIME];
	}
	
	private function __clone() { }
	private function __wakeup() { }
	
	/**
	 * @param array $config
	 */
	public static function init($config)
	{
		self::$instance=new BlueDBProperties($config);
	}
}
