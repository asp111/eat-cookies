<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eat_Cookies_Admin {
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Eat Cookies Settings',
            'Eat Cookies',
            'manage_options',
            'eat-cookies',
            [$this, 'display_plugin_setup_page'],
            'dashicons-shield',
            100
        );
    }

    public function register_settings() {
        register_setting('eat_cookies_settings', 'eat_cookies_scripts');
        register_setting('eat_cookies_settings', 'eat_cookies_appearance');
        register_setting('eat_cookies_settings', 'eat_cookies_data');
    }

    public function enqueue_styles($hook) {
        if ('toplevel_page_eat-cookies' !== $hook) {
            return;
        }
        wp_enqueue_style('eat-cookies-admin', EAT_COOKIES_URL . 'assets/css/admin.css', [], EAT_COOKIES_VERSION);
    }

    public function display_plugin_setup_page() {
        $scripts = get_option('eat_cookies_scripts', [
            'necessary' => '',
            'functional' => '',
            'analytics' => '',
            'performance' => '',
            'advertisement' => ''
        ]);
        $cookies_data = get_option('eat_cookies_data', [
            'necessary' => [],
            'functional' => [],
            'analytics' => [],
            'performance' => [],
            'advertisement' => []
        ]);
        ?>
        <div class="wrap">
            <div class="eat-cookies-admin-wrap">
                <h1 class="eat-cookies-admin-title">Eat Cookies Settings</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('eat_cookies_settings'); ?>
                    
                    <h2 class="eat-cookies-admin-subtitle">Category Scripts</h2>
                    <div class="eat-cookies-fields">
                        <?php foreach ($scripts as $category => $content) : ?>
                            <div class="eat-cookies-field-group">
                                <label class="eat-cookies-label"><?php echo esc_html(ucfirst($category)); ?> Scripts</label>
                                <textarea
                                    name="eat_cookies_scripts[<?php echo esc_attr($category); ?>]"
                                    class="eat-cookies-textarea"
                                    placeholder="Enter scripts (e.g. <script>...</script>) for <?php echo esc_html($category); ?> category"
                                ><?php echo esc_textarea($content); ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h2 class="eat-cookies-admin-subtitle" style="margin-top: 30px;">Cookie Declaration (Frontend Display)</h2>
                    <p class="description">These cookies will be shown in the frontend popup under their respective categories.</p>
                    <div class="eat-cookies-data-fields">
                        <?php foreach ($cookies_data as $category => $cookies) : ?>
                            <div class="eat-cookies-category-data" style="margin-bottom: 20px; border: 1px solid #ccd0d4; padding: 15px; background: #fff;">
                                <h3 style="margin-top: 0;"><?php echo esc_html(ucfirst($category)); ?> Cookies</h3>
                                <div id="cookies-list-<?php echo esc_attr($category); ?>">
                                    <?php if (!empty($cookies)) : ?>
                                        <?php foreach ($cookies as $index => $cookie) : ?>
                                            <div class="cookie-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                                <input type="text" name="eat_cookies_data[<?php echo esc_attr($category); ?>][<?php echo $index; ?>][name]" value="<?php echo esc_attr($cookie['name']); ?>" placeholder="Cookie Name" style="width: 20%;">
                                                <input type="text" name="eat_cookies_data[<?php echo esc_attr($category); ?>][<?php echo $index; ?>][provider]" value="<?php echo esc_attr(isset($cookie['provider']) ? $cookie['provider'] : ''); ?>" placeholder="Provider" style="width: 20%;">
                                                <input type="text" name="eat_cookies_data[<?php echo esc_attr($category); ?>][<?php echo $index; ?>][expiration]" value="<?php echo esc_attr(isset($cookie['expiration']) ? $cookie['expiration'] : ''); ?>" placeholder="Expiration" style="width: 20%;">
                                                <input type="text" name="eat_cookies_data[<?php echo esc_attr($category); ?>][<?php echo $index; ?>][description]" value="<?php echo esc_attr(isset($cookie['description']) ? $cookie['description'] : ''); ?>" placeholder="Description" style="width: 40%;">
                                                <button type="button" class="button remove-cookie">×</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button add-cookie" data-category="<?php echo esc_attr($category); ?>">+ Add Cookie</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="eat-cookies-admin-actions">
                        <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                        <button type="button" id="eat-cookies-scan" class="button button-secondary">Scan & Auto-Categorize</button>
                    </div>
                </form>

                <div id="scan-results" class="eat-cookies-scan-results" style="display:none; margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2271b1;">
                    <h3>Scan Results</h3>
                    <div id="scan-message"></div>
                </div>

                <div class="eat-cookies-log-container" style="margin-top: 40px;">
                    <h2 class="eat-cookies-admin-title" style="font-size: 20px;">Consent Log (Last 10)</h2>
                    <table class="eat-cookies-log-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Consent ID</th>
                                <th>State</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'eat_cookies_consents';
                            $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 10");
                            if ($results) :
                                foreach ($results as $row) : ?>
                                    <tr>
                                        <td><?php echo esc_html($row->id); ?></td>
                                        <td><?php echo esc_html($row->consent_id); ?></td>
                                        <td><code style="font-size: 11px;"><?php echo esc_html($row->consent_state); ?></code></td>
                                        <td><?php echo esc_html($row->timestamp); ?></td>
                                    </tr>
                                <?php endforeach;
                            else : ?>
                                <tr>
                                    <td colspan="4">No consent logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('.add-cookie').on('click', function() {
                    const category = $(this).data('category');
                    const $list = $('#cookies-list-' + category);
                    const index = $list.find('.cookie-row').length;
                    const html = `
                        <div class="cookie-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="text" name="eat_cookies_data[${category}][${index}][name]" value="" placeholder="Cookie Name" style="width: 20%;">
                            <input type="text" name="eat_cookies_data[${category}][${index}][provider]" value="" placeholder="Provider" style="width: 20%;">
                            <input type="text" name="eat_cookies_data[${category}][${index}][expiration]" value="" placeholder="Expiration" style="width: 20%;">
                            <input type="text" name="eat_cookies_data[${category}][${index}][description]" value="" placeholder="Description" style="width: 40%;">
                            <button type="button" class="button remove-cookie">×</button>
                        </div>
                    `;
                    $list.append(html);
                });

                $(document).on('click', '.remove-cookie', function() {
                    $(this).closest('.cookie-row').remove();
                });

                $('#eat-cookies-scan').on('click', function() {
                    const $btn = $(this);
                    $btn.prop('disabled', true).text('Scanning...');
                    
                    $.post(ajaxurl, {
                        action: 'eat_cookies_scan',
                        nonce: '<?php echo wp_create_nonce('eat-cookies-scan-nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#scan-results').show();
                            let html = '<p>' + response.data.message + '</p>';
                            if (response.data.found && response.data.found.length > 0) {
                                html += '<ul class="eat-cookies-scan-list">';
                                response.data.found.forEach(function(item) {
                                    html += '<li><strong>' + item.name + '</strong>: ' + item.category + '</li>';
                                });
                                html += '</ul>';
                                html += '<p style="margin-top:10px;"><strong>Important:</strong> Detected cookies have been added to the "Cookie Declaration" section below. Please refresh the page to edit them or save your changes.</p>';
                            }
                            $('#scan-message').html(html);
                        }
                        $btn.text('Scan Complete');
                    });
                });
            });
        </script>
        <?php
    }

    public function ajax_scan_cookies() {
        check_ajax_referer('eat-cookies-scan-nonce', 'nonce');

        $found = [];
        $active_plugins = get_option('active_plugins');
        
        $plugin_patterns = [
            'woocommerce/woocommerce.php' => [
                'name' => 'WooCommerce',
                'category' => 'necessary',
                'cookies' => [
                    ['name' => 'woocommerce_cart_hash', 'provider' => 'WooCommerce', 'expiration' => 'Session', 'description' => 'Helps WooCommerce determine when cart contents/data changes.'],
                    ['name' => 'woocommerce_items_in_cart', 'provider' => 'WooCommerce', 'expiration' => 'Session', 'description' => 'Helps WooCommerce determine when cart contents/data changes.'],
                    ['name' => 'wp_woocommerce_session_', 'provider' => 'WooCommerce', 'expiration' => '2 days', 'description' => 'Contains a unique code for each customer so that it knows where to find the cart data in the database for each customer.']
                ]
            ],
            'contact-form-7/wp-contact-form-7.php' => [
                'name' => 'Contact Form 7',
                'category' => 'functional',
                'cookies' => [
                    ['name' => '_wpcf7', 'provider' => 'Contact Form 7', 'expiration' => 'Session', 'description' => 'Used by Contact Form 7 to prevent spam and store session info.']
                ]
            ],
            'google-analytics-for-wordpress/googleanalytics.php' => [
                'name' => 'MonsterInsights (GA)',
                'category' => 'analytics',
                'cookies' => [
                    ['name' => '_ga', 'provider' => 'Google Analytics', 'expiration' => '2 years', 'description' => 'Used to distinguish users.'],
                    ['name' => '_gid', 'provider' => 'Google Analytics', 'expiration' => '24 hours', 'description' => 'Used to distinguish users.']
                ]
            ]
        ];

        foreach ($plugin_patterns as $path => $info) {
            if (in_array($path, $active_plugins)) {
                $found[] = $info;
            }
        }

        $home_url = home_url();
        $response = wp_remote_get($home_url);
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            
            $script_patterns = [
                'analytics' => [
                    'Google Analytics' => [
                        'patterns' => ['googletagmanager.com/gtag/js', 'google-analytics.com/analytics.js', '_ga', '_gid'],
                        'cookies' => [
                            ['name' => '_ga', 'provider' => 'Google Analytics', 'expiration' => '2 years', 'description' => 'Used to distinguish users.'],
                            ['name' => '_gid', 'provider' => 'Google Analytics', 'expiration' => '24 hours', 'description' => 'Used to distinguish users.']
                        ]
                    ],
                    'Hotjar' => [
                        'patterns' => ['static.hotjar.com'],
                        'cookies' => [
                            ['name' => '_hjSessionResumed', 'provider' => 'Hotjar', 'expiration' => 'Session', 'description' => 'Identifies if the session was resumed.']
                        ]
                    ]
                ],
                'advertisement' => [
                    'Facebook Pixel' => [
                        'patterns' => ['connect.facebook.net/en_US/fbevents.js', 'fbq('],
                        'cookies' => [
                            ['name' => '_fbp', 'provider' => 'Facebook', 'expiration' => '3 months', 'description' => 'Used by Facebook to deliver a series of advertisement products.']
                        ]
                    ],
                    'Google Ads' => [
                        'patterns' => ['googleadservices.com/pagead/conversion.js'],
                        'cookies' => [
                            ['name' => '_gcl_au', 'provider' => 'Google Ads', 'expiration' => '3 months', 'description' => 'Used by Google AdSense for experimenting with advertisement efficiency across websites using their services.']
                        ]
                    ]
                ],
                'functional' => [
                    'Google Maps' => [
                        'patterns' => ['maps.googleapis.com/maps/api/js'],
                        'cookies' => []
                    ]
                ]
            ];

            foreach ($script_patterns as $category => $services) {
                foreach ($services as $name => $data) {
                    foreach ($data['patterns'] as $pattern) {
                        if (strpos($body, $pattern) !== false) {
                            $already_found = false;
                            foreach ($found as $f) {
                                if ($f['name'] === $name) {
                                    $already_found = true;
                                    break;
                                }
                            }
                            if (!$already_found) {
                                $found[] = [
                                    'name' => $name,
                                    'category' => $category,
                                    'cookies' => $data['cookies']
                                ];
                            }
                            break; 
                        }
                    }
                }
            }
        }

        // Save detected cookies to a dedicated option if not already present
        $existing_cookies = get_option('eat_cookies_data', [
            'necessary' => [],
            'functional' => [],
            'analytics' => [],
            'performance' => [],
            'advertisement' => []
        ]);

        foreach ($found as $item) {
            $cat = $item['category'];
            if (isset($item['cookies'])) {
                foreach ($item['cookies'] as $cookie) {
                    // Avoid duplicates
                    $exists = false;
                    foreach ($existing_cookies[$cat] as $ex_cookie) {
                        if ($ex_cookie['name'] === $cookie['name']) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $existing_cookies[$cat][] = $cookie;
                    }
                }
            }
        }
        update_option('eat_cookies_data', $existing_cookies);

        $message = empty($found) ? "No common tracking scripts or cookies detected." : "Detected " . count($found) . " common services and their cookies.";

        wp_send_json_success([
            'message' => $message,
            'found' => $found
        ]);
    }

    private function get_cookie_knowledge_base() {
        return [
            // WordPress Core
            'wordpress_logged_in_' => [
                'category' => 'necessary',
                'provider' => 'WordPress',
                'expiration' => 'Session',
                'description' => 'Used by WordPress to maintain a logged-in user session.'
            ],
            'wordpress_sec_' => [
                'category' => 'necessary',
                'provider' => 'WordPress',
                'expiration' => 'Session',
                'description' => 'Used by WordPress to store authentication details for security purposes.'
            ],
            'wordpress_test_cookie' => [
                'category' => 'necessary',
                'provider' => 'WordPress',
                'expiration' => 'Session',
                'description' => 'Used to check if the browser has cookies enabled.'
            ],
            'wp-settings-' => [
                'category' => 'necessary',
                'provider' => 'WordPress',
                'expiration' => '1 year',
                'description' => 'Used to persist a user\'s wp-admin configuration.'
            ],
            'wp_lang' => [
                'category' => 'necessary',
                'provider' => 'WordPress',
                'expiration' => 'Session',
                'description' => 'Used to store language settings.'
            ],
            'eat_cookies_consent' => [
                'category' => 'necessary',
                'provider' => 'Eat Cookies',
                'expiration' => '1 year',
                'description' => 'Stores the user\'s cookie consent preferences.'
            ],
            // WooCommerce
            'woocommerce_cart_hash' => [
                'category' => 'necessary',
                'provider' => 'WooCommerce',
                'expiration' => 'Session',
                'description' => 'Helps WooCommerce determine when cart contents/data changes.'
            ],
            'woocommerce_items_in_cart' => [
                'category' => 'necessary',
                'provider' => 'WooCommerce',
                'expiration' => 'Session',
                'description' => 'Helps WooCommerce determine when cart contents/data changes.'
            ],
            'wp_woocommerce_session_' => [
                'category' => 'necessary',
                'provider' => 'WooCommerce',
                'expiration' => '2 days',
                'description' => 'Contains a unique code for each customer so that it knows where to find the cart data in the database for each customer.'
            ],
            'woocommerce_recently_viewed' => [
                'category' => 'functional',
                'provider' => 'WooCommerce',
                'expiration' => 'Session',
                'description' => 'Powers the Recently Viewed Products widget.'
            ],
            // Google Analytics
            '_ga' => [
                'category' => 'analytics',
                'provider' => 'Google Analytics',
                'expiration' => '2 years',
                'description' => 'Used to distinguish users and calculate visitor, session and campaign data.'
            ],
            '_gid' => [
                'category' => 'analytics',
                'provider' => 'Google Analytics',
                'expiration' => '24 hours',
                'description' => 'Used to distinguish users.'
            ],
            '_gat' => [
                'category' => 'analytics',
                'provider' => 'Google Analytics',
                'expiration' => '1 minute',
                'description' => 'Used to throttle request rate.'
            ],
            '_ga_' => [
                'category' => 'analytics',
                'provider' => 'Google Analytics',
                'expiration' => '2 years',
                'description' => 'Used to persist session state.'
            ],
            // Marketing / Ads
            '_fbp' => [
                'category' => 'advertisement',
                'provider' => 'Facebook',
                'expiration' => '3 months',
                'description' => 'Used by Facebook to deliver advertisement products such as real-time bidding from third party advertisers.'
            ],
            '_gcl_au' => [
                'category' => 'advertisement',
                'provider' => 'Google AdSense',
                'expiration' => '3 months',
                'description' => 'Used by Google AdSense for experimenting with advertisement efficiency across websites using their services.'
            ],
            'tk_ai' => [
                'category' => 'advertisement',
                'provider' => 'Jetpack',
                'expiration' => 'Session',
                'description' => 'Used by Jetpack to store a randomly generated anonymous ID for internal metrics.'
            ],
            // Hotjar
            '_hj' => [
                'category' => 'analytics',
                'provider' => 'Hotjar',
                'expiration' => 'Session/1 year',
                'description' => 'Used to persist the Hotjar User ID, unique to that site on the browser. This ensures that behavior in subsequent visits to the same site will be attributed to the same user ID.'
            ],
            // Cloudflare
            '__cf_bm' => [
                'category' => 'necessary',
                'provider' => 'Cloudflare',
                'expiration' => '30 minutes',
                'description' => 'Used to support Cloudflare Bot Management.'
            ],
            '_cfuvid' => [
                'category' => 'necessary',
                'provider' => 'Cloudflare',
                'expiration' => 'Session',
                'description' => 'Used for Cloudflare Rate Limiting.'
            ],
            // Others
            'PHPSESSID' => [
                'category' => 'necessary',
                'provider' => 'PHP',
                'expiration' => 'Session',
                'description' => 'General purpose identifier used to maintain user session variables.'
            ],
        ];
    }

    public function ajax_admin_scan() {
        check_ajax_referer('eat-cookies-scan-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $detected_cookies = isset($_POST['cookies']) ? (array) $_POST['cookies'] : [];
        if (empty($detected_cookies)) {
            wp_send_json_success(['new_cookies_found' => 0]);
        }

        $existing_cookies_data = get_option('eat_cookies_data', [
            'necessary' => [],
            'functional' => [],
            'analytics' => [],
            'performance' => [],
            'advertisement' => []
        ]);

        $new_found_count = 0;
        $knowledge_base = $this->get_cookie_knowledge_base();

        foreach ($detected_cookies as $cookie_name) {
            $cookie_name = sanitize_text_field($cookie_name);
            
            // Check if already in our data
            $already_exists = false;
            foreach ($existing_cookies_data as $cat => $cookies) {
                foreach ($cookies as $c) {
                    if ($c['name'] === $cookie_name) {
                        $already_exists = true;
                        break 2;
                    }
                }
            }

            if (!$already_exists) {
                $category = 'functional'; // Default
                $provider = 'Detected by Scanner';
                $expiration = 'Session/Persistent';
                $description = 'Automatically detected cookie.';

                // Try to find in knowledge base
                foreach ($knowledge_base as $pattern => $info) {
                    if (strpos($cookie_name, $pattern) !== false) {
                        $category = $info['category'];
                        $provider = $info['provider'];
                        $expiration = $info['expiration'];
                        $description = $info['description'];
                        break;
                    }
                }

                $existing_cookies_data[$category][] = [
                    'name' => $cookie_name,
                    'provider' => $provider,
                    'expiration' => $expiration,
                    'description' => $description
                ];
                $new_found_count++;
            }
        }

        if ($new_found_count > 0) {
            update_option('eat_cookies_data', $existing_cookies_data);
        }

        wp_send_json_success(['new_cookies_found' => $new_found_count]);
    }
}
