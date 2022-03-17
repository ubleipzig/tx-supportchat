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

## Hooks

Supportchat extension includes a couple of hooks of former plugin provider. These hooks has been adjusted but not tested.

### Library/Chat overwriteCreateChat

Use this to overwrite settings data of created chat.

### Library/Chat postProcessPostedMessage

Use this to manipulate posted messages after entry at the chat box

### Library/ChatHelper checkChatIsOnline

Use this to implement a personal method to check if chat is online

Method has to be of type _static_.

### Library/ChatMarket additionalInfo

Use this to add additional infos at backend chat box header



