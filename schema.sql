CREATE DATABASE IF NOT EXISTS alumnidirectorydb;
USE alumnidirectorydb;

CREATE TABLE account (
    account_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    first_Name varchar(255) NOT NULL,
    last_Name varchar(255) NOT NULL,
    middle_Name varchar(255) DEFAULT NULL,
    suffix varchar(20) DEFAULT NULL,
    school_ID varchar(8) DEFAULT NULL,
    email varchar(50) NOT NULL,
    password varchar(255) NOT NULL,
    photo MEDIUMBLOB,
    photo_Type varchar(50),
    phone varchar(20) DEFAULT NULL,
    nickname varchar(255) DEFAULT NULL,
    date_Of_Birth date DEFAULT NULL,
    gender varchar(30) DEFAULT NULL,
    bio text DEFAULT NULL,
    profile_Quote varchar(255) DEFAULT NULL,
    show_Email tinyint(1) NOT NULL DEFAULT 1,
    show_Phone tinyint(1) NOT NULL DEFAULT 0,
    show_Employment tinyint(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_account_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE awards (
    award_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    account_ID int(11) NOT NULL,
    award_Title varchar(255) NOT NULL,
    award_Description text DEFAULT NULL,
    year_received year(4) NOT NULL,
    KEY fk_awards_account (account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE college (
    college_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    college_Name varchar(255) NOT NULL,
    contact_Number varchar(20) DEFAULT NULL,
    email varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE employment (
    employment_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    account_ID int(11) NOT NULL,
    sector_ID int(11) DEFAULT NULL,
    occupation varchar(255) DEFAULT NULL,
    employer varchar(255) DEFAULT NULL,
    description text DEFAULT NULL,
    KEY fk_employment_account (account_ID),
    KEY fk_employment_sector (sector_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE graduation (
    graduation_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    account_ID int(11) NOT NULL,
    program_ID int(11) DEFAULT NULL,
    college_ID int(11) DEFAULT NULL,
    graduation_Year year(4) DEFAULT NULL,
    KEY fk_graduation_account (account_ID),
    KEY fk_graduation_program (program_ID),
    KEY fk_graduation_college (college_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE industry_sector (
    sector_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    sector_Name varchar(255) NOT NULL,
    sector_Description text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE modifications (
    modification_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    account_ID int(11) NOT NULL,
    staff_ID int(11) NOT NULL,
    is_Verified tinyint(1) NOT NULL DEFAULT 0,
    action_Type enum('Insert','Update','Delete') NOT NULL,
    modified_Records varchar(50) NOT NULL,
    time_Modified timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    KEY fk_modification_account (account_ID),
    KEY fk_modification_staff (staff_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE program (
    program_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    account_ID int(11) NOT NULL,
    program_Name varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE staff (
    staff_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    account_ID int(11) NOT NULL,
    staff_level int(11) NOT NULL,
    KEY fk_staff_account (account_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE awards
  ADD CONSTRAINT fk_awards_account FOREIGN KEY (account_ID) REFERENCES account (account_ID);

ALTER TABLE employment
  ADD CONSTRAINT fk_employment_account FOREIGN KEY (account_ID) REFERENCES account (account_ID),
  ADD CONSTRAINT fk_employment_sector FOREIGN KEY (sector_ID) REFERENCES industry_sector (sector_ID);

ALTER TABLE graduation
  ADD CONSTRAINT fk_graduation_account FOREIGN KEY (account_ID) REFERENCES account (account_ID),
  ADD CONSTRAINT fk_graduation_college FOREIGN KEY (college_ID) REFERENCES college (college_ID),
  ADD CONSTRAINT fk_graduation_program FOREIGN KEY (program_ID) REFERENCES program (program_ID);

ALTER TABLE modifications
  ADD CONSTRAINT fk_modification_account FOREIGN KEY (account_ID) REFERENCES account (account_ID),
  ADD CONSTRAINT fk_modification_staff FOREIGN KEY (staff_ID) REFERENCES staff (staff_ID);

ALTER TABLE staff
  ADD CONSTRAINT fk_staff_account FOREIGN KEY (account_ID) REFERENCES account (account_ID);
