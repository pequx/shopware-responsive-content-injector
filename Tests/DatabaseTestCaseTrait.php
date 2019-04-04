<?php

namespace PhagResponsiveContentInjector\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;

trait DatabaseTestCaseTrait
{
    /**
     * @return Connection|mixed
     */
    private function getConnection(): Connection
    {
        return Shopware()->Container()->get('dbal_connection');
    }

    protected function startTransactionBefore()
    {
        $this->getConnection()->beginTransaction();
    }

    protected function rollbackTransactionAfter()
    {
        try {
            $this->getConnection()->rollBack();
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Meh, Trait rollback failed.');
        }
    }
}
