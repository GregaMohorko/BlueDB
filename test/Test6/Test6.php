<?php

/*
 * Test6.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

require_once 'User.php';
require_once 'Student.php';
require_once 'Subject.php';
require_once 'Attendance.php';
require_once 'StudentAttendance.php';

use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use Test6\User;
use Test6\Student;
use Test6\Subject;
use Test6\Attendance;
use Test6\StudentAttendance;

/**
 * Tests SubAssociativeEntity.
 */
class Test6 extends Test
{
	public function run()
	{
		$sqlScript=file_get_contents("Test6/Test6.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test6.sql.";
			return;
		}
		MySQL::queryMulti($sqlScript);
		
		$this->testLoadListForSide();
		$this->testLoadListForSideByCriteria();
		$this->testLoadFor();
		$this->testLoadForByCriteria();
		$this->testLoadListFor();
		$this->testLoadListForByCriteria();
		$this->testLoadListByCriteria();
		$this->testUnlink();
		$this->testUnlinkMultiple();
		$this->testExpressions();
	}
	
	private function testLoadListForSide()
	{
		// loads all subjects of Leon
		$subjects=StudentAttendance::loadListForSide(StudentAttendance::StudentsSide, 1);
		assert(count($subjects)===4,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		assert($subjects[1]->Name==="Math","Leons subject");
		assert($subjects[2]->Name==="History","Leons subject");
		assert($subjects[3]->Name==="Geography","Leons subject");
		assert($subjects[1]->Attendances!==null);
		assert(count($subjects[1]->Attendances)===6);
		assert($subjects[0]->Students!==null,"Leons subjects");
		assert(count($subjects[0]->Students)===6,"ManyToMany field");
		assert($subjects[0]->Students[0]->User->Name==="Leon","ManyToMany field");
		assert($subjects[0]->Students[1]->User->Name==="Leon","ManyToMany field");
		
		// loads all subjects of Matic, but only the Name field
		$subjects=StudentAttendance::loadListForSide(StudentAttendance::StudentsSide, 2, [Subject::NameField]);
		assert(count($subjects)===4,"Count of Matics subjects");
		assert($subjects[0]->Name==="Math","Matics subject");
		assert($subjects[1]->Name==="Math","Matics subject");
		assert($subjects[2]->Name==="History","Matics subject");
		assert($subjects[3]->Name==="Geography","Matics subject");
		assert($subjects[1]->Attendances===null);
		assert($subjects[0]->Students===null,"ManyToMany field");
		
		// loads all subjects of Tadej, but without Name field
		$subjects=StudentAttendance::loadListForSide(StudentAttendance::StudentsSide, 3,null,[Subject::NameField]);
		assert(count($subjects)===4,"Count of Tadejs subjects");
		assert($subjects[0]->Name===null,"Tadejs subject");
		assert($subjects[0]->ID===1,"Tadejs subject");
		assert($subjects[1]->ID===1,"Tadejs subject");
		assert($subjects[2]->ID===2,"Tadejs subject");
		assert($subjects[3]->ID===3,"Tadejs subject");
		assert($subjects[1]->Attendances!==null);
		assert($subjects[0]->Students!==null,"ManyToMany field");
		assert(count($subjects[0]->Students)===6,"ManyToMany field");
		
		// loads all students who are studying Math
		$students=StudentAttendance::loadListForSide(StudentAttendance::SubjectsSide, 1);
		assert(count($students)===6,"Count of students studying Math");
		assert($students[0]->User->Name==="Leon","Students studying Math");
		assert($students[1]->User->Name==="Leon","Students studying Math");
		assert($students[2]->User->Name==="Matic","Students studying Math");
		assert($students[5]->User->Name==="Tadej","Students studying Math");
		assert($students[1]->Attendances!==null);
		assert(count($students[1]->Attendances)===4);
		assert($students[0]->Subjects!==null,"ManyToMany field");
		assert(count($students[3]->Subjects)===4,"ManyToMany field");
		assert($students[3]->Subjects[0]->Name==="Math","ManyToMany field");
		
		// loads all subjects of Leon, but without ManyToMany fields
		$subjects=StudentAttendance::loadListForSide(StudentAttendance::StudentsSide, 1,null,null,null,null,false);
		assert(count($subjects)===4,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		assert($subjects[1]->Name==="Math","Leons subject");
		assert($subjects[2]->Name==="History","Leons subject");
		assert($subjects[3]->Name==="Geography","Leons subject");
		assert($subjects[1]->Attendances!==null);
		assert($subjects[0]->Students===null,"Leons subjects");
	}
	
	private function testLoadListForSideByCriteria()
	{
		// load all students of Math that have the sequence '106' in the registration number
		$criteria=new Criteria(Student::class);
		$criteria->add(Expression::contains(Student::class, Student::RegistrationNumberField, "106"));
		$students=StudentAttendance::loadListForSideByCriteria(StudentAttendance::SubjectsSide, 1, $criteria);
		assert(count($students)===4,"loadListForSideByCriteria");
		assert($students[0]->User->Name==="Leon");
		assert($students[3]->User->Name==="Matic");
		
		// load all students of History that have the letter 'a' in the name
		$criteria=new Criteria(Student::class);
		$criteria->add(Expression::contains(Student::class,User::NameField,"a",User::class));
		$students=StudentAttendance::loadListForSideByCriteria(StudentAttendance::SubjectsSide, 2, $criteria);
		assert(count($students)===2);
		assert($students[0]->User->Name==="Matic");
		assert($students[1]->User->Name==="Tadej");
	}
	
	private function testLoadFor()
	{
		// load THE attendance between Leon and Math
		/* @var $leon Student */
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		try{
			// must throw exception because there are multiple attendances between Leon and Math
			$attendance=StudentAttendance::loadFor($leon, $math);
			assert(false,"Throwing exception because of multiple rows");
		} catch(Exception $ex) { }
		
		// load THE attendance between Leon and History
		$history=Subject::loadByID(2);
		/* @var $attendance StudentAttendance */
		$attendance=StudentAttendance::loadFor($leon, $history);
		assert($attendance!==null);
		assert($attendance->Student->RegistrationNumber===$leon->RegistrationNumber);
		assert($attendance->Subject->Name==="History");
		assert($attendance->Attendance->WasPresent);
	}
	
	private function testLoadForByCriteria()
	{
		// load THE attendance between Leon and History, but only if WasPresent is TRUE
		$leon=Student::loadByID(1);
		$history=Subject::loadByID(2);
		$criteria=new Criteria(StudentAttendance::class);
		$criteria->add(Expression::equal(StudentAttendance::class, Attendance::WasPresentField, true, Attendance::class));
		$attendance=StudentAttendance::loadForByCriteria($leon, $history, $criteria);
		assert($attendance!==null);
		assert($attendance->Student->RegistrationNumber===$leon->RegistrationNumber);
		assert($attendance->Subject->Name==="History");
		assert($attendance->Attendance->WasPresent);
		
		// load THE attendance between Leon and History, but only if WasPresent is FALSE
		$criteria=new Criteria(StudentAttendance::class);
		$criteria->add(Expression::equal(StudentAttendance::class, Attendance::WasPresentField, false, Attendance::class));
		$attendance=StudentAttendance::loadForByCriteria($leon, $history, $criteria);
		assert($attendance===null);
	}
	
	private function testLoadListFor()
	{
		// load all attendances between Leon and Math
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		$attendances=StudentAttendance::loadListFor($leon, $math);
		assert(count($attendances)===2,"loadListFor");
		assert($attendances[0]->Student->User->Name==="Leon","loadListFor");
	}
	
	private function testLoadListForByCriteria()
	{
		// load all attendances between Leon and Math, but only those that were on the 1st of May
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		$criteria=new Criteria(StudentAttendance::class);
		$dateTime=new DateTime("2017-5-1");
		$criteria->add(Expression::equal(StudentAttendance::class, Attendance::DateField, $dateTime, Attendance::class));
		$attendances=StudentAttendance::loadListForByCriteria($leon, $math, $criteria);
		assert(count($attendances)===1,"loadListForByCriteria");
		assert($attendances[0]->Attendance->WasPresent===false,"loadListForByCriteria");
		assert($attendances[0]->Subject->Name==="Math","loadListForByCriteria");
	}
	
	private function testLoadListByCriteria()
	{
		// load all attendances where the student was not present
		$criteria=new Criteria(StudentAttendance::class);
		$criteria->add(Expression::equal(StudentAttendance::class, Attendance::WasPresentField, false, Attendance::class));
		$attendances=StudentAttendance::loadListByCriteria($criteria);
		assert(count($attendances)===6,"loadListByCriteria");
	}
	
	private function testUnlink()
	{
		// unlink Tadej and History
		$tadej=Student::loadByID(3);
		$history=Subject::loadByID(2);
		StudentAttendance::unlink($tadej, $history, true, false);
		$attendance=StudentAttendance::loadFor($tadej, $history);
		assert($attendance===null,"Unlink");
		
		// unlink Leon and Math
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		StudentAttendance::unlink($leon, $math,false,true);
		$attendance=StudentAttendance::loadFor($leon, $math);
		assert($attendance===null,"Unlink");
	}
	
	private function testUnlinkMultiple()
	{
		// unlink Tadej and Matic from Geography
		$tadej=Student::loadByID(3);
		$matic=Student::loadByID(2);
		$geography=Subject::loadByID(3);
		StudentAttendance::unlinkMultipleA($geography, [$tadej,$matic], true, false);
		$attendance=StudentAttendance::loadFor($tadej, $geography);
		assert($attendance===null,"Unlink multiple A");
		$attendance=StudentAttendance::loadFor($matic, $geography);
		assert($attendance===null,"Unlink multiple A");
		
		// unlink Leon with all subjects
		/* @var $leon Student */
		$leon=Student::loadByID(1);
		StudentAttendance::unlinkMultipleB($leon, $leon->Subjects,false,true);
		$leonsSubjects=StudentAttendance::loadListForSide(StudentAttendance::StudentsSide, $leon->getID());
		assert(empty($leonsSubjects),"Unlink multiple B");
	}
	
	private function testExpressions()
	{
		// load all subjects that nobody is connected to
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::isNotIn(Subject::class, StudentAttendance::class, StudentAttendance::SubjectsSide));
		$subjects=Subject::loadListByCriteria($criteria);
		assert(count($subjects)===1,"Expression isNotIn");
		assert($subjects[0]->Name==="Geography");
		
		// load all students that have no subjects
		$criteria=new Criteria(Student::class);
		$criteria->add(Expression::isNotIn(Student::class, StudentAttendance::class, StudentAttendance::StudentsSide));
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===1,"Expression isNotIn");
		assert($students[0]->User->Name==="Leon","Expression isNotIn");
		
		// link Leon with Math
		/* @var $attendance StudentAttendance */
		$attendance=StudentAttendance::createEmpty();
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		$attendance->Student=$leon;
		$attendance->Subject=$math;
		$attendance->Attendance->Date=new DateTime("2015-5-27");
		$attendance->Attendance->WasPresent=true;
		StudentAttendance::save($attendance);
		
		// now load all students that have no subjects again
		$students=Student::loadListByCriteria($criteria);
		assert(empty($students),"Expression isNotIn");
	}
}
