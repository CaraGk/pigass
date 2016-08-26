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
use Symfony\Component\HttpFoundation\RequestStack,
    Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

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
     * @param AuthorizationChecker $security
     */
    public function createMainMenu(RequestStack $requestStack, AuthorizationChecker $security)
    {
        $menu = $this->factory->createItem('anon', array('navbar' => true));
        $session = $requestStack->getCurrentRequest()->getSession();

        if (!$security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $menu->addChild('Login', array('route' => 'fos_user_security_login', 'label' => 'S\'identifier', 'attributes' => array('title' => 'S\'identifier pour accéder au site')));
        } else {
            if ($security->isGranted('ROLE_MEMBER')) {
                $menu->addChild('My memberships', array('route' => 'user_register_list', 'label' => 'Mes adhésions', 'attributes' => array('title' => 'Mes adhésions à la structure')));
            }
            if ($security->isGranted('ROLE_STRUCTURE') or $security->isGranted('ROLE_ADMIN')) {
                $menu = $this->factory->createItem('admin', array('navbar' => true));
                $adminMenu = $menu->addChild('Administration', array('label' => 'Administrer le site', 'dropdown' => true, 'caret' => true,));
                if ($security->isGranted('ROLE_STRUCTURE') and $session->has('slug')) {
                    $slug = $session->get('slug');
                    $adminMenu->addChild('Persons', array('route' => 'user_person_index', 'route_parameters' => array('slug' => $slug), 'label' => 'Adhérents', 'attributes' => array('title' => 'Gérer les adhérents')));
                    $adminMenu->addChild('Parameters', array('route' => 'GParameter_PAIndex', 'route_parameters' => array('slug' => $slug), 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site')));
                }
                if ($security->isGranted('ROLE_ADMIN')) {
                    $adminMenu->addChild('Structures', array('route' => 'core_structure_index', 'label' => 'Structures', 'attributes' => array('title' => 'Gérer les structures')));
                    $adminMenu->addChild('Parameters', array('route' => 'parameter_admin_index', 'route_parameters' => array('slug' => null), 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site')));
                }
            }
            $menu->addChild('Logout', array('route' => 'fos_user_security_logout', 'label' => 'Se déconnecter', 'attributes' => array('title' => 'Se déconnecter du site')));
        }

        return $menu;
    }

}
