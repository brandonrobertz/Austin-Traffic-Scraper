# In case you don't want to use the PHP scripts to un/install...
CREATE TABLE `trafficker` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pubdate` VARCHAR(50) NULL DEFAULT NULL,
  `location` VARCHAR(200) NULL DEFAULT NULL,
  `description` VARCHAR(225) NULL DEFAULT NULL,
  `category` VARCHAR(50) NULL DEFAULT NULL,
  `lat` FLOAT(10,6) NOT NULL,
  `lng` FLOAT(10,6) NOT NULL,
  `filtered` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`id`)
)
COLLATE='latin1_swedish_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0;