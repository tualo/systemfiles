DELIMITER ;
CREATE TABLE IF NOT EXISTS `system_file` (
  `filename` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `mimetype` varchar(255) NOT NULL,
  `etag` varchar(255) NOT NULL,
  PRIMARY KEY (`filename`)
) ;

CREATE TABLE IF NOT EXISTS `system_file_data` (
  `filename` varchar(255) NOT NULL,
  `data` longtext, -- stores the file content in base64 format
  constraint `fk_system_file_data_filename`
  foreign key (`filename`)
  references (`system_file`)
  on delete cascade
  on update cascade
  PRIMARY KEY (`filename`)
) ;