-- Database setup script for Live Cricket Score Application

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `live-cricket`;

-- Use the database
USE `live-cricket`;

-- Create admins table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin user
INSERT INTO `admins` (`email`, `password`) VALUES
('admin@example.com', 'admin123');

-- Create players table
CREATE TABLE IF NOT EXISTS `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `country` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample players
INSERT INTO `players` (`name`, `country`, `role`) VALUES
('Virat Kohli', 'India', 'Batsman'),
('Rohit Sharma', 'India', 'Batsman'),
('Jasprit Bumrah', 'India', 'Bowler'),
('Joe Root', 'England', 'Batsman'),
('Ben Stokes', 'England', 'All-rounder'),
('Jofra Archer', 'England', 'Bowler'),
('Babar Azam', 'Pakistan', 'Batsman'),
('Shaheen Afridi', 'Pakistan', 'Bowler'),
('Steve Smith', 'Australia', 'Batsman'),
('Pat Cummins', 'Australia', 'Bowler');

-- Create matches table
CREATE TABLE IF NOT EXISTS `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team1` varchar(50) NOT NULL,
  `team2` varchar(50) NOT NULL,
  `venue` varchar(100) NOT NULL,
  `match_date` date NOT NULL,
  `match_type` varchar(20) NOT NULL DEFAULT 'T20',
  `status` varchar(20) NOT NULL,
  `total_overs` int(11) NOT NULL DEFAULT 20,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample matches
INSERT INTO `matches` (`team1`, `team2`, `venue`, `match_date`, `match_type`, `status`, `total_overs`) VALUES
('India', 'England', 'Narendra Modi Stadium, Ahmedabad', '2025-05-10', 'T20', 'Upcoming', 20),
('Australia', 'Pakistan', 'MCG, Melbourne', '2025-05-12', 'T20', 'Upcoming', 20),
('New Zealand', 'South Africa', 'Eden Park, Auckland', '2025-05-15', 'ODI', 'Upcoming', 50),
('West Indies', 'Sri Lanka', 'Kensington Oval, Barbados', '2025-05-05', 'T20', 'Live', 20),
('Bangladesh', 'Zimbabwe', 'Shere Bangla Stadium, Dhaka', '2025-05-03', 'ODI', 'Completed', 50);

-- Create match_scores table for ball-by-ball scoring
CREATE TABLE IF NOT EXISTS `match_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `innings` int(11) NOT NULL,
  `over_number` int(11) NOT NULL,
  `ball_number` int(11) NOT NULL,
  `batsman_id` int(11) NOT NULL,
  `bowler_id` int(11) NOT NULL,
  `runs` int(11) NOT NULL DEFAULT 0,
  `extras` int(11) NOT NULL DEFAULT 0,
  `extra_type` varchar(20) DEFAULT NULL,
  `wicket` tinyint(1) NOT NULL DEFAULT 0,
  `wicket_type` varchar(20) DEFAULT NULL,
  `fielder_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `batsman_id` (`batsman_id`),
  KEY `bowler_id` (`bowler_id`),
  KEY `fielder_id` (`fielder_id`),
  CONSTRAINT `match_scores_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `match_scores_ibfk_2` FOREIGN KEY (`batsman_id`) REFERENCES `players` (`id`),
  CONSTRAINT `match_scores_ibfk_3` FOREIGN KEY (`bowler_id`) REFERENCES `players` (`id`),
  CONSTRAINT `match_scores_ibfk_4` FOREIGN KEY (`fielder_id`) REFERENCES `players` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create a view for match summaries
CREATE OR REPLACE VIEW `match_summary` AS
SELECT 
    m.id AS match_id,
    m.team1,
    m.team2,
    m.venue,
    m.match_date,
    m.match_type,
    m.status,
    m.total_overs,
    (SELECT SUM(runs + extras) FROM match_scores WHERE match_id = m.id AND innings = 1) AS team1_score,
    (SELECT SUM(wicket) FROM match_scores WHERE match_id = m.id AND innings = 1) AS team1_wickets,
    (SELECT SUM(runs + extras) FROM match_scores WHERE match_id = m.id AND innings = 2) AS team2_score,
    (SELECT SUM(wicket) FROM match_scores WHERE match_id = m.id AND innings = 2) AS team2_wickets
FROM 
    matches m;
