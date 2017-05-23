<?php

/* 
 * Test1.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Mar 15, 2017 Grega Mohorko
 */

require_once 'User.php';
require_once 'UserType.php';

use BlueDB\Configuration\BlueDBProperties;
use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\IO\JSON;
use BlueDB\Utility\EntityUtility;
use Test1\User;
use Test1\UserType;

/**
 * Tests loading, exists, updating, saving and deleting of StrongEntities for fields of type Property.
 * 
 * Also tests Criteria Expressions (for properties only): above, after, between, contains, equal, any, startsWith, endsWith.
 */
class Test1 extends Test
{
	public function run()
	{
		// set the namespace for entities (this can also be done in the config.ini file)
		BlueDBProperties::instance()->Namespace_Entities="Test1";
		
		// run the .sql script
		$sqlScript=file_get_contents("Test1/Test1.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test1.sql.";
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
		// should load everything inside User table
		$users=User::loadList();
		assert(count($users)==3, "Count of all users");
		// checks if all data is correct (see Test1.sql)
		$this->checkGordon($users[0]);
		$this->checkAlyx($users[1]);
		$this->checkBarney($users[2]);
		
		// should also load everything inside User table, but only with ID
		$users=User::loadList([]);
		assert(count($users)==3, "Count of all users");
		assert($users[0]->ID===1,"Empty fields");
		assert($users[1]->Username===null);
		// let's just assume that everything else is OK
		
		// should load all 3, but only the username and password for each
		$users=User::loadList([User::UsernameField,User::PasswordField]);
		assert(count($users)==3, "Count of all users");
		// checks if really only Username and Password were loaded
		$gordon=$users[0];
		assert($gordon->Username==="Gordon","Gordons username");
		assert($gordon->Password==="Crowbar","Gordons password");
		assert($gordon->Email===null,"Gordons email");
		// lets just assume that if Email was null, all other non-included fields are also null
		// lets also assume that if it was okay for one user, it's also okay for all others
		
		// should load all 3 with all fields except Cash
		$users=User::loadList(null, [User::CashField]);
		assert(count($users)==3, "Count of all users");
		// checks if cash really wasn't loaded
		$alyx=$users[1];
		assert($alyx->Cash===null,"Alyxes cash");
		assert($alyx->Username==="Alyx","Alyxes username");
	}
	
	private function testLoadListByCriteria()
	{
		// should load all users (no expressions in criteria)
		$criteria=new Criteria(User::class);
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==3, "Count of all users");
		$this->checkGordon($users[0]);
		$this->checkAlyx($users[1]);
		$this->checkBarney($users[2]);
		
		// should load all users whose type is EDITOR, but only with Cash field
		$criteria->add(Expression::equal(User::class, User::TypeField, UserType::EDITOR));
		$users=User::loadListByCriteria($criteria, [User::CashField]);
		assert(count($users)==2, "Count of all users");
		/* @var $barney User */
		$barney=$users[1];
		assert($barney->Cash===0.0,"Barneys cash");
		assert($barney->Username===null,"Barneys username");
		
		// the same, with all fields except Username
		$users=User::loadListByCriteria($criteria, null, [User::UsernameField]);
		assert(count($users)==2, "Count of all users");
		/* @var $alyx User */
		$alyx=$users[0];
		assert($alyx->Username===null,"Alyxes username");
		assert($alyx->CarCount===3,"Alyxes car count");
		
		// should load all users whose type is EDITOR and whose username contains letter 'x'
		$criteria->add(Expression::contains(User::class, User::UsernameField, "x"));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==1, "Count of all users");
		$this->checkAlyx($users[0]);

		// should load all users whose email contains 'free'
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::contains(User::class, User::EmailField, "free"));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==1, "Count of all users");
		$this->checkGordon($users[0]);
		
		// should load all users whose car count is above 11
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::above(User::class, User::CarCountField, 11));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==1, "Count of all users");
		$this->checkGordon($users[0]);
		
		// should load all users whose car count is below 11
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::below(User::class, User::CarCountField, 11));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==1, "Count of all users");
		$this->checkAlyx($users[0]);
		
		// should load all users whose birthday is after current date
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::afterNow(User::class, User::BirthdayField));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==0, "Count of all users");
		
		// should load all users whose birthday is before current date
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::beforeNow(User::class, User::BirthdayField));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)===3, "Count of all users");
		$this->checkGordon($users[0]);
		$this->checkAlyx($users[1]);
		$this->checkBarney($users[2]);
		
		// should load all users whose birthday is between 1980 and 1990
		$criteria=new Criteria(User::class);
		$d1980=new DateTime();
		$d1980->setDate(1980, 1, 1);
		$d1990=new DateTime();
		$d1990->setDate(1990, 1, 1);
		$criteria->add(Expression::between(User::class, User::BirthdayField, $d1980, $d1990));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==2, "Count of all users");
		$this->checkGordon($users[0]);
		$this->checkAlyx($users[1]);
		
		// should load all users whose car count is between 10 and 50
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::between(User::class, User::CarCountField, 10, 50));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==2, "Count of all users");
		$this->checkGordon($users[0]);
		$this->checkBarney($users[1]);
		
		// should load all users whose cash is between 0 and 50
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::between(User::class, User::CashField, 0, 50));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==2, "Count of all users");
		$this->checkAlyx($users[0]);
		$this->checkBarney($users[1]);
		
		// should load all users whose car count is either 11 or 42
		$criteria=new Criteria(User::class);
		$expressions=[];
		$expressions[]=Expression::equal(User::class, User::CarCountField, 11);
		$expressions[]=Expression::equal(User::class, User::CarCountField, 42);
		$criteria->add(Expression::any($expressions));
		$users=User::loadListByCriteria($criteria);
		assert(count($users)==2, "Count of all users");
		$this->checkGordon($users[0]);
		$this->checkBarney($users[1]);
	}
	
	private function testLoadSingle()
	{
		// loads Gordon by username
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::equal(User::class, User::UsernameField, "Gordon"));
		/* @var $user User */
		$user=User::loadByCriteria($criteria);
		$this->checkGordon($user);
		
		// the same, but only email field
		$user=User::loadByCriteria($criteria, [User::EmailField]);
		assert($user->Email==="gordon.freeman@black.mesa","Gordons email");
		assert($user->Username===null,"Gordons username");
		
		// the same, with all fields except email
		$user=User::loadByCriteria($criteria, null, [User::EmailField]);
		assert($user->Email===null,"Gordons email");
		assert($user->Username==="Gordon","Gordons username");
		
		// loads Alyx by ID
		$user=User::loadByID(2);
		$this->checkAlyx($user);
		
		// trying to load by non-existing ID should return null
		$user=User::loadByID(9999);
		assert($user===null,"Loading by non-existing ID");
		
		// trying to load by criteria which conditition satisfies none of the entries, should return null
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::equal(User::class, User::UsernameField, "Kleiner"));
		$user=User::loadByCriteria($criteria);
		assert($user===null,"Loading by non-existing criteria");
		
		// trying to load by criteria which condition satisfies multiple entries, should throw an exception
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::contains(User::class, User::UsernameField, "a"));
		try{
			$user=User::loadByCriteria($criteria);
			assert(false,"Loading by criteria which is satisfied by multiple entries");
		} catch (Exception $ex) { }
	}
	
	private function testExists()
	{
		assert(User::exists(User::UsernameField, "Gordon")===true,"Exists username Gordon");
		assert(User::exists(User::UsernameField,"Kleiner")===false,"Exists username Kleiner");
		assert(User::exists(User::CarCountField,42)===true,"Exists car count 42");
		assert(User::exists(User::CarCountField,67)===false,"Exists car count 67");

		// checks if there exists a user whose username contains a letter 'a'
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::contains(User::class, User::UsernameField, "a"));
		assert(User::existsByCriteria($criteria)===true,"Exists user with letter 'a' in username");
		
		// check if there exists a user whose username starts with 'A'
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::startsWith(User::class, User::UsernameField, "A"));
		assert(User::existsByCriteria($criteria)===true,"Exists user whose username starts with 'A'");
		
		// checks if there exists a user whose username starts with 'on'
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::startsWith(User::class, User::UsernameField, "on"));
		assert(User::existsByCriteria($criteria)===false,"Exists user whose username starts with 'on'");
		
		// check if there exists a user whose username ends with 'yx'
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::endsWith(User::class, User::UsernameField, "yx"));
		assert(User::existsByCriteria($criteria)===true,"Exists user whose username ends with 'yx'");
		
		// check if there exists a user whose username ends with 'do'
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::endsWith(User::class, User::UsernameField, "do"));
		assert(User::existsByCriteria($criteria)===false,"Exists user whose username ends with 'do'");
	}
	
	private function testJson()
	{
		$gordon=User::loadByID(1);
		$alyx=User::loadByID(2);
		$barney=User::loadByID(3);
		
		// encode a single entity to JSON and then decode
		$json=JSON::encode($gordon);
		$gordonDecoded=JSON::decode($json);
		assert(!EntityUtility::areEqual($gordon, $alyx),"Comparing entities");
		assert(EntityUtility::areEqual($gordon, $gordonDecoded),"JSON encode decode");
		
		// clone?
		/* @var $alyxClone User */
		$alyxClone=clone $alyx;
		assert(EntityUtility::areEqual($alyx, $alyxClone),"JSON encode decode clone");
		$alyxClone->IsOkay=false;
		assert(!EntityUtility::areEqual($alyx, $alyxClone),"JSON encode decode clone");
		
		// encode and decode a list of entities
		$list=[$gordon,$alyx,$barney];
		$json=JSON::encode($list);
		$list=JSON::decode($json);
		assert(EntityUtility::areEqual($gordon, $list[0]),"JSON encode decode list");
		assert(EntityUtility::areEqual($alyx, $list[1]),"JSON encode decode list");
		assert(EntityUtility::areEqual($barney, $list[2]),"JSON encode decode list");
	}
	
	private function testUpdate()
	{
		// gives Gordon 100 cash and also begins transaction, but doesn't commit it
		/* @var $gordon User */
		$gordon=User::loadByID(1);
		$gordon->Cash+=100;
		User::update($gordon, true, false);
		// check if gordon really has +100 cash now
		$gordon=User::loadByID(1, [User::CashField]);
		assert($gordon->Cash===167.42,"Gordons cash update");
		
		// updates only the password of barney
		/* @var $barney User */
		$barney=User::loadByID(3);
		$barney->Password="IAmDead";
		User::update($barney, false, false, [User::PasswordField]);
		$barney=User::loadByID(3,[User::PasswordField]);
		assert($barney->Password==="IAmDead","Barneys password update");
		
		// set everyones email to null and also commit
		$all=User::loadList([User::IDField,User::EmailField]);
		foreach($all as $user)
			/* @var $user User */
			$user->Email=null;
		User::updateList($all, false, true, [User::EmailField]);
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::equal(User::class, User::EmailField, null));
		$all=User::loadListByCriteria($criteria);
		assert(count($all)==3, "Count of all users");
	}
	
	private function testSave()
	{
		// create new empty user
		$emptyUser=new User();
		User::save($emptyUser,true,false);
		// check if it was created
		$emptyUser=User::loadByID(4);
		assert($emptyUser!==null,"Saving new empty user");
		assert($emptyUser->Username===null,"Empty users username");
		// lets assume that if username is null, all other fields are too
		
		// create new user
		$kleiner=new User();
		$kleiner->Username="Kleiner";
		$kleiner->Password="Fiddlesticks";
		$kleiner->IsOkay=true;
		$kleiner->Created=new DateTime();
		User::save($kleiner, false, false);
		/* @var $kleiner User */
		$kleiner=User::loadByID(5);
		assert($kleiner!==null,"Saving new user");
		assert($kleiner->Username==="Kleiner","Kleiners username");
		
		// create three new users
		$newUsers=[];
		for($i=1;$i<=3;++$i){
			$soldier=new User();
			$soldier->Username="Soldier$i";
			$newUsers[]=$soldier;
		}
		User::saveList($newUsers, false, true);
		$criteria=new Criteria(User::class);
		$criteria->add(Expression::startsWith(User::class, User::UsernameField, "Soldier"));
		$newUsers=User::loadListByCriteria($criteria);
		assert(count($newUsers)==3, "Count of all saved users");
		assert($newUsers[0]->Username==="Soldier1","Soldier1s username");
		assert($newUsers[1]->Username==="Soldier2","Soldier2s username");
		assert($newUsers[2]->Username==="Soldier3","Soldier3s username");
	}
	
	private function testDelete()
	{
		// delete gordon
		$gordon=new User();
		$gordon->ID=1;
		User::delete($gordon, true, false);
		$gordon=User::loadByID(1);
		assert($gordon===null,"Deleting Gordon");
		
		// delete everybody
		$all=User::loadList();
		User::deleteList($all, false, true);
		$all=User::loadList();
		assert(count($all)===0,"Deleting everybody");
	}
	
	/**
	 * @param User $gordon
	 */
	private function checkGordon($gordon)
	{
		assert($gordon->ID===1,"Gordons ID");
		assert($gordon->Username==="Gordon","Gordons username");
		// note that password is a hidden field, therefore it should only be loaded when explicitly asked for!
		assert($gordon->Password===null,"Gordons password");
		assert($gordon->Type===UserType::ADMIN,"Gordons user type");
		assert($gordon->CarCount===42,"Gordons car count");
		assert($gordon->Cash===67.42,"Gordons cash");
		assert($gordon->IsOkay===true,"Gordons is okay");
		assert($gordon->Email==="gordon.freeman@black.mesa","Gordons email");
		assert($gordon->Birthday->format(DATE_W3C)==="1985-04-08T00:00:00+00:00","Gordons birthday");
		assert($gordon->AlarmClock->format(DATE_W3C)==="0000-01-01T10:00:00+00:00","Gordons alarm clock");
		assert($gordon->Created->format(DATE_W3C)==="2017-03-15T02:08:20+00:00","Gordons created");
	}
	
	/**
	 * @param User $alyx
	 */
	private function checkAlyx($alyx)
	{
		assert($alyx->ID===2,"Alyxes ID");
		assert($alyx->Username==="Alyx","Alyxes username");
		assert($alyx->Password===null,"Alyxes password");
		assert($alyx->Type===UserType::EDITOR,"Alyxes user type");
		assert($alyx->CarCount===3,"Alyxes car count");
		assert($alyx->Cash===42.67,"Alyxes cash");
		assert($alyx->IsOkay===true,"Alyxes is okay");
		assert($alyx->Email==="alyx.vance@black.mesa","Alyxes email");
		assert($alyx->Birthday->format(DATE_W3C)==="1987-12-24T00:00:00+00:00","Alyxes birthday");
		assert($alyx->AlarmClock->format(DATE_W3C)==="0000-01-01T09:50:00+00:00","Alyxes alarm clock");
		assert($alyx->Created->format(DATE_W3C)==="2017-03-15T03:09:21+00:00","Alyxes created");
	}
	
	/**
	 * @param User $barney
	 */
	private function checkBarney($barney)
	{
		assert($barney->ID===3,"Barneys ID");
		assert($barney->Username==="Barney","Barneys username");
		assert($barney->Password===null,"Barneys password");
		assert($barney->Type===UserType::EDITOR,"Barneys user type");
		assert($barney->CarCount===11,"Barneys car count");
		assert($barney->Cash===0.0,"Barneys cash");
		assert($barney->IsOkay===false,"Barneys is okay");
		assert($barney->Email==="barney.calhoun@black.mesa","Barneys email");
		assert($barney->Birthday->format(DATE_W3C)==="1990-08-07T00:00:00+00:00","Barneys birthday");
		assert($barney->AlarmClock->format(DATE_W3C)==="0000-01-01T10:15:34+00:00","Barneys alarm clock");
		assert($barney->Created->format(DATE_W3C)==="2017-03-15T04:10:22+00:00","Barneys created");
	}
}
