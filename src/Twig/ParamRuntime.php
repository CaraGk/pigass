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

use Twig\Extension\RuntimeExtensionInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Twig extension for parameters
 */
class ParamRuntime implements RuntimeExtensionInterface
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function showParam($param)
    {
        return $this->em->getRepository('App:Parameter')->findByName($param)->getValue();
    }
}
