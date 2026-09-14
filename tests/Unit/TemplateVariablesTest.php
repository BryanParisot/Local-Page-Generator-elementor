<?php
/**
 * Tests de détection des variables Elementor.
 */

class LPG_Template_Variables_Test extends WP_UnitTestCase
{
    public function test_should_extract_unique_sorted_lpg_variables()
    {
        $template_id = self::factory()->post->create(
            [
                'post_type'   => 'elementor_library',
                'post_status' => 'publish',
            ]
        );
        $city_tag = '[elementor-tag name="lpg-variable" settings="%7B%22variable_key%22%3A%22ville%22%7D"]';
        $service_tag = '[elementor-tag name="lpg-variable" settings="%7B%22variable_key%22%3A%22service%22%7D"]';
        $elementor_data = [
            [
                'settings' => [
                    'title'       => $city_tag,
                    'description' => $service_tag,
                    'duplicate'   => $city_tag,
                ],
            ],
        ];

        update_post_meta(
            $template_id,
            '_elementor_data',
            wp_slash(wp_json_encode($elementor_data))
        );

        $variables = (new LPG_Template_Variables())->get_variables($template_id);

        $this->assertSame(['service', 'ville'], $variables);
    }

    public function test_should_ignore_non_elementor_posts()
    {
        $post_id = self::factory()->post->create(['post_type' => 'page']);

        update_post_meta(
            $post_id,
            '_elementor_data',
            '[elementor-tag name="lpg-variable" settings="%7B%22variable_key%22%3A%22ville%22%7D"]'
        );

        $this->assertSame([], (new LPG_Template_Variables())->get_variables($post_id));
    }
}
