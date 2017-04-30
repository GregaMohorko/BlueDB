<?php

/*
 * ISubEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 14, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

interface ISubEntity extends IFieldEntity
{
	/**
	 * Returns the class of the parent (super) entity of this sub-entity. Note that the returned super entity can also be a sub-entity.
	 * 
	 * @return string
	 */
	static function getParentEntityClass();
	
	/**
	 * Returns the base super class of this sub-entity. A base class is always a StrongEntity.
	 * 
	 * @return string Name of the StrongEntity class.
	 */
	static function getBaseStrongEntityClass();
	
	/**
	 * Returns the name of the property that represents the parent of this sub-entity.
	 * 
	 * @return string
	 */
	static function getParentFieldName();
	
	/**
	 * Creates an empty instance of this SubEntity, with initialized parents.
	 * 
	 * @return SubEntity
	 */
	static function createEmpty();
}
