/*
  modifications_seed.sql
  Sample pending requests for testing the Applications tab, using the
  existing sample alumni (9001-9009) from random_data.sql.
  Run this AFTER schema.sql, schema_staff_additions.sql, and
  random_data.sql (and staff_seed.sql, so staff_ID 1 exists).
*/

USE alumnidirectorydb;

INSERT INTO modifications (modification_ID, account_ID, staff_ID, is_Verified, status, action_Type, modified_Records) VALUES
(9201, 9001, 1, 0, 'Pending', 'Update', 'employer,occupation'),
(9202, 9005, 1, 0, 'Pending', 'Update', 'phone,email');

INSERT INTO modification_detail (modification_ID, field_Label, field_Name, old_Value, new_Value) VALUES
(9201, 'Current Company', 'employer', 'Tech Corp Inc.', 'Global Solutions LLC'),
(9201, 'Job Title', 'occupation', 'Junior Developer', 'Senior Software Engineer'),
(9202, 'Contact Email', 'email', 'liza.fernandez@alumni.sample.edu.ph', 'liza.f.new@alumni.sample.edu.ph'),
(9202, 'Phone Number', 'phone', '+63 921 123 0005', '+63 921 999 0005');
