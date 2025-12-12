<?php

namespace Opencart\Catalog\Controller\Extension\AeroSearch\Module;

class AeroSearch extends \Opencart\System\Engine\Controller
{

    /*
    *  Helper log function for debug 
    */
    function log_data($data, string $file = 'aero_search.log'): void
    {
        $log_file = DIR_LOGS . $file;

        // Преобразуем данные в читаемую строку
        if (is_array($data) || is_object($data)) {
            $output = print_r($data, true); // удобно для массивов и объектов
        } elseif (is_bool($data)) {
            $output = $data ? 'true' : 'false';
        } elseif (is_null($data)) {
            $output = 'null';
        } else {
            $output = (string)$data; // всё остальное приводим к строке
        }

        // Добавляем временную метку и перенос строки
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $output . "\n";

        file_put_contents($log_file, $line, FILE_APPEND);
    }

    /*
    * Index 
    */
    public function index(): void {}


    /*
    * Universal language ID  (RU, EN, FR)
    */
    private function resolveLanguageId(): int
    {

        // Получаем сырой код из запроса
        $raw = $this->request->get['language']
            ?? $this->request->get['lang']
            ?? '';

        $raw = trim(strtolower((string)$raw));
        $raw = str_replace('_', '-', $raw);

        // Лог
        // $this->log_data("resolveLanguageId: raw input = {$raw}");

        // Карта простых преобразований
        $map = [
            // 🇷🇺 Русский
            'ru'        => 'ru-ru',
            'ru-ru'     => 'ru-ru',
            'ru_ru'     => 'ru-ru',
            'russian'   => 'ru-ru',

            // 🇬🇧 Английский
            'en'        => 'en-gb',
            'en-gb'     => 'en-gb',
            'en_us'     => 'en-gb',
            'en-us'     => 'en-gb',
            'gb'        => 'en-gb',
            'english'   => 'en-gb',

            // 🇫🇷 Французский
            'fr'        => 'fr-fr',
            'fr-fr'     => 'fr-fr',
            'fr_ca'     => 'fr-fr',
            'french'    => 'fr-fr',
        ];


        // Если пришёл короткий код, переписываем
        if (isset($map[$raw])) {
            $normalized = $map[$raw];
        } else {
            // fallback по первым двум символам
            $short = substr($raw, 0, 2);
            $normalized = $map[$short] ?? '';
        }

        // Если так и не получили код — берём из конфига
        if (!$normalized) {
            $normalized = strtolower(str_replace('_', '-', $this->config->get('config_language')));
        }

        // $this->log_data("resolveLanguageId: normalized = {$normalized}");

        // Загружаем список языков
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        // Пытаемся найти по ключу массива
        if (isset($languages[$normalized]['language_id'])) {
            return (int)$languages[$normalized]['language_id'];
        }

        // Пытаемся найти по полю code
        foreach ($languages as $key => $lang) {
            if (!isset($lang['code']) || !isset($lang['language_id'])) continue;

            // Полное совпадение
            if (strtolower($lang['code']) === $normalized) {
                return (int)$lang['language_id'];
            }

            // Совпадение по двум буквам
            if (substr(strtolower($lang['code']), 0, 2) === substr($normalized, 0, 2)) {
                return (int)$lang['language_id'];
            }
        }

        // Если уж вообще не нашли — ставим язык по умолчанию
        return 1;
    }

    /*
    *  Aero Search Method
    */
    public function search(): void
    {

        $this->load->model('extension/aero_search/module/aero_search');
        $this->load->language('extension/aero_search/module/aero_search');

        $settings = $this->model_setting_setting->getSetting('module_aero_search');
        $settings_config = $this->model_setting_setting->getSetting('config');

        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $search = $this->request->get['filter_name'];
        } else {
            $search = '';
        }

        if (isset($this->request->get['cat_id'])) {
            $cat_id = (int)$this->request->get['cat_id'];
        } else {
            $cat_id = 0;
        }

        $tag = $search;
        $search_result = 0;
        $error = false;

        if (version_compare(VERSION, '4.0.0.0', '>=')) {
            $currency_code = $this->session->data['currency'] ?? $settings_config('config_currency');
        } else {
            $error = true;
            $json[] = [
                'product_id' => 0,
                'minimum'    => 0,
                'image'      => null,
                'name'       => 'Version Error: ' . VERSION,
                'extra_info' => null,
                'price'      => 0,
                'special'    => 0,
                'url'        => '#'
            ];
        }

        if (!$error) {
            if (isset($search) && strlen($search) >= ($settings['module_aero_search_min_length'] ?? 1)) {

                $this->load->model('tool/image');
                $this->load->model('catalog/product');
                $this->load->model('extension/aero_search/module/aero_search');

                $language_id = $this->resolveLanguageId();

                $this->log_data($language_id);

                // Добавляем language_id в фильтры
                $filter_data = [
                    'filter_search'        => $search,
                    'filter_description'   => 0,
                    'filter_tag'           => $tag,
                    'filter_category_id'   => $cat_id,  // Добавил фильтрацию по категории
                    'filter_sub_category'  => 0,
                    'filter_filter'        => '',
                    'filter_manufacturer_id' => 0,
                    'start'                => 0,
                    'limit'                => $settings['module_aero_search_limit'] ?? 5,
                    'sort'                 => 'pd.name',
                    'order'                => 'ASC',
                    'language_id'          => $language_id
                ];

                // Получаем продукты с учетом языка
                $results = $this->model_extension_aero_search_module_aero_search->getProducts($filter_data);

                $search_result = $this->model_catalog_product->getTotalProducts($filter_data);
                $image_width        = $this->config->get('module_aero_search_image_width') ? (int)$this->config->get('module_aero_search_image_width') : 0;
                $image_height       = $this->config->get('module_aero_search_image_height') ? (int)$this->config->get('module_aero_search_image_height') : 0;
                $title_length       = (int)$this->config->get('module_aero_search_title_length');
                $description_length = (int)$this->config->get('module_aero_search_description_length');

                foreach ($results as $result) {
                    if ($result['image']) {
                        $image = $this->model_tool_image->resize($result['image'], $image_width, $image_height);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $image_width, $image_height);
                    }

                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $currency_code);
                    } else {
                        $price = false;
                    }

                    if ((float)$result['special']) {
                        $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $currency_code);
                    } else {
                        $special = false;
                    }

                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $currency_code);
                    } else {
                        $tax = false;
                    }

                    if ($this->config->get('config_review_status')) {
                        $rating = (int)$result['rating'];
                    } else {
                        $rating = false;
                    }

                    $json['total'] = (int)$search_result;
                    $json['products'][] = array(
                        'product_id' => $result['product_id'],
                        'minimum'    => $result['minimum'],
                        'image'      => $image,
                        'name'       => mb_substr(strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')), 0, $title_length, 'UTF-8') . '..',
                        'extra_info' => mb_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $description_length, 'UTF-8') . '..',
                        'price'      => $price,
                        'url'        => $this->url->link('product/product', 'product_id=' . $result['product_id']),
                        'rating'     => $rating,
                        'special'    => $special,
                        'tax'        => $tax,
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }
}
