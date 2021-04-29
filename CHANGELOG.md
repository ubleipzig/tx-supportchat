# Change Log

## [v2.3.2](https://github.com/ubleipzig/tx-supportchat/tree/2.3.2)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.3.1...2.3.2)

**Fixes**
* layout fix for enable scrolling chat window and hide outer scrolling panel

## [v2.3.1](https://github.com/ubleipzig/tx-supportchat/tree/2.3.1)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.3.0...2.3.1)

**Fixes**
* layout fixes at css for frontend and backend

## [v2.3.0](https://github.com/ubleipzig/tx-supportchat/tree/2.3.0)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.2.0...2.3.0)

**Refactoring**
* removes ```prototype.js``` and Mootools javascript libraries from frontend popup chat window
  * using pure javascript only
* getAll() POST messages request returns json instead of xml 
  * adds method ```processPostRequest``` to evaluate response
  
**Fixes**
* css failure of class ```supportchatbox``` setting ```height``` to get scrolling chat box
* adds label for select box of notifications sounds

## [v2.2.0](https://github.com/ubleipzig/tx-supportchat/tree/2.2.0)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.1.1...2.2.0)

**Refactoring**
* improves styling of front- and backend view of chat
  * adapts accessibility standards
* refactoring of implemented hooks
* encapsulate global $BE_USERS at single method 

**Fixes**
* German and English languages files
* comments at fluid templates
* fixes logging of extension and added new boolean setting _enableLogging_

## [v2.1.1](https://github.com/ubleipzig/tx-supportchat/tree/2.1.1)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.1.0...2.1.1)

**Fixes**
* adding plugin at backend w/ registerPlugin at _Configuration/TCA/Overrides/tt_content.php_ 


## [v2.1.0](https://github.com/ubleipzig/tx-supportchat/tree/2.1.0)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.0.2...2.1.0)

**New requirements**

* supports Typo3 v7 only
* PHP 7 at minimum

**Implemented enhancements:**

* backend user can select alert sound saved at cache

**Refactoring**

* replaces short syntax for arrays
* adjusts directory structure of Resources to common practise of Typo3 
* removes parallel javascripts libraries for SupportChatIsOnline.js
  * uses now prototype only as (transition) library for rewriting script later
  * removes addProttype and usePrototype at Configuration/TypoScript/setup.txt

**Fixes**

* adds html5 audio element for alert sound and removes flash artefacts
* fixes several image paths for e.g. online/offline images 
* fixes false call of method Classes/Ajax/Frontendlistener.php cmd -> checkIfOnline

## [v2.0.2](https://github.com/ubleipzig/tx-supportchat/tree/2.0.2)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.0.1...2.0.2)

**Fixes**

* removes duplicated entry of _addStaticFile_ supportchat and defines it at newly created Configuration/TCA/Overrides/sys_template.php  
* removes Configuration/TCA/Overrides/pages.php

## [v2.0.1](https://github.com/ubleipzig/tx-supportchat/tree/2.0.1)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.0.0...2.0.1)

**Fixes**

* changes JavaScript libraries include from ExtensionManagementUtility::extRelPath to ::siteRelPath due to resolving issues of instances with subpath
    * ExtensionManagementUtility::extRelPath will be deprecated w/ [TYPO3 v8.4](https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/8.4/Deprecation-78193-ExtensionManagementUtilityextRelPath.html)
* rename file Configuration/TypoScript/constants.txt correctly

## [v2.0.0](https://github.com/ubleipzig/tx-supportchat/tree/2.0.0)

**Refactoring**

* updates extension to typo3 v7
* adapts namespaces to make compatible with Typo3 v7
* replaces mod1 module by Classes/Controller/SupportChatModuleController
* moves translation to Resources/Private/Language
* refactoring translation from *.xml to *.xlf
* moves assets to Resources
* replaces pi1 frontend to Classes/Controller/SupportChatController
* moves ajaxResponse.php to Classes/Ajax/FrontendListener
* compiles definitions for tables at Configuration/TCA

**Implemented enhancements:**

* rewrites classes for PSR2 compatibility (ongoing)
