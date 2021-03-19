<?php
/**
 * Defines AJAX backend (module) routes only!
 */
return [
    'chat_response' => [
        'path' => '/supportchat/chat-response',
        'target' => \Ubl\Supportchat\Controller\SupportChatModuleController::class . '::getChatAction'
    ],
    'alert_sound' => [
        'path' => '/supportchat/alert-sound',
        'target' => \Ubl\Supportchat\Controller\SupportChatModuleController::class . '::setAlertSoundAction'
    ]
];