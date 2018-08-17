<?php

namespace PhagResponsiveContentInjector\Subscriber;

use Enlight\Event\SubscriberInterface;
use PhagResponsiveContentInjector\PhagResponsiveContentInjector;

class Frontend implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
//            'Enlight_Controller_Action_PostDispatch_Frontend_Blog' => 'onFrontendPostDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Blog' => 'onFrontendPostDispatch',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @throws \Enlight_Exception
     */
    public function onFrontendPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var $controller \Enlight_Controller_Action */
        $controller = $args->get('subject');
        $request = $controller->Request();
        $view = $controller->View();
        $assigns = $view->getAssign();

        $isBlogDetailAction = $request->getControllerName() === 'blog'
            && $request->getActionName() === 'detail';

        if (!$isBlogDetailAction) { return; }

        $controller->forward(
            'blogDetail', PhagResponsiveContentInjector::NAME, 'widgets', [
                'assigns' => $assigns,
            ]
        );
    }
}
