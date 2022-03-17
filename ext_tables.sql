#
# Table structure for table 'tx_supportchat_domain_model_chats'
#
CREATE TABLE tx_supportchat_domain_model_chats (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	be_user blob NOT NULL,
	session tinytext NOT NULL,
	active tinyint(3) DEFAULT '0' NOT NULL,
	status tinytext NOT NULL,
	surfer_ip tinytext NOT NULL,
	type_status tinytext DEFAULT '' NOT NULL,
	language_uid tinyint(11) DEFAULT '0' NOT NULL,

    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_supportchat_domain_model_messages'
#
CREATE TABLE tx_supportchat_domain_model_messages (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	code tinytext NOT NULL,
	chat_pid int(11) unsigned NOT NULL,
	name tinytext NOT NULL,
	message text NOT NULL,

    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT 0 NOT NULL,
    hidden tinyint(4) unsigned DEFAULT 0 NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_supportchat_domain_model_logs'
#
CREATE TABLE tx_supportchat_domain_model_logs (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	message text NOT NULL,

    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT 0 NOT NULL,
    hidden tinyint(4) unsigned DEFAULT 0 NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
