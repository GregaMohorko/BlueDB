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
	  * Contains a list of already fully loaded entities.
	  * 
	  * Keys: class > ID > [entity,bool]
	  * 
	  * @var array
	  */
	private $entities;
	
	public function __construct()
	{
		$this->entities=[];
	}
	
	/**
	 * @param string $entityClass
	 * @param int $ID
	 * @return FieldEntity|Boolean The entity with the specified class and id, or FALSE if it doesn't exist.
	 */
	public function lookUp($entityClass,$ID)
	{
		if(!isset($this->entities[$entityClass]))
			return false;
		$classArray=$this->entities[$entityClass];
		if(!isset($classArray[$ID]))
			return false;
		return $classArray[$ID];
	}
	
	/**
	 * @param string $entityClass
	 * @param string $manyToOneField
	 * @param int $ID
	 * @return array|bool An array of entities whose the specified ManyToOne field entity has the specified ID, or FALSE if no such entity exists.
	 */
	public function lookUpByOneToMany($entityClass,$manyToOneField,$ID)
	{
		if(!isset($this->entities[$entityClass]))
			return false;
		$classArray=$this->entities[$entityClass];
		
		$result=[];
		$foundAtLeastOne=false;
		foreach($classArray as $persistedEntity){
			/* @var $persistedEntity FieldEntity */
			
			if($persistedEntity->$manyToOneField!==null){
				$manyToOneFieldValue=$persistedEntity->$manyToOneField;
				if(is_int($manyToOneFieldValue)){
					// is still as a foreign key
					$id=$manyToOneFieldValue;
				}else if(is_string($manyToOneFieldValue)){
					// sometimes it is as a string ...
					$id=intval($manyToOneFieldValue);
				}else{
					// is already an object
					/* @var $manyToOneEntity FieldEntity */
					$manyToOneEntity=$manyToOneFieldValue;
					$id=$manyToOneEntity->getID();
				}
				if($id===$ID){
					$foundAtLeastOne=true;
					$result[]=$persistedEntity;
				}
			}
		}
		
		if($foundAtLeastOne)
			return $result;
		
		return false;
	}
	
	/**
	 * @param FieldEntity $entity
	 * @param string $entityClass
	 * @param int $ID [optional]
	 */
	public function add($entity,$entityClass,$ID=null)
	{
		if($ID===null)
			$ID=$entity->getID();
		
		$classArray=(isset($this->entities[$entityClass])) ? $this->entities[$entityClass] : [];
		$classArray[$ID]=$entity;
		$this->entities[$entityClass]=$classArray;
	}
}
