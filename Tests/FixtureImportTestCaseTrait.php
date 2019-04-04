<?php

namespace PhagResponsiveContentInjector\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

trait FixtureImportTestCaseTrait
{
    /**
     * @return Connection|mixed
     */
    private function getConnection(): Connection
    {
        return Shopware()->Container()->get('dbal_connection');
    }

    /**
     * @param string $file
     */
    public function importFixtures(string $file)
    {
        try {
            $this->getConnection()->executeQuery(file_get_contents($file));
        } catch (DBALException $exception) {
            throw new \RuntimeException('Meh, issue with fixture import.');
        }
    }
}
