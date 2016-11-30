#
# Table structure for table 'tx_snisupportchat_chats'
#
CREATE TABLE tx_snisupportchat_chats (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	be_user blob NOT NULL,
	session tinytext NOT NULL,
	active tinyint(3) DEFAULT '0' NOT NULL,
	status tinytext NOT NULL,	
	surfer_ip tinytext NOT NULL,
    language_uid tinyint(11) DEFAULT '0' NOT NULL,
	assume_to_be_user blob NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_snisupportchat_messages'
#
CREATE TABLE tx_snisupportchat_messages (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,

	code tinytext NOT NULL,
	from_supportler tinytext NOT NULL,
	to_supportler tinytext NOT NULL,
	
	chat_pid blob NOT NULL,
	name tinytext NOT NULL,
	message text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_snisupportchat_log'
#
CREATE TABLE tx_snisupportchat_log (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	message text NOT NULL,
		
	PRIMARY KEY (uid),
	KEY parent (pid)
);
