ALTER TABLE releasecomment DROP COLUMN siteid;

UPDATE site SET value = '199' WHERE setting = 'sqlpatch';
