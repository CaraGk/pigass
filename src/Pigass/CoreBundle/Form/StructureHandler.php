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
                $oldName = $this->onSuccess($this->form->getData());

                return $oldName;
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

        $uow = $this->em->getUnitOfWork();
        $metadata = $this->em->getClassMetadata('\Pigass\CoreBundle\Entity\Structure');
        $uow->recomputeSingleEntityChangeSet($metadata, $structure);
        $changeSet = $uow->getEntityChangeSet($structure);
        $oldName = true;
        if (isset($changeSet['slug']))
            $oldName = $changeSet['slug'][0];

        $this->em->persist($structure);

        return $oldName;
    }
}
