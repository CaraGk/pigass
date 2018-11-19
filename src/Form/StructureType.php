<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Form\Extension\Core\Type\FileType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\AddressType;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * StructureType
 */
class StructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('fullname', TextType::class, array(
                'label' => 'Nom complet',
                'required' => false,
            ))
            ->add('area', TextType::class, array(
                'label' => 'Zone géographique',
                'required' => false,
            ))
            ->add('areamap', ChoiceType::class, array(
                'label'    => 'Zones de la carte',
                'required' => false,
                'choices'  => [
                    'Alsace'                      => 'alsace',
                    'Aquitaine'                   => 'aquitaine',
                    'Auvergne'                    => 'auvergne',
                    'Basse-Normandie'             => 'bassenormandie',
                    'Bourgogne'                   => 'bourgogne',
                    'Bretagne'                    => 'bretagne',
                    'Centre'                      => 'centre',
                    'Champagne-Ardennes'          => 'champagneardennes',
                    'Corse'                       => 'corse',
                    'Franche-Comté'               => 'franchecomte',
                    'Haute-Normandie'             => 'hautenormandie',
                    'Île-de-France'               => 'iledefrance',
                    'Languedoc-Roussillon'        => 'languedocroussillon',
                    'Limousin'                    => 'limousin',
                    'Lorraine'                    => 'lorraine',
                    'Midi-Pyrénées'               => 'midipyrenees',
                    'Nord-Pas-de-Calais'          => 'nordpasdecalais',
                    'Pays-de-la-Loire'            => 'paysdelaloire',
                    'Picardie'                    => 'picardie',
                    'Poitou-Charentes'            => 'poitoucharentes',
                    'Provence-Alpes-Côte-d\'Azur' => 'paca',
                    'Rhones-Alpes'                => 'rhonesalpes',
                ],
                'expanded' => false,
                'multiple' => true,
            ))
            ->add('email')
            ->add('url')
            ->add('phone')
            ->add('address', AddressType::class, array(
                'label' => 'Adresse postale',
            ))
            ->add('logoFile', VichImageType::class, array(
                'label'    => 'Logo (image)',
                'required' => false,
                'allow_delete'   => false,
                'download_uri'   => false,
                'image_uri'      => true,
            ))
            ->add('activated', CheckboxType::class, array(
                'label'      => 'Activé ?',
                'required'   => false,
            ))
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function getName()
    {
        return 'pigass_corebundle_structuretype';
    }
}
