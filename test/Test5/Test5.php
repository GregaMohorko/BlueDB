<?php

/*
 * Test5.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 29, 2017 Grega Mohorko
 */

require_once 'Student.php';
require_once 'Subject.php';
require_once 'Attendance.php';

use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Criteria;
use BlueDB\DataAccess\Criteria\Expression;
use Test5\Student;
use Test5\Subject;
use Test5\Attendance;

/**
 * Tests StrongAssociativeEntity.
 */
class Test5 extends Test
{
	public function run()
	{
		$sqlScript=file_get_contents("Test5/Test5.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test5.sql.";
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
		/* @var $leon Student */
		$leon=Student::loadByID(1);
		$subjects=Attendance::loadListForSide(Attendance::StudentsSide, $leon->ID);
		assert(count($subjects)===1,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		assert($subjects[0]->Students!==null,"Leons subjects");
		assert(count($subjects[0]->Students)===2,"ManyToMany field");
		assert($subjects[0]->Students[0]->Name==="Leon","ManyToMany field");
		assert($subjects[0]->Students[1]->Name==="Tadej","ManyToMany field");
		
		// loads all subjects of Matic, but only the Name field
		/* @var $matic Student */
		$matic=Student::loadByID(2);
		$subjects=Attendance::loadListForSide(Attendance::StudentsSide, $matic->ID,[Subject::NameField]);
		assert(count($subjects)===2,"Count of Matics subjects");
		assert($subjects[0]->Name==="History","Matics subject");
		assert($subjects[1]->Name==="Geography","Matics subject");
		assert($subjects[1]->Students===null,"ManyToMany field");
		
		// loads all subjects of Tadej, but without Name field
		/* @var $tadej Student */
		$tadej=Student::loadByID(3);
		$subjects=Attendance::loadListForSide(Attendance::StudentsSide, $tadej->ID, null, [Subject::NameField]);
		assert(count($subjects)===3,"Count of Tadejs subjects");
		assert($subjects[0]->Name===null,"Tadejs subject");
		assert($subjects[0]->ID===1,"Tadejs subject");
		assert($subjects[1]->ID===2,"Tadejs subject");
		assert($subjects[2]->ID===3,"Tadejs subject");
		assert($subjects[1]->Students!==null,"ManyToMany field");
		assert(count($subjects[2]->Students)===2,"ManyToMany field");
		
		// loads all students who are studying Math
		// Maths ID is 1
		$students=Attendance::loadListForSide(Attendance::SubjectsSide, 1);
		assert(count($students)===2,"Count of students studying Math");
		assert($students[0]->Name==="Leon","Students studying Math");
		assert($students[1]->Name==="Tadej","Students studying Math");
		assert($students[0]->Subjects!==null,"ManyToMany field");
		assert(count($students[0]->Subjects)===1,"ManyToMany field");
		assert($students[0]->Subjects[0]->Name==="Math","ManyToMany field");
		
		// loads all subjects of Leon, but without ManyToMany fields
		$subjects=Attendance::loadListForSide(Attendance::StudentsSide, $leon->ID, null, null, null, null, false);
		assert(count($subjects)===1,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		assert($subjects[0]->Students===null,"Without ManyToMany fields");
	}
	
	private function testLoadListForSideByCriteria()
	{
		// load all subjects of Tadej that have the letter 'y' in it
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::contains(Subject::class, Subject::NameField, "y"));
		$subjects=Attendance::loadListForSideByCriteria(Attendance::StudentsSide, 3, $criteria);
		assert(count($subjects)===2,"Count of Tadejs subjects with letter 'y'");
		assert($subjects[0]->Name==="History","Subjects of Tadej with letter 'y'");
		assert($subjects[1]->Name==="Geography","Subjects of Tadej with letter 'y'");
	}
	
	private function testLoadFor()
	{
		// load the attendance between Tadej and History
		$tadej=Student::loadByID(3);
		$history=Subject::loadByID(2);
		/* @var $attendance Attendance */
		$attendance=Attendance::loadFor($tadej, $history);
		assert($attendance!==null,"LoadFor");
		assert($attendance->Student!==null,"LoadFor");
		assert($attendance->Student->Name==="Tadej","LoadFor");
		assert(count($attendance->Student->Subjects)===3,"LoadFor");
		assert($attendance->Subject!==null,"LoadFor");
		assert($attendance->Subject->Name==="History","LoadFor");
		assert(count($attendance->Subject->Students)===2,"LoadFor");
		assert($attendance->AverageGrade===5.76532,"LoadFor");
	}
	
	private function testLoadForByCriteria()
	{
		// load the attendance between Tadej and History, but only if AverageGrade is above 7.5
		$tadej=Student::loadByID(3);
		$history=Subject::loadByID(2);
		$criteria=new Criteria(Attendance::class);
		$criteria->add(Expression::above(Attendance::class, Attendance::AverageGradeField, 7.5));
		/* @var $attendance Attendance */
		$attendance=Attendance::loadForByCriteria($tadej, $history,$criteria);
		assert($attendance===null,"LoadForByCriteria");
		
		// load the attendance between Tadej and History, but only if AverageGrade is below 7.5
		$criteria=new Criteria(Attendance::class);
		$criteria->add(Expression::below(Attendance::class, Attendance::AverageGradeField, 7.5));
		$attendance=Attendance::loadForByCriteria($tadej, $history, $criteria);
		assert($attendance!==null,"LoadForByCriteria");
		assert($attendance->Student!==null,"LoadForByCriteria");
		assert($attendance->Student->Name==="Tadej","LoadForByCriteria");
		assert(count($attendance->Student->Subjects)===3,"LoadForByCriteria");
		assert($attendance->Subject!==null,"LoadForByCriteria");
		assert($attendance->Subject->Name==="History","LoadForByCriteria");
		assert(count($attendance->Subject->Students)===2,"LoadForByCriteria");
		assert($attendance->AverageGrade===5.76532,"LoadForByCriteria");
	}
	
	private function testLoadListFor()
	{
		// load the attendances between Tadej and History
		$tadej=Student::loadByID(3);
		$history=Subject::loadByID(2);
		$attendances=Attendance::loadListFor($tadej, $history);
		assert(count($attendances)===1,"loadListFor");
		/* @var $attendance Attendance */
		$attendance=$attendances[0];
		assert($attendance!==null,"loadListFor");
		assert($attendance->Student!==null,"loadListFor");
		assert($attendance->Student->Name==="Tadej","loadListFor");
		assert(count($attendance->Student->Subjects)===3,"loadListFor");
		assert($attendance->Subject!==null,"loadListFor");
		assert($attendance->Subject->Name==="History","loadListFor");
		assert(count($attendance->Subject->Students)===2,"loadListFor");
		assert($attendance->AverageGrade===5.76532,"loadListFor");
	}
	
	private function testLoadListForByCriteria()
	{
		// load the attendances between Tadej and History, but only if AverageGrade is above 7.5
		$tadej=Student::loadByID(3);
		$history=Subject::loadByID(2);
		$criteria=new Criteria(Attendance::class);
		$criteria->add(Expression::above(Attendance::class, Attendance::AverageGradeField, 7.5));
		$attendances=Attendance::loadListForByCriteria($tadej, $history, $criteria);
		assert(empty($attendances),"loadListForByCriteria");
		
		// load the attendances between Tadej and History, but only if AverageGrade is below 7.5
		$criteria=new Criteria(Attendance::class);
		$criteria->add(Expression::below(Attendance::class, Attendance::AverageGradeField, 7.5));
		$attendances=Attendance::loadListForByCriteria($tadej, $history, $criteria);
		assert(count($attendances)===1,"loadListForByCriteria");
		/* @var $attendance Attendance */
		$attendance=$attendances[0];
		assert($attendance!==null,"loadListForByCriteria");
		assert($attendance->Student!==null,"loadListForByCriteria");
		assert($attendance->Student->Name==="Tadej","loadListForByCriteria");
		assert(count($attendance->Student->Subjects)===3,"loadListForByCriteria");
		assert($attendance->Subject!==null,"loadListForByCriteria");
		assert($attendance->Subject->Name==="History","loadListForByCriteria");
		assert(count($attendance->Subject->Students)===2,"loadListForByCriteria");
		assert($attendance->AverageGrade===5.76532,"loadListForByCriteria");
	}
	
	private function testLoadListByCriteria()
	{
		// load all attendances with average grade above 7.5
		$criteria=new Criteria(Attendance::class);
		$criteria->add(Expression::above(Attendance::class, Attendance::AverageGradeField, 7.5));
		$attendances=Attendance::loadListByCriteria($criteria);
		assert(count($attendances)===3);
	}
	
	private function testUnlink()
	{
		// unlink Tadej and History
		$tadej=Student::loadByID(3);
		$history=Subject::loadByID(2);
		Attendance::unlink($tadej, $history);
		$attendance=Attendance::loadFor($tadej, $history);
		assert($attendance===null,"Unlink");
	}
	
	private function testUnlinkMultiple()
	{
		// unlink Tadej and Matic from Geography
		$tadej=Student::loadByID(3);
		$matic=Student::loadByID(2);
		$geography=Subject::loadByID(3);
		Attendance::unlinkMultipleA($geography, [$tadej,$matic],true,false);
		$attendance=Attendance::loadFor($tadej, $geography);
		assert($attendance===null,"Unlink multiple A");
		$attendance=Attendance::loadFor($matic, $geography);
		assert($attendance===null,"Unlink multiple A");
		
		// unlink Leon with all subjects
		/* @var $leon Student */
		$leon=Student::loadByID(1);
		Attendance::unlinkMultipleB($leon, $leon->Subjects,false,true);
		$leonsSubjects=Attendance::loadListForSide(Attendance::StudentsSide, $leon->ID);
		assert(empty($leonsSubjects),"Unlink multiple B");
	}
	
	private function testExpressions()
	{
		// load all subjects that nobody is connected to
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::isNotIn(Subject::class, Attendance::class, Attendance::SubjectsSide));
		$subjects=Subject::loadListByCriteria($criteria);
		assert(count($subjects)===1,"Expression isNotIn");
		assert($subjects[0]->Name==="Geography");
		
		// load all students that have no subjects
		$criteria=new Criteria(Student::class);
		$criteria->add(Expression::isNotIn(Student::class, Attendance::class, Attendance::StudentsSide));
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===1,"Expression isNotIn");
		assert($students[0]->Name==="Leon","Expression isNotIn");
		
		// link Leon with Math
		$attendance=new Attendance();
		$leon=Student::loadByID(1,[]);
		$math=Subject::loadByID(1,[]);
		$attendance->Student=$leon;
		$attendance->Subject=$math;
		$attendance->AverageGrade=5;
		Attendance::save($attendance);
		
		// now load all students that have no subjects again
		$students=Student::loadListByCriteria($criteria);
		assert(empty($students),"Expression isNotIn");
	}
}
