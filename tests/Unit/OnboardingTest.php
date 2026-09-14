<?php
/**
 * Tests de progression et de persistance de l'onboarding.
 */

class LPG_Onboarding_Test extends WP_UnitTestCase
{
    /**
     * Identifiant de l'utilisateur de test.
     *
     * @var int
     */
    private $user_id;

    public function set_up()
    {
        parent::set_up();

        $this->user_id = self::factory()->user->create(
            ['role' => 'administrator']
        );

        unset($_GET['lpg_step']);
    }

    public function tear_down()
    {
        unset($_GET['lpg_step']);
        parent::tear_down();
    }

    /**
     * @dataProvider accessible_step_provider
     */
    public function test_should_compute_accessible_step_from_real_prerequisites(
        $has_template,
        $has_variables,
        $has_csv,
        $has_result,
        $expected
    ) {
        $onboarding = new LPG_Onboarding();

        $this->assertSame(
            $expected,
            $onboarding->get_max_accessible_step(
                $has_template,
                $has_variables,
                $has_csv,
                $has_result
            )
        );
    }

    public function accessible_step_provider()
    {
        return [
            'no template'       => [false, false, false, false, 1],
            'template only'     => [true, false, false, false, 2],
            'variables ready'   => [true, true, false, false, 3],
            'csv ready'         => [true, true, true, false, 4],
            'generation result' => [true, true, false, true, 4],
        ];
    }

    public function test_should_prevent_url_from_bypassing_prerequisites()
    {
        $_GET['lpg_step'] = '99';
        $onboarding       = new LPG_Onboarding();

        $resolved = $onboarding->resolve_current_step($this->user_id, 2);

        $this->assertSame(2, $resolved['step']);
        $this->assertTrue($resolved['blocked']);
        $this->assertSame(
            2,
            (int) get_user_meta($this->user_id, LPG_Onboarding::CURRENT_STEP_META, true)
        );
    }

    public function test_reset_should_only_clear_workflow_transients()
    {
        $onboarding = new LPG_Onboarding();

        update_option('lpg_template_id', 123);
        set_transient('lpg_csv_preview_' . $this->user_id, ['rows' => [['slug' => 'paris']]]);
        set_transient('lpg_csv_errors_' . $this->user_id, ['Erreur']);
        set_transient('lpg_generation_result_' . $this->user_id, ['generated' => [1]]);
        $onboarding->set_current_step($this->user_id, 4);
        $onboarding->set_completed($this->user_id, true);
        $onboarding->set_mascot_hidden($this->user_id, true);

        $onboarding->reset_workflow($this->user_id);

        $this->assertFalse(get_transient('lpg_csv_preview_' . $this->user_id));
        $this->assertFalse(get_transient('lpg_csv_errors_' . $this->user_id));
        $this->assertFalse(get_transient('lpg_generation_result_' . $this->user_id));
        $this->assertSame(123, (int) get_option('lpg_template_id'));
        $this->assertSame(1, (int) get_user_meta($this->user_id, LPG_Onboarding::CURRENT_STEP_META, true));
        $this->assertFalse($onboarding->is_completed($this->user_id));
        $this->assertTrue($onboarding->is_mascot_hidden($this->user_id));
    }
}

