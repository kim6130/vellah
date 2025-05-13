
-- create a database named "alkansave" in phpMyAdmin then copy paste each table in SQL

-- Enhanced User Table (with critical indexes)
CREATE TABLE User (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    DOB DATE,
    Email VARCHAR(100) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    Role VARCHAR(20) DEFAULT 'user',
    ProfilePicture VARCHAR(255),
    AccountStatus ENUM('Active','Inactive') DEFAULT 'Active',
    DateCreated DATETIME DEFAULT CURRENT_TIMESTAMP,
    LastLogin DATETIME,
    IsDeleted BOOLEAN DEFAULT FALSE,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (Email),
    INDEX idx_account_status (AccountStatus)
);

-- Password Reset Table (no indexes - small table)
CREATE TABLE PasswordReset (
    ResetID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    Email VARCHAR(100) NOT NULL,
    VerificationCode VARCHAR(10) NOT NULL,
    Expiration DATETIME NOT NULL,
    Used BOOLEAN DEFAULT FALSE,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES User(UserID)
);

-- Category Table (no index - small lookup table)
CREATE TABLE Category (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL,
    DateCreated DATETIME DEFAULT CURRENT_TIMESTAMP,
    IsDeleted BOOLEAN DEFAULT FALSE,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Goal Table (with deadline/status indexes)
CREATE TABLE Goal (
    GoalID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    CategoryID INT,
    GoalName VARCHAR(100) NOT NULL,
    TargetAmount DECIMAL(10,2) NOT NULL,
    SavedAmount DECIMAL(10,2) DEFAULT 0.00,
    StartDate DATE NOT NULL,
    TargetDate DATE NOT NULL,
    Status ENUM('Active', 'Completed') DEFAULT 'Active',
    CompletionDate DATE NULL,
    IsDeleted BOOLEAN DEFAULT FALSE,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES User(UserID),
    FOREIGN KEY (CategoryID) REFERENCES Category(CategoryID),
    INDEX idx_target_date (TargetDate),
    INDEX idx_status (Status)
);

-- Savings Transaction Table (no index - accessed via GoalID)
CREATE TABLE SavingsTransaction (
    TransactionID INT AUTO_INCREMENT PRIMARY KEY,
    GoalID INT,
    Amount DECIMAL(10,2) NOT NULL,
    DateSaved DATE NOT NULL,
    IsDeleted BOOLEAN DEFAULT FALSE,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (GoalID) REFERENCES Goal(GoalID)
);

-- Admin Table (with username index)
CREATE TABLE Admin (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    PasswordHash VARCHAR(255) NOT NULL,
    LastLogin DATETIME,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    Email VARCHAR(100) UNIQUE NOT NULL,
    INDEX idx_email ON Admin (Email)
);

-- Activity Log (with timestamp index)
CREATE TABLE ActivityLog (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NULL,
    AdminID INT NULL,
    ActionType ENUM('Login', 'Logout', 'CreateGoal', 'UpdateGoal', 'DeleteGoal', 'AddSavings', 'ProfileUpdate', 'PasswordChange', 'AccountStatusChange') NOT NULL,
    ActionDetails TEXT,
    Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES User(UserID),
    FOREIGN KEY (AdminID) REFERENCES Admin(AdminID),
    INDEX idx_timestamp (Timestamp)
);

-- Analytics View (unchanged)
CREATE VIEW AdminAnalyticsView AS
WITH UserActivity AS (
    SELECT 
        COUNT(DISTINCT CASE WHEN LastLogin >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN UserID END) AS ActiveUsers,
        COUNT(DISTINCT CASE WHEN LastLogin < DATE_SUB(CURDATE(), INTERVAL 7 DAY) OR LastLogin IS NULL THEN UserID END) AS InactiveUsers
    FROM User
    WHERE IsDeleted = FALSE
),
CategoryUsage AS (
    SELECT
        c.CategoryName,
        AVG(g.SavedAmount) AS AvgSavedAmount
    FROM Category c
    LEFT JOIN Goal g ON c.CategoryID = g.CategoryID AND g.IsDeleted = FALSE
    WHERE c.IsDeleted = FALSE
    GROUP BY c.CategoryID
    ORDER BY COUNT(g.GoalID) DESC
    LIMIT 4
)
SELECT 
    (SELECT COUNT(*) FROM User WHERE IsDeleted = FALSE) AS TotalUsers,
    (SELECT ActiveUsers FROM UserActivity) AS ActiveUsers,
    (SELECT InactiveUsers FROM UserActivity) AS InactiveUsers,
    MONTHNAME(CURDATE()) AS CurrentMonth,
    YEAR(CURDATE()) AS CurrentYear,
    (SELECT CONCAT('₱', FORMAT(AvgSavedAmount, 2)) FROM CategoryUsage LIMIT 1) AS AvgSavingsPerCategory,
    (SELECT COUNT(DISTINCT UserID) FROM ActivityLog 
     WHERE ActionType = 'Login' AND MONTH(Timestamp) = MONTH(CURDATE())) AS MonthlyActiveUsers,
    (SELECT CONCAT('[', GROUP_CONCAT(CONCAT('"', CategoryName, '"')), ']') FROM CategoryUsage) AS TopCategories;

/*
Insert the default admin account
The admin account is pre-created in the database with:
Email: admin@gmail.com
Password: AdminAccount123
*/
INSERT INTO Admin (Email, PasswordHash) 
VALUES (
    'admin@gmail.com', 
    '$2y$10$DbagTVUo3pyP76TWJWqj9ee3z/COVFPs1HEFPdcWGwzVdwgTnkl6q'
    -- Password: AdminAccount123
);
