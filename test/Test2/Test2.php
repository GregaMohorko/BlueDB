<?php

/*
 * Test2.php
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
 * @copyright Apr 4, 2017 Grega Mohorko
 */

require_once 'Address.php';
require_once 'User.php';
require_once 'Car.php';

use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\Configuration\BlueDBProperties;
use BlueDB\IO\JSON;
use BlueDB\Utility\EntityUtility;
use Test2\User;
use Test2\Address;
use Test2\Car;

/**
 * Tests loading, updating, saving and deleting StrongEntities with fields of type ManyToOne and OneToMany.
 * 
 * Also tests Criteria Expressions for these fields.
 */
class Test2 extends Test
{
	public function run()
	{
		// set the namespace for entities (this can also be done in the config.ini file)
		BlueDBProperties::instance()->Namespace_Entities="Test2";
		
		// run the .sql script
		$sqlScript=file_get_contents("Test2/Test2.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test2.sql.";
			return;
		}
		MySQL::queryMulti($sqlScript);
		
		$this->testLoadList();
		$this->testLoadListByCriteria();
		$this->testLoadSingle();
		$this->testExists();
		$this->testJson();
		$this->testUpdate();
		$this->testSave();
		$this->testDelete();
	}
	
	private function testLoadList()
	{
		// should load everything
		$users=User::loadList();
		assert(count($users)==3,"Count of all users");
		// checks if all data is correct (see Test2.sql)
		$this->checkRyan($users[0]);
		$this->checkBruce($users[1]);
		$this->checkJohn($users[2]);
		
		// should load all 3, but only the Address for each
		$users=User::loadList([User::AddressField]);
		assert(count($users)==3,"count of all users");
		// checks if really only Address was loaded
		/* @var $ryan User */
		$ryan=$users[0];
		assert($ryan->Name===null,"Ryans name not null");
		assert($ryan->Address!=null,"Ryans address is null");
		
		// load all addresses, but without OneToMany fields
		$addresses=Address::loadList(null,null,null,false);
		assert(count($addresses)===3,"Addresses count");
		assert($addresses[1]->Users===null,"Address Users");
		
		// load all users, but without OneToMany fields
		$users=User::loadList(null, null, null, false);
		assert(count($users)==3,"Count of all users");
		$ryan=$users[0];
		assert($ryan->Name==="Ryan","Without OneToMany fields");
		assert($ryan->BestFriendTo===null,"Without OneToMany fields");
		assert($ryan->BestFriend->BestFriendTo===null,"Without OneToMany fields");
	}
	
	private function testLoadListByCriteria()
	{
		// should load all users (no expressions in criteria)
		$criteria=new Criteria(User::class);
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==3,"Count of all users");
		$this->checkRyan($users[0]);
		$this->checkBruce($users[1]);
		$this->checkJohn($users[2]);
		
		// should load all users whose address is 'Rapture'
		$rapture=new Address();
		$rapture->Street="Rapture";
		$criteria->add(Expression::equal(User::class, User::AddressField, $rapture));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==1,"Count of all users");
		/* @var $ryan User */
		$ryan=$users[0];
		$this->checkRyan($ryan);
		
		// should load all users whose address street is 'Gotham' and ID is 42
		$rapture->Street="Gotham";
		$rapture->ID=42;
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::equal(User::class, User::AddressField, $rapture));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==0,"Count of all users");
		
		// should load all users whose address street is 'Citadel' and ID is 3
		$rapture->Street="Citadel";
		$rapture->ID=3;
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::equal(User::class, User::AddressField, $rapture));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==1,"Count of all users");
		$this->checkJohn($users[0]);
		
		// should load all users whose address street is 'Rapture' or name is 'Bruce'
		$criteria=new Criteria(User::class);
		$rapture=new Address();
		$rapture->Street="Rapture";
		$expressions=[];
		$expressions[]=Expression::equal(User::class, User::AddressField, $rapture);
		$expressions[]=Expression::equal(User::class,User::NameField,"Bruce");
		$criteria->add(Expression::any($expressions));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==2,"Count of all users");
		$this->checkRyan($users[0]);
		$this->checkBruce($users[1]);
	}
	
	private function testLoadSingle()
	{
		// should load the user whose address ID is 3
		$criteria=new Criteria(User::class);
		$address3=new Address();
		$address3->ID=3;
		$criteria->add(Expression::equal(User::class, User::AddressField, $address3));
		/* @var $john User */
		$john=User::loadByCriteria($criteria);
		assert($john!=null,"John is not null");
		$this->checkJohn($john);
	}
	
	private function testExists()
	{
		assert(User::exists(User::AddressField, null)===false,"Exists user with null address");
		assert(User::exists(User::AddressField, 1)===true,"Exists user with address with ID=1");
		
		// check if there exists an user with address of street 'Citadel'
		$criteria=new Criteria(User::class);
		$citadel=new Address();
		$citadel->Street="Citadel";
		$criteria->add(Expression::equal(User::class, User::AddressField, $citadel));
		assert(User::existsByCriteria($criteria)===true,"Exists user with address Citadel");
		
		// check if there exists an user with address of street 'Celje'
		$criteria=new Criteria(User::class);
		$celje=new Address();
		$celje->Street="Celje";
		$criteria->add(Expression::equal(User::class, User::AddressField, $celje));
		assert(User::existsByCriteria($criteria)===false,"Exists user with address Celje");
	}
	
	private function testJson()
	{
		$ryan=User::loadByID(1);
		$bruce=User::loadByID(2);
		$john=User::loadByID(3);
		
		// encode a single entity to JSON and then decode
		$json=JSON::encode($ryan);
		$ryanDecoded=JSON::decode($json);
		assert(EntityUtility::areEqual($ryan, $ryanDecoded),"JSON encode decode");
		
		// clone?
		$bruceClone=clone $bruce;
		assert(EntityUtility::areEqual($bruce, $bruceClone),"JSON encode decode clone");
		
		// encode and decode a list of entities
		$list=[$ryan,$bruce,$john];
		$json=JSON::encode($list);
		$list=JSON::decode($json);
		assert(EntityUtility::areEqual($ryan, $list[0]),"JSON encode decode list");
		assert(EntityUtility::areEqual($bruce, $list[1]),"JSON encode decode list");
		assert(EntityUtility::areEqual($john, $list[2]),"JSON encode decode list");
	}
	
	private function testUpdate()
	{
		// renames Ryan to Grega
		/* @var $ryan User */
		$ryan=User::loadByID(1);
		$ryan->Name="Grega";
		User::update($ryan);
		// check if Ryan is now really Grega
		/* @var $grega User */
		$grega=User::loadByID(1);
		assert($grega->Name==="Grega","Renaming Ryan");
		$john=User::loadByID(3);
		assert($john->BestFriend->Name==="Grega","Renaming Ryan");
	}
	
	private function testSave()
	{
		// create new empty address
		$emptyAddress=new Address();
		Address::save($emptyAddress, true, false);
		// check if it was created
		/* @var $emptyAddress Address */
		$emptyAddress=Address::loadByID($emptyAddress->ID);
		assert($emptyAddress!==null,"Saving new empty address");
		assert($emptyAddress->Street===null,"Empty address street");
		
		// create address Celje and assign it to Grega
		$celje=new Address();
		$celje->Street="Celje";
		Address::save($celje,false,false);
		/* @var $grega User */
		$grega=User::loadByID(1);
		$grega->Address=$celje;
		User::update($grega,false,false,[User::AddressField]);
		$grega=User::loadByID(1);
		assert($grega->Address->Street==="Celje","Assigning new address to an user");
		
		// create new User Frodo with address Shire
		$shire=new Address();
		$shire->Street="Shire";
		Address::save($shire,false,false);
		$frodo=new User();
		$frodo->Name="Frodo";
		$frodo->Address=$shire;
		User::save($frodo,false,true);
		/* @var $frodo User */
		$frodo=User::loadByID($frodo->ID);
		assert($frodo->Name==="Frodo","Frodos name");
		assert($frodo->Address->Street==="Shire","Frodos address street");
	}
	
	private function testDelete()
	{
		// delete rapture
		$criteria=new Criteria(Address::class);
		$criteria->add(Expression::equal(Address::class, Address::StreetField, "Rapture"));
		$rapture=Address::loadByCriteria($criteria);
		Address::delete($rapture);
		$rapture=Address::loadByCriteria($criteria);
		assert($rapture===null,"Deleting rapture");
		
		// try to delete all addresses
		$allAddressses=Address::loadList();
		try{
			// Users are pointing to addresses ...
			Address::deleteList($allAddressses);
			assert(false,"Deleting all address not throwing exception because of foreign key constraints");
		} catch (Exception $ex) {
			// BlueDB automatically rerolls a transaction if it was open, so we don't need to do that here
		}
		
		// try to delete Bruce
		/* @var $bruce User */
		$bruce=User::loadByID(2);
		try{
			// Bruce is the best friend of Grega ...
			User::delete($bruce);
			assert(false,"Deleting Bruce not throwing exception because of foreign key constraints");
		} catch (Exception $ex) { }
		
		// set John as the bestfriend of Grega
		$john=$bruce->BestFriend;
		$grega=$john->BestFriend;
		$grega->BestFriend=$john;
		User::update($grega, true, false, [User::BestFriendField]);
		
		// Bruce can now be deleted, even though that Bruce and car Tank are pointing to each other. BlueDB takes care of that :)
		// but note that this works because the constraint in Car is set to cascade on delete
		$bruceID=$bruce->ID;
		$bruce=new User();
		$bruce->ID=$bruceID;
		User::delete($bruce,false,true);
		$bruce=User::loadByID(2);
		assert($bruce===null,"Deleting john");
		
		// let's try to delete John
		try{
			// Grega and John are now best friends with each other
			// deleting them is not possible because the constraint on BestFriend is not set to cascade on delete
			User::delete($john);
			assert(false,"Deleting John not throwing exception due to foreign key constraints");
		} catch (Exception $ex) { }
		
		$grega->BestFriend=null;
		User::update($grega,true,false,[User::BestFriendField]);
		// Grega is now still the best friend of John, but Grega doesn't have a best friend
		
		// deleting John is now possible
		User::delete($john,false,false);
		$john=User::loadByID(3);
		assert($john===null,"Deleting john");
		
		// delete all users
		// note that this does not raise exception due to foreign key constraints because of the ON DELETE CASCADE (see Test2.sql)
		$allUsers=User::loadList();
		User::deleteList($allUsers,false,false);
		$allUsers=User::loadList();
		assert(empty($allUsers),"Deleting all users");
		
		// delete all addresses
		$allAddressses=Address::loadList();
		Address::deleteList($allAddressses, false, false);
		$allAddressses=Address::loadList();
		assert(empty($allAddressses),"Deleting all addresses");
		
		// delete all cars
		$allCars=Car::loadList();
		Car::deleteList($allCars,false,true);
		$allCars=Car::loadList();
		assert(empty($allCars),"Deleting all cars");
	}
	
	/**
	 * @param User $ryan
	 */
	private function checkRyan($ryan)
	{
		assert($ryan->ID===1,"Ryans ID");
		assert($ryan->Name==="Ryan","Ryans name");
		assert($ryan->Address!=null,"Ryans address");
		assert($ryan->Address->Street=="Rapture","Ryan address street");
		assert(count($ryan->Address->Users)===1,"Ryans address users count");
		assert($ryan->Address->Users[0]->Name==="Ryan","Ryans address user");
		assert($ryan->Car!=null,"Ryans car");
		assert($ryan->Car->Brand==="Ford","Ryans car brand");
		assert($ryan->Car->Owner->ID===$ryan->ID,"Ryans car owner");
		assert($ryan->BestFriend!=null,"Ryans best friend");
		assert($ryan->BestFriend->Name==="Bruce","Ryans best friend name");
		assert(count($ryan->BestFriendTo)===1,"Ryans best friend to");
		assert($ryan->BestFriendTo[0]->Name==="John","Ryans best friend should be 'John' but is '".$ryan->BestFriendTo[0]->Name."'");
	}
	
	/**
	 * @param User $bruce
	 */
	private function checkBruce($bruce)
	{
		assert($bruce->ID===2,"Bruces ID");
		assert($bruce->Name==="Bruce","Bruces name");
		assert($bruce->Address!=null,"Bruces address");
		assert($bruce->Address->Street=="Gotham","Bruces address street");
		assert(count($bruce->Address->Users)===1,"Bruces address users count");
		assert($bruce->Address->Users[0]->Name==="Bruce","Bruces address user");
		assert($bruce->Car!=null,"Bruces car");
		assert($bruce->Car->Brand==="Tank","Bruces car brand");
		assert($bruce->Car->Owner->ID===$bruce->ID,"Bruces car owner");
		assert($bruce->BestFriend!=null,"Bruces best friend");
		assert($bruce->BestFriend->Name==="John","Bruces best friend name");
		assert(count($bruce->BestFriendTo)===1,"Bruces best friend to");
		assert($bruce->BestFriendTo[0]->Name==="Ryan","Bruces best friend should be 'Ryan' but is '".$bruce->BestFriendTo[0]->Name."'");
	}
	
	/**
	 * @param User $john
	 */
	private function checkJohn($john)
	{
		assert($john->ID===3,"Johns ID");
		assert($john->Name==="John","Johns name");
		assert($john->Address!=null,"Johns address");
		assert($john->Address->Street=="Citadel","Johns address street");
		assert(count($john->Address->Users)===1,"Johns address users count");
		assert($john->Address->Users[0]->Name==="John","Johns address user");
		assert($john->Car!=null,"Johns car");
		assert($john->Car->Brand==="Normandy","Johns car brand");
		assert($john->Car->Owner->ID===$john->ID,"Johns car owner");
		assert($john->BestFriend!=null,"Johns best friend");
		assert($john->BestFriend->Name==="Ryan","Johns best friend name");
		assert(count($john->BestFriendTo)===1,"Johns best friend to");
		assert($john->BestFriendTo[0]->Name==="Bruce","Johns best friend should be 'Bruce' but is '".$john->BestFriendTo[0]->Name."'");
	}
}
