<?php

namespace Plugin\TPSChatbotAI;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;

class Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'index.twig' => 'onDefaultFrameReady'
        ];
    }

    public function onDefaultFrameReady(TemplateEvent $event)
    {
        $event->addSnippet('@TPSChatbotAI/default/index.twig');
    }
}
