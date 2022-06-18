<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2020 Pierre-François Angrand
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
            ->leftJoin('m.person', 'p')
            ->addSelect('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('m.structure', 's')
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

    public function getByStructureQuery($slug, $expiration = null, $filter = null, $anticipated = null)
    {
        $query = $this->getBaseQuery();
        $query
            ->where('true != false')
            ->orderBy('p.surname', 'asc')
            ->addOrderBy('p.name', 'asc')
            ->addOrderBy('m.id', 'desc')
        ;

        if ($slug != null) {
            $query
                ->andWhere('s.slug = :slug')
                ->setParameter('slug', $slug)
            ;
        }

        if (isset($filter['search']) and $filter['search']) {
            $query
                ->andWhere('p.surname like :search OR p.name like :search OR u.email like :search')
                ->setParameter('search', '%' . $filter['search'] . '%')
                ->addOrderBy('m.expiredOn', 'desc')
            ;
        } else {
            if (isset($filter['expiration']) and is_array($filter['expiration'])) {
                $query
                    ->andWhere('m.expiredOn = :expiration')
                    ->setParameter('expiration', $filter['expiration'][0])
                    ->leftJoin('p.memberships', 'n', 'WITH', 'n.expiredOn = :filter')
                    ->setParameter('filter', $filter['expiration'][1])
                    ->andWhere('n.person IS NULL')
                ;
            } elseif ($expiration) {
                if (is_array($expiration))
                {
                    $query
                        ->andWhere('m.expiredOn = :expiration_first OR m.expiredOn = :expiration_second')
                        ->setParameter('expiration_first', $expiration[0])
                        ->setParameter('expiration_second', $expiration[1])
                        ;
                } else {
                    $query
                        ->andWhere('m.expiredOn = :expiration')
                        ->setParameter('expiration', $expiration)
                    ;
                }
            }

            if (isset($filter['valid'])) {
                if ($filter['valid']) {
                    $query
                        ->andWhere('m.payedOn IS NOT NULL')
                        ->andWhere('m.status = \'validated\' OR m.status = \'paid\'')
                    ;
                } elseif ($filter['valid'] == false) {
                    $query->andWhere('m.payedOn IS NULL OR (m.status != \'validated\' AND m.status != \'paid\')');
                }
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

            if (isset($filter['isCounted'])) {
                $query
                    ->join('m.fee', 'f')
                    ->andWhere('f.counted = :isCounted')
                    ->setParameter('isCounted', $filter['isCounted'])
                ;
            }
        }

        return $query;
    }

    public function getByStructure($slug, $expiration = null, $filter = null, $anticipated = null)
    {
        return $this->getByStructureQuery($slug, $expiration, $filter, $anticipated)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAllByStructure($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getByStructureQuery($slug, null, $filter, $anticipated);

        return $query
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCurrentByStructureQuery($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getByStructureQuery($slug, null, $filter, $anticipated);
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

    public function getByStructureWithInfos($slug, $expiration)
    {
        return $this->getByStructureQuery($slug, $expiration, ['valid' => true], null)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCurrentForPersonArray()
    {
        $query = $this->createQueryBuilder('m')
                      ->join('m.person', 'p')
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

    public function getMailsByStructure($slug, $expire = null, $filters = null, $anticipated = null)
    {
        $query = $this->getByStructureQuery($slug, $expire, $filters, $anticipated);

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
