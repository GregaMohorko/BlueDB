/*
Copyright 2018 Grega Mohorko

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS StudentAttendance,Student_Subject,Subject,Student,Teacher,Car,User,Address;
SET FOREIGN_KEY_CHECKS = 1;

-- Create Student table
CREATE TABLE Student (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Name VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Create Subject table
CREATE TABLE Subject (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Name VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Create associative table to make a ManyToMany connection between Student and Subject
CREATE TABLE Student_Subject (
	Student_ID INT UNSIGNED,
	Subject_ID INT UNSIGNED,
	PRIMARY KEY(Student_ID,Subject_ID)
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE Student_Subject ADD CONSTRAINT Student_Subject_FK1 FOREIGN KEY (Student_ID) REFERENCES Student(ID) ON DELETE CASCADE;
ALTER TABLE Student_Subject ADD CONSTRAINT Student_Subject_FK2 FOREIGN KEY (Subject_ID) REFERENCES Subject(ID) ON DELETE CASCADE;

-- Create 3 subjects to test on
INSERT INTO Student
	(ID, Name)
VALUES
	(1,"Leon"),
	(2,"Matic"),
	(3,"Tadej");
INSERT INTO Subject
	(ID,Name)
VALUES
	(1,"Math"),
	(2,"History"),
	(3,"Geography");
INSERT INTO Student_Subject
	(Student_ID, Subject_ID)
VALUES
	(1,1),
	(2,2),
	(2,3),
	(3,3),
	(3,1),
	(3,2);
