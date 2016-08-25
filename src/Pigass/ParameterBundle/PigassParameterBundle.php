<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\ParameterBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PigassParameterBundle extends Bundle
{
  public function getParent()
  {
    return 'KDBParametersBundle';
  }
}
