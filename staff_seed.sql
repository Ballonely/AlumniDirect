/*
  staff_seed.sql
  ---------------------------------------------------------------
  Seeds the first staff/admin account, testable with either:
    - Email:    Firstadmin123@gmail.com
    - Staff ID: 00-1001
    - Password: Admin123!
  (the login form accepts either the email or the staff ID in the
  same field -- see api/login.php)

  Numbering convention: staff use prefix "00" (no graduation year,
  since they're not alumni) with the sequence starting at 1001 so it
  never collides with an empty/default value.
*/

USE alumnidirectorydb;

INSERT INTO account (account_ID, first_Name, last_Name, school_ID, email, password, show_Email, show_Phone)
VALUES (9101, 'First', 'Admin', '00-1001', 'Firstadmin123@gmail.com',
        '$2b$10$/IbDpQNMS58PjV4s.ZPTxOYSC6piZ5oK9btA60w6eYtOmc2ZDCvYS', 0, 0);
-- ^ bcrypt hash verified with password_verify('Admin123!', $hash) === true

INSERT INTO staff (account_ID, staff_level)
VALUES (9101, 1);  -- staff_level 1 = full admin
