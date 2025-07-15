-- phpMyAdmin SQL Dump
-- version 5.2.2-1.fc42
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 15, 2025 at 08:09 AM
-- Server version: 10.11.11-MariaDB
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `votems`
--

-- --------------------------------------------------------

--
-- Table structure for table `Candidate`
--

CREATE TABLE `Candidate` (
  `CandidateID` int(11) NOT NULL,
  `VoterID` int(11) DEFAULT NULL,
  `PositionID` int(11) DEFAULT NULL,
  `Party` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Enumerator`
--

CREATE TABLE `Enumerator` (
  `EnumeratorID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Post`
--

CREATE TABLE `Post` (
  `PositionID` int(11) NOT NULL,
  `PositionName` varchar(100) DEFAULT NULL,
  `VoteSessionID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Vote`
--

CREATE TABLE `Vote` (
  `VoteID` int(11) NOT NULL,
  `VoterID` int(11) DEFAULT NULL,
  `CandidateID` int(11) DEFAULT NULL,
  `PositionID` int(11) DEFAULT NULL,
  `VoteSessionID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Voter`
--

CREATE TABLE `Voter` (
  `VoterID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Regno` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `VoteSession`
--

CREATE TABLE `VoteSession` (
  `VoteSessionID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `EnumeratorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Candidate`
--
ALTER TABLE `Candidate`
  ADD PRIMARY KEY (`CandidateID`),
  ADD KEY `VoterID` (`VoterID`),
  ADD KEY `PositionID` (`PositionID`);

--
-- Indexes for table `Enumerator`
--
ALTER TABLE `Enumerator`
  ADD PRIMARY KEY (`EnumeratorID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `Post`
--
ALTER TABLE `Post`
  ADD PRIMARY KEY (`PositionID`),
  ADD KEY `VoteSessionID` (`VoteSessionID`);

--
-- Indexes for table `Vote`
--
ALTER TABLE `Vote`
  ADD PRIMARY KEY (`VoteID`),
  ADD UNIQUE KEY `VoterID` (`VoterID`,`PositionID`),
  ADD KEY `CandidateID` (`CandidateID`),
  ADD KEY `PositionID` (`PositionID`),
  ADD KEY `VoteSessionID` (`VoteSessionID`);

--
-- Indexes for table `Voter`
--
ALTER TABLE `Voter`
  ADD PRIMARY KEY (`VoterID`),
  ADD UNIQUE KEY `Regno` (`Regno`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `VoteSession`
--
ALTER TABLE `VoteSession`
  ADD PRIMARY KEY (`VoteSessionID`),
  ADD KEY `EnumeratorID` (`EnumeratorID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Candidate`
--
ALTER TABLE `Candidate`
  MODIFY `CandidateID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Enumerator`
--
ALTER TABLE `Enumerator`
  MODIFY `EnumeratorID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Post`
--
ALTER TABLE `Post`
  MODIFY `PositionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Vote`
--
ALTER TABLE `Vote`
  MODIFY `VoteID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Voter`
--
ALTER TABLE `Voter`
  MODIFY `VoterID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `VoteSession`
--
ALTER TABLE `VoteSession`
  MODIFY `VoteSessionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Candidate`
--
ALTER TABLE `Candidate`
  ADD CONSTRAINT `Candidate_ibfk_1` FOREIGN KEY (`VoterID`) REFERENCES `Voter` (`VoterID`),
  ADD CONSTRAINT `Candidate_ibfk_3` FOREIGN KEY (`PositionID`) REFERENCES `Post` (`PositionID`);

--
-- Constraints for table `Post`
--
ALTER TABLE `Post`
  ADD CONSTRAINT `Post_ibfk_1` FOREIGN KEY (`VoteSessionID`) REFERENCES `VoteSession` (`VoteSessionID`);

--
-- Constraints for table `Vote`
--
ALTER TABLE `Vote`
  ADD CONSTRAINT `Vote_ibfk_1` FOREIGN KEY (`VoterID`) REFERENCES `Voter` (`VoterID`),
  ADD CONSTRAINT `Vote_ibfk_2` FOREIGN KEY (`CandidateID`) REFERENCES `Candidate` (`CandidateID`),
  ADD CONSTRAINT `Vote_ibfk_3` FOREIGN KEY (`PositionID`) REFERENCES `Post` (`PositionID`),
  ADD CONSTRAINT `Vote_ibfk_4` FOREIGN KEY (`VoteSessionID`) REFERENCES `VoteSession` (`VoteSessionID`);

--
-- Constraints for table `VoteSession`
--
ALTER TABLE `VoteSession`
  ADD CONSTRAINT `VoteSession_ibfk_1` FOREIGN KEY (`EnumeratorID`) REFERENCES `Enumerator` (`EnumeratorID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
