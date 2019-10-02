<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure;

/**
 * SectorRepository
 */
class SectorRepository extends EntityRepository
{
  public function listOtherSectors(array $exclude = null)
  {
    $query = $this->createQueryBuilder('s');
    if ($exclude != null) {
      $query->where('s.id NOT IN (' . implode(',', $exclude) . ')');
    }

    return $query;
  }

  public function getNext($sector_id = 0)
  {
      $query = $this->createQueryBuilder('s')
                    ->where('s.id > :sector_id')
                    ->setParameter('sector_id', $sector_id)
                    ->setMaxResults(1)
      ;

      return $query->getQuery()
                   ->getOneOrNullResult()
      ;
  }

  public function getAll(Structure $structure)
  {
      return $this->createQueryBuilder('s')
          ->where('s.structure = :structure')
          ->setParameter('structure', $structure)
          ->orderBy('s.id')
          ->getQuery()
          ->getResult()
      ;
  }
}
