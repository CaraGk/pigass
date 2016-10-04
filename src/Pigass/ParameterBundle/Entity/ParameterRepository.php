<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\ParameterBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ParameterRepository
 */
class ParameterRepository extends EntityRepository
{
    public function findAll()
    {
        return $this->findBy(array(), array('category' => 'asc'));
    }

    public function getBySlug($slug)
    {
        $query = $this->createQueryBuilder('p')
            ->where('p.name like :slug')
            ->setParameter('slug', '%' . $slug . '%')
            ;

        return $query->getQuery()
            ->getResult()
        ;
    }
}
