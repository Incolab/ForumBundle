<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Incolab\ForumBundle\Resources\SchemaDatabase;

use Incolab\DBALBundle\Manager\Manager;

/**
 * Description of CreateShema
 *
 * @author david
 */
class CreateShema extends Manager {
    
    private $fromSchema;
    private $schema;
    
    public function __construct(\Doctrine\DBAL\Connection $dbal) {
        parent::__construct($dbal);
        $schemaManager = $this->dbal->getSchemaManager();
        $this->fromSchema = $schemaManager->createSchema();
        $this->schema = clone $this->fromSchema;
    }
    
    public function create_database() {
        $this->createFrSchema();
        $this->createFcSchema();
        $this->createFtSchema();
        $this->createFpSchema();
        
        $this->setForeignKeys();
        
        $sql = $this->fromSchema->getMigrateToSql($this->schema, $this->dbal->getDatabasePlatform());

        return $sql;
    }
    
    public function createFrSchema() {
        $frTable = $this->schema->createTable("forum_role");
        $frTable->addColumn("id", "integer", ["unsigned" => true]);
        $frTable->addColumn("name", "string", ["length" => 255]);
        
        $frTable->setPrimaryKey(["id"]);
        $frTable->addUniqueIndex(["name"]);
        
        $this->schema->createSequence("forum_role_id_seq");
        
        $uafrTable = $this->schema->createTable("user_forum_role");
        $uafrTable->addColumn("user_id", "integer", ["unsigned" => true]);
        $uafrTable->addColumn("forum_role_id", "integer", ["unsigned" => true]);
        
        $fcrrTable = $this->schema->createTable("forum_category_read_roles");
        $fcrrTable->addColumn("category_id", "integer", ["unsigned" => true]);
        $fcrrTable->addColumn("forum_role_id", "integer", ["unsigned" => true]);
        
        $fcwrTable = $this->schema->createTable("forum_category_write_roles");
        $fcwrTable->addColumn("category_id", "integer", ["unsigned" => true]);
        $fcwrTable->addColumn("forum_role_id", "integer", ["unsigned" => true]);
    }

    public function createFcSchema() {
        
        $table = $this->schema->createTable("forum_category");
        $table->addColumn("id", "integer", ["unsigned" => true]);
        $table->addColumn("parent_id", "integer", ["unsigned" => true, "notnull" => false]);
        $table->addColumn("last_topic_id", "integer", ["unsigned" => true, "notnull" => false]);
        $table->addColumn("last_post_id", "integer", ["unsigned" => true, "notnull" => false]);
        $table->addColumn("name", "string", ["length" => 255]);
        $table->addColumn("description", "text");
        $table->addColumn("slug", "string", ["length" => 255]);
        $table->addColumn("position", "integer", ["unsigned" => true]);
        $table->addColumn("num_topics", "integer", ["unsigned" => true]);
        $table->addColumn("num_posts", "integer", ["unsigned" => true]);
        
        $table->setPrimaryKey(["id"]);
        $table->addUniqueIndex(["slug"]);
        
        $this->schema->createSequence("forum_category_id_seq");
    }
    
    public function createFpSchema() {
        $table = $this->schema->createTable("forum_post");
        $table->addColumn("id", "integer", ["unsigned" => true]);
        $table->addColumn("topic_id", "integer", ["unsigned" => true]);
        $table->addColumn("author_id", "integer", ["unsigned" => true]);
        $table->addColumn("message", "text");
        $table->addColumn("createdat", "datetime");
        $table->addColumn("updatedat", "datetime", ["notnull" => false]);
        
        $table->setPrimaryKey(["id"]);
        
        $this->schema->createSequence("forum_post_id_seq");
    }
    
    public function createFtSchema() {
        $table = $this->schema->createTable("forum_topic");
        $table->addColumn("id", "integer", ["unsigned" => true]);
        $table->addColumn("first_post_id", "integer", ["unsigned" => true, "notnull" => false]);
        $table->addColumn("last_post_id", "integer", ["unsigned" => true, "notnull" => false]);
        $table->addColumn("author_id", "integer", ["unsigned" => true]);
        $table->addColumn("category_id", "integer", ["unsigned" => true]);
        $table->addColumn("subject", "string", ["length" => 255]);
        $table->addColumn("slug", "string", ["length" => 255]);
        $table->addColumn("num_views", "integer", ["unsigned" => true]);
        $table->addColumn("num_posts", "integer", ["unsigned" => true]);
        $table->addColumn("is_closed", "boolean");
        $table->addColumn("is_pinned", "boolean");
        $table->addColumn("is_buried", "boolean");
        $table->addColumn("created_at", "datetime");
        $table->addColumn("pulled_at", "datetime", ["notnull" => false]);
        
        $table->setPrimaryKey(["id"]);
        $table->addUniqueIndex(["slug"]);
        
        $this->schema->createSequence("forum_topic_id_seq");
    }

    private function setForeignKeys() {
        $frTable = $this->schema->getTable("forum_role");
        $fcrrTable = $this->schema->getTable("forum_category_read_roles");
        $fcwrTable = $this->schema->getTable("forum_category_write_roles");
        $uafrTable = $this->schema->getTable("user_forum_role");
        $fcTable = $this->schema->getTable("forum_category");
        $ftTable = $this->schema->getTable("forum_topic");
        $fpTable = $this->schema->getTable("forum_post");
        $uaTable = $this->schema->getTable("user_account");
        
        // Forum Role
        $uafrTable->addForeignKeyConstraint($uaTable, ["user_id"], ["id"], ["onDelete" => "CASCADE"]);
        $uafrTable->addForeignKeyConstraint($frTable, ["forum_role_id"], ["id"], ["onDelete" => "CASCADE"]);
        
        $fcrrTable->addForeignKeyConstraint($frTable, ["forum_role_id"], ["id"], ["onDelete" => "CASCADE"]);
        $fcrrTable->addForeignKeyConstraint($fcTable, ["category_id"], ["id"], ["onDelete" => "CASCADE"]);
        $fcwrTable->addForeignKeyConstraint($frTable, ["forum_role_id"], ["id"], ["onDelete" => "CASCADE"]);
        $fcwrTable->addForeignKeyConstraint($fcTable, ["category_id"], ["id"], ["onDelete" => "CASCADE"]);
        
        
        // Category
        $fcTable->addForeignKeyConstraint($ftTable, ["last_topic_id"], ["id"], ["onDelete" => "SET NULL"]);
        $fcTable->addForeignKeyConstraint($fpTable, ["last_post_id"], ["id"], ["onDelete" => "SET NULL"]);
        $fcTable->addForeignKeyConstraint($fcTable, ["parent_id"], ["id"], ["onDelete" => "CASCADE"]);
        
        // Topic
        $ftTable->addForeignKeyConstraint($fpTable, ["first_post_id"], ["id"], ["onDelete" => "CASCADE"]);
        $ftTable->addForeignKeyConstraint($fpTable, ["last_post_id"], ["id"], ["onDelete" => "SET NULL"]);
        $ftTable->addForeignKeyConstraint($uaTable, ["author_id"], ["id"], ["onDelete" => "CASCADE"]);
        $ftTable->addForeignKeyConstraint($fcTable, ["category_id"], ["id"], ["onDelete" => "CASCADE"]);
        
        // Post
        $fpTable->addForeignKeyConstraint($ftTable, ["topic_id"], ["id"], ["onDelete" => "CASCADE"]);
        $fpTable->addForeignKeyConstraint($uaTable, ["author_id"], ["id"], ["onDelete" => "CASCADE"]);
    }
}
