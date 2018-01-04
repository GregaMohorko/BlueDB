<?php

/*
 * ISubEntity.php
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
}
