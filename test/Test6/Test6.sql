
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS StudentAttendance,Attendance,Subject,Student,Teacher,Car,User,Address;
SET FOREIGN_KEY_CHECKS = 1;

-- Create User table
CREATE TABLE User (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Name VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Create Student table
CREATE TABLE Student (
	User_ID INT UNSIGNED PRIMARY KEY,
	RegistrationNumber VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE Student ADD CONSTRAINT Student_FK1 FOREIGN KEY (User_ID) REFERENCES User(ID) ON DELETE CASCADE;

-- Create Subject table
CREATE TABLE Subject (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Name VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Create the base table for attendance
CREATE TABLE Attendance (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Date DATE,
	WasPresent BIT(1)
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Create the SubAssociativeEntity attendance designed for students (another example could be f.e. for teachers)
CREATE TABLE StudentAttendance (
	Attendance_ID INT UNSIGNED PRIMARY KEY,
	Student_ID INT UNSIGNED,
	Subject_ID INT UNSIGNED
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE StudentAttendance ADD CONSTRAINT StudentAttendance_FK1 FOREIGN KEY (Attendance_ID) REFERENCES Attendance(ID) ON DELETE CASCADE;
ALTER TABLE StudentAttendance ADD CONSTRAINT StudentAttendance_FK2 FOREIGN KEY (Student_ID) REFERENCES Student(User_ID);
ALTER TABLE StudentAttendance ADD CONSTRAINT StudentAttendance_FK3 FOREIGN KEY (Subject_ID) REFERENCES Subject(ID);

-- Create testing data
INSERT INTO User
	(ID,Name)
VALUES
	(1,"Leon"),
	(2,"Matic"),
	(3,"Tadej");
INSERT INTO Student
	(User_ID,RegistrationNumber)
VALUES
	(1,"E1066934"),
	(2,"E1068321"),
	(3,"E1073463");
INSERT INTO Subject
	(ID,Name)
VALUES
	(1,"Math"),
	(2,"History"),
	(3,"Geography");
INSERT INTO Attendance
	(ID,Date,WasPresent)
VALUES
	(1,'2017-5-1',0),
	(2,'2017-5-1',0),
	(3,'2017-5-1',1),
	(4,'2017-5-2',1),
	(5,'2017-5-2',0),
	(6,'2017-5-2',1),
	(7,'2017-5-3',1),
	(8,'2017-5-3',0),
	(9,'2017-5-3',0),
	(10,'2017-5-4',1),
	(11,'2017-5-4',1),
	(12,'2017-5-4',0);
INSERT INTO StudentAttendance
	(Attendance_ID, Student_ID, Subject_ID)
VALUES
	(1,1,1),
	(2,2,1),
	(3,3,1),
	(4,1,3),
	(5,2,3),
	(6,3,3),
	(7,1,2),
	(8,2,2),
	(9,3,2),
	(10,1,1),
	(11,2,1),
	(12,3,1);
