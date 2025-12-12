<?php

namespace Opencart\Admin\Controller\Extension\AeroSearch\Event;

class AeroSearch extends \Opencart\System\Engine\Controller {

    public function index(&$route, &$args): void {

        // Загружаем язык
        $this->load->language('extension/aero_search/module/aero_search');

        // Добавляем один пункт меню без детей
        $args['menus'][] = [
            'id'       => 'menu-aero-search',
            'icon'     => 'fa-solid fa-magnifying-glass',
            'name'     => $this->language->get('heading_title'), // Текст из языка (Live Search)
            'href'     => $this->url->link(
                'extension/aero_search/module/aero_search',
                'user_token=' . $this->session->data['user_token'],
                true
            ),
            'children' => [] // нет детей
        ];
    }
}
