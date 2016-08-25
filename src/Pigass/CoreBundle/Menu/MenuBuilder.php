<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Menu builder class
 */
class MenuBuilder
{
    private $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     */
    public function createAnonMenu(Request $request)
    {
        $menu = $this->factory->createItem('anon');

        return $menu;
    }

    /**
     * @param Request $request
     */
    public function createMemberMenu(Request $request)
    {
        $menu = $this->factory->createItem('member');
        $menu->addChild('My memberships', array('route' => 'GRegister_UIndex', 'label' => 'Mes adhésions', 'attributes' => array('title' => 'Mes adhésions à la structure')));

        return $menu;
    }

    /**
     * @param Request $request
     */
    public function createAdminMenu(Request $request)
    {
        $menu = $this->factory->createItem('admin');
        $menu->addChild('Structures', array('route' => 'core_structure_index', 'label' => 'Catégories', 'attributes' => array('title' => 'Gérer les catégories')));
        $menu->addChild('Persons', array('route' => 'GUser_SAIndex', 'label' => 'Étudiants', 'attributes' => array('title' => 'Gérer les étudiants')));
        $menu->addChild('Parameters', array('route' => 'GParameter_PAIndex', 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site')));

        return $menu;
    }

    /**
     * @param Request $request
     */
    public function createStructureMenu(Request $request)
    {
        $menu = $this->factory->createItem('structure');
        $menu->addChild('Parameters', array('route' => 'GParameter_PAIndex', 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site')));

        return $menu;
    }

}
