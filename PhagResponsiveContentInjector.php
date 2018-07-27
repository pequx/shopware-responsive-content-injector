<?php

namespace PhagResponsiveContentInjector;

use Doctrine\ORM\Tools\ToolsException;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use PhagResponsiveContentInjector\Models\PhagResponsiveContentInjector as Model;

/**
 * Shopware-Plugin PhagResponsiveContentInjector.
 */
class PhagResponsiveContentInjector extends Plugin
{
    const NAME = 'phagResponsiveContentInjector';

    /**
     * Adds the widget to the database and creates the database schema.
     *
     * @param Plugin\Context\InstallContext $installContext
     */
    public function install(Plugin\Context\InstallContext $installContext)
    {
        parent::install($installContext);
        try {
            $this->createSchema();
        } catch (ToolsException $exception) {
            return;
        }
    }

    /**
     * Remove widget and remove database schema.
     *
     * @param Plugin\Context\UninstallContext $uninstallContext
     */
    public function uninstall(Plugin\Context\UninstallContext $uninstallContext)
    {
        parent::uninstall($uninstallContext);

        $this->removeSchema();
    }

    /**
    * @param ContainerBuilder $container
    */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('phag_responsive_content_injector.plugin_dir', $this->getPath());
        parent::build($container);
    }

    /**
     * creates database tables on base of doctrine models
     * @throws ToolsException
     */
    private function createSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = [
            $this->container->get('models')->getClassMetadata(Model::class)
        ];
        $tool->createSchema($classes);
    }

    /**
     * Removes db schema.
     */
    private function removeSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $classes = [
            $this->container->get('models')->getClassMetadata(Model::class)
        ];
        $tool->dropSchema($classes);
    }
}
