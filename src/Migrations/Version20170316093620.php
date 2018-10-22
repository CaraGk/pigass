<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170316093620 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE structure ADD fullname VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(150) DEFAULT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE slug slug VARCHAR(100) NOT NULL, CHANGE area area VARCHAR(100) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE structure DROP fullname, DROP email, CHANGE name name VARCHAR(150) NOT NULL COLLATE utf8_unicode_ci, CHANGE slug slug VARCHAR(150) NOT NULL COLLATE utf8_unicode_ci, CHANGE area area VARCHAR(150) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
