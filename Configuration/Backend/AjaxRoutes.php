<?php
/**
 * Defines AJAX backend (module) routes only!
 */
return [
    'chat_response' => [
        'path' => '/supportchat/chat-response',
        'target' => \Ubl\Supportchat\Controller\SupportChatModuleController::class . '::getChatAction'
    ]
];