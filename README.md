# expensebuddy
A little PHP utility for tracking personal expenses.  Has a basic login framework as well.  
Uses Socketlabs for email sending by default which requires PHPMailer

Here are the three datatables to create:


CREATE TABLE `users` (
  `uID` int(11) NOT NULL,
  `accession` varchar(50) NOT NULL,
  `email` varchar(155) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstName` varchar(155) NOT NULL,
  `lastName` varchar(155) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '0',
  `reset_token` varchar(255) NOT NULL,
  `fID` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

___________________________________________________________________________________________________________

CREATE TABLE `expenses` (
  `eID` int(11) NOT NULL,
  `date` varchar(20) NOT NULL,
  `category` varchar(150) NOT NULL,
  `amount` varchar(150) NOT NULL,
  `description` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  `uID` varchar(10) NOT NULL,
  `fID` varchar(20) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`eID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `eID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

___________________________________________________________________________________________________________

CREATE TABLE `categories` (
  `cID` int(11) NOT NULL,
  `catName` varchar(150) NOT NULL,
  `catBudget` varchar(20) NOT NULL,
  `notes` text NOT NULL,
  `fID` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `cID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;


