<?php

/*
 * StrongAssociativeEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

abstract class StrongAssociativeEntity extends StrongEntity implements IAssociativeEntity
{
	use AssociativeEntityTrait;
}
