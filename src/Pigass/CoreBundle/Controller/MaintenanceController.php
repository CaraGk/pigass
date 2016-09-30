<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\DBAL\Migrations\Migration,
    Doctrine\DBAL\Migrations\Configuration\Configuration;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\ParameterBundle\Entity\Parameter;

/**
 * Maintenance controller.
 */
class MaintenanceController extends Controller
{
    /** @DI\Inject */
    private $session;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /**
     * Database update if needed
     *
     * @Route("/update", name="core_maintenance_update")
     */
    public function updateAction()
    {
        /** Update database if some migrations are pending */
        if ($this->hasToMigrate($this->getDoctrine()->getConnection())) {
            $this->session->getFlashBag()->add('notice', 'Mise à jour de la base de donnée effectuée.');

            /** Go to first user form if repository User is empty */
            if(!$this->em->getRepository('PigassUserBundle:User')->findAll()){
                return $this->redirect($this->generateUrl('user_person_install'));
            }
        } else {
            $this->session->getFlashBag()->add('notice', 'Toutes les mises à jour de la base de donnée ont déjà été effectuées.');
        }

        return $this->redirect($this->generateUrl('core_structure_index'));
    }

    private function hasToMigrate($conn)
    {
        $dir = __DIR__.'/../../../../app/DoctrineMigrations';
        $configuration = new Configuration($conn);
        $configuration->setMigrationsNamespace('Application\Migrations');
        $configuration->setMigrationsTableName('migration_versions');
        $configuration->setMigrationsDirectory($dir);
        $configuration->registerMigrationsFromDirectory($dir);

        $executedMigrations = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();
        $newMigrations = count($availableMigrations) - count($executedMigrations);
        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);

        if ($newMigrations > 0 and !$executedUnavailableMigrations) {
            $migration = new Migration($configuration);
            if ($migration->migrate()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Correct missing parameters
     *
     * @Route("/db/parameters", name="core_maintenance_correct_db_parameters")
     */
    public function correctDBParametersAction()
    {
        $structures = $this->em->getRepository('PigassCoreBundle:Structure')->findAll();
        $count = 0;

        foreach ($structures as $structure) {
            $slug = $structure->getSlug();
            $now = new \DateTime('now');
            $parameters = array(
                0 => array('setName' => 'reg_' . $slug . '_date', 'setValue' => $now->format('d-m-Y'), 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Date anniversaire des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                1 => array('setName' => 'reg_' . $slug . '_periodicity', 'setValue' => '+ 1 year', 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Périodicité des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("1 mois" => "+ 1 month", "2 mois" => "+ 2 months", "6 mois" => "+ 6 months", "1 an" => "+ 1 year", "2 ans" => "+ 2 years", "3 ans" => "+ 3 years"), 'setStructure' => $structure),
                2 => array('setName' => 'reg_' . $slug . '_payment', 'setValue' => 60, 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Montant de la cotisation (EUR)', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                3 => array('setName' => 'reg_' . $slug . '_print', 'setValue' => false, 'setActive' => true, 'setActivatesAt' => $now, 'setLabel' => 'Nécessité de retourner le bulletin d\'adhésion imprimé et signé', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("Oui" => true, "Non" => false), 'setStructure' => $structure),
            );

            foreach ($parameters as $parameter) {
                if (!$this->pm->findParamByName($parameter['setName'])) {
                    $structure_parameter = new Parameter();
                    foreach ($parameter as $name => $value) {
                        $structure_parameter->$name($value);
                    }
                    $this->em->persist($structure_parameter);
                    $count++;
                }
            }
        }

        $this->em->flush();
        $this->session->getFlashBag()->add('notice', $count . ' paramètres ajoutés pour ' . count($structures) . ' structures en base de données.');
        return $this->redirect($this->generateUrl('core_structure_index'));
    }

}
