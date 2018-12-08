<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure;

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

    public function getAll(Structure $structure = null, $type = null, $exclude = null)
    {
        $query = $this->getBaseQuery();

        if ($structure) {
            $query->where('q.structure is null OR q.structure = :structure_filter')
                ->setParameter('structure_filter', $structure)
                ->orderBy('q.structure', 'ASC')
            ;
        }

        if ($type) {
            $query->andWhere('q.type IN ' . $type)
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

    public function getAllArray(Structure $structure = null, $type = null, $exclude = null)
    {
        $results = $this->getAll($structure, $type, $exclude);

        foreach ($results as $result) {
            $array[$result->getId()] = $result;
        }

        return $array;
    }
}
