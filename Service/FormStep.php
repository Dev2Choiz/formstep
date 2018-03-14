<?php

namespace FormStepBundle\Service;

use FormStepBundle\Event\FormStepEvent;
use FormStepBundle\Form\FormStepDataType;
use FormStepBundle\FormStepEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FormStep
{
    /** @var $configForms */
    public $configForms;

    /** @var  EventDispatcher $dispatcher */
    public $dispatcher;

    /** @var  object $objectData */
    public $objectData;
    /** @var  string $formTypeClass */
    public $formTypeClass;
    /** @var  FormInterface $form */
    public $form;
    /** @var  FormFactory $form */
    public $formFactory;
    /** @var  string $formName */
    public $formName;
    /** @var  string $currentStep */
    public $currentStep;
    /** @var  string $finalStepName */
    public $finalStepName;
    /** @var  boolean $finalStep */
    public $finalStep;
    /** @var  MetaData $metadata */
    public $metadata;
    /** @var  Request $request */
    public $request;
    /** @var  string $encType */
    public $encType;
    /** @var  ExtraData $extraData */
    public $extraData;

    public function __construct()
    {
        $this->finalStep = false;
    }

    /**
     * @param string $currentStep
     * @param bool $populateObjectWithRequest
     *
     * @return Form
     */
    public function generateForm($currentStep, $populateObjectWithRequest = true)
    {
        //@todo method pour obtenir le enctype="multipart/form-data"

        $this->currentStep = $currentStep;
        $this->checkMetadata();
        $this->metadata->changeActiveStep($this->currentStep);
        $this->setFinalStep($this->metadata->isFinalStep($this->currentStep));

        // Recupération de l'objet via la requete http pour populer le formulaire
        if ($populateObjectWithRequest) {
            $form = $this->createForm();
            $form->handleRequest($this->request);
            unset($form);
        }

        $this->preSetData();

        return $this->createForm();
    }

    public function changeStep($target = null)
    {
        // recupere l'etape à activer selon la config
        $oldStep = $this->currentStep;
        $nextStep = $this->getNextStep($oldStep, $target);
        $this->checkMetadata();

        if (in_array($target, ['next', 'previous'], true)) {
            $nextStep = $this->prePreviousOrNextStep($target, $oldStep, $nextStep);
        }

        $this->metadata->changeActiveStep($nextStep);
        $this->currentStep = $nextStep;

        return $this->createForm();
    }

    public function nextStep()
    {
        $this->changeStep('next');
    }

    public function PreviousStep()
    {
        $this->changeStep('previous');
    }

    public function createForm($options = [])
    {
        $allOptions = array_merge($options, array('formStep_metadata' => $this->metadata));
        $form = $this->formFactory->createNamed($this->getFormName(), $this->formTypeClass, $this->objectData, $allOptions);

        $this->extractExtraData($form);

        $this->extraData->addPastStep($this->currentStep);

        $form->add('formStepData', FormStepDataType::class, array(
            'mapped' => false,
            'formStepData' => $this->extraData
        ));


        $viewForm = $form->createView();
        $this->encType = $viewForm->vars['multipart'] ? 'multipart/form-data' : '';

        return $form;
    }

    public function extractExtraData($form)
    {
        $request = ('POST' === $form->getConfig()->getMethod()) ? $this->request->request : $this->request->query;
        $data = $request->get($this->getFormName());
        $extraData = isset($data['formStepData']) ? $data['formStepData'] : [];

        $this->extraData = new ExtraData();
        $this->extraData->setAllData($extraData);

        return $this;
    }


    public function getNextStep($currentStep, $target = null)
    {
        if ('next' === $target) {
            return $this->getNextStepName($currentStep);
        } else if ('previous' === $target) {
            return $this->getPreviousStepName($currentStep);
        } else {
            return $currentStep;
        }
    }

    public function isPartiallyValid(Form $forms)
    {
        $atttributes = $forms->getConfig()->getAttribute('formstep');
        $active  = isset($atttributes['active']) ? $atttributes['active'] : false;

        if (0 === $forms->count() && (null === $active || false === $active)) {
            return true;
        } else if (0 === $forms->count() && true === $active) {
            return $forms->isValid();
        }

        $valid = true;
        foreach ($forms as $key => $form) {
            if (!$form instanceof Form) {
                $valid = $valid && true;
            } else {
                $valid = $valid && $this->isPartiallyValid($form);
            }
        }
        return $valid;
    }

    public function isValid(Form $forms)
    {

        if (0 === $forms->count()) {
            return $forms->isValid();
        }

        $valid = true;
        foreach ($forms as $key => $form) {
            if (!$form instanceof Form) {
                $valid = $valid && true;
            } else {
                $valid = $valid && $this->isValid($form);
            }
        }
        return $valid;
    }

    public function isValidByStep(Form $forms, array $steps)
    {
        $valid = true;
        foreach ($forms as $key => $form) {
            if (!$form instanceof Form) {
                $valid = $valid && true;
            } else if (in_array($form->getName(), $steps, true)) {
                $valid = $valid && $this->isValid($form);
            } else {
                $valid = $valid && true;
            }
        }
        return $valid;
    }

    public function pastStepsAreValid(Form $forms)
    {
        $steps = $this->extraData->getPastSteps()->all();
        return $this->isValidByStep($forms, $steps);
    }

    public function getConfigForm()
    {
        return $this->configForms[$this->formName];
    }

    public function getListStep($key = null)
    {
        $list = array_keys($this->getConfigForm()['steps']);
        $result = (null === $key) ? $list : $list[$key];
        return $result;
    }

    public function getNextStepName($nameStep)
    {
        $list = $this->getListStep();
        $key = array_search($nameStep, $list);
        if ($key < count($list) - 1) {
            $key++;
        }
        return $this->getListStep($key);
    }

    public function getPreviousStepName($nameStep)
    {
        $list = $this->getListStep();
        $key = array_search($nameStep, $list);
        $key = $key > 0 ? --$key : $key;
        return $this->getListStep($key);
    }



    public function generateMetaData()
    {
        $options = [
            'auto_initialize' => false,
        ];

        $configForm = $this->getConfigForm();
        $accessor   = PropertyAccess::createPropertyAccessor();
        $metadata   = new MetaData();
        $allForms   = $allData = $result = [];

        $configEntities = $configForm['entities'];
        foreach ($configForm['steps'] as $stepName => $step) {
            foreach ($step['fields'] as $fieldName => $fieldData) {
                $finalStep = isset($step['finalStep']) ?: false;

                if ($finalStep) {
                    $this->setFinalStepName($stepName);
                }

                $attributesView = [
                    'type' => $step['type'],
                    'step' => $stepName,
                    'auto_initialize' => false,
                    'finalStep' => $finalStep
                ];

                $formTypeClass = $fieldData['formtype'];
                $configEntitie = $configEntities[$fieldData['entity']];
                $propertyName = $configEntitie['property'];

                // recuperation du subObjectData
                $entity = null;
                if (class_exists($configEntitie['entity'])) {
                    if (is_object($this->objectData)) {
                        if (property_exists(get_class($this->objectData), $propertyName)) {
                            $entity = $accessor->getValue($this->objectData, $propertyName);
                        }
                    } elseif (is_array($this->objectData)) {
                        $entity = isset($this->objectData[$propertyName]) ? $this->objectData[$propertyName] : new $fieldData['class'];
                    }
                }

                // creation du formulaire
                if ('formtype' === $step['type']) {
                    if (!isset($allForms[$formTypeClass][$propertyName])) {
                        $subForm = $this->formFactory->createNamedBuilder($propertyName, $fieldData['formtype'], null, $options);
                        $allForms[$formTypeClass][$propertyName] = $subForm;

                    }
                    $subForm = $allForms[$formTypeClass][$propertyName];
                } elseif ('propertyEntity' === $step['type']) {
                    if (!isset($allForms[$formTypeClass][$propertyName])) {
                        $subForm = $this->formFactory->createNamedBuilder($propertyName, $fieldData['formtype'], null, $options);
                        $allForms[$formTypeClass][$propertyName] = $subForm;
                    }
                }

                // ajout au resultat
                if ('formtype' === $step['type']) {
                    $allData[$configEntitie['entity']] = $entity;
                    $result [$stepName] ['type'] = 'formtype';
                    $result [$stepName] ['finalStep'] = $finalStep;
                    $result [$stepName] ['fields'] [] = [
                        'formType' => $fieldData['formtype'],
                        'propertyGlobalObject' => $propertyName,
                        'entityProperties' => $fieldData['entityProperties'],
                        'entity' => $configEntitie['entity'],
                        'attributesView' => $attributesView,
                        'options' => $options,
                        'step' => $stepName
                    ];
                    unset($subForm);
                } elseif ('propertyEntity' === $step['type']) {
                    $allData[$configEntitie['entity']] = $entity;
                    $result [$stepName] ['type'] = 'propertyEntity';
                    $result [$stepName] ['finalStep'] = $finalStep;
                    $result [$stepName] ['fields'] [] = [
                        'formType' => $fieldData['formtype'],
                        'entity' => $fieldData['entity'],
                        'options' => $options,
                        'propertyEntity' => $fieldData['property'],
                        'propertyGlobalObject' => $propertyName,
                        'step' => $stepName,
                        'attributesView' => $attributesView
                    ];
                    unset($subForm);
                }
            }
        }

        $metadata->setFormName($configForm['name']);
        $metadata->setMetadata($result);
        $metadata->setAllForms($allForms);
        $metadata->setAllData($allData);
        return $metadata;
    }


    public function preSetData()
    {
        $eventName = FormStepEvents::PRE_SET_DATA . '.' . $this->getFormName() . '.' . $this->currentStep;
        if ($this->dispatcher->hasListeners($eventName)) {
            $event = new FormStepEvent();
            $event->setCurrentStep($this->currentStep);
            $event->setFormName($this->getFormName());
            $event->setdata($this->getObjectData());
            $event->setMetadata($this->metadata);
            $event->setFinalStep($this->finalStep);
            $this->dispatcher->dispatch($eventName, $event);
            $this->setFinalStep($event->isFinalStep());
        }
        return $this;
    }


    public function prePreviousOrNextStep($target, $oldStep, $nextStep)
    {
        $ref = new \ReflectionClass(FormStepEvents::class);
        $constantName = 'PRE_' . strtoupper($target) . '_STEP';
        $prefixEventName = $ref->getConstant($constantName);
        $eventName = $prefixEventName . '.' . $this->getFormName() . '.' . $oldStep;

        $event = new FormStepEvent();
        $event->setCurrentStep($oldStep);
        $event->setNextStep($nextStep);
        $event->setFormName($this->getFormName());
        $event->setdata($this->getObjectData());
        $event->setMetadata($this->metadata);

        if ($this->dispatcher->hasListeners($eventName)) {
            $this->dispatcher->dispatch($eventName, $event);
        }

        return $event->getNextStep();
    }


    /**
     * @return array
     */
    public function getConfigForms()
    {
        return $this->configForms;
    }

    /**
     * @param array $config
     * @return FormStep
     */
    public function setConfigForms($configForms)
    {
        $this->configForms = $configForms;
        return $this;
    }

    /**
     * @return object
     */
    public function getObjectData()
    {
        return $this->objectData;
    }

    /**
     * @param object $objectData
     * @return FormStep
     */
    public function setObjectData($objectData)
    {
        $this->objectData = $objectData;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormTypeClass()
    {
        return $this->formTypeClass;
    }

    /**
     * @param string $formTypeClass
     * @return FormStep
     */
    public function setFormTypeClass($formTypeClass)
    {
        $this->formTypeClass = $formTypeClass;
        return $this;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param FormInterface $form
     * @return FormStep
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * @param FormFactory $formFactory
     * @return FormStep
     */
    public function setFormFactory($formFactory)
    {
        $this->formFactory = $formFactory;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * @param string $formName
     * @return FormStep
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * @param string $currentStep
     * @return FormStep
     */
    public function setCurrentStep($currentStep)
    {
        $this->currentStep = $currentStep;
        return $this;
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param EventDispatcher $dispatcher
     * @return FormStep
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isFinalStep()
    {
        return $this->finalStep;
    }

    /**
     * @param bool $finalStep
     * @return FormStep
     */
    public function setFinalStep($finalStep)
    {
        $this->finalStep = $finalStep;
        return $this;
    }

    /**
     * @return string
     */
    public function getFinalStepName()
    {
        return $this->finalStepName;
    }

    /**
     * @param string $finalStepName
     * @return FormStep
     */
    public function setFinalStepName($finalStepName)
    {
        $this->finalStepName = $finalStepName;
        return $this;
    }

    /**
     * @return MetaData
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    public function checkMetadata()
    {
        $this->metadata = $this->metadata ?: $this->generateMetaData();
        return $this;
    }

    /**
     * @param MetaData $metadata
     * @return FormStep
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return FormStep
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncType()
    {
        return $this->encType;
    }

    /**
     * @param string $encType
     * @return FormStep
     */
    public function setEncType($encType)
    {
        $this->encType = $encType;
        return $this;
    }

    /**
     * @return OptionsResolver
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * @param OptionsResolver $extraData
     * @return FormStep
     */
    public function setExtraData($extraData)
    {
        $this->extraData = $extraData;
        return $this;
    }
}
