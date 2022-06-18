<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure;

/**
 * PeriodRepository
 */
class PeriodRepository extends EntityRepository
{
    public function getCurrent(Structure $structure)
    {
        return $this->createQueryBuilder('p')
                    ->where('p.structure = :structure')
                    ->setParameter('structure', $structure->getId())
                    ->andWhere('p.begin > :now')
                    ->andWhere('p.end < :now')
                    ->setParameter('now', date("Y-m-d"))
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function getLast(Structure $structure)
    {
        return $this->createQueryBuilder('p')
                    ->where('p.structure = :structure')
                    ->setParameter('structure', $structure->getId())
                    ->orderBy('p.end', 'desc')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function getSimulationActive(Structure $structure)
    {
        return $this->createQueryBuilder('s')
            ->where('p.structure = :structure')
            ->setParameter('structure', $structure->getId())
            ->andWhere('s.begin <= :now')
            ->andWhere('s.end >= :now')
            ->setParameter('now', new \DateTime('now'));
    }
}
