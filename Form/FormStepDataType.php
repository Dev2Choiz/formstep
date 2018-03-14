<?php

namespace FormStepBundle\Form;

use FormStepBundle\Service\ExtraData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormStepDataType extends AbstractType
{

    public $serialize;

    public function __construct()
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $extraData = isset($options['formStepData']) ? $options['formStepData'] : null;
        $pastSteps = $extraData->getPastSteps();

        $builder
            ->add('pastSteps', CollectionType::class, array(
                'entry_type' => TextType::class,
            ))
        ;

        $pastSteps = $pastSteps->all();
        $builder->get('pastSteps')->setData($pastSteps);
    }

    /**
     * @param OptionsResolver $resolver
     */
    final public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'formStepData' =>null,
            'data_class' => ExtraData::class
        ));
    }
}
