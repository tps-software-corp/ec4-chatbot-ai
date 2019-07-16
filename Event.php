<?php

namespace Plugin\TPSChatbotAI;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'index.twig' => 'onDefaultFrameReady',
            'Product/list.twig' => 'onDefaultFrameReady',
            'Product/detail.twig' => 'onDefaultFrameReady',
        ];
    }

    public function onDefaultFrameReady(TemplateEvent $event)
    {
        $event->addSnippet('@TPSChatbotAI/default/index.twig');
    }
}
