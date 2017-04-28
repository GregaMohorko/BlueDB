<?php

/*
 * Session.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 5, 2017 Grega Mohorko
 */

namespace BlueDB\DataAccess;

use BlueDB\Entity\FieldEntity;

/**
 * Note: Use Session only when loading by ID and when loading a full list of fields of the entity.
 */
class Session
{
	 /**
	  * Contains a list of already loaded entities.
	  * 
	  * Keys: class > ID
	  * 
	  * @var array
	  */
	private $entities;
	
	/**
	 * Contains a list of already loaded OneToMany lists.
	 * 
	 * Keys: class > OneToMany field > ID
	 * 
	 * @var array
	 */
	private $oneToManyLists;
	
	/**
	 * Contains a list of already loaded ManyToMany lists.
	 * 
	 * Keys: class > ManyToMany field > ID
	 * 
	 * @var array
	 */
	private $manyToManyLists;
	
	public function __construct()
	{
		$this->entities=[];
		$this->oneToManyLists=[];
		$this->manyToManyLists=[];
	}
	
	/**
	 * @param string $entityClass
	 * @param int $ID
	 * @return FieldEntity|bool The entity with the specified class and id, or FALSE if it doesn't exist.
	 */
	public function lookUp($entityClass,$ID)
	{
		if(!isset($this->entities[$entityClass])){
			return false;	
		}
		$classArray=$this->entities[$entityClass];
		if(!isset($classArray[$ID])){
			return false;	
		}
		return $classArray[$ID];
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param string $entityClass
	 * @param int $ID [optional]
	 * @return bool True, if the entity was added, or false, if an entity of the same class and ID already exists.
	 */
	public function add($entity,$entityClass,$ID)
	{
		if(isset($this->entities[$entityClass])){
			$classArray=&$this->entities[$entityClass];
			if(isset($classArray[$ID])){
				return false;
			}
		}else{
			$classArray=[];
			$this->entities[$entityClass]=&$classArray;
		}
		
		$classArray[$ID]=$entity;
		return true;
	}
	
	/**
	 * @param string $entityClass
	 * @param string $oneToManyField
	 * @param int $ID
	 * @return array|bool
	 */
	public function &lookUpOneToManyList($entityClass,$oneToManyField,$ID)
	{
		if(!isset($this->oneToManyLists[$entityClass])){
			$false=false;
			return $false;
		}
		$classArray=$this->oneToManyLists[$entityClass];
		if(!isset($classArray[$oneToManyField])){
			$false=false;
			return $false;
		}
		$fieldArray=$classArray[$oneToManyField];
		if(!isset($fieldArray[$ID])){
			$false=false;
			return $false;
		}
		return $fieldArray[$ID];
	}
	
	/**
	 * @param string $entityClass
	 * @param string $oneToManyField
	 * @param int $ID
	 * @return array
	 */
	public function &reserveOneToManyList($entityClass,$oneToManyField,$ID)
	{
		if(isset($this->oneToManyLists[$entityClass])){
			$classArray=&$this->oneToManyLists[$entityClass];
		}else{
			$classArray=[];
			$this->oneToManyLists[$entityClass]=&$classArray;
		}
		if(isset($classArray[$oneToManyField])){
			$fieldArray=&$classArray[$oneToManyField];
		}else{
			$fieldArray=[];
			$classArray[$oneToManyField]=&$fieldArray;
		}
		$newList=[];
		$fieldArray[$ID]=&$newList;
		
		return $newList;
	}
	
	/**
	 * @param string $entityClass
	 * @param string $manyToManyField
	 * @param int $ID
	 * @return array|bool
	 */
	public function &lookUpManyToManyList($entityClass,$manyToManyField,$ID)
	{
		if(!isset($this->manyToManyLists[$entityClass])){
			$false=false;
			return $false;
		}
		$classArray=$this->manyToManyLists[$entityClass];
		if(!isset($classArray[$manyToManyField])){
			$false=false;
			return $false;
		}
		$fieldArray=$classArray[$manyToManyField];
		if(!isset($fieldArray[$ID])){
			$false=false;
			return $false;
		}
		return $fieldArray[$ID];
	}
	
	/**
	 * @param string $entityClass
	 * @param string $manyToManyField
	 * @param int $ID
	 * @return array
	 */
	public function &reserveManyToManyList($entityClass,$manyToManyField,$ID)
	{
		if(isset($this->manyToManyLists[$entityClass])){
			$classArray=&$this->manyToManyLists[$entityClass];
		}else{
			$classArray=[];
			$this->manyToManyLists[$entityClass]=&$classArray;
		}
		if(isset($classArray[$manyToManyField])){
			$fieldArray=&$classArray[$manyToManyField];
		}else{
			$fieldArray=[];
			$classArray[$manyToManyField]=&$fieldArray;
		}
		$newList=[];
		$fieldArray[$ID]=&$newList;
		
		return $newList;
	}
}
