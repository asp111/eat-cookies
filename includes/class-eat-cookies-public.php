<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eat_Cookies_Public {
    public function enqueue_styles() {
        wp_enqueue_style('eat-cookies-public', EAT_COOKIES_URL . 'assets/css/public.css', [], EAT_COOKIES_VERSION);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('eat-cookies-public', EAT_COOKIES_URL . 'assets/js/public.js', ['jquery'], EAT_COOKIES_VERSION, true);
        
        $scripts = get_option('eat_cookies_scripts', []);
        $cookies_data = get_option('eat_cookies_data', []);
        
        wp_localize_script('eat-cookies-public', 'eatCookiesData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'scripts'  => $scripts,
            'cookies'  => $cookies_data,
            'nonce'    => wp_create_nonce('eat-cookies-nonce'),
            'isAdmin'  => current_user_can('manage_options'),
            'scanNonce' => wp_create_nonce('eat-cookies-scan-nonce'),
            'scriptsRendered' => isset($_COOKIE['eat_cookies_consent'])
        ]);
    }

    public function render_consent_scripts() {
        if (!isset($_COOKIE['eat_cookies_consent'])) {
            return;
        }

        $consent = json_decode(stripslashes($_COOKIE['eat_cookies_consent']), true);
        if (!$consent) {
            return;
        }

        $scripts = get_option('eat_cookies_scripts', []);
        foreach ($consent as $category => $allowed) {
            if ($allowed && !empty($scripts[$category])) {
                echo $scripts[$category];
            }
        }
    }

    public function render_cookie_popup() {
        $cookies_data = get_option('eat_cookies_data', []);
        ?>
        <div id="eat-cookies-trigger" title="Cookie Settings">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
                <path d="M7 12a1 1 0 100-2 1 1 0 000 2zm3-4a1 1 0 100-2 1 1 0 000 2zm4 0a1 1 0 100-2 1 1 0 000 2zm3 4a1 1 0 100-2 1 1 0 000 2zm-3 4a1 1 0 100-2 1 1 0 000 2zm-4 0a1 1 0 100-2 1 1 0 000 2z" fill="currentColor"/>
            </svg>
        </div>

        <!-- Small Initial Banner -->
        <div id="eat-cookies-banner">
            <div class="eat-cookies-banner-content">
                <div class="eat-cookies-banner-text">
                    <h3 class="eat-cookies-banner-title">This website uses cookies</h3>
                    <p class="eat-cookies-banner-description">
                        We use cookies to personalise content, ads and to analyse our traffic. We also share information about your use of our site with our advertising and analytics partners.
                    </p>
                    <div class="eat-cookies-banner-checkboxes">
                        <?php 
                        $mini_categories = [
                            'necessary' => 'Strictly necessary',
                            'functional' => 'Functional',
                            'analytics' => 'Analytics',
                            'performance' => 'Performance',
                            'advertisement' => 'Advertisement',
                        ];
                        foreach ($mini_categories as $id => $title) : ?>
                            <div class="eat-cookies-banner-checkbox">
                                <input type="checkbox" id="banner-cat-<?php echo esc_attr($id); ?>" class="category-checkbox" <?php echo $id === 'necessary' ? 'checked disabled' : ''; ?>>
                                <label for="banner-cat-<?php echo esc_attr($id); ?>"><?php echo esc_html($title); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="eat-cookies-show-details" class="eat-cookies-banner-manage">
                        <svg viewBox="0 0 9.62 9.57" width="12" height="12">
                            <path d="M9.46,6.06l-1.1-.78c0-.16.06-.31.06-.47a1.27,1.27,0,0,0-.06-.47L9.57,3.4l-1.15-2L7,1.93a2.74,2.74,0,0,0-.83-.47L6,0H3.61L3.35,1.46a7.14,7.14,0,0,0-.79.47L1.15,1.36,0,3.4l1.15.94c0,.16,0,.31,0,.47a1.51,1.51,0,0,0,0,.47l-1,.78A.75.75,0,0,0,0,6.17l1.15,2,1.41-.58a2.49,2.49,0,0,0,.84.47l.21,1.47H6a.53.53,0,0,1,0-.21L6.22,8.1a4,4,0,0,0,.84-.47l1.41.58,1.15-2A.75.75,0,0,0,9.46,6.06Zm-4.65.19A1.47,1.47,0,1,1,6.28,4.78,1.47,1.47,0,0,1,4.81,6.25Z" fill="currentColor"/>
                        </svg>
                        <span>Show details</span>
                    </div>
                </div>
                <div class="eat-cookies-banner-actions">
                    <button id="eat-cookies-banner-save" class="eat-cookies-btn eat-cookies-btn-banner-save">Save & Close</button>
                    <button id="eat-cookies-banner-accept" class="eat-cookies-btn eat-cookies-btn-banner-accept">Accept all</button>
                    <button id="eat-cookies-banner-decline" class="eat-cookies-btn eat-cookies-btn-banner-decline">Decline all</button>
                </div>
            </div>
        </div>

        <div id="eat-cookies-popup">
            <div class="eat-cookies-container">
                <div class="eat-cookies-header">
                    <div class="eat-cookies-header-top">
                        <h3 class="eat-cookies-title">This website uses cookies</h3>
                        <button id="eat-cookies-close" class="eat-cookies-close-btn">&times;</button>
                    </div>
                    <p class="eat-cookies-description">
                        We use cookies to personalise content, ads and to analyse our traffic. We also share information about your use of our site with our advertising and analytics partners.
                    </p>
                </div>

                <div class="eat-cookies-tabs">
                    <button class="eat-cookies-tab-btn active" data-tab="details">Cookie declaration</button>
                    <button class="eat-cookies-tab-btn" data-tab="about">About cookies</button>
                </div>

                <div class="eat-cookies-tab-content-container">
                    <div id="eat-cookies-tab-content-details" class="eat-cookies-tab-content active">
                        <div id="eat-cookies-settings-panel" class="eat-cookies-settings">
                            <div class="eat-cookies-categories">
                                <?php 
                                $categories = [
                                    'necessary' => ['title' => 'Strictly necessary', 'desc' => 'Strictly necessary cookies allow core website functionality such as user login and account management. The website cannot be used properly without strictly necessary cookies.'],
                                    'functional' => ['title' => 'Functional', 'desc' => 'Functionality cookies are used to remember visitor information on the website, eg. language, timezone, enhanced content.'],
                                    'analytics' => ['title' => 'Analytics', 'desc' => 'Analytics cookies are used to see how visitors use the website. These cookies cannot be used to directly identify a certain visitor.'],
                                    'performance' => ['title' => 'Performance', 'desc' => 'Performance cookies are used to see how visitors use the website, eg. analytics cookies.'],
                                    'advertisement' => ['title' => 'Advertisement', 'desc' => 'Targeting cookies are used to identify visitors between different websites, eg. content partners, banner networks.'],
                                ];

                                foreach ($categories as $id => $cat) : ?>
                                    <div class="eat-cookies-category">
                                        <div class="eat-cookies-category-header">
                                            <div class="eat-cookies-category-info">
                                                <h4><?php echo esc_html($cat['title']); ?></h4>
                                                <p><?php echo esc_html($cat['desc']); ?></p>
                                            </div>
                                            <div class="eat-cookies-toggle">
                                                <input type="checkbox" id="cat-<?php echo esc_attr($id); ?>" class="category-checkbox" <?php echo $id === 'necessary' ? 'checked disabled' : ''; ?>>
                                                <label for="cat-<?php echo esc_attr($id); ?>"></label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="eat-cookies-save-container">
                                <button id="eat-cookies-save-settings" class="eat-cookies-btn eat-cookies-btn-save">
                                    Save & Close
                                </button>
                            </div>
                        </div>

                        <div class="eat-cookies-declaration">
                            <?php foreach ($categories as $id => $cat) : ?>
                                <div class="eat-cookies-declaration-category">
                                    <h3><?php echo esc_html($cat['title']); ?></h3>
                                    <?php if (!empty($cookies_data[$id])) : ?>
                                        <div class="eat-cookies-table-wrapper">
                                            <table class="eat-cookies-table">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Provider/Domain</th>
                                                        <th>Expiration</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cookies_data[$id] as $cookie) : ?>
                                                        <tr>
                                                            <td><?php echo esc_html($cookie['name']); ?></td>
                                                            <td><?php echo esc_html(isset($cookie['provider']) ? $cookie['provider'] : ''); ?></td>
                                                            <td><?php echo esc_html(isset($cookie['expiration']) ? $cookie['expiration'] : ''); ?></td>
                                                            <td><?php echo esc_html(isset($cookie['description']) ? $cookie['description'] : ''); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else : ?>
                                        <p class="eat-cookies-no-data">No cookies detected in this category.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="eat-cookies-tab-content-about" class="eat-cookies-tab-content">
                        <div class="eat-cookies-about">
                            <p>Cookies are small text files that are placed on your computer by websites that you visit. Websites use cookies to help users navigate efficiently and perform certain functions. Cookies that are required for the website to operate properly are allowed to be set without your permission. All other cookies need to be approved before they can be set in the browser.</p>
                            <p>You can change your consent to cookie usage at any time on our Privacy Policy page.</p>
                        </div>
                    </div>
                </div>

                <div class="eat-cookies-footer">
                    <div class="eat-cookies-actions">
                        <button id="eat-cookies-decline" class="eat-cookies-btn eat-cookies-btn-decline">
                            Decline all
                        </button>
                        <button id="eat-cookies-settings-btn" class="eat-cookies-btn eat-cookies-btn-customize">
                            Customize
                        </button>
                        <button id="eat-cookies-accept-all" class="eat-cookies-btn eat-cookies-btn-accept">
                            Accept all
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function save_consent() {
        check_ajax_referer('eat-cookies-nonce', 'nonce');

        $consent_id = isset($_POST['consent_id']) ? sanitize_text_field(wp_unslash($_POST['consent_id'])) : '';
        $consent_state = isset($_POST['consent_state']) ? (array) wp_unslash($_POST['consent_state']) : [];
        
        $sanitized_state = [];
        foreach ($consent_state as $key => $value) {
            $sanitized_state[sanitize_key($key)] = sanitize_text_field($value);
        }

        if (!empty($consent_id)) {
            Eat_Cookies_Database::save_consent($consent_id, $sanitized_state);
            wp_send_json_success();
        }

        wp_send_json_error('Missing consent ID');
    }
}
