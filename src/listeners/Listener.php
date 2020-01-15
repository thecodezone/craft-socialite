<?php


namespace CodeZone\socialite\listeners;

use yii\base\Event;

abstract class Listener
{
    public $class;
    public $event;

    public static function listen($class, $event) {
        Event::on(
            $class,
            $event,
            function (Event $event) {
                (new static)->handle($event);
            }
        );
    }

    abstract function handle(Event $event);
}