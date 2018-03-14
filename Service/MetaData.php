<?php

namespace FormStepBundle\Service;

class MetaData
{
    /** @var  array $metadata */
    public $metadata;
    /** @var  array $allForms */
    public $allForms;
    /** @var  array $allData */
    public $allData;
    /** @var  string $formName */
    public $formName;

    public function changeActiveStep($step)
    {
        foreach ($this->metadata as &$dataStep) {
            foreach ($dataStep['fields'] as &$field) {
                $field['attributesView']['active'] = ($field['step'] === $step);
                $field['attributesView']['currentStep'] = $step;
            }

            unset($field);
        }
    }

    public function isFinalStep($step)
    {
        if (! isset($this->metadata[$step])) {
            return false;
        }

        return isset($this->metadata[$step]['finalStep']) ? $this->metadata[$step]['finalStep'] : false;
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
     * @return MetaData
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllForms()
    {
        return $this->allForms;
    }

    /**
     * @param array $allForms
     * @return MetaData
     */
    public function setAllForms($allForms)
    {
        $this->allForms = $allForms;

        return $this;
    }

    /**
     * @return array
     */
    public function getAllData()
    {
        return $this->allData;
    }

    /**
     * @param array $allData
     * @return MetaData
     */
    public function setAllData($allData)
    {
        $this->allData = $allData;

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
     * @return MetaData
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;

        return $this;
    }
}
