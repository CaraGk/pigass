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
use Pigass\CoreBundle\Entity\Structure;

/**
 * MemberQuestionRepository
 */
class MemberQuestionRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('q');
    }

    public function countAll(Structure $structure = null)
    {
        $query = $this->createQueryBuilder('q')
            ->select('COUNT(q)');

        return $query->getQuery()->getSingleScalarResult();
    }

    public function getAll(Structure $structure = null, $exclude = null)
    {
        $query = $this->getBaseQuery();

        if ($structure) {
            $query->where('q.structure is null OR q.structure = :structure_filter')
                ->setParameter('structure_filter', $structure)
                ->orderBy('q.structure', 'ASC')
            ;
        }

        if ($exclude) {
            $query->andWhere('q.id NOT IN ' . $exclude)
            ;
        }

        $query->addOrderBy('q.rank', 'ASC');

        return $query->getQuery()
            ->getResult()
        ;
    }
}
