<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Twig\ParamRuntime;

/**
 * Twig extension for parameters
 */
class ParamExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('param', [ParamRuntime::class, 'showParam']),
        ];
    }
}
