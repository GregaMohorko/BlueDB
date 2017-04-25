SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Teacher,Student,Car,User,Address;
SET FOREIGN_KEY_CHECKS = 1;

-- Create Address table
CREATE TABLE Address (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Street VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Create User table
CREATE TABLE User (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Name VARCHAR(32),
	Address_ID INT UNSIGNED
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE User ADD CONSTRAINT User_FK1 FOREIGN KEY (Address_ID) REFERENCES Address(ID);

-- Create Student table
CREATE TABLE Student (
	User_ID INT UNSIGNED PRIMARY KEY,
	RegistrationNumber VARCHAR(32)
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE Student ADD CONSTRAINT Student_FK1 FOREIGN KEY (User_ID) REFERENCES User(ID) ON DELETE CASCADE;

-- Create Teacher table
CREATE TABLE Teacher (
	User_ID INT UNSIGNED PRIMARY KEY
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE Teacher ADD CONSTRAINT Teacher_FK1 FOREIGN KEY (User_ID) REFERENCES User(ID) ON DELETE CASCADE;

-- Create 3 subjects to test on
INSERT INTO Address
	(ID, Street)
VALUES
	(1,'Ljubljana'),
	(2,'Maribor'),
	(3,'Celje');
INSERT INTO User
	(ID,Name,Address_ID)
VALUES
	(1,'Lojzi',1),
	(2,'Tadej',2),
	(3,'Grega',3);
INSERT INTO Student
	(User_ID,RegistrationNumber)
VALUES
	(1,"E1066934"),
	(2,"E1068321");
INSERT INTO Teacher
	(User_ID)
VALUES
	(3);
