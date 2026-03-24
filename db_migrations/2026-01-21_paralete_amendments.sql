-- Paralete client amendments (15.1.26) - schema changes

-- 1) Allow up to 2 specialisms to be stored (pipe-separated) in paralegal_profiles.specialism
ALTER TABLE paralegal_profiles
  MODIFY specialism VARCHAR(255) DEFAULT NULL;

-- 2) Paralegal additional preferences
ALTER TABLE paralegal_profiles
  ADD COLUMN can_travel_abroad TINYINT(1) NOT NULL DEFAULT 0 AFTER is_available,
  ADD COLUMN night_work TINYINT(1) NOT NULL DEFAULT 0 AFTER can_travel_abroad,
  ADD COLUMN languages VARCHAR(255) DEFAULT NULL AFTER night_work,
  ADD COLUMN availability_start DATE DEFAULT NULL AFTER languages,
  ADD COLUMN availability_end DATE DEFAULT NULL AFTER availability_start;

-- 3) Employer job additional requirements
ALTER TABLE jobs
  ADD COLUMN urgent_work TINYINT(1) NOT NULL DEFAULT 0 AFTER on_site,
  ADD COLUMN work_247 TINYINT(1) NOT NULL DEFAULT 0 AFTER urgent_work,
  ADD COLUMN languages_required VARCHAR(255) DEFAULT NULL AFTER work_247,
  ADD COLUMN urgent_travel_required TINYINT(1) NOT NULL DEFAULT 0 AFTER languages_required,
  ADD COLUMN dual_qualifications VARCHAR(255) DEFAULT NULL AFTER urgent_travel_required;

-- 4) Document sharing method options
-- Expand enum and preserve existing data
ALTER TABLE job_handover
  MODIFY method ENUM('LINK','FIRM_EMAIL','PERSONAL_EMAIL','NOT_REQUIRED') NOT NULL DEFAULT 'LINK';

UPDATE job_handover SET method='FIRM_EMAIL' WHERE method='EMAIL';
