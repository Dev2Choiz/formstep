<?php

namespace FormStepBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractStepType extends AbstractType
{
    /** @var  array $paramsFields */
    public $paramsFields = null;
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->addAttributeFormStep($view, $form);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->preConfigureOptions($resolver);
        $resolver->setDefaults(array(
            'formStep_metadata' => null,
        ));
    }

    abstract public function preConfigureOptions(OptionsResolver $resolver);

    public function getBlockPrefix()
    {
        $name = static::class;
        $name = explode('\\', $name);
        $name = end($name);
        $name = str_replace('Type', '', $name);
        $name = lcfirst($name );
        return $name;
    }

    public function getParamsField($field, $paramName, $builder)
    {
        if (null === $this->paramsFields) {
            $this->settingParamsFields($builder);
        }

        if (! isset($this->paramsFields[$field]) || ! isset($this->paramsFields[$field][$paramName])) {
            // le cas ou c'est pas défini
            switch ($paramName) {
                case 'type':
                    return TextType::class;
                case 'options':
                    return ['auto_initialize' => false];
                    break;
                default:
                    throw new \Symfony\Component\Form\Exception\InvalidArgumentException("Le parametre \"$paramName\" du champ \"$field\" n'existe pas.");
                    break;
            }
        }

        return $this->paramsFields[$field][$paramName];
    }

    /**
     * @return mixed
     *
     * Parametrage de chaque champs dans $paramsFields
     */
    public function settingParamsFields($form)
    {
        // la variable $form n'est pas utilisé c'est normal
        // @todo ici on doit juste avoir la signature de la methode ==> initialisation de paramsFields se fera ailleurs
        $this->paramsFields = [];
    }

    private function addAttributeFormStep(FormView $view, FormInterface $form)
    {
        if ($form->getConfig()->hasAttribute('formstep')) {
            $params = $form->getConfig()->getAttribute('formstep');
            $view->vars['attr'] = $params;
        } else {
            foreach ($form->all() as $subForm) {
                if ($subForm->getConfig()->hasAttribute('formstep')) {
                    $params = $subForm->getConfig()->getAttribute('formstep');
                    $view->vars['attr']['formstep'][$subForm->getName()] = $params;
                }
                //@todo rendre recurcif si le niveau d'imbrication augmente
            }
        }
    }

}
