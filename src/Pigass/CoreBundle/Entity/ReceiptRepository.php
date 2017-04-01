<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Pigass\CoreBundle\Entity\Structure;

/**
 * ReceiptRepository
 */
class ReceiptRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.end', 'desc')
        ;
    }

    private function getForStructureQuery(Structure $structure)
    {
        $query = $this->getBaseQuery();

        return $query->where('r.structure = :structure')
            ->setParameter('structure', $structure->getId())
        ;
    }

    public function getForStructure(Structure $structure)
    {
        return $this->getForStructureQuery($structure)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getOneByDate(Structure $structure, \DateTime $date)
    {
        $query = $this->getForStructureQuery($structure);
        $query->andWhere('r.begin <= :date')
            ->andWhere('r.end >= :date')
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ;

        return $query->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
