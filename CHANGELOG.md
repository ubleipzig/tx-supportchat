# Change Log

## [v2.7.0](https://github.com/ubleipzig/tx-supportchat/tree/2.6.9)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.8...2.7.0)

**Fixes**
* fixin' deprecated @inject annotation cmp. [82869](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.0/Feature-82869-ReplaceInjectWithTYPO3CMSExtbaseAnnotationInject.html)
* fixin' deprecated @validate annotation cmp. [83167](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.3/Deprecation-83167-ReplaceValidateWithTYPO3CMSExtbaseAnnotationValidate.html)
* fixin' deprecated _contentObjectRenderer_ replaced by class _TYPO3\CMS\Core\Service\MarkerBasedTemplateService_ cmp. [80527](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/8.7/Deprecation-80527-Marker-relatedMethodsInContentObjectRenderer.html) concerning following methods:
  * getSubpart()
  * substituteMarkerArrayCached()
* fixin' deprecated _siteRelPath_ to _PathUtility_ class cmp. [82899](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.0/Deprecation-82899-ExtensionManagementUtilityMethods.html)
* fixin' deprecated _userTS_ to _getTSConfig_ method cmp. [84984](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.3/Deprecation-84984-ProtectedUserTSconfigPropertiesInBackendUserAuthentication.html) 
* refactoring scheduler tasks _cleanupCommand_ to Symfony console command [85977](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.4/Deprecation-85977-ExtbaseCommandControllersAndCliAnnotation.html)
  * splits and creates two commands: _supportchat:cleanupChats_ and _supportchat:cleanupLogs_  
* adapts table names at command line clean up scripts to domain model scheme 
* introduces PSR-7 _ResponseInterface_ for AJAX requests

## [v2.6.8](https://github.com/ubleipzig/tx-supportchat/tree/2.6.8)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.7...2.6.8)

**Patches**
* adds [GDPR](https://en.wikipedia.org/wiki/General_Data_Protection_Regulation) info text to frontend chat window at start

## [v2.6.7](https://github.com/ubleipzig/tx-supportchat/tree/2.6.7)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.6...2.6.7)

**Fixes**
* fixes initializing extension configuration by new class _\TYPO3\CMS\Core\Configuration\ExtensionConfiguration_

## [v2.6.6](https://github.com/ubleipzig/tx-supportchat/tree/2.6.6)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.5...2.6.6)

**Fixes**
* fixes displaying other logged-in backend user below tab Options
  
## [v2.6.5](https://github.com/ubleipzig/tx-supportchat/tree/2.6.5)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.4...2.6.5)

**Fixes**
* fixes scrolling of text messages at chat window to latest
  * creates _this.delay_ to define, keep and unify frequency of scroll request  
  * fixes id for scrolling to last message if client send one

## [v2.6.4](https://github.com/ubleipzig/tx-supportchat/tree/2.6.4)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.3...2.6.4)

**Fixes**
* fixes status of typing at both direction from client to backend and vice versa
* fixes position of typing status buttons at frontend and backend chat

## [v2.6.3](https://github.com/ubleipzig/tx-supportchat/tree/2.6.3)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.2...2.6.3)

**Fixes**
* fixes _autofocus_ of backend chat textarea field switching randomly between chat windows 

## [v2.6.2](https://github.com/ubleipzig/tx-supportchat/tree/2.6.2)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.1...2.6.2)

**Minor changes**
* adds LICENSE text 
* cleaning up description of _hooks_ at [README.md](README.md) text. Hooks are already removed before

## [v2.6.1](https://github.com/ubleipzig/tx-supportchat/tree/2.6.1)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.6.0...2.6.1)

**Implemented enhancement**
* anonymize ip addresses of clients and hosts

## [v2.6.0](https://github.com/ubleipzig/tx-supportchat/tree/2.6.0)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.5.0...2.6.0)

**Implemented enhancement**
* adds scheduler task
  * **Supportchat Cleanup: cleanupChatMessages** to removes outdated chat messages from table: `tx_supportchat_messages`
  * **Supportchat Cleanup: cleanupChatLogs** to removes outdated chat logs from table: `tx_supportchat_logs`

## [v2.5.0](https://github.com/ubleipzig/tx-supportchat/tree/2.5.0)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.4.0...2.5.0)

**Refactoring**
* replaces DatabaseMapper w/ DBAL Doctrine 
  * implementing Domain/Model-Repository model
  * renames all databases to `tx_supportchat_domain_model_*` and updates translation for databases fields
* removes JavaScript Mootools library and dependent scripts from backend
  * replaces it by Vanilla Javascript
  * replaces XML of response by JSON
* partly redesign of backend chat windows

**Fixes**
* fixes minor error at _SupportchatBackendAlert.js_
* replaces several icons by *.svg

## [v2.4.0](https://github.com/ubleipzig/tx-supportchat/tree/2.4.0)

[Full Changelog](https://github.com/ubleipzig/tx-supportchat/compare/2.3.2...2.4.0)

**Support**
* removes support for typo3 minor than version 7.6 and adds support for version 8

**Fixes**
* adjusts /Configuration/TCA models due to deprecation warnings

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
