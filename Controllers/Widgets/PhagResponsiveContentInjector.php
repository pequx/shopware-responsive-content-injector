<?php

use PhagResponsiveContentInjector\Models\PhagResponsiveContentInjector;
use PhagResponsiveContentInjector\Models\Repository;
use Shopware\Models\Category\Category;

/**
 * Frontend controller
 */
class Shopware_Controllers_Widgets_PhagResponsiveContentInjector extends Enlight_Controller_Action
{
    /**
     * @var Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @var Repository
     */
    private $modelRepository;

    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    protected $elements;

    /**
     * @var array
     */
    protected $blog;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->templateManager = Shopware()->Template();
        $this->modelRepository = Shopware()->Models()->getRepository(PhagResponsiveContentInjector::class);
        $this->config = $this->modelRepository->getConfig();
    }

    /**
     * Blog detail action.
     *
     * @todo:
     * - consider extending original controller entity
     *   and iterate over the model instead within a separated controller
     * - check model event listeners and possible applications
     * - consider using preDispatch event instead
     */
    public function blogDetailAction()
    {
        $this->Request()->setModuleName('frontend');
        $this->Request()->setControllerName('blog');
        $this->Request()->setActionName('detail');
        $this->Request()->setDispatched(true);

        $parameters = $this->Request()->getParams();
        $assigns = $parameters['assigns'];
        $this->blog = $assigns['sArticle'];
        if (!$assigns || !$this->blog) { return false; }

        $isContent = $this->blogContentProcessor();
        $this->View()->loadTemplate('frontend/blog/detail.tpl');
        if (!$isContent) { return $this->View(); }

        $this->View()
            ->assign([
                'sBreadcrumb' => $assigns['sBreadcrumb'],
                'sArticle' => $this->blog,
                'rand' => $assigns['rand'],
            ]);

        return $this->View();
    }

    /**
     * Process the blog and injected the content.
     *
     * @return boolean
     */
    private function blogContentProcessor(): bool
    {
        $isRender = $this->modelRepository->checkRenderContent($this->blog); //execute if there is no need to re-render content
        if ($isRender) {
            $isAssociate = $this->modelRepository->associateContent(); //connects frontend to backend
            if (!$isAssociate) { return false; }
        }

        //@todo: figure out why js ins not collected (or instantiated?)

        $html = $this->modelRepository->renderContent();
        if (!$html) { return false; }
        $this->blog['description'] = $html;
        return true;
    }
}
