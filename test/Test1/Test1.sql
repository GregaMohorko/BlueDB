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
DROP TABLE IF EXISTS StudentAttendance,Car,User,UserType;
SET FOREIGN_KEY_CHECKS = 1;

-- Create UserType Enum table
CREATE TABLE UserType (
	ID INT UNSIGNED PRIMARY KEY,
	Type VARCHAR(16)
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE UserType ADD CONSTRAINT UserType_UQ1 UNIQUE (Type);

-- Insert the enum values
INSERT INTO UserType
	(ID,Type)
VALUES
	(1,'ADMIN'),
	(2,'EDITOR');


-- Create User table
CREATE TABLE User (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Username VARCHAR(32),
	Password VARCHAR(64) COLLATE utf8_bin,
	Type_ID INT UNSIGNED,
	CarCount INT,
	Cash DECIMAL(10,2),
	IsOkay BIT(1),
	Email VARCHAR(128),
	Birthday DATE,
	AlarmClock TIME,
	Created DATETIME
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE User ADD CONSTRAINT User_FK1 FOREIGN KEY (Type_ID) REFERENCES UserType(ID);

-- Create 3 users to test on
INSERT INTO User
	(Username, Password, Type_ID, CarCount, Cash, IsOkay, Email, Birthday, AlarmClock, Created)
VALUES
	('Gordon', 'Crowbar', 1, 42, 67.42, b'1', 'gordon.freeman@black.mesa', '1985-04-08', '10:00:00', '2017-03-15 02:08:20'),
	('Alyx', 'ILikeGordon', 2, 3, 42.67, b'1', 'alyx.vance@black.mesa', '1987-12-24', '9:50:00', '2017-03-15 03:09:21'),
	('Barney', 'IAmGood', 2, 11, 0, b'0', 'barney.calhoun@black.mesa', '1990-8-7', '10:15:34', '2017-03-15 04:10:22');
