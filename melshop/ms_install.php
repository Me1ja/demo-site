<?php
// MelShop installer (CLI). Usage: php ms_install.php /path/to/public_html
$root = rtrim($argv[1] ?? '', '/');
if (!$root || !is_file($root . '/config.php')) { fwrite(STDERR, "Usage: php ms_install.php /path/to/public_html\n"); exit(1); }
include $root . '/config.php';
$m = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$m->set_charset('utf8mb4');
$P = DB_PREFIX;
function e($s) { global $m; return $m->real_escape_string($s); }
function setting($code, $key, $value) {
	global $m, $P;
	$ser = is_array($value) ? 1 : 0;
	$v = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
	$m->query("DELETE FROM `{$P}setting` WHERE store_id=0 AND `code`='" . e($code) . "' AND `key`='" . e($key) . "'");
	$m->query("INSERT INTO `{$P}setting` SET store_id=0, `code`='" . e($code) . "', `key`='" . e($key) . "', `value`='" . e($v) . "', serialized=$ser");
}
function get($key) {
	global $m, $P;
	$r = $m->query("SELECT value FROM `{$P}setting` WHERE store_id=0 AND `key`='" . e($key) . "'")->fetch_assoc();
	return $r ? $r['value'] : null;
}

// 1. extension record
if (!$m->query("SELECT 1 FROM `{$P}extension` WHERE type='module' AND code='ms_settings'")->num_rows) {
	$m->query("INSERT INTO `{$P}extension` SET type='module', code='ms_settings'");
}
// 2. permissions for every admin group that can modify extensions
$r = $m->query("SELECT user_group_id, permission FROM `{$P}user_group`");
while ($g = $r->fetch_assoc()) {
	$p = json_decode($g['permission'], true);
	if (!is_array($p) || empty($p['modify']) || !in_array('marketplace/extension', $p['modify'])) continue;
	foreach (array('access', 'modify') as $t) {
		if (!in_array('extension/module/ms_settings', $p[$t])) $p[$t][] = 'extension/module/ms_settings';
	}
	$m->query("UPDATE `{$P}user_group` SET permission='" . e(json_encode($p)) . "' WHERE user_group_id=" . (int)$g['user_group_id']);
}
// 3. event
$m->query("DELETE FROM `{$P}event` WHERE `code`='melshop'");
$cols = array(); $c = $m->query("SHOW COLUMNS FROM `{$P}event`"); while ($x = $c->fetch_assoc()) $cols[] = $x['Field'];
$sql = "INSERT INTO `{$P}event` SET `code`='melshop', `trigger`='catalog/view/*/before', `action`='extension/module/ms_settings/inject', `status`=1";
if (in_array('sort_order', $cols)) $sql .= ", `sort_order`=0";
$m->query($sql);
// 4. default settings (keep existing values if already configured)
$cur = json_decode((string)get('module_ms_settings_data'), true);
if (!is_array($cur)) {
	$cur = array(
		'color_primary' => '#12a150', 'color_accent' => '#ff6a00', 'color_text' => '#1f2328', 'color_bg' => '#f5f6f8', 'radius' => '14', 'font' => 'Manrope',
		'phones' => "+38 (0XX) 000-00-00", 'phone_note' => 'Щодня з 9:00 до 20:00', 'hours' => 'Пн–Нд 9:00–20:00', 'email' => '', 'address' => '',
		'topbar_text' => 'Безкоштовна доставка від 2000 грн', 'top_links' => '', 'footer_about' => 'Інтернет-магазин техніки для дому.',
		'advantages' => "truck|Доставка по Україні|Новою поштою за 1–2 дні\nshield|Офіційна гарантія|На всю техніку\nrefresh|Обмін і повернення|Протягом 14 днів\ncredit-card|Зручна оплата|Карткою або при отриманні",
		'social_telegram' => '', 'social_viber' => '', 'social_instagram' => '', 'social_facebook' => '', 'social_tiktok' => '', 'social_youtube' => '',
		'mobile_bar' => '1', 'show_credit' => '1', 'show_desc' => '0', 'credit_href' => '',
	);
}
setting('module_ms_settings', 'module_ms_settings_data', $cur);
setting('module_ms_settings', 'module_ms_settings_status', '1');
// 5. activate theme (default extension pointing to melshop directory)
$prev = get('config_theme') . '/' . get('theme_default_directory');
setting('config', 'config_theme', 'default');
setting('theme_default', 'theme_default_directory', 'melshop');
setting('theme_default', 'theme_default_status', '1');
$sizes = array('product_limit' => 15, 'product_description_length' => 100, 'image_category_width' => 80, 'image_category_height' => 80, 'image_thumb_width' => 600, 'image_thumb_height' => 600, 'image_popup_width' => 1000, 'image_popup_height' => 1000, 'image_product_width' => 400, 'image_product_height' => 400, 'image_additional_width' => 120, 'image_additional_height' => 120, 'image_related_width' => 400, 'image_related_height' => 400, 'image_compare_width' => 120, 'image_compare_height' => 120, 'image_wishlist_width' => 80, 'image_wishlist_height' => 80, 'image_cart_width' => 80, 'image_cart_height' => 80, 'image_location_width' => 268, 'image_location_height' => 50);
foreach ($sizes as $k => $v) { if (!get('theme_default_' . $k)) setting('theme_default', 'theme_default_' . $k, $v); }
echo "MELSHOP_INSTALLED (previous theme: $prev)\n";
