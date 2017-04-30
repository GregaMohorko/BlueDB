<?php

/*
 * IAssociativeEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

interface IAssociativeEntity extends IAssociative
{
	/**
	 * Loads the entity that has the provided A and B object.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return IFieldEntity
	 */
	static function loadFor($AObject,$BObject,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * Loads the entity that has the provided A and B object and satisfies the provided criteria.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return IFieldEntity
	 */
	static function loadForByCriteria($AObject,$BObject,$criteria,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * Loads the entities that has the provided A and B object.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	static function loadListFor($AObject,$BObject,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null);
	
	/**
	 * Loads the entities that has the provided A and B object and satisfy the provided criteria.
	 * 
	 * @param IFieldEntity $AObject
	 * @param IFieldEntity $BObject
	 * @param $criteria
	 * @param array $fields [optional]
	 * @param array $fieldsToIgnore [optional]
	 * @param bool $inclManyToOne [optional]
	 * @param bool $inclOneToMany [optional]
	 * @param bool $inclManyToMany [optional]
	 * @return array
	 */
	static function loadListForByCriteria($AObject,$BObject,$criteria,$fields=null,$fieldsToIgnore=null,$inclManyToOne=null,$inclOneToMany=null,$inclManyToMany=null);
}
