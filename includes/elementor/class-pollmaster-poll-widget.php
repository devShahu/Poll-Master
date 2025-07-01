<?php
/**
 * PollMaster Poll Widget for Elementor
 * 
 * @package PollMaster
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PollMaster Poll Widget Class
 */
class PollMaster_Poll_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'pollmaster-poll';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Poll Display', 'pollmaster');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-form-horizontal';
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
        return ['poll', 'vote', 'survey', 'pollmaster'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Poll Settings', 'pollmaster'),
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
                'description' => __('Choose which poll to display', 'pollmaster'),
            ]
        );
        
        // Display Type
        $this->add_control(
            'display_type',
            [
                'label' => __('Display Type', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'full' => __('Full Poll', 'pollmaster'),
                    'compact' => __('Compact View', 'pollmaster'),
                    'results_only' => __('Results Only', 'pollmaster'),
                ],
                'default' => 'full',
            ]
        );
        
        // Show Results
        $this->add_control(
            'show_results',
            [
                'label' => __('Show Results', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'pollmaster'),
                'label_off' => __('No', 'pollmaster'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'display_type!' => 'results_only',
                ],
            ]
        );
        
        // Show Social Share
        $this->add_control(
            'show_social',
            [
                'label' => __('Show Social Share', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'pollmaster'),
                'label_off' => __('No', 'pollmaster'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Poll Style', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // Primary Color
        $this->add_control(
            'primary_color',
            [
                'label' => __('Primary Color', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-poll' => '--primary-color: {{VALUE}}',
                ],
            ]
        );
        
        // Secondary Color
        $this->add_control(
            'secondary_color',
            [
                'label' => __('Secondary Color', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f0f0f0',
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-poll' => '--secondary-color: {{VALUE}}',
                ],
            ]
        );
        
        // Border Radius
        $this->add_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-poll' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        // Padding
        $this->add_responsive_control(
            'padding',
            [
                'label' => __('Padding', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 20,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-poll' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // Box Shadow
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'box_shadow',
                'label' => __('Box Shadow', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-poll',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography Section
        $this->start_controls_section(
            'typography_section',
            [
                'label' => __('Typography', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // Title Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-poll .poll-title',
            ]
        );
        
        // Option Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'option_typography',
                'label' => __('Option Typography', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-poll .poll-option label',
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
        
        // Get poll based on selection
        $poll_id = $settings['poll_id'];
        $db = new PollMaster_Database();
        
        switch ($poll_id) {
            case 'latest':
                $poll = $db->get_latest_poll();
                break;
            case 'weekly':
                $poll = $db->get_weekly_poll();
                break;
            case 'random':
                $poll = $db->get_random_poll();
                break;
            default:
                $poll = $db->get_poll($poll_id);
                break;
        }
        
        if (!$poll) {
            echo '<div class="pollmaster-error">' . __('No poll found.', 'pollmaster') . '</div>';
            return;
        }
        
        // Prepare attributes
        $attributes = [
            'poll_id' => $poll->id,
            'display_type' => $settings['display_type'],
            'show_results' => $settings['show_results'] === 'yes' ? 'true' : 'false',
            'show_social' => $settings['show_social'] === 'yes' ? 'true' : 'false',
        ];
        
        // Build shortcode
        $shortcode_attrs = [];
        foreach ($attributes as $key => $value) {
            $shortcode_attrs[] = $key . '="' . esc_attr($value) . '"';
        }
        
        $shortcode = '[pollmaster_poll ' . implode(' ', $shortcode_attrs) . ']';
        
        // Render shortcode
        echo '<div class="pollmaster-elementor-widget">';
        echo do_shortcode($shortcode);
        echo '</div>';
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="pollmaster-elementor-widget">
            <div class="pollmaster-poll elementor-preview">
                <div class="poll-header">
                    <h3 class="poll-title">{{ settings.poll_id === 'latest' ? 'Latest Poll' : 'Selected Poll' }}</h3>
                    <p class="poll-description">This is a preview of the poll widget. The actual poll will be displayed on the frontend.</p>
                </div>
                <div class="poll-options">
                    <div class="poll-option">
                        <label>
                            <input type="radio" name="poll_preview" value="1">
                            <span>Option 1</span>
                        </label>
                    </div>
                    <div class="poll-option">
                        <label>
                            <input type="radio" name="poll_preview" value="2">
                            <span>Option 2</span>
                        </label>
                    </div>
                </div>
                <div class="poll-actions">
                    <button type="button" class="poll-vote-btn">Vote</button>
                    <# if (settings.show_social === 'yes') { #>
                    <div class="poll-social">
                        <span>Share:</span>
                        <a href="#">Facebook</a>
                        <a href="#">Twitter</a>
                    </div>
                    <# } #>
                </div>
            </div>
        </div>
        <?php
    }
}