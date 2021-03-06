<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure;

/**
 * FeeRepository
 */
class FeeRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.amount', 'desc')
            ->join('f.structure', 's')
            ->addSelect('s')
        ;
    }

    public function getForStructureQuery(Structure $structure)
    {
        $query = $this->getBaseQuery();

        return $query->where('f.structure = :structure')
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

    public function getAllWithStructure()
    {
        return $this->getBaseQuery()
            ->getQuery()
            ->getResult()
        ;
    }
}
