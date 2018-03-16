# FormStep
FormStep permet de générer des formulaires qui s'affichent l'un après l'autre.

## Installation

Installer la dernière version via [Composer] en passant par le fichier composer.json

- Ajouter le repository [https://github.com/dev2choiz/formstep] et la dépendande [dev2choiz/formstep].
```composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/dev2choiz/formstep"
        }
    ],
    "require": {
       "dev2choiz/formstep": "*"
    }
}
```
- Puis en ligne de commande, au niveau du fichier composer.json
```bash
$ composer update dev2choiz/formstep
```

## Utilisation

- Ajoutez une comfig similaire à celle ci-dessous pour créer un formulaire nommé **formtest1**.
Ce formulaire contiendra trois étapes (**myStep1**, **myStep2** et **myStep3**) qui s'enchaineront dans cet ordre.
Ces etapes seront stocker sous forme d'entités dans un objet principal (exemple : MainObject).

```yaml
form_step:
    forms:
        formtest1:
            name: myForms1
            entities:
                myStep1: # nom de la premiere étape
                    entity: MyBundle\Entity\MyForms1\Step1  # entité
                    property: step1 # nom de la propriété dans l'objet MainObject ou sera stocké l'entite MyBundle\Entity\MyForms1\Step1 
                myStep2:
                    entity: MyBundle\Entity\MyForms1\Step2  # entité
                    property: step2 # nom de la propriété dans l'objet MainObject ou sera stocké l'entite MyBundle\Entity\MyForms1\Step2
                myStep3:
                    entity: MyBundle\Entity\MyForms1\Step3
                    property: step3
            steps:
                step1: # nom de la premiere etape
                    type: propertyEntity # type de formulaire : calqué sur l'entité 
                    fields:
                        step1_champText1: # nom arbitraire représentant un champ de l'entité
                            formtype: MyBundle\Form\MyForms1\Step1Type # nom arbitraire representant un champ de l'entité
                            entity: myStep1 # nom de l'étape déclaré dans "form_step.forms.formtest1.entities"
                            property: champ1 # nom du champ dans l'entité qui stockera le champ "step1_champText1" du formulaire
                step2:
                    type: propertyEntity
                    fields:
                        step2_champText1:
                            formtype: MyBundle\Form\MyForms1\Step2Type
                            entity: myStep2
                            property: champ1
                        step2_champText2:
                            formtype: MyBundle\Form\MyForms1\Step2Type
                            entity: myStep2
                            property: champ2
                step3: # nom de la troisième étape
                    type: formtype  # type de formulaire : reprise du formBuilder dans un FormType
                    fields:
                        step3_choix1:
                            formtype: MyBundle\Form\MyForms1\Step3Type
                            entity: myStep3
                            entityProperties: # liste des champs à reprendre du FormType
                                - choix1
```

- Créer l'objet MainObject évoqué ci-dessus et lui ajouter les proprietés step1, step2, step3. 


- Creer chaque formulaire renseigné dans le champ _formtype_ de la config
et les faires extendre la classe \FormStepBundle\Form\AbstractStepType.
Exemple pour l'étape 2 ci-dessous.

```php
<?php
use Symfony\Component\OptionsResolver\OptionsResolver;
class Step2Type extends \FormStepBundle\Form\AbstractStepType
{
    public function preConfigureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Etape4::class,
        ));
    }

    public function settingParamsFields($form)
    {
        $champ1 = [
            'type' => TextType::class,
            'options' => ['auto_initialize' => false]
        ];
        $champ2 = [
            'type' => TextType::class,
            'options' => ['auto_initialize' => false]
        ];

        $this->paramsFields = [
            'champ1' => $champ1,
            'champ2' => $champ2
        ];
    }
}
```

Vous pouvez remarquer qu'il n'y a pas de méthode _"buildForm"_ générant un FormBuilder.
A la place, il faut implementer la methode _"settingParamsFields"_ qui stockera chaque config
de chaque champ dans une propriété _"$this->paramsFields"_ comme on le ferait dans la méthode buildForm.

## Utilisation

- Instanciation du formulaire :
```php
<?php
// Dans un controleur

// recupere le service FormStep
$svcFormStep = $this->get('form_step.form_step');
// Instanciation de l'objet principal
$object = new MainObject();

// création du formulaire positionné à l'étape 
$svcFormStep->setFormName('formtest1')
    ->setObjectData($object)
    ->setFormTypeClass(\FormStepBundle\Form\FormStepType::class);
$svcFormStep->setRequest(Request::createFromGlobals());
$form = $svcFormStep->generateForm("step1");
//...
```

- Il est aussi possible de gérer l'enchainement des étapes en fonction des valeurs recu des precedentes étapes.
Pour cela, FormStep met à disposition trois évenements : 

* PRE_NEXT_STEP
Permet de changer l'etape suivante à afficher quand on fait appel à la methode nextStep du service FormStep.
On ne peut pas mettre fin au formulaire ici.

* PRE_PREVIOUS_STEP
Permet de changer l'etape suivante à afficher quand on fait appel à la methode previousStep du service FormStep.
On ne peut pas mettre fin au formulaire ici.

* PRE_SET_DATA
Permet de modifier les données, les metadatas.
* Mettre fin au formulaire
* Empecher la fin du formulaire (definir la prochaine étape se fera dans un événement PRE_NEXT_STEP)
* Ne permet pas de changer l'étape à afficher apres


##Evénements

Si nous voulons par exemple que à l'étape _"step2"_, lorsque le champ _"champ2"_ vaut _"go back"_ et qu'on clique sur suivant,
on revienne à l'étape _"step1"_ et que sa valeur devienne _"gone back"_ :

- Déclarer un service listener qui écoute l'évènement _PRE_NEXT_STEP_ du formulaire _formtest1_ de l'étape _step2_.
```yaml
services:
    formtest1_listener:
        class: TestFormStepBundle\Listener\FormTest1Listener
        tags:
            - { name: kernel.event_listener, event: 'formstep.preNextStep.formtest1.step2', method: 'step2PreNextStep' }
```
L'alias de l'evenement est composé de (formstep), du nom de l'évènement (preNextStep), du nom du formulaire (formtest1) et du nom de l'étape (step2).


```php
use FormStepBundle\Event\FormStepEvent;

class FormTest1Listener
{
    public function step2PreNextStep(FormStepEvent $event)
    {
        // Ici on peut changer l'étape suivante et les données
        /** @var MainObject $data */
        $data = $event->getData();
        if("go back" === $data->getStep2()->getChamp2()) {
            // modification de la valeur du champ "champ2"
            $data->getStep2()->setChamp2("gone back");
            // changement de l'étape suivante
            $event->setNextStep("step1");
        }
    }
}
```

## Exemple
Un exemple concret d'utilisation : [https://github.com/Dev2Choiz/testformstep]
