<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * StructureRepository
 */
class StructureRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.area', 'asc')
        ;
    }

    public function getAll($activated = true)
    {
        $query = $this->getBaseQuery();

        if ($activated)
            $query->where('s.activated = TRUE');

        return $query->getQuery()
            ->getResult()
        ;
    }
}
