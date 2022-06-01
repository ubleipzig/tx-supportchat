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

$EM_CONF[$_EXTKEY] = [
	'title' => 'Support Chat',
	'description' => 'A Support Chat for Typo3',
	'category' => 'misc',
	'shy' => 0,
	'version' => '2.6.0',
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
	'author' => 'Leipzig University Library',
	'author_email' => 'info@ub.uni-leipzig.de',
	'author_company' => 'www.ub.uni-leipzig.de',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => [
		'depends' => [
			'typo3' => '8.7.0-9.5.99',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
    'autoload' =>
        [
            'psr-4' =>
                [
                    'Ubl\\Supportchat\\' => 'Classes',
                ]
        ],
    'autoload-dev' =>
        [
            'psr-4' =>
                [
                    'Ubl\\Supportchat\\Tests' => 'Tests',
                ]
        ],
];

