<?php
/**
 * Plugin Name: DYBOT İçerik Üretme
 * Description: Gemini API destekli makale ve görsel üretimi yapan SEO odaklı içerik üretme aracı.
 * Version: 1.0.0
 * Author: OpenAI Assistant
 * Text Domain: dybot-icerik-uretme
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Dybot_Icerik_Uretme')) {
    class Dybot_Icerik_Uretme
    {
        const OPTION_KEY = 'dybot_icerik_uretme_options';
        const NONCE_ACTION = 'dybot_icerik_uretme_action';
        const TEXT_MODEL = 'gemini-2.5-flash-lite-preview-09-2025';
        const IMAGE_MODEL = 'gemini-2.0-flash-preview-image-generation';

        public function __construct()
        {
            add_action('admin_menu', [$this, 'register_menu']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        }

        public function register_menu(): void
        {
            add_menu_page(
                __('DYBOT İçerik Üretme', 'dybot-icerik-uretme'),
                __('DYBOT İçerik', 'dybot-icerik-uretme'),
                'manage_options',
                'dybot-icerik-uretme',
                [$this, 'render_admin_page'],
                'dashicons-edit-large',
                3
            );
        }

        public function register_settings(): void
        {
            register_setting(self::OPTION_KEY, self::OPTION_KEY, function ($input) {
                $output = [];
                $output['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
                $output['target_language'] = isset($input['target_language']) ? sanitize_text_field($input['target_language']) : 'tr';
                $output['default_words'] = isset($input['default_words']) ? absint($input['default_words']) : 1600;
                return $output;
            });

            add_settings_section(
                'dybot_general_section',
                __('Genel Ayarlar', 'dybot-icerik-uretme'),
                function () {
                    echo '<p>' . esc_html__('Gemini API anahtarınızı ve varsayılan üretim tercihlerinizi girin.', 'dybot-icerik-uretme') . '</p>';
                },
                self::OPTION_KEY
            );

            add_settings_field(
                'dybot_api_key',
                __('Gemini API Anahtarı', 'dybot-icerik-uretme'),
                function () {
                    $options = $this->get_options();
                    printf(
                        '<input type="password" name="%1$s[api_key]" value="%2$s" class="regular-text" placeholder="AIza..." />',
                        esc_attr(self::OPTION_KEY),
                        esc_attr($options['api_key'] ?? '')
                    );
                },
                self::OPTION_KEY,
                'dybot_general_section'
            );

            add_settings_field(
                'dybot_target_language',
                __('Hedef Dil', 'dybot-icerik-uretme'),
                function () {
                    $options = $this->get_options();
                    printf(
                        '<input type="text" name="%1$s[target_language]" value="%2$s" class="regular-text" placeholder="tr" />',
                        esc_attr(self::OPTION_KEY),
                        esc_attr($options['target_language'] ?? 'tr')
                    );
                },
                self::OPTION_KEY,
                'dybot_general_section'
            );

            add_settings_field(
                'dybot_default_words',
                __('Varsayılan Kelime Sayısı', 'dybot-icerik-uretme'),
                function () {
                    $options = $this->get_options();
                    printf(
                        '<input type="number" min="400" max="4000" step="50" name="%1$s[default_words]" value="%2$s" class="small-text" />',
                        esc_attr(self::OPTION_KEY),
                        esc_attr($options['default_words'] ?? 1600)
                    );
                },
                self::OPTION_KEY,
                'dybot_general_section'
            );
        }

        public function enqueue_assets(string $hook): void
        {
            if ($hook !== 'toplevel_page_dybot-icerik-uretme') {
                return;
            }

            wp_enqueue_style(
                'dybot-admin',
                plugins_url('assets/css/admin.css', __FILE__),
                [],
                '1.0.0'
            );

            wp_enqueue_script(
                'dybot-admin',
                plugins_url('assets/js/admin.js', __FILE__),
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('dybot-admin', 'dybotAdmin', [
                'copiedText' => __('İçerik panoya kopyalandı.', 'dybot-icerik-uretme'),
            ]);
        }

        private function get_options(): array
        {
            $defaults = [
                'api_key' => '',
                'target_language' => 'tr',
                'default_words' => 1600,
            ];
            return wp_parse_args(get_option(self::OPTION_KEY, []), $defaults);
        }

        public function render_admin_page(): void
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            $options = $this->get_options();
            $text_result = '';
            $image_result = '';
            $image_mime = '';
            $errors = [];

            if (isset($_POST['dybot_text_submit'])) {
                check_admin_referer(self::NONCE_ACTION);
                $topic = sanitize_text_field($_POST['dybot_topic'] ?? '');
                $keywords = sanitize_text_field($_POST['dybot_keywords'] ?? '');
                $word_count = absint($_POST['dybot_word_count'] ?? $options['default_words']);
                $intent = sanitize_text_field($_POST['dybot_intent'] ?? '');

                if (empty($options['api_key'])) {
                    $errors[] = __('Lütfen önce API anahtarınızı kaydedin.', 'dybot-icerik-uretme');
                } elseif (empty($topic)) {
                    $errors[] = __('Konu başlığı gerekli.', 'dybot-icerik-uretme');
                } else {
                    $text_result = $this->generate_text($topic, $keywords, $word_count, $intent, $options);
                    if (is_wp_error($text_result)) {
                        $errors[] = $text_result->get_error_message();
                        $text_result = '';
                    }
                }
            }

            if (isset($_POST['dybot_image_submit'])) {
                check_admin_referer(self::NONCE_ACTION);
                $image_prompt = sanitize_text_field($_POST['dybot_image_prompt'] ?? '');
                if (empty($options['api_key'])) {
                    $errors[] = __('Lütfen önce API anahtarınızı kaydedin.', 'dybot-icerik-uretme');
                } elseif (empty($image_prompt)) {
                    $errors[] = __('Görsel prompt alanı boş bırakılamaz.', 'dybot-icerik-uretme');
                } else {
                    $image_response = $this->generate_image($image_prompt, $options);
                    if (is_wp_error($image_response)) {
                        $errors[] = $image_response->get_error_message();
                    } else {
                        $image_result = $image_response['data'];
                        $image_mime = $image_response['mime'];
                    }
                }
            }

            echo '<div class="wrap dybot-wrapper">';
            echo '<h1 class="dybot-title">' . esc_html__('DYBOT İçerik Üretme', 'dybot-icerik-uretme') . '</h1>';

            if ($errors) {
                foreach ($errors as $error) {
                    printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($error));
                }
            }

            echo '<div class="dybot-panels">';
            $this->render_settings_panel($options);
            $this->render_text_panel($text_result);
            $this->render_image_panel($image_result, $image_mime);
            echo '</div>';
            echo '</div>';
        }

        private function render_settings_panel(array $options): void
        {
            echo '<section class="dybot-panel">';
            echo '<h2>' . esc_html__('API ve Tercihler', 'dybot-icerik-uretme') . '</h2>';
            echo '<form method="post" action="options.php">';
            settings_fields(self::OPTION_KEY);
            do_settings_sections(self::OPTION_KEY);
            submit_button(__('Kaydet', 'dybot-icerik-uretme'));
            echo '</form>';
            echo '</section>';
        }

        private function render_text_panel(string $text_result): void
        {
            echo '<section class="dybot-panel">';
            echo '<h2>' . esc_html__('SEO Makale Üretimi', 'dybot-icerik-uretme') . '</h2>';
            echo '<form method="post">';
            wp_nonce_field(self::NONCE_ACTION);
            echo '<div class="dybot-field-grid">';
            $this->render_input(__('Konu Başlığı', 'dybot-icerik-uretme'), 'dybot_topic', 'text', ['placeholder' => __('Örn: Sürdürülebilir Enerji Trendleri', 'dybot-icerik-uretme')]);
            $this->render_input(__('Hedef Anahtar Kelimeler', 'dybot-icerik-uretme'), 'dybot_keywords', 'text', ['placeholder' => __('Virgülle ayırın', 'dybot-icerik-uretme')]);
            $this->render_input(__('İçerik Amacı / CTA', 'dybot-icerik-uretme'), 'dybot_intent', 'text', ['placeholder' => __('Blog, ürün tanıtımı, e-ticaret vb.', 'dybot-icerik-uretme')]);
            $this->render_input(__('Kelime Sayısı', 'dybot-icerik-uretme'), 'dybot_word_count', 'number', ['min' => 800, 'max' => 4000, 'step' => 50, 'value' => 1600]);
            echo '</div>';
            submit_button(__('Makale Üret', 'dybot-icerik-uretme'), 'primary', 'dybot_text_submit');
            echo '</form>';

            echo '<textarea id="dybot-text-output" class="dybot-output" rows="20" placeholder="' . esc_attr__('Üretilen içerik burada görünecek.', 'dybot-icerik-uretme') . '">' . esc_textarea($text_result) . '</textarea>';
            echo '<button type="button" class="button button-secondary dybot-copy" data-target="dybot-text-output">' . esc_html__('Metni Kopyala', 'dybot-icerik-uretme') . '</button>';
            echo '</section>';
        }

        private function render_image_panel(string $image_result, string $image_mime): void
        {
            echo '<section class="dybot-panel">';
            echo '<h2>' . esc_html__('Görsel Üretimi', 'dybot-icerik-uretme') . '</h2>';
            echo '<form method="post">';
            wp_nonce_field(self::NONCE_ACTION);
            $this->render_input(__('Görsel Prompt', 'dybot-icerik-uretme'), 'dybot_image_prompt', 'text', ['placeholder' => __('Örn: Gece vakti neon ışıklı futuristik şehir', 'dybot-icerik-uretme')]);
            submit_button(__('Görsel Üret', 'dybot-icerik-uretme'), 'primary', 'dybot_image_submit');
            echo '</form>';

            if ($image_result) {
                $src = sprintf('data:%s;base64,%s', esc_attr($image_mime), esc_attr($image_result));
                echo '<div class="dybot-image-preview">';
                echo '<img src="' . $src . '" alt="Gemini görsel çıktısı" />';
                echo '<textarea readonly class="dybot-output">' . esc_textarea($src) . '</textarea>';
                echo '</div>';
            }

            echo '</section>';
        }

        private function render_input(string $label, string $name, string $type, array $attrs = []): void
        {
            $attributes = '';
            foreach ($attrs as $attr => $value) {
                $attributes .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($value));
            }
            printf(
                '<label class="dybot-field"><span>%1$s</span><input type="%2$s" name="%3$s" %4$s /></label>',
                esc_html($label),
                esc_attr($type),
                esc_attr($name),
                $attributes
            );
        }

        private function generate_text(string $topic, string $keywords, int $word_count, string $intent, array $options)
        {
            $api_key = $options['api_key'];
            $target_language = $options['target_language'];

            $prompt = sprintf(
                'Profesyonel bir SEO içerik yazarı gibi davran. %1$s konusunu %2$d kelime arasında olacak şekilde detaylı, başlıklar içeren, FAQ bölümü ve güçlü CTA barındıran, %%10000 SEO uyumlu bir Türkçe makale olarak yaz. Hedef anahtar kelimeler: %3$s. İçeriğin amacı: %4$s. Dil: %5$s.',
                $topic,
                $word_count,
                $keywords ?: __('Belirtilmedi', 'dybot-icerik-uretme'),
                $intent ?: __('Genel bilgilendirme', 'dybot-icerik-uretme'),
                $target_language
            );

            $body = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 32,
                    'topP' => 0.95,
                    'maxOutputTokens' => 4096,
                ],
            ];

            $response = wp_remote_post(
                sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', self::TEXT_MODEL, rawurlencode($api_key)),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => wp_json_encode($body),
                    'timeout' => 60,
                ]
            );

            if (is_wp_error($response)) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code >= 400) {
                $message = $body['error']['message'] ?? __('Gemini API hatası.', 'dybot-icerik-uretme');
                return new WP_Error('dybot_text_error', $message);
            }

            if (empty($body['candidates'][0]['content']['parts'][0]['text'])) {
                return new WP_Error('dybot_text_empty', __('API boş yanıt döndürdü.', 'dybot-icerik-uretme'));
            }

            return $body['candidates'][0]['content']['parts'][0]['text'];
        }

        private function generate_image(string $prompt, array $options)
        {
            $api_key = $options['api_key'];

            $body = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ];

            $response = wp_remote_post(
                sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateImage?key=%s', self::IMAGE_MODEL, rawurlencode($api_key)),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => wp_json_encode($body),
                    'timeout' => 60,
                ]
            );

            if (is_wp_error($response)) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code >= 400) {
                $message = $body['error']['message'] ?? __('Gemini API hatası.', 'dybot-icerik-uretme');
                return new WP_Error('dybot_image_error', $message);
            }

            if (empty($body['generatedImages'][0]['data'])) {
                return new WP_Error('dybot_image_empty', __('API görsel verisi döndürmedi.', 'dybot-icerik-uretme'));
            }

            $mime = $body['generatedImages'][0]['mimeType'] ?? 'image/png';
            return [
                'data' => $body['generatedImages'][0]['data'],
                'mime' => $mime,
            ];
        }
    }

    new Dybot_Icerik_Uretme();
}
