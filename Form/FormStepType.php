<?php

namespace FormStepBundle\Form;

use FormStepBundle\Service\MetaData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Validation;

class FormStepType extends AbstractType
{
    public $configField;
    /** @var FormFactory $formFactory */
    public $formFactory;
    /** @var  array $configYaml */
    public $configYaml;

    /** @var  array $metadata */
    public $metadata;
    /** @var  \Symfony\Component\Validator\Validator\RecursiveValidator $validator */
    public $validator;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->formFactory = $builder->getFormFactory();
        $this->metadata = $options['formStep_metadata'];

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($builder, $options) {
            $form = $event->getForm();
            $this->buildFields($form, $this->metadata);
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    final public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('formStep_metadata');
        $resolver->setDefaults(array(
            'formStep_metadata' => null,
            'cascade_validation' => true,
            'allow_extra_fields' => true,
        ));
    }

    public function buildFields($form, MetaData $metadata)
    {
        $allForms = $metadata->getAllForms();
        foreach ($metadata->getMetadata() as $entityClass => $fields) {
            if ('formtype' === $fields['type']) {
                foreach ($fields['fields'] as $key => $field) {
                    $properties = $field['entityProperties'];
                    /** @var Form $subForm */
                    $subForm = $allForms[$field['formType']][$field['propertyGlobalObject']];

                    foreach ($properties as $property) {
                        /** @var AbstractStepType $formType */
                        $formType      = new $field['formType'];
                        $formTypeClass = $formType->getParamsField($property, 'type', $form);
                        $options       = $formType->getParamsField($property, 'options', $form);

                        $subForm->add($property, $formTypeClass, $options);
                        $subSubForm = $subForm->get($property);
                        $subSubForm->setAttribute('formstep', $field['attributesView']);
                        unset($subSubForm);
                    }
                    unset($subForm);
                }

            } elseif ('propertyEntity' === $fields['type']) {

                foreach ($fields['fields'] as $key => $field) {
                    $subForm       = $allForms[$field['formType']][$field['entity']];
                    $formType      = new $field['formType'];
                    $formTypeClass = $formType->getParamsField($field['propertyEntity'], 'type', $form);
                    $options       = $formType->getParamsField($field['propertyEntity'], 'options', $form);
                    $options       = array_merge($options, [
                        'auto_initialize' => false,
                        'label' => $field['propertyEntity'],
                    ]);
                    $dataClass     = null;

                    $subForm->add($field['propertyEntity'], $formTypeClass, $options);
                    $subSubForm = $subForm->get($field['propertyEntity']);
                    $subSubForm->setAttribute('formstep', $field['attributesView']);

                    unset($subForm, $subSubForm);
                }
            }
        }

        foreach ($allForms as $class => &$subForms) {
            foreach ($subForms as $property => &$subForm) {
                $subForm = $subForm->getForm();
                $form->add($subForm);
                unset($subForm);
            }
            unset($subForms);
        }
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     * @return FormStepType
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

}
