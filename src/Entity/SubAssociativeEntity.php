<?php

/*
 * SubAssociativeEntity.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

namespace BlueDB\Entity;

abstract class SubAssociativeEntity extends SubEntity implements IAssociativeEntity
{
	use AssociativeEntityTrait;
}
