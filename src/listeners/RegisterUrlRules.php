<?php


namespace CodeZone\socialite\listeners;


use yii\base\Event;

class RegisterUrlRules extends Listener
{

    function handle(Event $event)
    {
        $event->rules['socialite/<slug:{slug}>/auth'] = 'socialite/auth/handshake';
    }
}