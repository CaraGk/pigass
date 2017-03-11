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

    public function getForStructure(Structure $structure)
    {
        $query = $this->getBaseQuery();
        $query->where('r.structure = :structure')
            ->setParameter('structure', $structure->getId())
        ;

        return $query->getQuery()
            ->getResult()
        ;
    }
}
