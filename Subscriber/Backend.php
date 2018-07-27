<?php

namespace PhagResponsiveContentInjector\Subscriber;

use Enlight\Event\SubscriberInterface;

class Backend implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
//            'Enlight_Controller_Action_PreDispatch_Backend_Blog' => 'onBeforeSaveBlogArticleAction', //more or less ;)
            'Enlight_Controller_Action_PreDispatch_Backend_Blog' => 'onPostDispatch',
//            'Shopware_Controllers_Backend_Blog::saveBlogArticleAction::before' => 'test',
            //@todo: media update event invalidates the cache over media service
        );
    }

    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var $controller \Enlight_Controller_Action */
        $controller = $args->get('subject');
        /** @var \Enlight_Controller_Request_Request $request */
        $request = $controller->Request();

        if (!$this->isSaveBlogArticleAction($request)) { return; }

        $controller->forward(
            'saveBlogArticle', 'phagResponsiveContentInjector', 'backend'
        );
    }

    /**
     * @param \Enlight_Controller_Request_Request $request
     * @return bool
     */
    protected function isSaveBlogArticleAction(\Enlight_Controller_Request_Request $request): bool
    {
        return $request->getControllerName() ==='Blog' && $request->getActionName() === 'saveBlogArticle';
    }

}
