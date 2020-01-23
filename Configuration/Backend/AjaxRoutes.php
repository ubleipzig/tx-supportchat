<?php

return [
    'chat_response' => [
        'path' => '/supportchat/chat-response',
        'target' => \Ubl\Supportchat\Controller\SupportChatModuleController::class . '::getChatAction'
    ]
];