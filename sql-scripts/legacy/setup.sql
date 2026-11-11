-- Check if the user already exists
SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = 'root' AND host = '%') AS user_exists;

-- If the user doesn't exist, create it without a password
CREATE USER IF NOT EXISTS 'root'@'%';

-- Grant privileges to the user
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `projectdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `projectdb`;

CREATE TABLE IF NOT EXISTS `Dealer`
(
    `dealerID`        varchar(50)  NOT NULL,
    `password`        varchar(100) NOT NULL,
    `dealerName`      varchar(100) NOT NULL,
    `contactName`     varchar(100) NOT NULL,
    `contactNumber`   int(30)      NOT NULL,
    `faxNumber`       int(30) DEFAULT NULL,
    `deliveryAddress` varchar(255) NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `Dealer` (`dealerID`, `password`, `dealerName`, `contactName`, `contactNumber`, `faxNumber`,
                      `deliveryAddress`)
VALUES ('alex@auto-racing.com', '$2y$12$MZXpXxxJUpT6Fh04FrNzG.AGLurEhfDgeKD3PbsjCbLsosP.EWIzS', 'Auto Racing',
        'Alex Wong', 21232123, 22223333, 'G/F, ABC Building, King Yip Street, KwunTong, Kowloon, Hong Kong'),
       ('bowie＠car-care.com', '$2y$12$MZXpXxxJUpT6Fh04FrNzG.AGLurEhfDgeKD3PbsjCbLsosP.EWIzS', 'Car Care', 'Bowie',
        61236123, 31112222, '401, Sing Kei Building, Kowloon, Hong Kong'),
       ('tina@good-service.com', '$2y$12$MZXpXxxJUpT6Fh04FrNzG.AGLurEhfDgeKD3PbsjCbLsosP.EWIzS', 'Good Service',
        'Tina Chan', 31233123, 33334444, '303, Mei Hing Center, Yuen Long, NT, Hong Kong');

CREATE TABLE IF NOT EXISTS `Item`
(
    `sparePartNum`         int(10)      NOT NULL,
    `sparePartCategory`    int(1)       NOT NULL,
    `sparePartName`        varchar(255) NOT NULL,
    `sparePartImage`       varchar(100) NOT NULL,
    `sparePartDescription` varchar(255) DEFAULT NULL,
    `weight`               double       NOT NULL,
    `stockItemQty`         int(10)      NOT NULL,
    `price`                double       NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `Item` (`sparePartNum`, `sparePartCategory`, `sparePartName`, `sparePartImage`, `sparePartDescription`,
                    `weight`, `stockItemQty`, `price`)
VALUES (1, 1, 'Vehicle Firewall (Lower Front Section)', '100001.jpg',
        'This robust barrier is constructed from high-grade, heat-resistant materials that prevent engine heat, noise, and potential hazards from reaching the interior of the vehicle. ',
        70, 3, 5000),
       (2, 1, 'Front Left Door (Unpainted) (Outside)', '100002.jpg',
        'Front left door replacement for various vehicle models.Crafted from high-quality, durable materials, this door is designed to provide the structural integrity and fit of an OEM part.',
        30, 0, 3500),
       (3, 2, 'AC Compressor Bump', '200001.jpg',
        'Our Complete Cooling System Kit is your one-stop solution for overhauling your vehicles cooling system. This comprehensive kit includes everything from the radiator to the thermostat, ensuring your engine stays cool under any driving conditions. ',
        30, 3, 6000),
       (4, 2, 'Engine Rebuild Kit', '200002.jpg',
        'This kit is meticulously assembled to support a complete overhaul of your vehicles power plant, ensuring that it runs as smoothly and efficiently as the day you first turned the key. ',
        100, 0, 10000),
       (5, 3, 'Headlight Bulb Replacement', '300001.jpg',
        'Illuminate the road ahead with superior brightness by upgrading to our Premium Headlight Bulbs. Engineered for maximum visibility and clarity.',
        1, 3, 500),
       (6, 3, 'Ultra-Bright Vertical Truck Rear Lamp', '300002.jpg',
        'Elevate the safety and style of your truck with our Ultra-Bright Vertical Truck Rear Lamp Assembly. Designed to fit a variety of commercial trucks, trailers, and heavy-duty vehicles.',
        10, 0, 1000),
       (7, 4, 'Tire Pressure Monitoring System (TPMS)', '400001.jpg',
        'Designed to provide real-time tire pressure information, these sensors help you maintain proper tire inflation.',
        50, 3, 1000),
       (8, 4, '12V Single USB Car Charger', '400002.jpg',
        'Keep your devices powered up while on the move with the CompactDrive 12V Single USB Car Charger. ', 10, 0,
        100),
       (9, 1, 'Front Left Door (Inside)', '100003.jpg',
        'The inner door panel is a critical component that covers the interior part of the door and houses various functional elements like the handle, controls for windows and locks.',
        10, 10, 3000),
       (10, 1, 'Engine Hood Cover Replacement', '100004.jpg',
        'Replace your worn or damaged engine hood cover with our Premium Engine Hood Cover Replacement. ', 30, 10,
        4000),
       (11, 1, 'Trunk Door Panel', '100005.jpg',
        'Our custom trunk door panel replacement is designed to fit the specific contours and style of your vehicles trunk door.',
        30, 10, 4000);

CREATE TABLE IF NOT EXISTS `login_attempts`
(
    `id`           int(11)      NOT NULL AUTO_INCREMENT,
    `username`     varchar(255) NOT NULL,
    `ip_address`   varchar(45)  NOT NULL,
    `attempt_time` datetime     NOT NULL,
    `success`      tinyint(1)   NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 13
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `attempt_time`, `success`)
VALUES (3, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 09:16:43', 1),
       (4, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 09:16:50', 1),
       (5, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:03:30', 1),
       (6, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:31:50', 1),
       (7, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:33:17', 1),
       (8, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:35:48', 1),
       (9, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:36:51', 1),
       (10, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:37:06', 1),
       (11, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:38:05', 1),
       (12, 'alex@auto-racing.com', '172.21.0.1', '2024-07-09 11:42:08', 1);

CREATE TABLE IF NOT EXISTS `Orders`
(
    `orderID`         int(10)      NOT NULL,
    `dealerID`        varchar(50)  NOT NULL,
    `salesManagerID`  varchar(50)  NOT NULL,
    `orderDateTime`   timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deliveryAddress` varchar(255) NOT NULL,
    `deliveryDate`    date         NOT NULL,
    `orderStatus`     varchar(50)  NOT NULL,
    `shipCost`        int(5)       NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `Orders` (`orderID`, `dealerID`, `salesManagerID`, `orderDateTime`, `deliveryAddress`, `deliveryDate`,
                      `orderStatus`, `shipCost`)
VALUES (1, 'alex@auto-racing.com', 'peter@slms.com', '2024-03-31 09:50:00',
        'G/F, ABC Building, King Yip Street, KwunTong, Kowloon, Hong Kong', '2024-04-05', 'Shipped', 3750),
       (2, 'alex@auto-racing.com', 'mary@slms.com', '2024-04-01 04:01:00',
        'G/F, ABC Building, King Yip Street, KwunTong, Kowloon, Hong Kong', '2024-04-10', 'Processing', 480),
       (3, 'tina@good-service.com', 'kit@slms.com', '2024-04-22 01:37:00',
        '303, Mei Hing Center, Yuen Long, NT, Hong Kong', '2024-04-29', 'Cancelled', 780);

CREATE TABLE IF NOT EXISTS `OrdersItem`
(
    `orderID`             int(10) NOT NULL,
    `sparePartNum`        int(10) NOT NULL,
    `orderQty`            int(10) NOT NULL,
    `sparePartOrderPrice` double  NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `OrdersItem` (`orderID`, `sparePartNum`, `orderQty`, `sparePartOrderPrice`)
VALUES (1, 1, 1, 3999),
       (2, 2, 2, 3500),
       (2, 3, 2, 6000),
       (3, 4, 3, 10000),
       (3, 5, 3, 500),
       (3, 6, 3, 1000);

CREATE TABLE IF NOT EXISTS `SalesManager`
(
    `salesManagerID` varchar(50)  NOT NULL,
    `password`       varchar(100) NOT NULL,
    `managerName`    varchar(100) NOT NULL,
    `contactName`    varchar(100) NOT NULL,
    `contactNumber`  int(30)      NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `SalesManager` (`salesManagerID`, `password`, `managerName`, `contactName`, `contactNumber`)
VALUES ('kit@slms.com', '$2y$12$MZXpXxxJUpT6Fh04FrNzG.AGLurEhfDgeKD3PbsjCbLsosP.EWIzS', 'Li Chun Kit, Kit', 'Kit',
        31233123),
       ('mary@slms.com', '$2y$12$MZXpXxxJUpT6Fh04FrNzG.AGLurEhfDgeKD3PbsjCbLsosP.EWIzS', 'Wong Lai Man, Mary', 'Mary',
        51235123),
       ('peter@slms.com', '$2y$12$MZXpXxxJUpT6Fh04FrNzG.AGLurEhfDgeKD3PbsjCbLsosP.EWIzS', 'Chan Tai Man, Peter',
        'Peter', 91239123);
CREATE TABLE IF NOT EXISTS `UserRoles`
(
    `UserID`          varchar(50),
    `password`        varchar(100),
    `CompanyName`     varchar(100),
    `contactName`     varchar(100),
    `contactNumber`   int(30),
    `faxNumber`       int(30),
    `deliveryAddress` varchar(255),
    `Surname`         varchar(100),
    `UserRole`        varchar(12)
);

CREATE TABLE IF NOT EXISTS `user_tokens`
(
    `id`         int(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    varchar(255) NOT NULL,
    `token`      varchar(64)  NOT NULL,
    `expires_at` datetime     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 8
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `expires_at`)
VALUES (1, 'alex@auto-racing.com', 'f6c2d5b1b52626eb8e0337ea1ab737a105cb81cd7bd8c99dcd4949c2492394a0',
        '2024-07-09 12:31:50'),
       (2, 'alex@auto-racing.com', 'b832d2b2042828e560ab421ac1b39c4401725ae389dea77a6dabcef6007dfb62',
        '2024-07-09 12:33:17'),
       (3, 'alex@auto-racing.com', '1303f98bce7c773a9e73df65adbfdbeaa6c8bf8a5a993de8f108304b10cb6a01',
        '2024-07-09 12:35:48'),
       (4, 'alex@auto-racing.com', '333cc928325af7096be6c83914e3b8ec92561cc676b7c0412efe5c9c27e6dab0',
        '2024-07-09 12:36:51'),
       (5, 'alex@auto-racing.com', 'd626d2297a4c086048f6080cc22976fbb768c4402c7d5492a566e67092a391e8',
        '2024-07-09 12:37:06'),
       (6, 'alex@auto-racing.com', '78e08d2fad8756562f2d26300ffa7bb3f17952b32b7aa39e1e78b36ea59b31e5',
        '2024-07-09 12:38:05'),
       (7, 'alex@auto-racing.com', '31329816252dac90606f97594df99476c6118df01c2959e5912699132a4d2c7d',
        '2024-07-09 12:42:08');
DROP TABLE IF EXISTS `UserRoles`;

CREATE ALGORITHM = UNDEFINED DEFINER =`root`@`%` SQL SECURITY DEFINER VIEW `UserRoles` AS
SELECT `Dealer`.`dealerID`        AS `UserID`,
       `Dealer`.`password`        AS `password`,
       `Dealer`.`dealerName`      AS `CompanyName`,
       `Dealer`.`contactName`     AS `contactName`,
       `Dealer`.`contactNumber`   AS `contactNumber`,
       `Dealer`.`faxNumber`       AS `faxNumber`,
       `Dealer`.`deliveryAddress` AS `deliveryAddress`,
       NULL                       AS `Surname`,
       'Dealer'                   AS `UserRole`
FROM `Dealer`
union all
select `SalesManager`.`salesManagerID` AS `UserID`,
       `SalesManager`.`password`       AS `password`,
       NULL                            AS `CompanyName`,
       `SalesManager`.`managerName`    AS `contactName`,
       `SalesManager`.`contactNumber`  AS `ContactNumber`,
       NULL                            AS `faxNumber`,
       NULL                            AS `deliveryAddress`,
       `SalesManager`.`contactName`    AS `Surname`,
       'SalesManager'                  AS `UserRole`
from `SalesManager`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
