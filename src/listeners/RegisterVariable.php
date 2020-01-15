<?php


namespace CodeZone\socialite\listeners;


use CodeZone\socialite\variables\SocialiteVariable;
use yii\base\Event;

class RegisterVariable extends Listener
{

    function handle(Event $event)
    {
        $variable = $event->sender;
        $variable->set('socialite', SocialiteVariable::class);
    }
}