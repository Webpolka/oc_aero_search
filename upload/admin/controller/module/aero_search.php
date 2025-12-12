<?php

namespace Opencart\Admin\Controller\Extension\AeroSearch\Module;

class AeroSearch extends \Opencart\System\Engine\Controller
{
	private $error = array();

	/**
	 * Install
	 */
	public function install(): void
	{		
		$this->load->model('extension/aero_search/module/aero_search');		
		$this->load->model('localisation/language');
		$this->load->model('setting/event');
		$this->load->model('setting/setting');

		$this->load->language('extension/aero_search/module/aero_search');

		// Регистрация событий
		$events = [
			[
				'code'        => 'aero_search_header_after',
				'description' => 'Inject Aero Search styles and scripts after header',
				'trigger'     => 'catalog/controller/common/header/after',
				'action'      => 'extension/aero_search/event/aero_search.headerAfter',
				'status'      => 1,
				'sort_order'  => 0
			]
		];

		foreach ($events as $event) {
			$this->model_setting_event->deleteEventByCode($event['code']);
			$this->model_setting_event->addEvent($event);
		}

		// Дефолтные настройки		
		$languages = $this->model_localisation_language->getLanguages();

		$default_view_all_results = [];
		foreach ($languages as $lang) {
			$default_view_all_results['module_aero_search_view_all_results_' . $lang['language_id']] = json_encode([
				'name' => $this->language->get('text_view_all_results_'. $lang['language_id'])
			], JSON_UNESCAPED_UNICODE);
			
		}

		$defaults = [
			'module_aero_search_show_image'         => 0,
			'module_aero_search_show_price'         => 0,
			'module_aero_search_show_description'   => 0,
			'module_aero_search_show_add_button'    => 0,
			'module_aero_search_limit'              => 5,
			'module_aero_search_image_width'        => 50,
			'module_aero_search_image_height'       => 50,
			'module_aero_search_title_length'       => 100,
			'module_aero_search_description_length' => 100,
			'module_aero_search_min_length'         => 1,
			'module_aero_search_status'             => 0
		];

		// Сохраняем мультиязычные
		$settings = array_merge($defaults, $default_view_all_results);

		// Сохраняем все настройки в базе
		$this->model_setting_setting->editSetting('module_aero_search', $settings);	
		$this->model_extension_aero_search_module_aero_search->install();
		
	}

	/**
	 * Uninstall
	 */
	public function uninstall(): void
	{
		$this->load->model('setting/event');
		$this->load->model('setting/setting');

		$this->model_setting_event->deleteEventByCode('aero_search_header_after');	
		$this->model_setting_setting->deleteSetting('module_aero_search');
	}

	/**
	 * Index (Settings)
	 */
	public function index(): void
	{
		$this->load->language('extension/aero_search/module/aero_search');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {

			$post = $this->request->post;

			// Преобразуем языки в плоские ключи для корректного JSON
			if (!empty($post['module_aero_search_view_all_results'])) {
				$flat_langs = [];
				foreach ($post['module_aero_search_view_all_results'] as $lang_id => $val) {
					$flat_langs['module_aero_search_view_all_results_' . $lang_id] = json_encode($val, JSON_UNESCAPED_UNICODE);
				}
				unset($post['module_aero_search_view_all_results']);
				$post = array_merge($post, $flat_langs);
			}


			$this->model_setting_setting->editSetting('module_aero_search', $post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link('extension/aero_search/module/aero_search', 'user_token=' . $this->session->data['user_token'])
			);
		}

		// Errors
		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['error_view_all_results'] = $this->error['view_all_results'] ?? '';
		$data['error_limit'] = $this->error['limit'] ?? '';
		$data['error_width'] = $this->error['width'] ?? '';
		$data['error_height'] = $this->error['height'] ?? '';
		$data['error_title_length'] = $this->error['title_length'] ?? '';
		$data['error_description_length'] = $this->error['description_length'] ?? '';
		$data['error_min_length'] = $this->error['min_length'] ?? '';

		// Breadcrumbs
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
			],
			[
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'])
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/aero_search/module/aero_search', 'user_token=' . $this->session->data['user_token'])
			]
		];

		$data['action'] = $this->url->link('extension/aero_search/module/aero_search', 'user_token=' . $this->session->data['user_token']);
		$data['cancel'] = $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token']);

		// Languages
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages(['sort' => 'code']);

		$default_view_all_results = [];
		foreach ($data['languages'] as &$lang) {
			$flag = 'language/' . $lang['code'] . '/' . $lang['image'];
			if (!is_file($flag)) {
				$flag = 'language/' . $lang['code'] . '/' . $lang['code'] . '.png';
				if (!is_file($flag)) $flag = null;
			}
			$lang['flag_img'] = $flag;

			$default_view_all_results['lang_' . $lang['language_id']] = [
				'name' => $this->language->get('text_view_all_results')
			];
		}
		unset($lang);		

		// Загружаем сохранённые настройки
		$saved = $this->model_setting_setting->getSetting('module_aero_search');

		// Инициализируем массив для мульти-языка
		$data['module_aero_search_view_all_results'] = [];

		// Проходим по сохранённым ключам
		foreach ($saved as $key => $value) {
			if (strpos($key, 'module_aero_search_view_all_results_') === 0) {
				$lang_id = (int) str_replace('module_aero_search_view_all_results_', '', $key);
				$decoded = json_decode($value, true);
				$data['module_aero_search_view_all_results'][$lang_id] = [
					'name' => $decoded['name'] ?? ''
				];
			} else {
				$data[$key] = $value;
			}
		}

		// Если был POST, перекрываем
		foreach ($this->request->post as $key => $value) {
			if ($key === 'module_aero_search_view_all_results') {
				foreach ($value as $lang_id => $v) {
					$data['module_aero_search_view_all_results'][$lang_id] = $v;
				}
			} else {
				$data[$key] = $value;
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['current_lang_id'] = $this->config->get('config_language_id');

		$this->response->setOutput($this->load->view('extension/aero_search/module/aero_search', $data));
	}

	protected function validate(): bool
	{
		if (!$this->user->hasPermission('modify', 'extension/aero_search/module/aero_search')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Validate languages
		if (!empty($this->request->post['module_aero_search_view_all_results'])) {
			foreach ($this->request->post['module_aero_search_view_all_results'] as $lang_id => $val) {
				if (empty($val['name'])) {
					$this->error['view_all_results'][$lang_id] = $this->language->get('error_view_all_results');
				}
			}
		}

		$numeric_fields = [
			'module_aero_search_limit',
			'module_aero_search_image_width',
			'module_aero_search_image_height',
			'module_aero_search_title_length',
			'module_aero_search_description_length',
			'module_aero_search_min_length'
		];

		foreach ($numeric_fields as $field) {
			if (!isset($this->request->post[$field]) || $this->request->post[$field] === '') {
				$this->error[$field] = $this->language->get('error_' . $field);
			}
		}

		return !$this->error;
	}
}
