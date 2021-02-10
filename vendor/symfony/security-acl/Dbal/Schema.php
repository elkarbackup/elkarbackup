<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Acl\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema as BaseSchema;

/**
 * The schema used for the ACL system.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class Schema extends BaseSchema
{
    protected $options;

    /**
     * Constructor.
     *
     * @param array      $options    the names for tables
     * @param Connection $connection
     */
    public function __construct(array $options, Connection $connection = null)
    {
        $schemaConfig = null === $connection ? null : $connection->getSchemaManager()->createSchemaConfig();

        parent::__construct([], [], $schemaConfig);

        $this->options = $options;

        $this->addClassTable();
        $this->addSecurityIdentitiesTable();
        $this->addObjectIdentitiesTable();
        $this->addObjectIdentityAncestorsTable();
        $this->addEntryTable();
    }

    /**
     * Merges ACL schema with the given schema.
     */
    public function addToSchema(BaseSchema $schema)
    {
        foreach ($this->getTables() as $table) {
            $schema->_addTable($table);
        }

        foreach ($this->getSequences() as $sequence) {
            $schema->_addSequence($sequence);
        }
    }

    /**
     * Adds the class table to the schema.
     */
    protected function addClassTable()
    {
        $table = $this->createTable($this->options['class_table_name']);
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('class_type', 'string', ['length' => 200]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['class_type']);
    }

    /**
     * Adds the entry table to the schema.
     */
    protected function addEntryTable()
    {
        $table = $this->createTable($this->options['entry_table_name']);

        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('class_id', 'integer', ['unsigned' => true]);
        $table->addColumn('object_identity_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('field_name', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('ace_order', 'smallint', ['unsigned' => true]);
        $table->addColumn('security_identity_id', 'integer', ['unsigned' => true]);
        $table->addColumn('mask', 'integer');
        $table->addColumn('granting', 'boolean');
        $table->addColumn('granting_strategy', 'string', ['length' => 30]);
        $table->addColumn('audit_success', 'boolean');
        $table->addColumn('audit_failure', 'boolean');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['class_id', 'object_identity_id', 'field_name', 'ace_order']);
        $table->addIndex(['class_id', 'object_identity_id', 'security_identity_id']);

        $table->addForeignKeyConstraint($this->getTable($this->options['class_table_name']), ['class_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->getTable($this->options['oid_table_name']), ['object_identity_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->getTable($this->options['sid_table_name']), ['security_identity_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE']);
    }

    /**
     * Adds the object identity table to the schema.
     */
    protected function addObjectIdentitiesTable()
    {
        $table = $this->createTable($this->options['oid_table_name']);

        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('class_id', 'integer', ['unsigned' => true]);
        $table->addColumn('object_identifier', 'string', ['length' => 100]);
        $table->addColumn('parent_object_identity_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('entries_inheriting', 'boolean');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['object_identifier', 'class_id']);
        $table->addIndex(['parent_object_identity_id']);

        $table->addForeignKeyConstraint($table, ['parent_object_identity_id'], ['id']);
    }

    /**
     * Adds the object identity relation table to the schema.
     */
    protected function addObjectIdentityAncestorsTable()
    {
        $table = $this->createTable($this->options['oid_ancestors_table_name']);

        $table->addColumn('object_identity_id', 'integer', ['unsigned' => true]);
        $table->addColumn('ancestor_id', 'integer', ['unsigned' => true]);

        $table->setPrimaryKey(['object_identity_id', 'ancestor_id']);

        $oidTable = $this->getTable($this->options['oid_table_name']);
        $table->addForeignKeyConstraint($oidTable, ['object_identity_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE']);
        $table->addForeignKeyConstraint($oidTable, ['ancestor_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE']);
    }

    /**
     * Adds the security identity table to the schema.
     */
    protected function addSecurityIdentitiesTable()
    {
        $table = $this->createTable($this->options['sid_table_name']);

        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('identifier', 'string', ['length' => 200]);
        $table->addColumn('username', 'boolean');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['identifier', 'username']);
    }
}
