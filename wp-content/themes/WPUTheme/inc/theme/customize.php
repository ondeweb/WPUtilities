<?php

/*
Name: Theme Customize
Version: 0.4
*/

class WPUCustomizeTheme
{
    function __construct() {
        $this->sections = array(
            'global' => array(
                'name' => 'Global',
                'priority' => 199
            ) ,
            'header' => array(
                'name' => 'Header',
                'priority' => 200
            )
        );
        $this->settings = array(
            'wpu_link_color' => array(
                'label' => __('Link Color') ,
                'default' => '#6699CC',
                'section' => 'global',
                'css_selector' => 'a',
                'css_property' => 'color'
            ) ,
            'wpu_link_color_hover' => array(
                'label' => __('Link Color :hover') ,
                'default' => '#336699',
                'section' => 'global',
                'css_selector' => 'a:hover',
                'css_property' => 'color'
            ) ,
            'wpu_background_color' => array(
                'label' => __('Background Color') ,
                'default' => '#FFFFFF',
                'section' => 'global',
                'css_selector' => 'body',
                'css_property' => 'background-color'
            ) ,
            'wpu_background_image' => array(
                'label' => __('Background image site') ,
                'default' => '',
                'section' => 'global',
                'css_selector' => 'body',
                'css_property' => 'background-image'
            ) ,
            'wpu_text_title_align' => array(
                'label' => __('Title align') ,
                'default' => 'left',
                'section' => 'header',
                'css_selector' => '.main-title',
                'css_property' => 'text-align'
            ) ,
            'wpu_text_title_size' => array(
                'label' => __('Title size') ,
                'default' => '25px',
                'section' => 'header',
                'css_selector' => '.main-title',
                'css_property' => 'font-size'
            ) ,
        );

        // Init some values
        $upload_dir = wp_upload_dir();
        $this->up_dir = $upload_dir['basedir'] . '/wputheme-cache';
        $this->up_url = $upload_dir['baseurl'] . '/wputheme-cache';
        $this->cache_file = $this->up_dir . '/theme-customizer.js';
        $this->cache_file_url = $this->up_url . '/theme-customizer.js';
        $this->cached_code_version = get_option('wputh_customize_theme_version');

        // Set events
        add_action('wp_head', array(&$this,
            'display_mod'
        ));
        add_action('customize_register', array(&$this,
            'wpu_customize_theme'
        ));
        add_action('customize_preview_init', array(&$this,
            'customizer_live_preview'
        ));
        add_action('current_screen', array(&$this,
            'regenerate_js_file'
        ));
    }

    // Function regenerate_js_file
    function regenerate_js_file() {
        $screen = get_current_screen();
        if (!isset($screen->base) || $screen->base != 'customize') {
            return;
        }

        // Check cache directory
        if (!is_dir($this->up_dir)) {
            @mkdir($this->up_dir, 0777);
            @chmod($this->up_dir, 0777);
        }

        // Check code version
        $str_sections = serialize($this->sections);
        $str_settings = serialize($this->settings);
        $code_version = md5($str_settings . $str_sections);

        if ($this->cached_code_version !== $code_version) {
            $content_cache = "(function($) {\n";
            foreach ($this->settings as $id => $setting) {
                $tmp_id = 'wputheme_' . $id;
                $property = trim(esc_attr($setting['css_property']));
                $selector = trim(esc_attr($setting['css_selector']));
                if (!empty($property) && !empty($selector)) {
                    $content_cache.= "wp.customize('" . $tmp_id . "', function(value) {
                    value.bind(function(newval) {
                        var selector = '" . $selector . "',
                            property = '" . $property . "';
                        if(property == 'background-image'){
                            newval = 'url('+newval+')';
                        }
                        $(selector).css(property, newval);
                    });
                });
                ";
                }
            }
            $content_cache.= "})(jQuery);\n";

            // Regenerate JS file
            file_put_contents($this->cache_file, $content_cache);

            // Set code version
            $this->cached_code_version = $code_version;
            update_option('wputh_customize_theme_version', $code_version);
        }
    }

    // Display theme modifications in front-end
    function display_mod() {
        $content = '';
        foreach ($this->settings as $id => $setting) {
            $tmp_id = 'wputheme_' . $id;
            $mod = strtolower(get_theme_mod($tmp_id));
            $def = strtolower($setting['default']);
            $property = trim(esc_attr($setting['css_property']));
            $selector = trim(esc_attr($setting['css_selector']));
            if (!empty($mod) && !empty($property) && !empty($selector) && $mod != $def) {

                if ($property == 'background-image') {
                    $mod = 'url(' . $mod . ')';
                }

                $content.= $selector . '{' . $property . ':' . $mod . '}';
            }
        }
        if (!empty($content)) {
            echo '<style>' . $content . '</style>';
        }
    }

    // Register functions
    function wpu_customize_theme($wp_customize) {

        foreach ($this->sections as $id => $section) {
            $wp_customize->add_section('wputh_' . $id, array(
                'title' => $section['name'],
                'priority' => isset($section['priority']) ? $section['priority'] : 200
            ));
        }

        foreach ($this->settings as $id => $setting) {
            $tmp_id = 'wputh_conf_' . $id;
            if (!isset($setting['css_property'])) {
                $setting['css_property'] = '';
            }
            $detail_setting = array(
                'default' => $setting['default'],
                'type' => 'theme_mod',
                'capability' => 'edit_theme_options',
                'transport' => 'postMessage',
            );
            $detail_control = array(
                'label' => $setting['label'],
                'section' => 'wputh_' . $setting['section'],
                'settings' => 'wputheme_' . $id,
                'priority' => 10,
            );

            /* Special types */

            /* text-align */
            if ($setting['css_property'] == 'text-align') {
                $detail_control['type'] = 'radio';
                $detail_control['choices'] = array(
                    'left' => 'left',
                    'center' => 'center',
                    'justify' => 'justify',
                    'right' => 'right',
                );
            }

            /* Font size */
            if ($setting['css_property'] == 'font-size') {
                $detail_control['type'] = 'select';
                $detail_control['choices'] = array();
                $font_sizes = array();
                for ($i = 8; $i <= 70; $i++) {
                    $detail_control['choices'][$i . 'px'] = $i . 'px';
                }
            }

            $wp_customize->add_setting('wputheme_' . $id, $detail_setting);
            switch ($setting['css_property']) {
                case 'color':
                case 'background-color':
                    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'wputheme_' . $id, $detail_control));
                    break;

                case 'background-image':
                    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'wputheme_' . $id, $detail_control));
                    break;

                default:
                    $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'wputheme_' . $id, $detail_control));
            }
        }

        return $wp_customize;
    }

    // Enable live preview in admin
    function customizer_live_preview() {
        wp_enqueue_script('wputheme-themecustomizer', $this->cache_file_url, array(
            'jquery',
            'customize-preview'
        ) , $this->cached_code_version, true);
    }
}

$WPUCustomizeTheme = new WPUCustomizeTheme();
