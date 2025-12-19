<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;

abstract class MigrationStep
{
    public const MIGRATION_VARIABLE_FORMAT = '@MIGRATION_%s_IS_ACTIVE';
    public const INSTALL_ENVIRONMENT_VARIABLE = 'SHOPWARE_INSTALL';

    /**
     * get creation timestamp
     */
    abstract public function getCreationTimestamp(): int;

    /**
     * update non-destructive changes
     */
    abstract public function update(Connection $connection): void;

    /**
     * update destructive changes
     */
    abstract public function updateDestructive(Connection $connection): void;

    public function removeTrigger(Connection $connection, string $name): void
    {
        try {
            $connection->executeUpdate(sprintf('DROP TRIGGER IF EXISTS %s', $name));
        } catch (Exception $e) {
        }
    }

    public function isInstallation(): bool
    {
        return (bool) EnvironmentHelper::getVariable(self::INSTALL_ENVIRONMENT_VARIABLE, false);
    }

    protected function addForwardTrigger(Connection $connection, string $name, string $table, string $time, string $event, string $statements): void
    {
        $this->addTrigger($connection, $name, $table, $time, $event, $statements, 'IS NULL');
    }

    protected function addBackwardTrigger(Connection $connection, string $name, string $table, string $time, string $event, string $statements): void
    {
    }

    protected function addTrigger(Connection $connection, string $name, string $table, string $time, string $event, string $statements, string $condition): void
    {
        $query = sprintf(
            'CREATE TRIGGER %s
            %s %s ON `%s` FOR EACH ROW
            thisTrigger: BEGIN
                IF (%s %s)
                THEN
                    LEAVE thisTrigger;
                END IF;

                %s;
            END;
            ',
            $name,
            $time,
            $event,
            $table,
            sprintf(self::MIGRATION_VARIABLE_FORMAT, $this->getCreationTimestamp()),
            $condition,
            $statements
        );
        $connection->executeStatement($query);
    }

    /**
     * @param mixed[] $params
     */
    protected function createTrigger(Connection $connection, string $query, array $params = []): void
    {
        $blueGreenDeployment = EnvironmentHelper::getVariable('BLUE_GREEN_DEPLOYMENT', false);
        if ((int) $blueGreenDeployment === 0) {
            return;
        }

        $connection->executeStatement($query, $params);
    }

    protected function columnExists(Connection $connection, string $table, string $column): bool
    {
        $exists = $connection->fetchOne(
            'SHOW COLUMNS FROM ' . $table . ' WHERE `Field` LIKE :column',
            ['column' => $column]
        );

        return !empty($exists);
    }
}
