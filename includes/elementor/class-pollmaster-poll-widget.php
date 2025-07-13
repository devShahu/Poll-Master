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
        
        // Poll Count
        $this->add_control(
            'poll_count',
            [
                'label' => __('Number of Polls', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 20,
                'step' => 1,
                'default' => 1,
                'description' => __('Number of polls to display', 'pollmaster'),
                'condition' => [
                    'poll_id' => ['latest', 'random', 'weekly']
                ]
            ]
        );
        
        // Auto Show Popup
        $this->add_control(
            'auto_show_popup',
            [
                'label' => __('Auto Show as Popup', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'pollmaster'),
                'label_off' => __('No', 'pollmaster'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Automatically show poll in a popup overlay', 'pollmaster'),
            ]
        );
        
        // Popup Delay
        $this->add_control(
            'popup_delay',
            [
                'label' => __('Popup Delay (seconds)', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 60,
                'step' => 1,
                'default' => 3,
                'description' => __('Delay before showing popup (in seconds)', 'pollmaster'),
                'condition' => [
                    'auto_show_popup' => 'yes'
                ]
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
        
        // Typography Section
        $this->start_controls_section(
            'typography_section',
            [
                'label' => __('Typography', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // Question Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'question_typography',
                'label' => __('Question Typography', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-poll .poll-question',
            ]
        );
        
        // Options Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'options_typography',
                'label' => __('Options Typography', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-poll .poll-option',
            ]
        );
        
        $this->end_controls_section();
        
        // Animation Section
        $this->start_controls_section(
            'animation_section',
            [
                'label' => __('Animation & Effects', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // Hover Animation
        $this->add_control(
            'hover_animation',
            [
                'label' => __('Hover Animation', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'pollmaster'),
                    'scale' => __('Scale', 'pollmaster'),
                    'lift' => __('Lift', 'pollmaster'),
                    'glow' => __('Glow', 'pollmaster'),
                ],
            ]
        );
        
        // Entrance Animation
        $this->add_control(
            'entrance_animation',
            [
                'label' => __('Entrance Animation', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'fadeIn',
                'options' => [
                    'none' => __('None', 'pollmaster'),
                    'fadeIn' => __('Fade In', 'pollmaster'),
                    'slideInUp' => __('Slide In Up', 'pollmaster'),
                    'slideInDown' => __('Slide In Down', 'pollmaster'),
                    'zoomIn' => __('Zoom In', 'pollmaster'),
                ],
            ]
        );
        
        // Shadow
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'poll_shadow',
                'label' => __('Box Shadow', 'pollmaster'),
                'selector' => '{{WRAPPER}} .pollmaster-poll',
            ]
        );
        
        $this->end_controls_section();
        
        // Layout Section
        $this->start_controls_section(
            'layout_section',
            [
                'label' => __('Layout & Spacing', 'pollmaster'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // Option Spacing
        $this->add_control(
            'option_spacing',
            [
                'label' => __('Option Spacing', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pollmaster-poll .poll-option' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        // Button Style
        $this->add_control(
            'button_style',
            [
                'label' => __('Button Style', 'pollmaster'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'rounded',
                'options' => [
                    'square' => __('Square', 'pollmaster'),
                    'rounded' => __('Rounded', 'pollmaster'),
                    'pill' => __('Pill', 'pollmaster'),
                ],
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
        
        // Get poll(s) based on selection
        $poll_id = $settings['poll_id'];
        $poll_count = isset($settings['poll_count']) ? intval($settings['poll_count']) : 1;
        $db = new PollMaster_Database();
        
        $polls = [];
        
        switch ($poll_id) {
            case 'latest':
                $polls = $db->get_polls(['status' => 'active', 'limit' => $poll_count, 'order' => 'created_at DESC']);
                break;
            case 'weekly':
                $weekly_poll = $db->get_weekly_poll();
                if ($weekly_poll) $polls = [$weekly_poll];
                break;
            case 'random':
                $polls = $db->get_polls(['status' => 'active', 'limit' => $poll_count, 'order' => 'RAND()']);
                break;
            default:
                $poll = $db->get_poll($poll_id);
                if ($poll) $polls = [$poll];
                break;
        }
        
        if (empty($polls)) {
            echo '<div class="pollmaster-error">' . __('No polls found.', 'pollmaster') . '</div>';
            return;
        }
        
        // Auto-show popup functionality
        $auto_show_popup = $settings['auto_show_popup'] === 'yes';
        $popup_delay = isset($settings['popup_delay']) ? intval($settings['popup_delay']) * 1000 : 3000; // Convert to milliseconds
        
        if ($auto_show_popup) {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    // Create popup overlay
                    const overlay = document.createElement("div");
                    overlay.className = "pollmaster-popup-overlay";
                    overlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        z-index: 9999;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    `;
                    
                    const popup = document.createElement("div");
                    popup.className = "pollmaster-popup-content";
                    popup.style.cssText = `
                        background: white;
                        border-radius: 12px;
                        padding: 30px;
                        max-width: 500px;
                        width: 90%;
                        max-height: 80vh;
                        overflow-y: auto;
                        position: relative;
                        transform: scale(0.8);
                        transition: transform 0.3s ease;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    `;
                    
                    const closeBtn = document.createElement("button");
                    closeBtn.innerHTML = "×";
                    closeBtn.style.cssText = `
                        position: absolute;
                        top: 15px;
                        right: 20px;
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: #666;
                        padding: 0;
                        width: 30px;
                        height: 30px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;
                    
                    closeBtn.onclick = function() {
                        overlay.style.opacity = "0";
                        popup.style.transform = "scale(0.8)";
                        setTimeout(() => overlay.remove(), 300);
                    };
                    
                    overlay.onclick = function(e) {
                        if (e.target === overlay) {
                            closeBtn.onclick();
                        }
                    };
                    
                    popup.appendChild(closeBtn);
                    overlay.appendChild(popup);
                    document.body.appendChild(overlay);
                    
                    // Animate in
                    setTimeout(() => {
                        overlay.style.opacity = "1";
                        popup.style.transform = "scale(1)";
                    }, 10);
                    
                }, ' . $popup_delay . ');
            });
            </script>';
        }
        
        // Render polls
        foreach ($polls as $poll) {
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
            
            // Add some spacing between multiple polls
            if (count($polls) > 1) {
                echo '<div style="margin-bottom: 20px;"></div>';
            }
        }
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