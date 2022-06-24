# ubl/supportchat
Support chat for frontend users

This typo3 extension was created to manage support requests of library clients.

Library stuff answers to clients at typo3 backend module in single chat windows. 

## Requirements
* Typo3 > 8.7 < 9.5.99
* PHP >= 7.2

It's not tested with higher versions but codebase should be sufficient.

## Usage
This extension provides a plugin which has to be assigned to the designated page.

### Install plugin at TYPO3 backend

#### Enable Plugin

* Add a *New content element->Plugins->General Plugin*.
* Under Tab *Plugin* choose **Support Chat**.

#### Load settings at typo3 page

Go to your page where do you like include the chat.

* Create a new template e.g. ext: Support Chat
* Edit template and load typoscript settings **Support Chat (supportchat)** at tab _Contains_

## Command line tools

### Cleanup chat messages

There is a cleanup-command on typo3's commandline interface to remove chat messages from _tx_supportchat_messages_ table.

Parameters:

```
--days int (Default: 7) Amount of days to keep chat message.
```

### Cleanup chat logs

There is a cleanup-command on typo3's commandline interface to remove chat logs from _tx_supportchat_logs_ table.

Parameters:

```
--days int (Default: 30) Amount of days to keep chat logs.
```

#### Instruction for setting up at backend

Go to *Scheduler->Add Task*
* At *Class* choose **Extbase-CommandController-Task**
* At *Frequency* specify how often and in which period scheduler task should be run. (Seconds or cronjob settings required.)
* At select box of *CommandController Command* choose **Supportchat Cleanup: cleanupChatMessages**
* On next step save the task! This is important to display the form element for additional arguments of command line tool.
* Scroll down and specify the amount of **days** to keep the record since last login of user. Default are _7_ days.
* Save the task again!

For developing issue it is also possible to run the task on a terminal. Go to typo3 root folder and try:

```
/usr/bin/php typo3/cli_dispatch.phpsh extbase cleanup:cleanupchatmessages -days=7
```
