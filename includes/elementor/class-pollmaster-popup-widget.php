<?php
/**
 * PollMaster Popup Widget for Elementor
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PollMaster Popup Widget Class
 */
class PollMaster_Popup_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'pollmaster-popup';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Poll Popup', 'pollmaster');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-popup';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['pollmaster'];
    }
    
    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['poll', 'popup', 'modal', 'vote', 'pollmaster'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Popup Settings', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Poll Selection
        $this->add_control(
            'poll_id',
            [
                'label' => __('Select Poll', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_polls_list(),
                'default' => 'latest',
                'description' => __('Choose which poll to display in popup', 'pollmaster'),
            ]
        );
        
        // Auto Show
        $this->add_control(
            'auto_show',
            [
                'label' => __('Auto Show Popup', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'pollmaster'),
                'label_off' => __('No', 'pollmaster'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Automatically show popup when page loads', 'pollmaster'),
            ]
        );
        
        // Show Delay
        $this->add_control(
            'show_delay',
            [
                'label' => __('Show Delay (seconds)', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 60,
                'step' => 1,
                'default' => 3,
                'condition' => [
                    'auto_show' => 'yes',
                ],
            ]
        );
        
        // Trigger Button Text
        $this->add_control(
            'trigger_text',
            [
                'label' => __('Trigger Button Text', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Take Poll', 'pollmaster'),
                'condition' => [
                    'auto_show!' => 'yes',
                ],
            ]
        );
        
        // Trigger Button Icon
        $this->add_control(
            'trigger_icon',
            [
                'label' => __('Trigger Button Icon', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-poll',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'auto_show!' => 'yes',
                ],
            ]
        );
        
        // Show Once Per Session
        $this->add_control(
            'show_once',
            [
                'label' => __('Show Once Per Session', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'pollmaster'),
                'label_off' => __('No', 'pollmaster'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Only show popup once per user session', 'pollmaster'),
            ]
        );
        
        // Close on Vote
        $this->add_control(
            'close_on_vote',
            [
                'label' => __('Close After Vote', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'pollmaster'),
                'label_off' => __('No', 'pollmaster'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Automatically close popup after user votes', 'pollmaster'),
            ]
        );
        
        $this->end_controls_section();
        
        // Popup Style Section
        $this->start_controls_section(
            'popup_style_section',
            [
                'label' => __('Popup Style', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // Popup Width
        $this->add_responsive_control(
            'popup_width',
            [
                'label' => __('Popup Width', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 1000,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 20,
                        'max' => 90,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 500,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-content' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        // Popup Background
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'popup_background',
                'label' => __('Popup Background', 'pollmaster'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .pollmaster-popup-content',
            ]
        );
        
        // Popup Border
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'popup_border',
                'label' => __('Popup Border', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-popup-content',
            ]
        );
        
        // Popup Border Radius
        $this->add_control(
            'popup_border_radius',
            [
                'label' => __('Border Radius', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // Popup Box Shadow
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'popup_box_shadow',
                'label' => __('Box Shadow', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-popup-content',
            ]
        );
        
        // Overlay Background
        $this->add_control(
            'overlay_background',
            [
                'label' => __('Overlay Background', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'rgba(0,0,0,0.5)',
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Trigger Button Style Section
        $this->start_controls_section(
            'trigger_style_section',
            [
                'label' => __('Trigger Button Style', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'auto_show!' => 'yes',
                ],
            ]
        );
        
        // Button Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'trigger_typography',
                'label' => __('Typography', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-popup-trigger',
            ]
        );
        
        // Button Colors
        $this->start_controls_tabs('trigger_colors');
        
        $this->start_controls_tab(
            'trigger_normal',
            [
                'label' => __('Normal', 'pollmaster'),
            ]
        );
        
        $this->add_control(
            'trigger_text_color',
            [
                'label' => __('Text Color', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-trigger' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'trigger_background_color',
            [
                'label' => __('Background Color', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-trigger' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'trigger_hover',
            [
                'label' => __('Hover', 'pollmaster'),
            ]
        );
        
        $this->add_control(
            'trigger_hover_text_color',
            [
                'label' => __('Text Color', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-trigger:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'trigger_hover_background_color',
            [
                'label' => __('Background Color', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-trigger:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        // Button Padding
        $this->add_responsive_control(
            'trigger_padding',
            [
                'label' => __('Padding', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // Button Border Radius
        $this->add_control(
            'trigger_border_radius',
            [
                'label' => __('Border Radius', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-popup-trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Get polls list for dropdown
     */
    private function get_polls_list() {
        $options = [
            'latest' => __('Latest Poll', 'pollmaster'),
            'weekly' => __('Current Weekly Poll', 'pollmaster'),
            'random' => __('Random Poll', 'pollmaster'),
        ];
        
        // Get specific polls
        $db = new PollMaster_Database();
        $polls = $db->get_polls(['status' => 'active', 'limit' => 20]);
        
        foreach ($polls as $poll) {
            $options[$poll->id] = $poll->title;
        }
        
        return $options;
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Prepare attributes
        $attributes = [
            'poll_id' => $settings['poll_id'],
            'auto_show' => $settings['auto_show'] === 'yes' ? 'true' : 'false',
            'show_delay' => $settings['show_delay'],
            'trigger_text' => $settings['trigger_text'],
            'show_once' => $settings['show_once'] === 'yes' ? 'true' : 'false',
            'close_on_vote' => $settings['close_on_vote'] === 'yes' ? 'true' : 'false',
        ];
        
        // Build shortcode
        $shortcode_attrs = [];
        foreach ($attributes as $key => $value) {
            if (!empty($value)) {
                $shortcode_attrs[] = $key . '="' . esc_attr($value) . '"';
            }
        }
        
        $shortcode = '[pollmaster_popup ' . implode(' ', $shortcode_attrs) . ']';
        
        // Render shortcode
        echo '<div class="pollmaster-elementor-popup-widget">';
        echo do_shortcode($shortcode);
        echo '</div>';
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="pollmaster-elementor-popup-widget">
            <# if (settings.auto_show !== 'yes') { #>
                <button type="button" class="pollmaster-popup-trigger elementor-preview">
                    <# if (settings.trigger_icon && settings.trigger_icon.value) { #>
                        <i class="{{ settings.trigger_icon.value }}"></i>
                    <# } #>
                    {{ settings.trigger_text || 'Take Poll' }}
                </button>
            <# } else { #>
                <div class="elementor-preview-notice">
                    <p><strong>Poll Popup Widget</strong></p>
                    <p>Auto-show popup is enabled. The popup will appear automatically on the frontend after {{ settings.show_delay || 3 }} seconds.</p>
                </div>
            <# } #>
        </div>
        
        <style>
        .elementor-preview-notice {
            background: #f0f0f0;
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            border-radius: 4px;
        }
        .elementor-preview-notice p {
            margin: 5px 0;
        }
        .pollmaster-popup-trigger.elementor-preview {
            background: #0073aa;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        </style>
        <?php
    }
}