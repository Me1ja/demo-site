<?php
/**
 * MelShop — theme settings injector (catalog side).
 * Adds $ms (settings) and $ms_text (labels) to every view rendered by the MelShop theme.
 */
class ControllerExtensionModuleMsSettings extends Controller {
	const VERSION = '1.0.0';
	private static $cache = null;

	public function inject(&$route, &$data) {
		if (!$this->isActive()) {
			return;
		}
		if (self::$cache === null) {
			self::$cache = $this->build();
		}
		if (!isset($data['ms'])) {
			$data['ms'] = self::$cache['ms'];
		}
		if (!isset($data['ms_text'])) {
			$data['ms_text'] = self::$cache['text'];
		}
		if (!isset($data['ms_show_desc'])) {
			$data['ms_show_desc'] = self::$cache['ms']['show_desc'];
		}
	}

	private function isActive() {
		if ($this->config->get('config_theme') == 'default') {
			$dir = $this->config->get('theme_default_directory');
		} else {
			$dir = $this->config->get('config_theme');
		}
		return $dir == 'melshop';
	}

	private function defaults() {
		return array(
			'color_primary' => '#12a150',
			'color_accent'  => '#ff6a00',
			'color_text'    => '#1f2328',
			'color_bg'      => '#f5f6f8',
			'radius'        => '14',
			'font'          => 'Manrope',
			'phones'        => '',
			'phone_note'    => '',
			'hours'         => '',
			'email'         => '',
			'address'       => '',
			'topbar_text'   => '',
			'top_links'     => '',
			'footer_about'  => '',
			'advantages'    => '',
			'social_telegram'  => '',
			'social_viber'     => '',
			'social_instagram' => '',
			'social_facebook'  => '',
			'social_tiktok'    => '',
			'social_youtube'   => '',
			'mobile_bar'    => '1',
			'show_credit'   => '1',
			'show_desc'     => '0',
			'credit_href'   => '',
		);
	}

	private function build() {
		$s = $this->config->get('module_ms_settings_data');
		if (!is_array($s)) {
			$s = array();
		}
		$s = array_merge($this->defaults(), $s);

		$ms = array();
		$ms['version'] = self::VERSION;
		$ms['color_primary'] = $this->color($s['color_primary'], '#12a150');
		$ms['color_primary_dark'] = $this->shade($ms['color_primary'], -0.14);
		$ms['color_accent'] = $this->color($s['color_accent'], '#ff6a00');
		$ms['color_text'] = $this->color($s['color_text'], '#1f2328');
		$ms['color_bg'] = $this->color($s['color_bg'], '#f5f6f8');
		$ms['radius'] = max(0, min(30, (int)$s['radius']));
		$ms['font'] = preg_replace('/[^A-Za-z0-9 ]/', '', $s['font']) ?: 'Manrope';
		$ms['font_url'] = str_replace(' ', '+', $ms['font']);

		$ms['phones'] = array();
		foreach (preg_split('/\r\n|\r|\n/', (string)$s['phones']) as $line) {
			$line = trim($line);
			if ($line === '') continue;
			$ms['phones'][] = array('text' => $line, 'tel' => preg_replace('/[^0-9+]/', '', $line));
		}
		if (!$ms['phones'] && $this->config->get('config_telephone')) {
			$t = $this->config->get('config_telephone');
			$ms['phones'][] = array('text' => $t, 'tel' => preg_replace('/[^0-9+]/', '', $t));
		}
		$ms['phone_note'] = $s['phone_note'];
		$ms['hours'] = $s['hours'] ?: $this->config->get('config_open');
		$ms['email'] = $s['email'] ?: $this->config->get('config_email');
		$ms['address'] = $s['address'] ?: $this->config->get('config_address');
		$ms['topbar_text'] = $s['topbar_text'];
		$ms['footer_about'] = $s['footer_about'];

		$ms['top_links'] = array();
		foreach (preg_split('/\r\n|\r|\n/', (string)$s['top_links']) as $line) {
			$p = array_map('trim', explode('|', $line));
			if (count($p) >= 2 && $p[0] !== '' && $p[1] !== '') {
				$ms['top_links'][] = array('title' => $p[0], 'href' => $p[1]);
			}
		}

		$ms['advantages'] = array();
		foreach (preg_split('/\r\n|\r|\n/', (string)$s['advantages']) as $line) {
			$p = array_map('trim', explode('|', $line));
			if (count($p) >= 2 && $p[1] !== '') {
				$icon = preg_replace('/[^a-z0-9-]/', '', $p[0]);
				$ms['advantages'][] = array('icon' => $icon ? (strpos($icon, 'fa-') === 0 ? $icon : 'fa-' . $icon) : 'fa-check', 'title' => $p[1], 'text' => isset($p[2]) ? $p[2] : '');
			}
		}

		$icons = array('telegram' => 'fa-telegram', 'viber' => 'fa-phone', 'instagram' => 'fa-instagram', 'facebook' => 'fa-facebook', 'tiktok' => 'fa-music', 'youtube' => 'fa-youtube-play');
		$ms['socials'] = array();
		foreach ($icons as $k => $icon) {
			if (!empty($s['social_' . $k])) {
				$ms['socials'][] = array('name' => ucfirst($k), 'href' => $s['social_' . $k], 'icon' => $icon);
			}
		}

		$ms['mobile_bar'] = (bool)$s['mobile_bar'];
		$ms['show_credit'] = (bool)$s['show_credit'];
		$ms['show_desc'] = (bool)$s['show_desc'];
		$ms['credit_href'] = trim((string)$s['credit_href']);
		$ms['store_name'] = $this->config->get('config_name');
		$ms['year'] = date('Y');
		$ms['home_href'] = $this->url->link('common/home');
		$ms['cart_href'] = $this->url->link('checkout/cart');
		$ms['compare_href'] = $this->url->link('product/compare');
		$route = isset($this->request->get['route']) ? $this->request->get['route'] : 'common/home';
		$ms['is_home'] = ($route == 'common/home');

		$lang = new Language($this->config->get('config_language'));
		$lang->load('extension/module/ms_settings');
		$text = $lang->all();

		return array('ms' => $ms, 'text' => $text);
	}

	private function color($c, $def) {
		return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', trim($c)) ? trim($c) : $def;
	}

	private function shade($hex, $p) {
		$hex = ltrim($hex, '#');
		if (strlen($hex) == 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		$out = '#';
		for ($i = 0; $i < 3; $i++) {
			$v = hexdec(substr($hex, $i * 2, 2));
			$v = $p < 0 ? $v * (1 + $p) : $v + (255 - $v) * $p;
			$out .= str_pad(dechex(max(0, min(255, (int)round($v)))), 2, '0', STR_PAD_LEFT);
		}
		return $out;
	}
}
