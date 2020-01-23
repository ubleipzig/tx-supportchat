<?php

########################################################################
# Extension Manager/Repository config file for ext "supportchat".
#
# Auto generated 17-02-2014 23:59
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Support Chat',
	'description' => 'A Support Chat for Typo3',
	'category' => 'misc',
	'shy' => 0,
	'version' => '2.0.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Georg Schoenweger',
	'author_email' => 'Georg.Schoenweger@gmail.com',
	'author_company' => 'www.profi-web.it',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-7.6.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
    'autoload' =>
        array(
            'psr-4' =>
                array(
                    'Ubl\\Supportchat\\' => 'Classes',
                ),
        ),
    'autoload-dev' =>
        array(
            'psr-4' =>
                array(
                    'Ubl\\Supportchat\\Tests' => 'Tests',
                ),
        ),
);

