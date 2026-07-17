/*
  All sample accounts use the password: Password123!
*/

USE alumnidirectorydb;

INSERT INTO college (college_ID, college_Name, contact_Number, email) VALUES
(1, 'College of Engineering', '032-253-1000', 'engineering@sample-university.edu.ph'),
(2, 'College of Business and Accountancy', '032-253-1001', 'business@sample-university.edu.ph'),
(3, 'College of Arts and Sciences', '032-253-1002', 'artsandsciences@sample-university.edu.ph'),
(4, 'College of Law and Governance', '032-253-1003', 'law@sample-university.edu.ph'),
(5, 'College of Health Care Professions', '032-253-1004', 'healthcare@sample-university.edu.ph');

INSERT INTO industry_sector (sector_ID, sector_Name, sector_Description) VALUES
(1, 'Technology', 'Software, IT services, and digital products'),
(2, 'Finance', 'Banking, investment, and financial services'),
(3, 'Healthcare', 'Medical practice, hospital administration, and public health'),
(4, 'Public Service', 'Government, civic leadership, and public administration'),
(5, 'Arts & Media', 'Film, broadcast, and creative industries'),
(6, 'Education', 'Teaching, academic administration, and education policy');


-- ── ACCOUNTS (sample alumni, IDs 9001–9009) ──────────────────
-- Bcrypt hash below is real and verifiable: password_verify('Password123!', $hash) === true
-- phone and show_Phone added; show_Email defaults to 1 (visible)
INSERT INTO account (
  account_ID, 
  first_Name, 
  last_Name, 
  middle_Name, 
  suffix, 
  school_ID, 
  email, 
  password, 
  photo,            
  photo_Type,       
  phone, 
  nickname,         
  date_Of_Birth,    
  gender,           
  bio,              
  profile_Quote,    
  show_Email, 
  show_Phone, 
  show_Employment   
) VALUES 
(9001, 'Maria', 'Santos', 'Lopez', NULL, '1800018', 'maria.santos@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09171234567', 'Mary', '2000-05-14', 'Female', 'Hardware enthusiast, embedded systems developer, and proud Computer Engineering alumna.', 'To engineer is to solve problems you didnt know you had.', 1, 0, 1),
(9002, 'Juan', 'Dela Cruz', 'Ramos', NULL, '1500023', 'juan.delacruz@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09182345678', 'Johnny', '1997-08-22', 'Male', 'Certified Public Accountant (CPA) focused on corporate tax compliance and financial auditing.', 'Balancing books and living life one ledger at a time.', 1, 0, 1),
(9003, 'Ana', 'Reyes', 'Cruz', 'MD', '1200035', 'ana.reyes@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09193456789', 'Doc Ana', '1994-12-01', 'Female', 'Pediatrician dedicated to community healthcare and early childhood development initiatives.', 'Healing is a matter of time, but it is sometimes also a matter of opportunity.', 1, 0, 1),
(9004, 'Carlos', 'Mendoza', 'Garcia', NULL, '1000041', 'carlos.mendoza@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09204567890', 'Caloy', '1992-03-30', 'Male', 'Corporate lawyer specializing in intellectual property rights, data privacy, and tech contracts.', 'The law is reason, free from passion.', 1, 0, 1),
(9005, 'Liza', 'Fernandez', 'Torres', NULL, '1900059', 'liza.fernandez@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09215678901', 'Liz', '2001-07-19', 'Female', 'Public relations specialist, freelance digital copywriter, and media content strategist.', 'Communication works for those who work at it.', 1, 0, 1),
(9006, 'Mark', 'Villanueva', 'Bautista', NULL, '1600062', 'mark.villanueva@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09226789012', 'Teacher Mark', '1998-11-05', 'Male', 'High school Science teacher passionate about interactive learning and STEM curriculum design.', 'Education is the most powerful weapon which you can use to change the world.', 1, 0, 1),
(9007, 'Patricia', 'Lim', 'Ong', NULL, '2000077', 'patricia.lim@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09237890123', 'Pat', '2002-10-25', 'Female', 'Full stack software engineer tinkering with relational databases, APIs, and web architectures.', 'Talk is cheap. Show me the code.', 1, 0, 1),
(9008, 'Roberto', 'Tan', 'Sy', NULL, '0800084', 'roberto.tan@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09248901234', 'Rob', '1990-01-15', 'Male', 'Marketing manager, entrepreneur, and business development strategist helping local startups scale.', 'Opportunities dont happen. You create them.', 1, 0, 1),
(9009, 'Grace', 'Aquino', 'Pascual', NULL, '1400096', 'grace.aquino@alumni.sample.edu.ph', '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', NULL, 'image/png', '09259012345', 'Gracie', '1996-04-08', 'Female', 'Registered nurse (RN) working in the Intensive Care Unit, committed to patient-centered care.', 'To do what nobody else will do, in a way that nobody else can, in spite of all.', 1, 0, 1);

-- ── PROGRAMS (IDs 9001–9009, one row per sample account) ─────
-- Same normalization issue flagged before: program is tied 1:1 to an
-- account instead of being a shared catalog. Following existing design as-is.
INSERT INTO program (program_ID, account_ID, program_Name) VALUES
(9001, 9001, 'BS Computer Engineering'),
(9002, 9002, 'BS Accountancy'),
(9003, 9003, 'Doctor of Medicine'),
(9004, 9004, 'Bachelor of Laws'),
(9005, 9005, 'BA Communication'),
(9006, 9006, 'BS Secondary Education'),
(9007, 9007, 'BS Computer Science'),
(9008, 9008, 'BS Business Administration'),
(9009, 9009, 'BS Nursing');

-- ── GRADUATION ─────────────────────────────────────────────
-- college_ID values: 1 Engineering, 2 Business and Accountancy,
-- 3 Arts and Sciences, 4 Law and Governance, 5 Health Care Professions
INSERT INTO graduation (account_ID, program_ID, college_ID, graduation_Year) VALUES
(9001, 9001, 1, 2018),
(9002, 9002, 2, 2015),
(9003, 9003, 5, 2012),
(9004, 9004, 4, 2010),
(9005, 9005, 3, 2019),
(9006, 9006, 3, 2016),
(9007, 9007, 1, 2020),
(9008, 9008, 2, 2008),
(9009, 9009, 5, 2014);

-- ── EMPLOYMENT ─────────────────────────────────────────────
-- sector_ID values: 1 Technology, 2 Finance, 3 Healthcare,
-- 4 Public Service, 5 Arts & Media, 6 Education
INSERT INTO employment (account_ID, sector_ID, occupation, description) VALUES
(9001, 1, 'Senior Software Engineer', 'Builds backend systems for a telecommunications company, leading a team of 5 engineers.'),
(9002, 2, 'Financial Analyst', 'Analyzes investment portfolios and corporate finance strategy for a major Philippine bank.'),
(9003, 3, 'Pediatrician', 'Practices pediatric medicine and runs a free weekend clinic for underserved communities.'),
(9004, 4, 'City Councilor', 'Serves as an elected city councilor, focused on education and youth development policy.'),
(9005, 5, 'Film Director', 'Directs independent films and works on a national broadcast network as a segment producer.'),
(9006, 6, 'High School Principal', 'Oversees academic operations and teacher development at a private secondary school.'),
(9007, 1, 'Data Scientist', 'Builds machine learning models for a multinational consulting firm.'),
(9008, 2, 'VP for Finance', 'Leads the finance division of a conglomerate, overseeing budgeting and investor relations.'),
(9009, 3, 'Hospital Administrator', 'Manages day-to-day hospital operations and patient services for a regional hospital.');

-- ── AWARDS ─────────────────────────────────────────────────
INSERT INTO awards (account_ID, award_Title, award_Description, year_received) VALUES
(9001, 'Outstanding Young Engineer Award', 'Recognized by the national engineering board for innovation in telecom infrastructure.', 2022),
(9003, 'Community Health Service Award', 'Awarded for sustained volunteer medical service to underserved communities.', 2021),
(9004, 'Public Service Excellence Award', 'Recognized by the city government for policy work in youth education.', 2023),
(9007, 'Rising Tech Leader Award', 'Recognized by a national tech association for contributions to applied AI.', 2023),
(9009, 'Healthcare Leadership Award', 'Awarded for improving patient care standards across regional hospital operations.', 2020);

-- ── ID NUMBERS FOR STUDENTS ─────────────────────────────────────────────────

INSERT INTO student (school_id, email, password) VALUES
('2410018', 'juan.delacruz@usc.edu.ph',    NULL),
('2410023', 'maria.santos@usc.edu.ph',      NULL),
('2410035', 'jose.reyes@usc.edu.ph',        NULL),
('2410041', 'ana.garcia@usc.edu.ph',        NULL),
('2410059', 'carlos.mendoza@usc.edu.ph',    NULL),
('2320112', 'pia.fernandez@usc.edu.ph',     NULL),
('2320127', 'marco.villanueva@usc.edu.ph',  NULL),
('2320134', 'sofia.aquino@usc.edu.ph',      NULL),
('2230216', 'luis.bautista@usc.edu.ph',     NULL),
('2230220', 'clara.ramos@usc.edu.ph',       NULL);


INSERT INTO account (account_ID, first_Name, last_Name, school_ID, email, password, show_Email, show_Phone)
VALUES (9101, 'First', 'Admin', '00-1001', 'Firstadmin123@gmail.com',
        '$2b$10$/IbDpQNMS58PjV4s.ZPTxOYSC6piZ5oK9btA60w6eYtOmc2ZDCvYS', 0, 0);
-- ^ bcrypt hash verified with password_verify('Admin123!', $hash) === true

INSERT INTO staff (account_ID, staff_level)
VALUES (9101, 1);  -- staff_level 1 = full admin

