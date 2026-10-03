CREATE DATABASE IF NOT EXISTS HostelComplaintMaintenanceTracker;
USE HostelComplaintMaintenanceTracker;
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20),
    Password VARCHAR(255) NOT NULL,
    Role ENUM('Student','MaintenanceStaff','Admin') NOT NULL,
    Status ENUM('Active','Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO Users (FullName, Email, Phone, Password, Role, Status)
VALUES
('Abu Talha Swargo', 'swargo@student.com', '01711111111', 'Student@123', 'Student', 'Active'),
('Rakib Hasan', 'rakib@student.com', '01722222222', 'Student@123', 'Student', 'Active'),
('Md. Rahim', 'rahim@staff.com', '01811111111', 'Staff@123', 'MaintenanceStaff', 'Active'),
('Karim Uddin', 'karim@staff.com', '01822222222', 'Staff@123', 'MaintenanceStaff', 'Active'),
('System Admin', 'admin@hostel.com', '01911111111', 'Admin@123', 'Admin', 'Active');
CREATE TABLE Hostel (
    HostelID INT AUTO_INCREMENT PRIMARY KEY,
    HostelName VARCHAR(100) NOT NULL,
    Address VARCHAR(200),
    TotalRooms INT
);
INSERT INTO Hostel (HostelName, Address, TotalRooms)
VALUES
('EWU Boys Hostel A', 'Aftabnagar, Dhaka', 50),
('EWU Boys Hostel B', 'Badda, Dhaka', 40),
('EWU Girls Hostel A', 'Aftabnagar, Dhaka', 45),
('EWU Girls Hostel B', 'Rampura, Dhaka', 35),
('International Hostel', 'Uttara, Dhaka', 60);
CREATE TABLE Room (
    RoomID INT AUTO_INCREMENT PRIMARY KEY,
    HostelID INT,
    RoomNumber VARCHAR(20),
    FloorNo INT,
    Capacity INT,
    Status ENUM('Available','Occupied','Maintenance'),
    FOREIGN KEY (HostelID)
	REFERENCES Hostel(HostelID)
);
INSERT INTO Room (HostelID, RoomNumber, FloorNo, Capacity, Status)
VALUES
(1, 'A101', 1, 4, 'Occupied'),
(1, 'A102', 1, 4, 'Available'),
(1, 'A201', 2, 4, 'Maintenance'),
(2, 'B101', 1, 3, 'Occupied'),
(2, 'B102', 1, 3, 'Available'),
(3, 'G101', 1, 2, 'Occupied'),
(3, 'G102', 1, 2, 'Available'),
(4, 'G201', 2, 4, 'Maintenance'),
(4, 'G202', 2, 4, 'Occupied'),
(5, 'I101', 1, 6, 'Available');
CREATE TABLE Student (
    StudentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    StudentNumber VARCHAR(30) UNIQUE,
    Department VARCHAR(100),
    RoomID INT,
    FOREIGN KEY (UserID)
	REFERENCES Users(UserID),
    FOREIGN KEY (RoomID)
	REFERENCES Room(RoomID)
);
INSERT INTO Student (UserID, StudentNumber, Department, RoomID)
VALUES
(1, '2023-1-60-001', 'Computer Science and Engineering', 1),
(2, '2023-1-60-002', 'Computer Science and Engineering', 2),
(3, '2023-1-60-003', 'Electrical and Electronic Engineering', 4),
(4, '2023-1-60-004', 'Business Administration', 6),
(5, '2023-1-60-005', 'Economics', 9);
CREATE TABLE ComplaintCategory (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(50),
    Description TEXT
);
INSERT INTO ComplaintCategory (CategoryName, Description)
VALUES
('Electrical', 'Problems related to lights, fans, switches, sockets, and wiring'),
('Plumbing', 'Problems related to water supply, leaking pipes, taps, washrooms, and drainage'),
('Internet', 'Wi-Fi connectivity, slow internet, or network issues'),
('Furniture', 'Damaged beds, chairs, tables, wardrobes, or other furniture'),
('Cleaning', 'Room, bathroom, corridor, or hostel cleanliness issues'),
('Water Supply', 'No water, low water pressure, or water quality issues'),
('Security', 'Security-related concerns, unauthorized entry, or CCTV problems'),
('Air Conditioning', 'Air conditioner or ventilation system problems'),
('Appliance', 'Issues with electrical appliances such as refrigerators, water dispensers, or washing machines'),
('Other', 'Any complaint that does not fall under the above categories');
CREATE TABLE Complaint (
    ComplaintID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID INT,
    CategoryID INT,
    RoomID INT,
    Title VARCHAR(150),
    Description TEXT,
    Priority ENUM('Low','Medium','High','Emergency'),
    Status ENUM(
        'Submitted',
        'Verified',
        'Assigned',
        'In Progress',
        'Resolved',
        'Closed',
        'Rejected'
    ),
    SubmittedDate DATETIME,
    ResolutionDate DATETIME,
    FOREIGN KEY (StudentID)
	REFERENCES Student(StudentID),
    FOREIGN KEY (CategoryID)
	REFERENCES ComplaintCategory(CategoryID),
    FOREIGN KEY (RoomID)
	REFERENCES Room(RoomID)
);
INSERT INTO Complaint
(StudentID, CategoryID, RoomID, Title, Description, Priority, Status, SubmittedDate, ResolutionDate)
VALUES
(1, 1, 1,'Light Not Working','The tube light in Room A101 is not working.','Medium','Submitted','2026-07-31 09:00:00',NULL),
(2, 2, 2,'Water Leakage','Water is leaking from the bathroom pipe.','High','In Progress','2026-07-30 10:30:00',NULL),
(3, 3, 4,'Wi-Fi Not Working','No internet connection in the room.','Medium','Assigned','2026-07-29 02:15:00',NULL),
(4, 4, 6,'Broken Study Table','The study table is damaged and needs replacement.','Low','Resolved','2026-07-25 11:45:00','2026-07-27 03:30:00'),
(5, 5, 9,'Room Cleaning Required','The room has not been cleaned for several days.','Low','Closed','2026-07-20 08:20:00','2026-07-21 12:00:00'),
(1, 6, 1,'No Water Supply','There is no water supply since morning.','Emergency','Verified','2026-07-31 07:30:00',NULL),
(2, 7, 2,'Unauthorized Person Entered','An unknown person entered the hostel without permission.','High','Assigned','2026-07-30 09:45:00',NULL),
(3, 8, 4,'Air Conditioner Not Cooling','The AC is running but not cooling properly.','Medium','Submitted','2026-07-28 01:10:00',NULL),
(4, 9, 6,'Refrigerator Not Working','The shared refrigerator has stopped working.','High','In Progress','2026-07-29 04:20:00',NULL),
(5, 10, 9,'Window Lock Damaged','The window lock is broken and needs repair.','Medium','Submitted','2026-07-31 10:15:00',NULL);
CREATE TABLE MaintenanceStaff (
    StaffID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    Department VARCHAR(50),
    Skill VARCHAR(50),
    FOREIGN KEY (UserID)
	REFERENCES Users(UserID)
);
INSERT INTO MaintenanceStaff (UserID, Department, Skill)
VALUES
(3, 'Electrical Maintenance', 'Electrical Wiring'),
(4, 'Plumbing', 'Pipe Repair');
CREATE TABLE ComplaintAssignment (
    AssignmentID INT AUTO_INCREMENT PRIMARY KEY,
    ComplaintID INT,
    StaffID INT,
    AssignedBy INT,
    AssignedDate DATETIME,
    FOREIGN KEY (ComplaintID)
	REFERENCES Complaint(ComplaintID),
    FOREIGN KEY (StaffID)
	REFERENCES MaintenanceStaff(StaffID),
    FOREIGN KEY (AssignedBy)
	REFERENCES Users(UserID)
);
INSERT INTO ComplaintAssignment
(ComplaintID, StaffID, AssignedBy, AssignedDate)
VALUES
(1, 1, 3, '2026-07-31 09:00:00'),
(2, 2, 3, '2026-07-31 10:15:00'),
(3, 1, 3, '2026-07-31 11:30:00'),
(4, 1, 3, '2026-07-31 01:45:00'),
(5, 2, 3, '2026-07-31 03:00:00');
CREATE TABLE ComplaintStatusHistory (
    HistoryID INT AUTO_INCREMENT PRIMARY KEY,
    ComplaintID INT,
    UpdatedBy INT,
    Status VARCHAR(50),
    Remarks TEXT,
    UpdateTime DATETIME,
    FOREIGN KEY (ComplaintID)
	REFERENCES Complaint(ComplaintID),
    FOREIGN KEY (UpdatedBy)
	REFERENCES Users(UserID)
);
INSERT INTO ComplaintStatusHistory
(ComplaintID, UpdatedBy, Status, Remarks, UpdateTime)
VALUES
(1, 3, 'Submitted', 'Complaint received from student.', '2026-07-31 08:30:00'),
(1, 3, 'Verified', 'Complaint verified by hostel admin.', '2026-07-31 09:00:00'),
(1, 4, 'Assigned', 'Assigned to electrician.', '2026-07-31 09:15:00'),
(1, 4, 'In Progress', 'Repair work has started.', '2026-07-31 10:00:00'),
(1, 4, 'Resolved', 'Electrical issue fixed successfully.', '2026-07-31 11:30:00'),
(1, 3, 'Closed', 'Student confirmed the issue is resolved.', '2026-07-31 12:00:00'),
(2, 3, 'Submitted', 'Water leakage complaint received.', '2026-07-31 08:45:00'),
(2, 3, 'Verified', 'Complaint verified.', '2026-07-31 09:20:00'),
(2, 5, 'Assigned', 'Assigned to plumber.', '2026-07-31 09:45:00'),
(2, 5, 'In Progress', 'Leakage repair started.', '2026-07-31 10:30:00');
CREATE TABLE Feedback (
    FeedbackID INT AUTO_INCREMENT PRIMARY KEY,
    ComplaintID INT,
    StudentID INT,
    Rating INT,
    Comment TEXT,
    FeedbackDate DATETIME,
    FOREIGN KEY (ComplaintID)
	REFERENCES Complaint(ComplaintID),
    FOREIGN KEY (StudentID)
	REFERENCES Student(StudentID)
);
INSERT INTO Feedback
(ComplaintID, StudentID, Rating, Comment, FeedbackDate)
VALUES
(1, 1, 5, 'The electrical issue was resolved quickly. Excellent service.', '2026-07-31 12:30:00'),
(2, 2, 4, 'The plumbing problem was fixed, but it took longer than expected.', '2026-07-31 02:15:00'),
(3, 3, 5, 'Internet connection is working perfectly now. Thank you.', '2026-07-30 11:45:00'),
(4, 4, 3, 'The furniture was repaired, but it still needs some improvement.', '2026-07-29 04:20:00'),
(5, 5, 5, 'The room was cleaned properly. Very satisfied.', '2026-07-28 10:00:00');
CREATE TABLE Notification (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    Message TEXT,
    Status ENUM('Read','Unread'),
    SentDate DATETIME,
    FOREIGN KEY (UserID)
	REFERENCES Users(UserID)
);
INSERT INTO Notification
(UserID, Message, Status, SentDate)
VALUES
(1, 'Your complaint has been submitted successfully.', 'Read', '2026-07-31 09:00:00'),
(2, 'Your complaint has been assigned to a maintenance staff member.', 'Unread', '2026-07-31 10:15:00'),
(3, 'A new maintenance task has been assigned to you.', 'Read', '2026-07-31 10:30:00'),
(4, 'Please inspect Room A102 before completing the repair.', 'Unread', '2026-07-31 11:00:00'),
(5, 'A new complaint has been submitted and requires your approval.', 'Read', '2026-07-31 11:30:00'),
(1, 'Your complaint status has been updated to Resolved.', 'Unread', '2026-07-31 12:00:00'),
(2, 'Please provide feedback for your completed complaint.', 'Unread', '2026-07-31 12:30:00'),
(3, 'Reminder: Complete your assigned maintenance task today.', 'Read', '2026-07-31 01:00:00'),
(4, 'Your maintenance report has been received successfully.', 'Read', '2026-07-31 02:00:00'),
(5, 'System maintenance is scheduled for tonight at 10:00 PM.', 'Unread', '2026-07-31 03:00:00');

