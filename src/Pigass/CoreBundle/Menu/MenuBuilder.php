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
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @param RequestStack $requestStack
     */
    public function createAnonMenu(RequestStack $requestStack)
    {
        $menu = $this->factory->createItem('anon');
        $menu->addChild('Register', array('route' => 'user_register_register', 'label' => 'S\'enregistrer', 'attributes' => array('title' => 'S\'enregistrer et adhérer à une structure')));

        return $menu;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function createMemberMenu(RequestStack $requestStack)
    {
        $menu = $this->factory->createItem('member');
        $menu->addChild('My memberships', array('route' => 'user_register_list', 'label' => 'Mes adhésions', 'attributes' => array('title' => 'Mes adhésions à la structure')));

        return $menu;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function createAdminMenu(RequestStack $requestStack)
    {
        $menu = $this->factory->createItem('admin');
        $menu->addChild('Structures', array('route' => 'core_structure_index', 'label' => 'Catégories', 'attributes' => array('title' => 'Gérer les catégories')));
        $menu->addChild('Persons', array('route' => 'user_person_index', 'label' => 'Étudiants', 'attributes' => array('title' => 'Gérer les étudiants')));
        $menu->addChild('Parameters', array('route' => 'parameter_admin_index', 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site')));

        return $menu;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function createStructureMenu(RequestStack $requestStack)
    {
        $menu = $this->factory->createItem('structure');
        $menu->addChild('Parameters', array('route' => 'GParameter_PAIndex', 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site')));

        return $menu;
    }

}
