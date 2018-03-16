<?php

namespace FormStepBundle;

/**
 * Contient des evenements qui permettent de modifier les données, le prochain etape à afficher,
 * empecher la fin du formulaire tout en specifiant la prochaine etape à afficher
 *
 */
final class FormStepEvents
{

    /**
     * PRE_NEXT_STEP
     * De modifier les données
     * On ne peut pas mettre fin au formulaire ici
     * Permet de changer l'etape suivante à afficher quand on fait appel à la methode nextStep du service FormStep
     *
     */
    const PRE_NEXT_STEP = 'formstep.preNextStep';

    /**
     * PRE_PREVIOUS_STEP
     * De modifier les données
     * On ne peut pas mettre fin au formulaire ici
     * Permet de changer l'etape suivante à afficher quand on fait appel à la methode previousStep du service FormStep
     *
     */
    const PRE_PREVIOUS_STEP = 'formstep.prePreviousStep';


    /**
     * PRE_SET_DATA
     *
     * Permet de modifier les données, les metadatas
     * Mettre fin au formulaire
     * Empecher la fin du formulaire (definir la prochaine étape se fera dans un événement PRE_NEXT_STEP)
     * Ne permet pas de changer l'etape à afficher apres
     *
     */
    const PRE_SET_DATA = 'formstep.preSetData';
}
