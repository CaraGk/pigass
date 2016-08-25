<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PigassCoreBundle extends Bundle
{
  public function boot()
  {
    $em = $this->container->get('doctrine.orm.default_entity_manager');

    $em->getConnection()->getDatabasePlatform();
  }
}
