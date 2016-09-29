<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\CoreBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Pigass\CoreBundle\Entity\Structure;

/**
 * StructureType Handler
 */
class StructureHandler
{
    private $form;
    private $request;
    private $em;
    private $targetDir;

    public function __construct(Form $form, Request $request, EntityManager $em, $targetDir)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->em      = $em;
        $this->targetDir = $targetDir;
    }

    public function process()
    {
        if ($this->request->getMethod() == 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isSubmitted() and $this->form->isValid()) {
                $this->onSuccess($this->form->getData());

            return true;
            }
        }

        return false;
    }

    public function onSuccess(Structure $structure)
    {
        if ($file = $structure->getLogo()) {
            $fileName = $structure->getSlug() . '.' . $file->guessExtension();
            $file->move(
                $this->targetDir,
                $fileName
            );
            $structure->setLogo($fileName);
        }

        $this->em->persist($structure);
        $this->em->flush();
    }
}
