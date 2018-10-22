<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160929195514 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
        $this->addSql('ALTER TABLE structure ADD address LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ADD area VARCHAR(150) DEFAULT NULL, ADD logo VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $this->connection->exec('UPDATE structure SET address = \'a:7:{s:6:"number";N;s:4:"type";N;s:6:"street";N;s:10:"complement";N;s:4:"code";N;s:4:"city";N;s:7:"country";N;}\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE structure DROP address, DROP area, DROP logo');
        $this->addSql('DROP INDEX UNIQ_8D93D649C05FB297 ON user');
    }
}
