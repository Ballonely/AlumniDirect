/*
  Run this AFTER migration_add_photo_columns.sql.
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
INSERT INTO account (account_ID, first_Name, last_Name, middle_Name, suffix, school_ID, email, password, phone, show_Email, show_Phone) VALUES
(9001, 'Maria',     'Santos',     'Lopez',    NULL, '18-0001', 'maria.santos@alumni.sample.edu.ph',     '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 917 123 0001', 1, 1),
(9002, 'Juan',       'Dela Cruz', 'Ramos',    NULL, '15-0002', 'juan.delacruz@alumni.sample.edu.ph',    '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 918 123 0002', 1, 1),
(9003, 'Ana',        'Reyes',     'Cruz',     'MD', '12-0003', 'ana.reyes@alumni.sample.edu.ph',        '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 919 123 0003', 1, 1),
(9004, 'Carlos',     'Mendoza',   'Garcia',   NULL, '10-0004', 'carlos.mendoza@alumni.sample.edu.ph',   '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 920 123 0004', 1, 0),
(9005, 'Liza',       'Fernandez', 'Torres',   NULL, '19-0005', 'liza.fernandez@alumni.sample.edu.ph',   '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 921 123 0005', 1, 1),
(9006, 'Mark',       'Villanueva','Bautista', NULL, '16-0006', 'mark.villanueva@alumni.sample.edu.ph',  '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 922 123 0006', 1, 0),
(9007, 'Patricia',   'Lim',       'Ong',      NULL, '20-0007', 'patricia.lim@alumni.sample.edu.ph',     '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 923 123 0007', 1, 1),
(9008, 'Roberto',    'Tan',       'Sy',       NULL, '08-0008', 'roberto.tan@alumni.sample.edu.ph',      '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 924 123 0008', 1, 1),
(9009, 'Grace',      'Aquino',    'Pascual',  NULL, '14-0009', 'grace.aquino@alumni.sample.edu.ph',     '$2b$10$QPZfXxrXqDTGexpngcvjTO5pmn8ECW6lCK2BIl4.4vkvH9VILRTgG', '+63 925 123 0009', 1, 1);

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
('24-1001', 'juan.delacruz@usc.edu.ph',    NULL),
('24-1002', 'maria.santos@usc.edu.ph',      NULL),
('24-1003', 'jose.reyes@usc.edu.ph',        NULL),
('24-1004', 'ana.garcia@usc.edu.ph',        NULL),
('24-1005', 'carlos.mendoza@usc.edu.ph',    NULL),
('23-2011', 'pia.fernandez@usc.edu.ph',     NULL),
('23-2012', 'marco.villanueva@usc.edu.ph',  NULL),
('23-2013', 'sofia.aquino@usc.edu.ph',      NULL),
('22-3021', 'luis.bautista@usc.edu.ph',     NULL),
('22-3022', 'clara.ramos@usc.edu.ph',       NULL);