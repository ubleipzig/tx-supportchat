# Change Log

## [v2.0.1](https://github.com/ubleipzig/tx-supportchat/tree/v2.0.1)

[Full Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/2.0.0...2.0.1)

**Fixes**

* changes JavaScript libraries include from ExtensionManagementUtility::extRelPath to ::siteRelPath due to resolving issues of instances with subpath
    * ExtensionManagementUtility::extRelPath will be deprecated w/ [TYPO3 v8.4](https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/8.4/Deprecation-78193-ExtensionManagementUtilityextRelPath.html)
* rename file Configuration/TypoScript/constants.txt correctly

## [v2.0.0](https://github.com/ubleipzig/tx-supportchat/tree/v2.0.0)

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
