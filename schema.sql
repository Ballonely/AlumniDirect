/* Use this if you want to test the db and api endpoints */
CREATE alumnidirectorydb;
USE alumnidirectorydb;

CREATE TABLE account (
    account_ID int PRIMARY KEY AUTO_INCREMENT,
    first_Name varchar(255) NOT NULL,
    last_Name varchar(255) NOT NULL,
    middle_Name varchar(255),
    suffix varchar(20),
    school_ID varhcar(8) NOT NULL UNIQUE, 
    title varchar(10),
    email varchar(50) NOT NULL UNIQUE,
    password varchar(255) NOT NULL
);

CREATE TABLE awards (
    award_ID int PRIMARY KEY AUTO_INCREMENT,
    account_ID int NOT NULL,
    award_Title varchar(255) NOT NULL,
    award_Description text,    
    year_received year NOT NULL
);

ALTER TABLE awards
ADD CONSTRAINT fk_awards_account FOREIGN KEY (account_ID) REFERENCES account(account_ID);

CREATE TABLE industry_Sector (
    sector_ID int PRIMARY KEY AUTO_INCREMENT,
    sector_Name varchar(255) NOT NULL,
    sector_Description text
);

CREATE TABLE employment (
    employment_ID int PRIMARY KEY AUTO_INCREMENT,
    account_ID int NOT NULL,
    sector_ID int NOT NULL,
    occupation varchar(255) NOT NULL,
    description text
);

ALTER TABLE employment
ADD CONSTRAINT fk_employment_account FOREIGN KEY (account_ID) REFERENCES account(account_ID),
ADD CONSTRAINT fk_employment_sector FOREIGN KEY (sector_ID) REFERENCES industry_Sector(sector_ID);

CREATE TABLE college (
    college_ID int PRIMARY KEY AUTO_INCREMENT,
    college_Name varchar(255) NOT NULL,
    contact_Number varchar(20),
    email varchar(50) NOT NULL
);

CREATE TABLE program (
    program_ID int PRIMARY KEY AUTO_INCREMENT,
    account_ID int NOT NULL,
    program_Name varchar(255) NOT NULL
);

CREATE TABLE graduation (
    graduation_ID int PRIMARY KEY AUTO_INCREMENT,
    account_ID int NOT NULL,
    program_ID int NOT NULL,
    college_ID int NOT NULL,
    graduation_Year year NOT NULL
);

ALTER TABLE graduation
ADD CONSTRAINT fk_graduation_account FOREIGN KEY (account_ID) REFERENCES account(account_ID),
ADD CONSTRAINT fk_graduation_program FOREIGN KEY (program_ID) REFERENCES program(program_ID),
ADD CONSTRAINT fk_graduation_college FOREIGN KEY (college_ID) REFERENCES college(college_ID);

CREATE TABLE staff (
    staff_ID int PRIMARY KEY AUTO_INCREMENT,
    account_ID int NOT NULL,
    staff_level int NOT NULL
);

ALTER TABLE staff
ADD CONSTRAINT fk_staff_account FOREIGN KEY (account_ID) REFERENCES account(account_ID);

CREATE TABLE modifications (
    modification_ID int PRIMARY KEY AUTO_INCREMENT,
    account_ID int NOT NULL,
    staff_ID int NOT NULL,
    is_Verified BOOLEAN DEFAULT 0 NOT NULL,
    action_Type ENUM('Insert', 'Update', 'Delete') NOT NULL,
    modified_Records varchar(50) NOT NULL,
    time_Modified timestamp NOT NULL
);

ALTER TABLE modifications
ADD CONSTRAINT fk_modification_account FOREIGN KEY (account_ID) REFERENCES account(account_ID),
ADD CONSTRAINT fk_modification_staff FOREIGN KEY (staff_ID) REFERENCES staff(staff_ID);
