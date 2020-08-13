<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

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

    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function getBySlug($slug)
    {
        $query = $this->createQueryBuilder('p')
            ->where('p.name like :slug')
            ->setParameter('slug', '%' . $slug . '%')
            ->addOrderBy('p.structure', 'asc')
            ->addOrderBy('p.category', 'asc')
            ->addOrderBy('p.name', 'asc')
            ;

        return $query->getQuery()
            ->getResult()
        ;
    }
}
