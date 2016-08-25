<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
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
    public function countAll()
    {
        $query = $this->createQueryBuilder('q')
            ->select('COUNT(q)');

        return $query->getQuery()->getSingleScalarResult();
    }
}
