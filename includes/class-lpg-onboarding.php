<?php
/**
 * État du parcours guidé de Local Page Generator.
 *
 * @package Local_Page_Generator
 */

defined('ABSPATH') || exit;

class LPG_Onboarding
{
    const CURRENT_STEP_META = '_lpg_current_step';
    const COMPLETED_META    = '_lpg_onboarding_completed';
    const MASCOT_HIDDEN_META = '_lpg_mascot_hidden';

    /**
     * Détermine la dernière étape consultable à partir de l'état réel du workflow.
     *
     * @param bool $has_template   Un modèle Elementor valide est enregistré.
     * @param bool $has_variables Au moins une variable LPG est détectée.
     * @param bool $has_csv       Un CSV valide est encore disponible.
     * @param bool $has_result    Une génération a déjà été exécutée.
     *
     * @return int
     */
    public function get_max_accessible_step(
        $has_template,
        $has_variables,
        $has_csv,
        $has_result
    ) {
        if (!$has_template) {
            return 1;
        }

        if (!$has_variables) {
            return 2;
        }

        if (!$has_csv && !$has_result) {
            return 3;
        }

        return 4;
    }

    /**
     * Résout l'étape demandée sans permettre de contourner les prérequis.
     *
     * @param int $user_id             Identifiant de l'utilisateur.
     * @param int $max_accessible_step Dernière étape autorisée.
     *
     * @return array{step:int,blocked:bool}
     */
    public function resolve_current_step($user_id, $max_accessible_step)
    {
        $user_id             = absint($user_id);
        $max_accessible_step = max(1, min(4, absint($max_accessible_step)));
        $stored_step         = absint(get_user_meta($user_id, self::CURRENT_STEP_META, true));
        $requested_step      = $stored_step ?: 1;

        if (isset($_GET['lpg_step']) && is_scalar($_GET['lpg_step'])) {
            $requested_step     = absint(wp_unslash($_GET['lpg_step']));
        }

        $requested_step = max(1, min(4, $requested_step));
        $step           = min($requested_step, $max_accessible_step);

        update_user_meta($user_id, self::CURRENT_STEP_META, $step);

        return [
            'step'    => $step,
            'blocked' => $requested_step > $max_accessible_step,
        ];
    }

    /**
     * Enregistre directement l'étape courante après une action validée.
     *
     * @param int $user_id Identifiant de l'utilisateur.
     * @param int $step    Étape comprise entre 1 et 4.
     *
     * @return void
     */
    public function set_current_step($user_id, $step)
    {
        update_user_meta(
            absint($user_id),
            self::CURRENT_STEP_META,
            max(1, min(4, absint($step)))
        );
    }

    /**
     * Indique si l'utilisateur a terminé le tutoriel.
     *
     * @param int $user_id Identifiant de l'utilisateur.
     *
     * @return bool
     */
    public function is_completed($user_id)
    {
        return (bool) get_user_meta(absint($user_id), self::COMPLETED_META, true);
    }

    /**
     * Marque le tutoriel comme terminé ou à revoir.
     *
     * @param int  $user_id   Identifiant de l'utilisateur.
     * @param bool $completed État souhaité.
     *
     * @return void
     */
    public function set_completed($user_id, $completed)
    {
        update_user_meta(absint($user_id), self::COMPLETED_META, $completed ? 1 : 0);
    }

    /**
     * Indique si les conseils de la mascotte sont masqués.
     *
     * @param int $user_id Identifiant de l'utilisateur.
     *
     * @return bool
     */
    public function is_mascot_hidden($user_id)
    {
        return (bool) get_user_meta(absint($user_id), self::MASCOT_HIDDEN_META, true);
    }

    /**
     * Modifie la visibilité de la mascotte.
     *
     * @param int  $user_id Identifiant de l'utilisateur.
     * @param bool $hidden  Nouvel état de visibilité.
     *
     * @return void
     */
    public function set_mascot_hidden($user_id, $hidden)
    {
        update_user_meta(absint($user_id), self::MASCOT_HIDDEN_META, $hidden ? 1 : 0);
    }

    /**
     * Supprime seulement les données temporaires du workflow courant.
     *
     * @param int $user_id Identifiant de l'utilisateur.
     *
     * @return void
     */
    public function reset_workflow($user_id)
    {
        $user_id = absint($user_id);

        delete_transient('lpg_csv_preview_' . $user_id);
        delete_transient('lpg_csv_errors_' . $user_id);
        delete_transient('lpg_generation_result_' . $user_id);

        $this->set_current_step($user_id, 1);
        $this->set_completed($user_id, false);
    }

    /**
     * Retourne les véritables images du projet associées au parcours.
     *
     * @return string[]
     */
    public function get_mascot_images()
    {
        return [
            1         => 'step1.png',
            2         => 'sstep2.png',
            3         => 'step3.png',
            4         => 'step4.png',
            'success' => 'succes.png',
        ];
    }
}
