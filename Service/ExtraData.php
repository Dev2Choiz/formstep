<?php

namespace FormStepBundle\Service;

use Symfony\Component\HttpFoundation\ParameterBag;

class ExtraData
{
    /** @var  ParameterBag $pastSteps */
    public $pastSteps;

    public function __construct()
    {
        $this->pastSteps = new ParameterBag();
    }


    public function setAllData(array $data)
    {
        $this->pastSteps->add(isset($data['pastSteps']) ? $data['pastSteps'] : []);
    }

    public function addPastStep($step)
    {
        $all = $this->pastSteps->all();
        $lastStep = end($all);
        if ($lastStep === $step) {
            return;
        }

        $all[] = $step;
        $this->pastSteps->replace($all);
    }

    /**
     * @return ParameterBag
     */
    public function getPastSteps()
    {
        return $this->pastSteps;
    }

    /**
     * @param ParameterBag $pastSteps
     * @return Extradata
     */
    public function setPastSteps($pastSteps)
    {
        $this->pastSteps = $pastSteps;
        return $this;
    }



}
