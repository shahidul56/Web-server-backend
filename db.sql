CREATE TABLE weeki.users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  name VARCHAR(50) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  registration_id TEXT DEFAULT NULL,
  api TEXT NOT NULL,
  created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  disabled TINYINT(1) DEFAULT 0,
  status VARCHAR(130) DEFAULT 'Just another user',
  icon TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX id (id)
);

CREATE TABLE weeki.groups (
  group_id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  icon TEXT DEFAULT NULL,
  description VARCHAR(130) NOT NULL,
  creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id)
);

CREATE TABLE weeki.messages (
  message_id INT(11) NOT NULL AUTO_INCREMENT,
  receiver_id INT(11) NOT NULL,
  sender_id INT(11) NOT NULL,
  msg_type INT(11) NOT NULL DEFAULT 0,
  message VARCHAR(255) NOT NULL,
  created_At DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  UNIQUE INDEX id (message_id),
  CONSTRAINT FK_messages_users_id FOREIGN KEY (receiver_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.messages_receipt (
  message_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  is_delivered INT(11) NOT NULL DEFAULT 0,
  CONSTRAINT FK_messages_receipt_messages_id FOREIGN KEY (message_id)
    REFERENCES weeki.messages(message_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT FK_messages_receipt_users_id FOREIGN KEY (user_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.group_messages (
  message_id INT(11) NOT NULL AUTO_INCREMENT,
  group_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  msg_type INT(11) NOT NULL DEFAULT 0,
  message VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  PRIMARY KEY (message_id),
  CONSTRAINT FK_group_messages_groups_group_id FOREIGN KEY (group_id)
    REFERENCES weeki.groups(group_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT FK_group_messages_users_id FOREIGN KEY (user_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.group_members (
  group_id INT(11) DEFAULT NULL,
  user_id INT(11) DEFAULT NULL,
  CONSTRAINT FK_group_members_groups_group_id FOREIGN KEY (group_id)
    REFERENCES weeki.groups(group_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT FK_group_members_users_id FOREIGN KEY (user_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.group_receipt (
  message_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  is_delivered INT(11) NOT NULL DEFAULT 0,
  INDEX FK_group_stats_groups_group_id (user_id),
  CONSTRAINT FK_group_stats_group_messages_message_id FOREIGN KEY (message_id)
    REFERENCES weeki.group_messages(message_id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE FUNCTION weeki.CreateGroup(GroupName VARCHAR(50), GroupIcon TEXT, GroupDescription VARCHAR(130), GroupCreator INT)
  RETURNS int(11)
  DETERMINISTIC
BEGIN
  DECLARE groupID INT;
  INSERT INTO groups (name, icon, description) VALUES (GroupName, GroupIcon, GroupDescription);
  SET groupID = LAST_INSERT_ID();
  INSERT INTO group_members VALUES (groupID, GroupCreator);
  RETURN groupID;
END;

CREATE FUNCTION weeki.AddMessage(r VARCHAR(255), s INT, t INT, m VARCHAR(255), creation DATETIME)
  RETURNS int(11)
  DETERMINISTIC
BEGIN
        DECLARE lastID INT;
        DECLARE receiver INT;
        SELECT id INTO receiver from users WHERE username=r;
        INSERT INTO messages (receiver_id, sender_id, msg_type, message, created_At) values(receiver, s, t, m, creation);
        SET lastID = LAST_INSERT_ID();
        INSERT INTO messages_receipt (message_id, user_id, is_delivered) VALUES (lastID, receiver, 0);
        RETURN lastID;
END;

CREATE FUNCTION weeki.AddGroupMessage(gid INT, s INT, t INT, m VARCHAR(255), creation DATETIME)
  RETURNS int(11)
  DETERMINISTIC
BEGIN
  DECLARE lastID INT; DECLARE rowCount INT;
  SELECT COUNT(*) INTO rowCount FROM group_members WHERE user_id = s AND group_id = gid;
  IF rowCount = 1 THEN
  INSERT INTO group_messages (group_id, user_id, msg_type, message, created_at) VALUES (gid, s, t, m, creation);
  SET lastID = LAST_INSERT_ID();
  INSERT INTO group_receipt (message_id, user_id, is_delivered) SELECT gm.message_id, gmembers.user_id, 0 FROM
  group_messages gm LEFT JOIN group_members gmembers ON gmembers.group_id = gm.group_id WHERE gm.group_id = gid AND gm.message_id = lastID AND NOT gmembers.user_id = s;
  RETURN lastID;
  ELSE
    return 0;
  END IF;
END;

CREATE DEFINER = 'root'@'localhost'
FUNCTION weeki.AddGroupMember(GroupID INT, MemberName VARCHAR(255), Username INT)
  RETURNS int(11)
BEGIN
  INSERT INTO group_members VALUES (GroupID, MemberName);
  RETURN 1;
END;

CREATE TABLE weeki.users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  name VARCHAR(50) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  registration_id TEXT DEFAULT NULL,
  api TEXT NOT NULL,
  created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  disabled TINYINT(1) DEFAULT 0,
  status VARCHAR(130) DEFAULT 'Just another user',
  icon TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX id (id)
);

CREATE TABLE weeki.groups (
  group_id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  icon TEXT DEFAULT NULL,
  description VARCHAR(130) NOT NULL,
  creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id)
);

CREATE TABLE weeki.messages (
  message_id INT(11) NOT NULL AUTO_INCREMENT,
  receiver_id INT(11) NOT NULL,
  sender_id INT(11) NOT NULL,
  msg_type INT(11) NOT NULL DEFAULT 0,
  message VARCHAR(255) NOT NULL,
  created_At DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  UNIQUE INDEX id (message_id),
  CONSTRAINT FK_messages_users_id FOREIGN KEY (receiver_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.messages_receipt (
  message_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  is_delivered INT(11) NOT NULL DEFAULT 0,
  CONSTRAINT FK_messages_receipt_messages_id FOREIGN KEY (message_id)
    REFERENCES weeki.messages(message_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT FK_messages_receipt_users_id FOREIGN KEY (user_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.group_messages (
  message_id INT(11) NOT NULL AUTO_INCREMENT,
  group_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  msg_type INT(11) NOT NULL DEFAULT 0,
  message VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  PRIMARY KEY (message_id),
  CONSTRAINT FK_group_messages_groups_group_id FOREIGN KEY (group_id)
    REFERENCES weeki.groups(group_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT FK_group_messages_users_id FOREIGN KEY (user_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.group_members (
  group_id INT(11) DEFAULT NULL,
  user_id INT(11) DEFAULT NULL,
  CONSTRAINT FK_group_members_groups_group_id FOREIGN KEY (group_id)
    REFERENCES weeki.groups(group_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT FK_group_members_users_id FOREIGN KEY (user_id)
    REFERENCES weeki.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE weeki.group_receipt (
  message_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  is_delivered INT(11) NOT NULL DEFAULT 0,
  INDEX FK_group_stats_groups_group_id (user_id),
  CONSTRAINT FK_group_stats_group_messages_message_id FOREIGN KEY (message_id)
    REFERENCES weeki.group_messages(message_id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE FUNCTION weeki.CreateGroup(GroupName VARCHAR(50), GroupIcon TEXT, GroupDescription VARCHAR(130), GroupCreator INT)
  RETURNS int(11)
  DETERMINISTIC
BEGIN
  DECLARE groupID INT;
  INSERT INTO groups (name, icon, description) VALUES (GroupName, GroupIcon, GroupDescription);
  SET groupID = LAST_INSERT_ID();
  INSERT INTO group_members VALUES (groupID, GroupCreator);
  RETURN groupID;
END;

CREATE FUNCTION weeki.AddMessage(r VARCHAR(255), s INT, t INT, m VARCHAR(255), creation DATETIME)
  RETURNS int(11)
  DETERMINISTIC
BEGIN
        DECLARE lastID INT;
        DECLARE receiver INT;
        SELECT id INTO receiver from users WHERE username=r;
        INSERT INTO messages (receiver_id, sender_id, msg_type, message, created_At) values(receiver, s, t, m, creation);
        SET lastID = LAST_INSERT_ID();
        INSERT INTO messages_receipt (message_id, user_id, is_delivered) VALUES (lastID, receiver, 0);
        RETURN lastID;
END;

CREATE FUNCTION weeki.AddGroupMessage(gid INT, s INT, t INT, m VARCHAR(255), creation DATETIME)
  RETURNS int(11)
  DETERMINISTIC
BEGIN
  DECLARE lastID INT; DECLARE rowCount INT;
  SELECT COUNT(*) INTO rowCount FROM group_members WHERE user_id = s AND group_id = gid;
  IF rowCount = 1 THEN
  INSERT INTO group_messages (group_id, user_id, msg_type, message, created_at) VALUES (gid, s, t, m, creation);
  SET lastID = LAST_INSERT_ID();
  INSERT INTO group_receipt (message_id, user_id, is_delivered) SELECT gm.message_id, gmembers.user_id, 0 FROM
  group_messages gm LEFT JOIN group_members gmembers ON gmembers.group_id = gm.group_id WHERE gm.group_id = gid AND gm.message_id = lastID AND NOT gmembers.user_id = s;
  RETURN lastID;
  ELSE
    return 0;
  END IF;
END;

CREATE DEFINER = 'root'@'localhost'
FUNCTION weeki.AddGroupMember(GroupID INT, MemberName VARCHAR(255), Username INT)
  RETURNS int(11)
BEGIN
  INSERT INTO group_members VALUES (GroupID, MemberName);
  RETURN 1;
END;