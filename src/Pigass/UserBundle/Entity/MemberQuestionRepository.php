<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * MemberQuestionRepository
 */
class MemberQuestionRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('q');
    }

    public function countAll($structure_filter = null)
    {
        $query = $this->createQueryBuilder('q')
            ->select('COUNT(q)');

        return $query->getQuery()->getSingleScalarResult();
    }

    public function getAll($structure_filter = null)
    {
        $query = $this->getBaseQuery();

        if ($structure_filter) {
            $query->where('q.structure = :structure_filter')
                ->setParameter('structure_filter', $structure_filter)
            ;
        }

        return $query->getQuery()
            ->getResult()
        ;
    }
}
