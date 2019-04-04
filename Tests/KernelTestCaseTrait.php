<?php

namespace PhagResponsiveContentInjector\Tests;

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Repository;
use Doctrine\DBAL\ConnectionException;
use Shopware\Components\DependencyInjection\Container;
use Doctrine\DBAL\DBALException;


trait KernelTestCaseTrait
{
    /**
     * @var Kernel
     */
    private static $kernel;

    /**
     * @return Kernel
     */
    protected static function getKernel(): Kernel
    {
        $debug = true;
        $environment = getenv('SHOPWARE_ENV') ?: 'testing';
        try {
            /** @var Kernel $kernel */
            $kernel = new self($environment, $debug);
            return $kernel;
        } catch (\Exception $exception) {
            throw new \RuntimeException('Meh, cannot get kernel.');
        }
    }

    /**
     * @return Container
     */
    protected static function getContainer(): Container
    {
        if (!self::$kernel) { self::bootKernelBefore(); }
        return self::$kernel->getContainer();
    }

    /**
     * @param string $query
     */
    private function executeSql(string $query)
    {
        try {
            self::getContainer()->get('dbal_connection')->exec($query);
        } catch (DBALException $exception) {
            throw new \RuntimeException('Meh, query not executed.');
        }
    }

    /**
     * @before
     */
    protected static function bootKernelBefore()
    {
        if (self::$kernel instanceof Kernel) { return; }
        self::$kernel = self::getKernel();
        try {
            self::$kernel->boot();
        } catch (\Exception $exception) {
            throw new \RuntimeException('Meh, kernel not booted.');
        }
        self::$kernel->getContainer()->get('dbal_connection')->beginTransaction();
        /** @var Repository $repository */
        $repository = Shopware()->Container()->get('models')
            ->getRepository(Shop::class);
        $shop = $repository->getActiveDefault();
        try {
            $shop->registerResources();
        } catch (\Exception $exception) {
            throw new \RuntimeException('Meh, kernel resources not registered.');
        }

        //@todo: check if we need to load additional resources
    }

    /**
     * @after
     */
    protected static function destroyKernelAfter()
    {
        try {
            self::$kernel->getContainer()->get('dbal_connection')->rollBack();
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Meh, db transaction rollback failed.');
        }
        self::$kernel = null;
        gc_collect_cycles(); //garbage collection.
        Shopware(new EmptyShopwareApplication());
    }
}

class EmptyShopwareApplication
{
    public function __call(string $name)
    {
        throw new \RuntimeException('Restricted to call ' . $name . ' because you should not have a test kernel in this test case.');
    }
}
