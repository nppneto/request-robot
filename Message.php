<?php

class Message
{
    public function __construct() {}

    public static function displayMessage($message, $display = 'info')
    {
        $show = null;

        switch ($display) {
            case 'error':
                $show = '[ERROR]';
                break;
            case 'warning':
                $show = '[WARNING]';
            default:
                $show = '[INFO]';
                break;
        }

        echo sprintf('%s %s: %s', date('Y-m-d H:i:s'), $show, $message) . "\n";
    }
}