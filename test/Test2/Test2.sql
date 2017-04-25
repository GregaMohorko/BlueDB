SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Car,User,Address;
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
	Address_ID INT UNSIGNED,
	Car_ID INT UNSIGNED,
	BestFriend_ID INT UNSIGNED
) COLLATE utf8_general_ci ENGINE=INNODB;
ALTER TABLE User ADD CONSTRAINT User_FK1 FOREIGN KEY (Address_ID) REFERENCES Address(ID);
ALTER TABLE User ADD CONSTRAINT User_FK2 FOREIGN KEY (BestFriend_ID) REFERENCES User(ID);

-- Create Car table
CREATE TABLE Car (
	ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	Brand VARCHAR(32),
	Owner_ID INT UNSIGNED
) COLLATE utf8_general_ci ENGINE=INNODB;

-- Connect Car and User
ALTER TABLE User ADD CONSTRAINT User_FK3 FOREIGN KEY (Car_ID) REFERENCES Car(ID);
ALTER TABLE Car ADD CONSTRAINT Car_FK2 FOREIGN KEY (Owner_ID) REFERENCES User(ID) ON DELETE CASCADE;

-- Create 3 subjects to test on
INSERT INTO Address
	(ID, Street)
VALUES
	(1,'Rapture'),
	(2,'Gotham'),
	(3,'Citadel');
INSERT INTO User
	(ID,Name,Address_ID)
VALUES
	(1,'Ryan',1),
	(2,'Bruce',2),
	(3,'John',3);
INSERT INTO Car
	(ID,Brand,Owner_ID)
VALUES
	(1,'Ford',1),
	(2,'Tank',2),
	(3,'Normandy',3);
UPDATE User SET Car_ID=1,BestFriend_ID=2 WHERE ID=1;
UPDATE User SET Car_ID=2,BestFriend_ID=3 WHERE ID=2;
UPDATE User SET Car_ID=3,BestFriend_ID=1 WHERE ID=3;