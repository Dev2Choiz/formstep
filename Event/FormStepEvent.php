<?php

namespace FormStepBundle\Event;

use FormStepBundle\Service\MetaData;
use Symfony\Component\EventDispatcher\Event;

final class FormStepEvent  extends Event
{

    /** @var  MetaData $metadata */
    protected $metadata;
    /** @var  object $data */
    protected $data;
    /** @var  string $currentStep */
    protected $currentStep;
    /** @var  string $nextStep */
    protected $nextStep;
    /** @var  string $formName */
    protected $formName;
    /** @var  boolean $finalStep */
    protected $finalStep;

    public function __construct()
    {
        $this->finalStep = false;
    }

    /**
     * @return MetaData
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param MetaData $metadata
     * @return FormStepEvent
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param object $data
     * @return FormStepEvent
     */
    public function setData($data)
    {
        $this->data = $data;
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
     * @return FormStepEvent
     */
    public function setCurrentStep($currentStep)
    {
        $this->currentStep = $currentStep;
        return $this;
    }

    /**
     * @return string
     */
    public function getNextStep()
    {
        return $this->nextStep;
    }

    /**
     * @param string $nextStep
     * @return FormStepEvent
     */
    public function setNextStep($nextStep)
    {
        $this->nextStep = $nextStep;
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
     * @return FormStepEvent
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFinalStep()
    {
        return $this->finalStep;
    }

    /**
     * @param bool $finalStep
     * @return FormStepEvent
     */
    public function setFinalStep($finalStep)
    {
        $this->finalStep = $finalStep;
        return $this;
    }
}

