<?php

namespace Opencart\Admin\Model\Extension\AeroSearch\Module;

class AeroSearch extends \Opencart\System\Engine\Model
{

    private const MODULE_CODE = 'aero_search.aero_search';
    private const MODULE_KEY  = 'module_aero_search_id';
    private const SETTINGS_KEY = 'module_aero_search_settings';

    /**
     * Установка модуля
     */
    public function install(): void
    {
        $this->load->model('setting/module');
        $this->load->model('setting/setting');

        // Проверяем, существует ли уже module_id в settings
        $module_id = (int)$this->model_setting_setting->getValue(self::MODULE_KEY);

        if (!$module_id) {
            // Создаём единственный экземпляр модуля
            $module_id = $this->model_setting_module->addModule(self::MODULE_CODE, [
                'name'   => 'Aero Search',
                'status' => 1
            ]);

            // Сохраняем ID модуля в settings
            $this->model_setting_setting->editValue('module_aero_search', self::MODULE_KEY, $module_id);

            // Создаём пустые настройки
            $this->model_setting_setting->editValue('module_aero_search', self::SETTINGS_KEY, []);
        }
    }


    /**
     * Удаление модуля
     */
    public function uninstall(): void
    {
        $this->load->model('setting/module');
        $this->load->model('setting/setting');

        $module_id = (int)$this->model_setting_setting->getValue(self::MODULE_KEY);

        if ($module_id) {
            $this->model_setting_module->deleteModule($module_id);
        }

        $this->model_setting_setting->deleteSetting('module_aero_search');
    }

    /**
     * Получить ID модуля
     */
    public function getModuleId(): int
    {
        $this->load->model('setting/setting');
        return (int)$this->model_setting_setting->getValue(self::MODULE_KEY);
    }

    /**
     * Получить настройки модуля
     */
    public function getSettings(): array
    {
        $this->load->model('setting/setting');
        return (array)$this->model_setting_setting->getValue(self::SETTINGS_KEY);
    }

    /**
     * Сохранить настройки модуля
     */
    public function editSettings(array $data): void
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editValue('module_aero_search', self::SETTINGS_KEY, $data);
    }

    /**
     * Получить данные модуля
     */
    public function getModule(int $module_id): array
    {
        $this->load->model('setting/module');
        return $this->model_setting_module->getModule($module_id) ?? [];
    }

    /**
     * Редактировать данные модуля
     */
    public function editModule(int $module_id, array $data): void
    {
        $this->load->model('setting/module');
        $this->model_setting_module->editModule($module_id, $data);
    }
}
