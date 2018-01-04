<?php

/* 
 * index.php
 * 
 * Copyright 2018 Grega Mohorko
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 15, 2017 Grega Mohorko
 */

// enable error reporting
error_reporting(E_ALL|E_STRICT);
ini_set("display_errors","On");

$include=filter_input(INPUT_GET, "include");
if($include===null){
	$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
	header("Location: ".$actual_link."?include=all");
	exit();
}

include 'Test.php';

// stop execution on failed assert
assert_options(ASSERT_BAIL, 1);

try{
	$tests=getTestsToInclude($include);
	
	require_once "../src/BlueDB.php";
	
	foreach($tests as $testName){
		$testFilePath=$testName."/".$testName.".php";
		
		$resolved=stream_resolve_include_path($testFilePath);
		if(!$resolved){
			echo "<b>Error:</b> $testFilePath could not be resolved.";
			continue;
		}
		
		include $resolved;
		
		if(!class_exists($testName)){
			echo "<b>Error:</b> $testName class does not exist inside $testFilePath.";
			continue;
		}
		
		$test=new $testName();
		echo "<h4>Running $testName ...</h4><br/>";
		$test->run();
	}
	
	// connection should be closed when it will not be used anymore
	BlueDB\DataAccess\MySQL::close();
	
	echo "<h3>TESTING FINISHED</h3>";
} catch (Exception $ex) {
	echo getDescription($ex);
}

function getTestsToInclude($include)
{
	if($include==="" || $include==="all"){
		// include all folders
		return glob('Test*', GLOB_ONLYDIR);
	}
	
	$tests=[];
	$exploded=explode(",", $include);
	foreach($exploded as $number){
		$int=intval($number);
		if($int<1){
			die("Invalid parameter: include");
		}
		$testName="Test".$int;
		if(!file_exists($testName)){
			echo "<b>Warning:</b> Test $int doesn't exist.<br/>";
			continue;
		}
		$tests[]=$testName;
	}
	return $tests;
}

/**
* @param Exception $exception
* @return string
*/
function getDescription($exception)
{
	$desc="";

	$tabs="";

	$exCount=1;
	while($exception!=null){
		if($exCount>1){
			$desc.=$tabs."Inner Exception:".PHP_EOL;
			$tabs.="&nbsp;&nbsp;&nbsp;&nbsp;";
		}

		$stackTrace=$exception->getTraceAsString();
		$stackTrace=$tabs.str_replace(PHP_EOL,PHP_EOL.$tabs,$stackTrace);

		$desc.=$tabs."Message: ".$exception->getMessage().PHP_EOL;
		$desc.=$tabs."File: ".$exception->getFile()."(".$exception->getLine().")".PHP_EOL;
		$desc.=$tabs."StackTrace: ".PHP_EOL.$stackTrace.PHP_EOL;

		$exception=$exception->getPrevious();
		$exCount++;
	}

	$desc=str_replace(PHP_EOL, "<br/>", $desc);
	
	return $desc;
}
