<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2017 Pierre-François Angrand
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
        $menu = $this->factory->createItem('main', array('navbar' => true));
        $session = $requestStack->getCurrentRequest()->getSession();

        if (!$security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $menu->addChild('Structures', array('route' => 'core_structure_index', 'label' => 'S\'enregistrer', 'attributes' => array('title' => 'S\'enregistrer et adhérer à une structure')));
            $menu->addChild('Login', array('route' => 'fos_user_security_login', 'label' => 'S\'identifier', 'attributes' => array('title' => 'S\'identifier pour accéder au site')));
        } else {
            if ($security->isGranted('ROLE_MEMBER')) {
                $menu->addChild('My memberships', array('route' => 'user_register_list', 'label' => 'Mes adhésions', 'attributes' => array('title' => 'Mes adhésions à la structure')));
            }
            if ($security->isGranted('ROLE_STRUCTURE') and $session->has('slug')) {
                $slug = $session->get('slug');
                $strMenu = $menu->addChild('Structure', array('label' => $slug, 'dropdown' => true, 'caret' => true, 'icon' => 'king'));
                $strMenu->addChild('Memberships', array('route' => 'user_register_index', 'routeParameters' => array('slug' => $slug), 'label' => 'Adhérents', 'attributes' => array('title' => 'Gérer les adhérents'), 'icon' => 'user'));
                $strMenu->addChild('Parameters', array('route' => 'parameter_admin_index', 'routeParameters' => array('slug' => $slug), 'label' => 'Paramètres', 'attributes' => array('title' => 'Gérer les paramètres du site'), 'icon' => 'cog'));
            }
            if ($security->isGranted('ROLE_ADMIN')) {
                $adminMenu = $menu->addChild('Administration', array('label' => 'Administrer', 'dropdown' => true, 'caret' => true, 'icon' => 'king'));
                $adminMenu->addChild('Structures', array('route' => 'core_structure_index', 'label' => 'Structures', 'attributes' => array('title' => 'Gérer les structures'), 'icon' => 'home'));
                $adminMenu->addChild('Questions', array('route' => 'user_register_question_index', 'routeParameters' => ['slug' => 'all'], 'label' => 'Questions complémentaires', 'attributes' => array('title' => 'Gérer les questions complémentaires'), 'icon' => 'question-sign'));
            }
            $menu->addChild('Logout', array('route' => 'fos_user_security_logout', 'label' => 'Se déconnecter', 'attributes' => array('title' => 'Se déconnecter du site'), 'icon' => 'log-out'));
        }

        return $menu;
    }

    /**
     * @param RequestStack $requestStack
     * @param AuthorizationChecker $security
     */
    public function createParameterMenu(RequestStack $requestStack, AuthorizationChecker $security)
    {
        $menu = $this->factory->createItem('parameter', [
            'childrenAttributes' => ['class' => 'btn-group-vertical'],
        ]);
        $session = $requestStack->getCurrentRequest()->getSession();
        $slug_session = $session->get('slug');
        $slug_request = $requestStack->getCurrentRequest()->get('slug');
        if ($security->isGranted('ROLE_ADMIN') and $slug_session != $slug_request)
            $slug = $slug_request;
        else
            $slug = $slug_session;

        $menu->addChild('Edit', array('route' => 'core_structure_edit', 'routeParameters' => array('slug' => $slug), 'label' => 'Modifier la structure', 'attributes' => array('title' => 'Modifier la structure', 'class' => 'btn btn-primary'), 'icon' => 'pencil'));
        $menu->addChild('Parameters', array('route' => 'parameter_admin_index', 'routeParameters' => array('slug' => $slug), 'label' => 'Paramètres du site', 'attributes' => array('title' => 'Gérer les paramètres du site', 'class' => 'btn btn-primary'), 'icon' => 'cog'));
        $menu->addChild('Gateways', array('route' => 'user_payment_index', 'routeParameters' => array('slug' => $slug), 'label' => 'Moyens de paiement', 'attributes' => array('title' => 'Gérer les moyens de paiement', 'class' => 'btn btn-primary'), 'icon' => 'piggy-bank'));
        $menu->addChild('Fees', array('route' => 'core_fee_index', 'routeParameters' => array('slug' => $slug), 'label' => 'Tarifs', 'attributes' => array('title' => 'Gérer les tarifs d\'adhésion', 'class' => 'btn btn-primary'), 'icon' => 'eur'));
        $menu->addChild('Receipts', array('route' => 'core_receipt_index', 'routeParameters' => array('slug' => $slug), 'label' => 'Reçus fiscaux', 'attributes' => array('title' => 'Gérer les signataires de reçus fiscaux', 'class' => 'btn btn-primary'), 'icon' => 'pencil'));
        $menu->addChild('Questions', array('route' => 'user_register_question_index', 'routeParameters' => ['slug' => $slug], 'label' => 'Questions complémentaires', 'attributes' => array('title' => 'Gérer les questions complémentaires', 'class' => 'btn btn-primary'), 'icon' => 'question-sign'));

        if ($security->isGranted('ROLE_ADMIN')) {
            $menu->addChild('Delete', array('route' => 'core_structure_delete', 'routeParameters' => array('slug' => $slug), 'label' => 'Supprimer la structure', 'attributes' => array('title' => 'Supprimer la structure', 'class' => 'btn btn-primary delete'), 'icon' => 'trash'));
        }

        return $menu;
    }
}
