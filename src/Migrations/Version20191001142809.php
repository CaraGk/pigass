<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191001142809 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payum_gateway ADD active TINYINT(1) NOT NULL');
    }

    public function postUp(Schema $schema) : void
    {
        $this->connection->exec('UPDATE payum_gateway SET active = true WHERE 1');
        $this->connection->exec('UPDATE parameter SET more = \'a:6:{s:6:"1 mois";s:9:"+ 1 month";s:6:"2 mois";s:10:"+ 2 months";s:6:"6 mois";s:10:"+ 6 months";s:4:"1 an";s:8:"+ 1 year";s:5:"2 ans";s:9:"+ 2 years";s:5:"3 ans";s:9:"+ 3 years";}\' WHERE name LIKE "reg%periodicity"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE payum_gateway DROP active');
    }
}
