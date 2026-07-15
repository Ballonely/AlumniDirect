/*
  schema_staff_additions.sql
  ---------------------------------------------------------------
  Run this AFTER schema.sql. It does NOT replace anything you have —
  it only adds what the Staff/Admin panel needs that wasn't in the
  original schema yet.

  Why these are needed:
  1. `modifications.modified_Records` only stores a short string of
     field names. There was nowhere to store the actual OLD value vs
     the REQUESTED value, which is exactly what the Applications
     review card displays (left column vs right column). So a real
     "Approve" action had nothing to apply.
  2. There was no table at all for the uploaded verification files
     ("blobs") shown as attachment chips in the review card.
  3. `is_Verified` is just a 0/1 flag, so a denied request looks
     identical to a pending one. Added an explicit status so denied
     requests can be told apart and hidden from the queue.
*/

USE alumnidirectorydb;

-- Per-field diff for a modification request
CREATE TABLE IF NOT EXISTS modification_detail (
    detail_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    modification_ID int(11) NOT NULL,
    field_Label varchar(100) NOT NULL,   -- e.g. "Current Company" (shown to staff)
    field_Name varchar(100) NOT NULL,    -- e.g. "employer" (used to apply the change)
    old_Value text DEFAULT NULL,
    new_Value text DEFAULT NULL,
    KEY fk_moddetail_modification (modification_ID),
    CONSTRAINT fk_moddetail_modification FOREIGN KEY (modification_ID)
        REFERENCES modifications (modification_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Uploaded proof documents ("verification blobs") for a modification request
CREATE TABLE IF NOT EXISTS modification_attachment (
    attachment_ID int(11) PRIMARY KEY AUTO_INCREMENT,
    modification_ID int(11) NOT NULL,
    file_Name varchar(255) NOT NULL,
    file_Type varchar(100) DEFAULT NULL,
    file_Blob MEDIUMBLOB NOT NULL,
    KEY fk_modattach_modification (modification_ID),
    CONSTRAINT fk_modattach_modification FOREIGN KEY (modification_ID)
        REFERENCES modifications (modification_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Explicit status so a denied request isn't indistinguishable from pending
ALTER TABLE modifications
    ADD COLUMN status enum('Pending','Approved','Denied') NOT NULL DEFAULT 'Pending' AFTER is_Verified,
    ADD COLUMN admin_Comment text DEFAULT NULL AFTER status;
