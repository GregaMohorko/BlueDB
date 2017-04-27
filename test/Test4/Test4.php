<?php

/*
 * Test4.php
 * 
 * @project BlueDB
 * @author Grega Mohorko <grega@mohorko.info>
 * @copyright Apr 26, 2017 Grega Mohorko
 */

require_once 'Student.php';
require_once 'Subject.php';
require_once 'Student_Subject.php';

use BlueDB\DataAccess\MySQL;
use BlueDB\DataAccess\Criteria\Expression;
use BlueDB\DataAccess\Criteria\Criteria;

use Test4\Student;
use Test4\Subject;
use Test4\Student_Subject;

/**
 * Tests loading, linking and unlinking using AssociativeEntities.
 * 
 * Also test expressions designed for associative entities.
 */
class Test4 extends Test
{
	public function run()
	{
		$sqlScript=file_get_contents("Test4/Test4.sql");
		if($sqlScript===false){
			echo "<b>Error:</b> Failed to read contents of Test4.sql.";
			return;
		}
		MySQL::queryMulti($sqlScript);
		
		$this->testLoadListForSide();
		$this->testLoadListForSideByCriteria();
		$this->testLink();
		$this->testUnlink();
		$this->testUnlinkMultiple();
		$this->testLinkMultiple();
		$this->testExpressions();
	}
	
	private function testLoadListForSide()
	{
		// loads all subjects of Leon
		/* @var $leon Student */
		$leon=Student::loadByID(1);
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $leon->ID);
		assert(count($subjects)===1,"Count of Leons subjects");
		assert($subjects[0]->Name==="Math","Leons subject");
		
		// loads all subjects of Matic, but only the Name field
		/* @var $matic Student */
		$matic=Student::loadByID(2);
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $matic->ID,[Subject::NameField]);
		assert(count($subjects)===2,"Count of Matics subjects");
		assert($subjects[0]->ID===null,"Matics subject");
		assert($subjects[0]->Name==="History","Matics subject");
		assert($subjects[1]->Name==="Geography","Matics subject");
		
		// loads all subjects of Tadej, but without Name field
		/* @var $tadej Student */
		$tadej=Student::loadByID(3);
		$subjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, $tadej->ID, null, [Subject::NameField]);
		assert(count($subjects)===3,"Count of Tadejs subjects");
		assert($subjects[0]->Name===null,"Tadejs subject");
		assert($subjects[0]->ID===1,"Tadejs subject");
		assert($subjects[1]->ID===2,"Tadejs subject");
		assert($subjects[2]->ID===3,"Tadejs subject");
		
		// loads all students who are studying Math
		// Maths ID is 1
		$students=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 1);
		assert(count($students)===2,"Count of students studying Math");
		assert($students[0]->Name==="Leon","Students studying Math");
		assert($students[1]->Name==="Tadej","Students studying Math");
	}
	
	private function testLoadListForSideByCriteria()
	{
		// load all subjects of Tadej that have the letter 'y' in it
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::contains(Subject::class, Subject::NameField, "y"));
		$subjects=Student_Subject::loadListForSideByCriteria(Student_Subject::StudentsSide, 3, $criteria);
		assert(count($subjects)===2,"Count of Tadejs subjects with letter 'y'");
		assert($subjects[0]->Name==="History","Subjects of Tadej with letter 'y'");
		assert($subjects[1]->Name==="Geography","Subjects of Tadej with letter 'y'");
	}
	
	private function testLink()
	{
		// link Leon with Geography
		$leon=Student::loadByID(1);
		$geography=Subject::loadByID(3);
		Student_Subject::link($leon, $geography);
		$subjectsOfLeon=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 1);
		assert(count($subjectsOfLeon)===2,"Linking Leon and Geography");
		assert($subjectsOfLeon[0]->Name==="Math","Linking Leon and Geography");
		assert($subjectsOfLeon[1]->Name==="Geography","Linking Leon and Geography");
	}
	
	private function testUnlink()
	{
		// unlink Leon with Math
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		Student_Subject::unlink($leon, $math);
		$subjectsOfLeon=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 1);
		assert(count($subjectsOfLeon)===1,"Unlinking Leon and Math");
		assert($subjectsOfLeon[0]->Name==="Geography","Unlinking Leon and Math");
	}
	
	private function testUnlinkMultiple()
	{
		// unlink Tadej with all subjects
		$tadej=Student::loadByID(3);
		$tadejsSubjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 3);
		Student_Subject::unlinkMultipleB($tadej, $tadejsSubjects,true,false);
		$tadejsSubjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 3);
		assert(count($tadejsSubjects)===0,"Unlinking multiple subjects of Tadej");
		
		// unlink Geography with all students
		$geography=Subject::loadByID(3);
		$studentsOfGeography=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 3);
		Student_Subject::unlinkMultipleA($geography, $studentsOfGeography,false,true);
		$studentsOfGeography=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 3);
		assert(count($studentsOfGeography)===0,"Unlinking multiple students of Geography");
	}
	
	private function testLinkMultiple()
	{
		// link Tadej with all subjects
		$tadej=Student::loadByID(3);
		$allSubjects=Subject::loadList();
		Student_Subject::linkMultipleB($tadej, $allSubjects, true, false);
		$tadejsSubjects=Student_Subject::loadListForSide(Student_Subject::StudentsSide, 3);
		assert(count($tadejsSubjects)===count($allSubjects),"Linking multiple subjects with Tadej");
		assert($tadejsSubjects[0]->Name===$allSubjects[0]->Name,"Linking multiple subjects with Tadej");
		assert($tadejsSubjects[1]->Name===$allSubjects[1]->Name,"Linking multiple subjects with Tadej");
		assert($tadejsSubjects[2]->Name===$allSubjects[2]->Name,"Linking multiple subjects with Tadej");
		
		// link Math with all students who are not already
		$math=Subject::loadByID(1);
		$allStudents=Student::loadList();
		$studentsAlreadyInMath=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 1);
		for($i=count($studentsAlreadyInMath)-1;$i>=0;--$i){
			/* @var $studentAlreadyInMath Student */
			$studentAlreadyInMath=$studentsAlreadyInMath[$i];
			for($j=count($allStudents)-1;$j>=0;--$j){
				/* @var $student Student */
				$student=$allStudents[$j];
				if($student->getID()===$studentAlreadyInMath->getID()){
					unset($allStudents[$j]);
					break;
				}
			}
		}
		Student_Subject::linkMultipleA($math, $allStudents, false, true);
		$studentsOfMath=Student_Subject::loadListForSide(Student_Subject::SubjectsSide, 1);
		assert(count($studentsOfMath)===3,"Linking multiple students with Math");
		assert($studentsOfMath[0]->Name==="Leon","Linking multiple students with Math");
		assert($studentsOfMath[1]->Name==="Matic","Linking multiple students with Math");
		assert($studentsOfMath[2]->Name==="Tadej","Linking multiple students with Math");
	}
	
	private function testExpressions()
	{
		// first unlink Tadej and Geography
		$tadej=Student::loadByID(3);
		$geography=Subject::loadByID(3);
		Student_Subject::unlink($tadej, $geography,true,false);
		
		// load all subjects that nobody is connected to
		$criteria=new Criteria(Subject::class);
		$criteria->add(Expression::isNotIn(Subject::class, Student_Subject::class, Student_Subject::SubjectsSide));
		$subjects=Subject::loadListByCriteria($criteria);
		assert(count($subjects)===1,"Expression notIn");
		assert($subjects[0]->Name==="Geography","Expression notIn");
		
		// load all students that have no subjects
		$criteria=new Criteria(Student::class);
		$criteria->add(Expression::isNotIn(Student::class, Student_Subject::class, Student_Subject::StudentsSide));
		$students=Student::loadListByCriteria($criteria);
		assert(empty($students),"Expression notIn");
		
		// unlink Leon with his only subject (Math)
		$leon=Student::loadByID(1);
		$math=Subject::loadByID(1);
		Student_Subject::unlink($leon, $math,false,true);
		
		// now load all students that have no subjects again
		$students=Student::loadListByCriteria($criteria);
		assert(count($students)===1,"Expression notIn");
		assert($students[0]->Name==="Leon","Expression notIn");
	}
}
