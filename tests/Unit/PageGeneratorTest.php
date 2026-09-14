<?php
/**
 * Tests de génération des pages WordPress.
 */

class LPG_Page_Generator_Test extends WP_UnitTestCase
{
    public function set_up()
    {
        parent::set_up();

        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
    }

    public function test_should_create_pages_and_copy_elementor_metadata()
    {
        $template_id = $this->create_elementor_template();
        $rows = [
            [
                'page_title' => 'Plombier Paris',
                'slug'       => 'plombier-paris',
                'ville'      => 'Paris',
            ],
            [
                'page_title' => 'Plombier Lyon',
                'slug'       => 'plombier-lyon',
                'ville'      => 'Lyon',
            ],
        ];

        $result = (new LPG_Page_Generator())->generate(
            $template_id,
            $rows,
            ['ville'],
            'draft'
        );

        $this->assertSame([], $result['errors']);
        $this->assertCount(2, $result['generated']);

        $paris_page = get_page_by_path('plombier-paris', OBJECT, 'page');

        $this->assertInstanceOf(WP_Post::class, $paris_page);
        $this->assertSame('draft', $paris_page->post_status);
        $this->assertSame('Paris', get_post_meta($paris_page->ID, '_lpg_var_ville', true));
        $this->assertSame('1', (string) get_post_meta($paris_page->ID, '_lpg_generated', true));
        $this->assertSame($template_id, (int) get_post_meta($paris_page->ID, '_lpg_template_id', true));
        $this->assertSame('builder', get_post_meta($paris_page->ID, '_elementor_edit_mode', true));
        $this->assertSame(
            ['hide_title' => 'yes'],
            get_post_meta($paris_page->ID, '_elementor_page_settings', true)
        );
    }

    public function test_should_not_create_any_page_when_one_slug_is_already_used()
    {
        $template_id = $this->create_elementor_template();

        self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'slug-existant',
            ]
        );

        $result = (new LPG_Page_Generator())->generate(
            $template_id,
            [
                ['page_title' => 'Nouvelle page', 'slug' => 'nouvelle-page'],
                ['page_title' => 'Conflit', 'slug' => 'slug-existant'],
            ],
            [],
            'draft'
        );

        $this->assertNotEmpty($result['errors']);
        $this->assertSame([], $result['generated']);
        $this->assertNull(get_page_by_path('nouvelle-page', OBJECT, 'page'));
    }

    /**
     * Crée un modèle Elementor minimal.
     *
     * @return int
     */
    private function create_elementor_template()
    {
        $template_id = self::factory()->post->create(
            [
                'post_type'    => 'elementor_library',
                'post_status'  => 'publish',
                'post_title'   => 'Modèle LPG',
                'post_content' => 'Contenu de secours',
            ]
        );

        update_post_meta($template_id, '_elementor_data', '[{"id":"lpg-test","settings":{}}]');
        update_post_meta($template_id, '_elementor_page_settings', ['hide_title' => 'yes']);
        update_post_meta($template_id, '_wp_page_template', 'elementor_canvas');

        return $template_id;
    }
}

