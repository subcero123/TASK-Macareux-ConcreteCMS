<?php

namespace Concrete\Package\PopulationImporter\Src;

use Concrete\Core\Database\Connection\Connection;
use Doctrine\DBAL\Schema\Schema;

class SchemaManager
{
    /**
     * @var Connection
     */
    protected $connection;


    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createTables()
    {
        $schema = new Schema();
        
        if (!$schema->hasTable('population_data')) {
            $table = $schema->createTable('population_data');
            
            $table->addColumn('id', 'integer', [
                'unsigned' => true,
                'autoincrement' => true
            ]);
            $table->addColumn('prefecture', 'string', [
                'length' => 50,
                'notnull' => true
            ]);
            $table->addColumn('year', 'integer', [
                'notnull' => true
            ]);
            $table->addColumn('population', 'bigint', [
                'notnull' => true
            ]);
            $table->addColumn('prefecture_code', 'string', [
                'length' => 10,
                'notnull' => false
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => true
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['prefecture', 'year'], 'idx_prefecture_year');
            $table->addIndex(['prefecture'], 'idx_prefecture');
            $table->addIndex(['year'], 'idx_year');

            // Set table options for UTF-8
            $table->addOption('charset', 'utf8mb4');
            $table->addOption('collate', 'utf8mb4_unicode_ci');
            $table->addOption('engine', 'InnoDB');

        
            $platform = $this->connection->getDatabasePlatform();
            $queries = $schema->toSql($platform);
            
            foreach ($queries as $query) {
                $this->connection->executeQuery($query);
            }
        }
    }

    public function dropTables()
    {
        $schema = new Schema();
        
        if ($schema->hasTable('population_data')) {
            $schema->dropTable('population_data');
            
            // Execute schema
            $platform = $this->connection->getDatabasePlatform();
            $queries = $schema->toSql($platform);
            
            foreach ($queries as $query) {
                $this->connection->executeQuery($query);
            }
        }
    }
}
