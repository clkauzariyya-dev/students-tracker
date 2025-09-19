-- --------------------------------------------------------
-- Table structure for table `classes`
-- --------------------------------------------------------

CREATE TABLE `classes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_name` VARCHAR(50) NOT NULL,
  `teacher` VARCHAR(100) NOT NULL,
  `section` VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
