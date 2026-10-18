<?php
/**
 * MelShop — theme settings (admin side).
 */
class ControllerExtensionModuleMsSettings extends Controller {
	private $error = array();

	private $fields = array(
		'color_primary' => '#12a150', 'color_accent' => '#ff6a00', 'color_text' => '#1f2328', 'color_bg' => '#f5f6f8',
		'radius' => '14', 'font' => 'Manrope',
		'phones' => '', 'phone_note' => '', 'hours' => '', 'email' => '', 'address' => '',
		'topbar_text' => '', 'top_links' => '', 'footer_about' => '', 'advantages' => '',
		'social_telegram' => '', 'social_viber' => '', 'social_instagram' => '', 'social_facebook' => '', 'social_tiktok' => '', 'social_youtube' => '',
		'mobile_bar' => '1', 'show_credit' => '1', 'show_desc' => '0', 'credit_href' => '',
	);

	private $fonts = array('Manrope', 'Inter', 'Montserrat', 'Rubik', 'Nunito', 'Open Sans', 'Roboto', 'Golos Text', 'Onest', 'Exo 2');

	public function index() {
		$this->load->language('extension/module/ms_settings');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$in = isset($this->request->post['ms']) && is_array($this->request->post['ms']) ? $this->request->post['ms'] : array();
			$clean = array();
			foreach ($this->fields as $k => $def) {
				$clean[$k] = isset($in[$k]) ? trim((string)$in[$k]) : (in_array($k, array('mobile_bar', 'show_credit', 'show_desc')) ? '0' : $def);
			}
			$this->model_setting_setting->editSetting('module_ms_settings', array(
				'module_ms_settings_status' => isset($this->request->post['module_ms_settings_status']) ? (int)$this->request->post['module_ms_settings_status'] : 1,
				'module_ms_settings_data'   => $clean,
			));
			if (!empty($this->request->post['activate_theme'])) {
				$this->activateTheme();
			}
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module/ms_settings', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = '';
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}

		$token = 'user_token=' . $this->session->data['user_token'];
		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token, true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/ms_settings', $token, true)),
		);
		$data['action'] = $this->url->link('extension/module/ms_settings', $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', $token . '&type=module', true);

		$saved = $this->config->get('module_ms_settings_data');
		if (!is_array($saved)) {
			$saved = array();
		}
		$data['ms'] = array_merge($this->fields, $saved);
		$data['fonts'] = $this->fonts;
		$data['module_ms_settings_status'] = $this->config->get('module_ms_settings_status') === null ? 1 : $this->config->get('module_ms_settings_status');
		$data['theme_active'] = ($this->config->get('config_theme') == 'default' && $this->config->get('theme_default_directory') == 'melshop');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/ms_settings', $data));
	}

	private function activateTheme() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSettingValue('config', 'config_theme', 'default');
		$theme = $this->model_setting_setting->getSetting('theme_default');
		$theme['theme_default_directory'] = 'melshop';
		$theme['theme_default_status'] = 1;
		foreach (array('theme_default_product_limit' => 15, 'theme_default_product_description_length' => 100, 'theme_default_image_category_width' => 80, 'theme_default_image_category_height' => 80, 'theme_default_image_thumb_width' => 600, 'theme_default_image_thumb_height' => 600, 'theme_default_image_popup_width' => 1000, 'theme_default_image_popup_height' => 1000, 'theme_default_image_product_width' => 400, 'theme_default_image_product_height' => 400, 'theme_default_image_additional_width' => 120, 'theme_default_image_additional_height' => 120, 'theme_default_image_related_width' => 400, 'theme_default_image_related_height' => 400, 'theme_default_image_compare_width' => 120, 'theme_default_image_compare_height' => 120, 'theme_default_image_wishlist_width' => 80, 'theme_default_image_wishlist_height' => 80, 'theme_default_image_cart_width' => 80, 'theme_default_image_cart_height' => 80, 'theme_default_image_location_width' => 268, 'theme_default_image_location_height' => 50) as $k => $v) {
			if (empty($theme[$k])) {
				$theme[$k] = $v;
			}
		}
		$this->model_setting_setting->editSetting('theme_default', $theme);
	}

	public function install() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('melshop');
		$this->model_setting_event->addEvent('melshop', 'catalog/view/*/before', 'extension/module/ms_settings/inject', 1, 0);
	}

	public function uninstall() {
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('melshop');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/ms_settings')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}
