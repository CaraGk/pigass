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
use App\Entity\Person;

/**
 * MembershipRepository
 */
class MembershipRepository extends EntityRepository
{
    private function getBaseQuery()
    {
        return $this->createQueryBuilder('m')
            ->join('m.person', 'p')
            ->addSelect('p')
            ->join('p.user', 'u')
            ->addSelect('u')
            ->join('m.structure', 's')
            ->addSelect('s')
        ;
    }

    public function getLastForPerson(Person $person)
    {
        $query = $this->getBaseQuery();
        $query
            ->where('p.id = :person')
            ->setParameter(':person', $person->getId())
            ->orderBy('m.id', 'desc')
            ->setMaxResults(1)
        ;

        return $query
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getCurrentForPerson(Person $person, $payed = false)
    {
        $query = $this->getBaseQuery();
        $query
            ->where('m.person = :person')
            ->setParameter('person', $person->getId())
            ->andWhere('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('m.expiredOn', 'desc')
            ->setMaxResults(1)
        ;

        if ($payed)
            $query->andWhere('m.payedOn is not NULL');

        return $query->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getAllByStructureQuery($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getBaseQuery();
        $query
            ->where('true != false')
            ->orderBy('p.name', 'asc')
        ;

        if ($slug != null) {
            $query
                ->andWhere('s.slug = :slug')
                ->setParameter('slug', $slug)
            ;
        }

        if (isset($filter['valid'])) {
            if ($filter['valid'])
                $query->andWhere('m.payedOn is not NULL');
            elseif ($filter['valid'] != null)
                $query->andWhere('m.payedOn is NULL');
        }

        if (isset($filter['ending']) and $anticipated) {
            if ($filter['ending'] == true) {
                $query
                    ->andWhere('m.expiredOn < :anticipated')
                    ->setParameter('anticipated', $anticipated)
                ;
            } elseif($filter['ending'] == false) {
                $query
                    ->andWhere('m.expiredOn > :anticipated')
                    ->setParameter('anticipated', $anticipated)
                ;
            }
        }

        if (isset($filter['fee']) and $filter['fee']) {
            $query
                ->andWhere('m.fee = :fee')
                ->setParameter('fee', $filter['fee'])
            ;
        }

        if (isset($filter['gateway']) and $filter['gateway']) {
            $query
                ->join('m.method', 'g')
                ->addSelect('g')
                ->andWhere('g.gatewayName = :gateway')
                ->setParameter('gateway', $filter['gateway'])
            ;
        }

        if (isset($filter['questions']) and $filter['questions']) {
            $query
                ->join('m.infos', 'i')
                ->join('i.question', 'q')
            ;
            foreach ($filter['questions'] as $question_id => $value) {
                $query
                    ->andWhere('q.id = :question_id')
                    ->setParameter('question_id', $question_id)
                    ->andWhere('i.value = :info_value')
                    ->setParameter('info_value', $value)
                ;
            }
        }

        if (isset($filter['search']) and $filter['search']) {
            $query
                ->andWhere('p.surname like :search OR p.name like :search OR u.email like :search')
                ->setParameter('search', '%' . $filter['search'] . '%')
                ->addOrderBy('m.expiredOn', 'desc')
            ;
        }

        return $query;
    }


    public function getAllByStructure($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getAllByStructureQuery($slug, $filter, $anticipated);

        return $query
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCurrentByStructureQuery($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getAllByStructureQuery($slug, $filter, $anticipated);
        $query
            ->andWhere('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
        ;

        return $query;
    }


    public function getCurrentByStructure($slug, $filter = null, $anticipated = null) {
        return $this->getCurrentByStructureQuery($slug, $filter, $anticipated)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCurrentByStructureWithInfos($slug)
    {
        $query = $this->getBaseQuery();
        $query
            ->where('s.slug = :slug')
            ->setParameter('slug', $slug)
            ->andWhere('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
            ->andWhere('m.payedOn is not NULL')
            ->addOrderBy('p.surname', 'asc')
            ->addOrderBy('p.name', 'asc')
        ;

        return $query
            ->getQuery()
            ->getResult();
    }

    public function getCurrentForPersonArray()
    {
        $query = $this->createQueryBuilder('m')
                      ->join('m.person', 's')
                      ->where('m.expiredOn > :now')
                      ->setParameter('now', new \DateTime('now'))
                      ->andWhere('m.payedOn is not NULL')
                      ->groupBy('p.id')
                      ->select('p.id')
        ;

        return $query->getQuery()
                     ->getArrayResult()
        ;
    }

    public function getAll()
    {
        return $this->getBaseQuery()
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMailsByStructure($slug, $filters , $anticipated = null)
    {
        $query = $this->getCurrentByStructureQuery($slug, $filters, $anticipated);

        $result= $query
            ->getQuery()
            ->getResult()
        ;
        $list = "";

        foreach ($result as $membership) {
            $list .= $membership->getPerson()->getUser()->getEmail() . ", ";
        }

        $list = substr($list, 0, -2);

        return $list;
    }

}
