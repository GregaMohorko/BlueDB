
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Attendance,Student_Subject,Subject,Student,Teacher,Car,User,Address;
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

-- Create a strong associative table between Student and Subject
CREATE TABLE Attendance (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Student_ID INT UNSIGNED,
	Subject_ID INT UNSIGNED,
	AverageGrade FLOAT
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE Attendance ADD CONSTRAINT Attendance_FK1 FOREIGN KEY (Student_ID) REFERENCES Student(ID) ON DELETE CASCADE;
ALTER TABLE Attendance ADD CONSTRAINT Attendance_FK2 FOREIGN KEY (Subject_ID) REFERENCES Subject(ID);
ALTER TABLE Attendance ADD CONSTRAINT Attendance_UQ1 UNIQUE (Student_ID,Subject_ID);

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
INSERT INTO Attendance
	(ID, Student_ID, Subject_ID, AverageGrade)
VALUES
	(1,1,1,6.7),
	(2,2,2,7.46),
	(3,2,3,8.232),
	(4,3,3,9.6345),
	(5,3,1,10),
	(6,3,2,5.76532);
