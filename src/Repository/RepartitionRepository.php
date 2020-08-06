<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Period,
    App\Entity\Sector,
    App\Entity\Structure;

/**
 * RepartitionRepository
 */
class RepartitionRepository extends EntityRepository
{
    public function getBaseQuery()
    {
        return $this->createQueryBuilder('r')
                    ->join('r.department', 'd')
                    ->join('r.period', 'p')
                    ->join('d.hospital', 'h')
                    ->leftJoin('d.accreditations', 'a')
                    ->leftJoin('a.sector', 's')
                    ->addSelect('d')
                    ->addSelect('h')
                    ->addSelect('a')
                    ->addSelect('s')
        ;
    }

    public function getByPeriodQuery(Structure $structure, Period $period)
    {
        $query = $this->getBaseQuery();
        return $query
            ->where('r.structure = :structure')
            ->setParameter('structure', $structure->getId())
            ->andWhere('p.id = :period')
            ->andWhere('a.begin <= :period_begin')
            ->andWhere('(a.end IS NULL OR a.end <= :period_end)')
            ->setParameter('period', $period->getId())
            ->setParameter('period_begin', $period->getBegin())
            ->setParameter('period_end', $period->getEnd())
            ->addOrderBy('h.name', 'asc')
            ->addOrderBy('d.name', 'asc')
        ;
    }

    public function getAvailableQuery(Structure $structure, Period $period)
    {
        $query = $this->getByPeriodQuery($structure, $period);
        $query->andWhere('r.number > 0');
        return $query;
    }

    public function getAvailable(Structure $structure, Period $period)
    {
        $query = $this->getAvailableQuery($structure, $period);

        return $query->getQuery()
                     ->getResult()
        ;
    }

    public function getAvailableForSector(Structure $structure, Period $period, Sector $sector)
    {
        $query = $this->getAvailableQuery($structure, $period);
        $query->andWhere('s.id = :sector_id')
              ->setParameter('sector_id', $sector->getId())
        ;
        return $query->getQuery()
                     ->getResult()
        ;
    }

    public function getByPeriod(Structure $structure, Period $period, $hospital_id = null)
    {
        $query = $this->getByPeriodQuery($structure, $period);

        if ($hospital_id != null) {
            $query->andWhere('h.id = :hospital_id')
                  ->setParameter('hospital_id', $hospital_id)
            ;
        }

        return $query->getQuery()
                     ->getResult()
        ;
    }

    public function getByDepartment($department_id)
    {
        return $this->getBaseQuery()
                    ->where('d.id = :department_id')
                    ->setParameter('department_id', $department_id)
                    ->orderBy('p.begin', 'desc')
                    ->getQuery()
                    ->getResult()
        ;
    }

    public function getByPeriodAndDepartmentSector(Structure $structure, Period $period, $sector_id)
    {
        $query = $this->getByPeriodQuery($structure, $period);
        $query
            ->andWhere('s.id = :sector_id')
            ->setParameter('sector_id', $sector_id)
        ;

        return $query->getQuery()
                     ->getResult()
        ;
    }

    public function getByPeriodAndCluster(Structure $structure, Period $period, $cluster)
    {
        $query = $this->getByPeriodQuery($structure, $period);
        $query->andWhere('r.cluster = :cluster')
              ->setParameter('cluster', $cluster)
        ;

        return $query->getQuery()
                     ->getResult()
        ;
    }

    public function getByPeriodAndDepartment(Structure $structure, Period $period, $department_id)
    {
        $query = $this->getByPeriodQuery($structure, $period);
        $query->andWhere('d.id = :department_id')
            ->setParameter('department_id', $department_id)
            ->setMaxResults(1)
        ;

        return $query->getQuery()
                     ->getOneOrNullResult()
        ;
    }

    public function getFirstBeforeDateByDepartment($department_id, \DateTime $date)
    {
        $query = $this->getBaseQuery();
        $query->where('d.id = :department_id')
            ->setParameter('department_id', $department_id)
            ->andWhere('p.end < :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('p.end', 'desc')
            ->setMaxResults(1)
        ;

        return $query->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
