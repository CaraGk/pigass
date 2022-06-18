<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * GatewayRepository
 */
class GatewayRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('g')
            ->join('g.structure', 's')
            ->addSelect('s')
        ;
    }

    public function getBySlug($slug)
    {
        $query = $this->getBaseQuery();
        $query->where('s.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('g.factoryName', 'asc')
        ;

        return $query->getQuery()
            ->getResult()
        ;
    }
}
