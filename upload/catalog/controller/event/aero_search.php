<?php

namespace Opencart\Catalog\Controller\Extension\AeroSearch\Event;

class AeroSearch extends \Opencart\System\Engine\Controller
{

       public function headerBefore(string &$route, array &$args): void
    {
        // Добавляем CSS-файл в массив $args, если он передан
        // Но чаще в header можно напрямую использовать document
        $this->document->addStyle('extension/aero_search/catalog/view/stylesheet/aero_search.css');
        $this->document->addStyle('extension/aero_search/catalog/view/stylesheet/addon.css');
    }


    public function footerAfter(&$route, $args, &$output): void
    {
        if ($this->config->get('module_aero_search_status')) {

            $this->load->language('extension/aero_search/module/aero_search');
            $this->load->model('setting/setting');

            // Получаем настройки
            $settings = $this->model_setting_setting->getSetting('module_aero_search');

            // Декодим мультиязычные JSON
            $view_all_results = [];
            foreach ($settings as $key => $value) {
                if (strpos($key, 'module_aero_search_view_all_results_') === 0) {
                    $lang_id = (int) str_replace('module_aero_search_view_all_results_', '', $key);
                    $decoded = json_decode($value, true);
                    $view_all_results[$lang_id] = $decoded['name'] ?? '';
                }
            }

            $aeroSearchOptions = [
                'text_view_all_results'               => $view_all_results[$this->config->get('config_language_id')],
                'text_empty'                          => $this->language->get('text_no_results'),
                'module_aero_search_show_image'       => $this->config->get('module_aero_search_show_image'),
                'module_aero_search_show_price'       => $this->config->get('module_aero_search_show_price'),
                'module_aero_search_show_description' => $this->config->get('module_aero_search_show_description'),
                'module_aero_search_min_length'       => $this->config->get('module_aero_search_min_length'),
                'module_aero_search_show_add_button'  => $this->config->get('module_aero_search_show_add_button'),
                'module_aero_search_language_id'      => $this->config->get('config_language_id')
            ];
            
            $aeroSearchOutput = '<script src="extension/aero_search/catalog/view/javascript/aero_search.js"></script>' . "\n";
            $aeroSearchOutput .= '<script type="text/javascript">' . "\n";
            $aeroSearchOutput .= '$(document).ready(function() {' . "\n";
            $aeroSearchOutput .= 'const options = ' . json_encode($aeroSearchOptions) . ';' . "\n";
            $aeroSearchOutput .= 'AeroSearch.start(options); ' . "\n";
            $aeroSearchOutput .= '});' . "\n";
            $aeroSearchOutput .= '//</script>';

            $output = str_replace('</body>',  $aeroSearchOutput . '</body>', $output);
        }
    }
}
