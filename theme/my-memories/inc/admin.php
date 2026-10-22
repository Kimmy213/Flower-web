<?php
/**
 * หลังบ้านแบบง่ายสำหรับร้าน
 * - หน้า "ข้อมูลร้าน" แก้เบอร์ LINE เวลาเปิด ฯลฯ ได้เองโดยไม่ต้องแตะโค้ด
 * - บัญชีของร้าน (role: shop_manager) เห็นเฉพาะเมนูที่ใช้จริง และหน้าแรกเป็นปุ่มลัด
 * บัญชี admin ยังเห็นทุกอย่างตามปกติ
 */

/* ---------------------------------------------------------------- ข้อมูลร้าน */

function mm_shop_fields() {
	return [
		'ข้อมูลร้าน' => [
			'name'      => [ 'ชื่อร้าน', 'แสดงเป็นโลโก้มุมซ้ายบน' ],
			'sub'       => [ 'บรรทัดใต้ชื่อร้าน', 'เช่น Flower Shop' ],
			'tagline'   => [ 'คำโปรย', 'แสดงใน footer' ],
			'address'   => [ 'ที่อยู่', '' ],
			'hours'     => [ 'เวลาเปิด', 'เช่น เปิดทุกวัน 08:00–18:00' ],
			'map_query' => [ 'ค้นหาใน Google Maps ด้วยคำว่า', 'ใส่ชื่อร้านหรือที่อยู่ แผนที่ใน footer จะชี้ไปที่นี่' ],
		],
		'ช่องทางติดต่อ (ช่องไหนว่าง จะไม่แสดงบนเว็บ)' => [
			'line'      => [ 'LINE ID', 'ใส่ @ นำหน้าถ้าเป็น LINE Official Account ปุ่ม "สั่งทาง LINE" ใช้ ID นี้' ],
			'line_shop' => [ 'ลิงก์ LINE Shopping', '' ],
			'phone'     => [ 'เบอร์โทร', '' ],
			'email'     => [ 'Email', '' ],
			'instagram' => [ 'ลิงก์ Instagram', '' ],
			'facebook'  => [ 'ลิงก์ Facebook', '' ],
			'tiktok'    => [ 'ลิงก์ TikTok', '' ],
		],
	];
}

add_action( 'admin_menu', function () {
	add_menu_page( 'ข้อมูลร้าน', 'ข้อมูลร้าน', 'manage_woocommerce', 'mm-shop', 'mm_shop_page', 'dashicons-store', 3 );
} );

add_action( 'admin_init', function () {
	register_setting( 'mm_shop_group', 'mm_shop', [
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = [];
			foreach ( mm_shop_fields() as $fields ) {
				foreach ( array_keys( $fields ) as $key ) {
					$v           = trim( wp_unslash( $in[ $key ] ?? '' ) );
					$out[ $key ] = in_array( $key, [ 'line_shop', 'instagram', 'facebook', 'tiktok' ], true ) ? esc_url_raw( $v ) : sanitize_text_field( $v );
				}
			}
			$out['show_bar'] = empty( $in['show_bar'] ) ? 0 : 1;
			return $out;
		},
	] );
} );
// ให้บัญชีร้าน (shop_manager) บันทึกหน้านี้ได้
add_filter( 'option_page_capability_mm_shop_group', fn() => 'manage_woocommerce' );

function mm_shop_page() {
	$s = mm_shop();
	echo '<div class="wrap mm-admin"><h1>ข้อมูลร้าน</h1><p class="mm-lead">แก้แล้วกด <strong>บันทึก</strong> หน้าเว็บจะเปลี่ยนทันที</p><form method="post" action="options.php">';
	settings_fields( 'mm_shop_group' );
	foreach ( mm_shop_fields() as $section => $fields ) {
		echo '<h2>' . esc_html( $section ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $fields as $key => [ $label, $help ] ) {
			printf(
				'<tr><th scope="row"><label for="mm-%1$s">%2$s</label></th><td><input type="text" class="regular-text" id="mm-%1$s" name="mm_shop[%1$s]" value="%3$s">%4$s</td></tr>',
				esc_attr( $key ), esc_html( $label ), esc_attr( $s[ $key ] ), $help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
			);
		}
		echo '</table>';
	}
	printf(
		'<h2>เว็บตัวอย่าง</h2><label><input type="checkbox" name="mm_shop[show_bar]" value="1" %s> แสดงแถบ "Showcase · เว็บตัวอย่าง" ด้านบนสุด (เอาติ๊กออกเมื่อเปิดเว็บจริง)</label>',
		checked( $s['show_bar'], 1, false )
	);
	submit_button( 'บันทึก' );
	echo '</form></div>';
}

/* ---------------------------------------------------------------- หน้าแรกของหลังบ้าน: ปุ่มลัด */

function mm_is_shop_user() {
	return is_user_logged_in() && ! current_user_can( 'manage_options' );
}

add_action( 'wp_dashboard_setup', function () {
	if ( mm_is_shop_user() ) {
		global $wp_meta_boxes;
		$wp_meta_boxes['dashboard'] = [];
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
	wp_add_dashboard_widget( 'mm_start', 'จัดการร้าน My Memories', 'mm_dashboard_widget' );
}, 99 );

function mm_dashboard_widget() {
	$links = [
		[ 'plus-alt', 'เพิ่มสินค้าใหม่', admin_url( 'post-new.php?post_type=product' ) ],
		[ 'products', 'สินค้าทั้งหมด', admin_url( 'edit.php?post_type=product' ) ],
		[ 'category', 'หมวดสินค้า', admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ],
		[ 'tag', 'ประเภทสินค้า', admin_url( 'edit-tags.php?taxonomy=mm_arrangement&post_type=product' ) ],
		[ 'edit-page', 'แก้ข้อความหน้าเว็บ', admin_url( 'edit.php?post_type=page' ) ],
		[ 'store', 'ข้อมูลร้าน', admin_url( 'admin.php?page=mm-shop' ) ],
		[ 'admin-site-alt3', 'ดูหน้าเว็บ', home_url( '/' ) ],
	];
	echo '<div class="mm-tiles">';
	foreach ( $links as [ $icon, $label, $url ] ) {
		printf( '<a class="mm-tile" href="%s"><span class="dashicons dashicons-%s"></span>%s</a>', esc_url( $url ), esc_attr( $icon ), esc_html( $label ) );
	}
	echo '</div><h3>เคล็ดลับ</h3><ul class="mm-tips">'
		. '<li><strong>ขึ้นหน้าแรก (Best Sellers):</strong> ไปที่ สินค้าทั้งหมด แล้วกดรูปดาว ☆ ของสินค้านั้นให้เป็น ★</li>'
		. '<li><strong>ซ่อนสินค้าชั่วคราว:</strong> เปิดสินค้า แล้วเปลี่ยนสถานะเป็น "ฉบับร่าง" (Draft)</li>'
		. '<li><strong>แก้ราคาเร็ว ๆ:</strong> ในหน้าสินค้าทั้งหมด ชี้เมาส์ที่สินค้า แล้วกด "แก้ไขด่วน"</li>'
		. '<li><strong>เพิ่มหมวดใหม่:</strong> หมวดจะขึ้นเมนูด้านบนเอง ถ้าเป็นหมวดย่อย ให้เลือก "หมวดหมู่หลัก" ตอนสร้าง</li>'
		. '<li><strong>รูปการ์ดของหมวดหน้าแรก:</strong> แก้ที่ หมวดสินค้า → เลือกหมวด → ภาพย่อ (Thumbnail)</li>'
		. '</ul>';
}

// ไม่ให้ WooCommerce พาไปหน้า "Welcome… start selling" (ตั้งค่าร้านขายของ ซึ่งร้านไม่ได้ใช้)
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );
add_filter( 'woocommerce_enable_setup_wizard', '__return_false' );
add_action( 'admin_init', function () {
	delete_transient( '_wc_activation_redirect' );
	if ( mm_is_shop_user() && 'wc-admin' === ( $_GET['page'] ?? '' ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}, 1 );

/* ---------------------------------------------------------------- เมนูของบัญชีร้าน: เหลือแค่ที่ใช้ */

add_action( 'admin_menu', function () {
	if ( ! mm_is_shop_user() ) return;
	global $menu, $submenu;
	$keep = [ 'index.php', 'edit.php?post_type=product', 'edit.php?post_type=page', 'upload.php', 'mm-shop', 'profile.php' ];
	foreach ( $menu as $i => $item ) {
		if ( ! in_array( $item[2], $keep, true ) && ! str_starts_with( $item[2], 'separator' ) ) unset( $menu[ $i ] );
	}
	$product_keep = [ 'edit.php?post_type=product', 'post-new.php?post_type=product', 'edit-tags.php?taxonomy=product_cat&amp;post_type=product', 'edit-tags.php?taxonomy=mm_arrangement&amp;post_type=product' ];
	foreach ( $submenu['edit.php?post_type=product'] ?? [] as $i => $item ) {
		if ( ! in_array( $item[2], $product_keep, true ) ) unset( $submenu['edit.php?post_type=product'][ $i ] );
	}
	unset( $submenu['index.php'] );
}, 9999 );

add_action( 'admin_bar_menu', function ( $bar ) {
	if ( mm_is_shop_user() ) $bar->remove_node( 'comments' );
}, 999 );

// ฟอร์มสินค้า: ซ่อนช่องที่ร้านไม่ได้ใช้
add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	return mm_is_shop_user() ? array_intersect_key( $tabs, [ 'general' => 1 ] ) : $tabs;
}, 99 );

add_action( 'add_meta_boxes_product', function () {
	if ( ! mm_is_shop_user() ) return;
	foreach ( [ 'tagsdiv-product_tag', 'product_branddiv', 'tagsdiv-product_brand', 'commentsdiv', 'commentstatusdiv', 'slugdiv', 'postcustom' ] as $box ) {
		remove_meta_box( $box, 'product', 'side' );
		remove_meta_box( $box, 'product', 'normal' );
	}
}, 99 );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->id ) return;
	echo '<div class="notice notice-info mm-howto"><p><strong>กรอกตามลำดับนี้:</strong> ① ชื่อสินค้า ② รายละเอียด ③ ราคา (ช่อง "ราคาปกติ" ในกล่องข้อมูลสินค้า ใส่แค่ตัวเลข) ④ ติ๊ก <strong>หมวด</strong> และ <strong>ประเภท</strong> ทางขวา ⑤ ใส่ <strong>รูปสินค้า</strong> ทางขวา ⑥ กด <strong>เผยแพร่</strong></p></div>';
} );

add_action( 'admin_head', function () {
	echo '<style>
		.mm-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin:4px 0 16px}
		.mm-tile{display:flex;flex-direction:column;align-items:center;gap:8px;padding:18px 10px;border:1px solid #e5d6d2;border-radius:10px;background:#fbf7f4;text-decoration:none;color:#3a2e2e;font-size:14px;font-weight:600;text-align:center}
		.mm-tile:hover{border-color:#b76e79;color:#94515c}
		.mm-tile .dashicons{font-size:30px;width:30px;height:30px;color:#b76e79}
		.mm-tips li{margin-bottom:6px}
		.mm-admin .mm-lead{font-size:14px}
		#dashboard-widgets #mm_start{max-width:900px}
	</style>';
	if ( mm_is_shop_user() ) {
		// ใช้สินค้าแบบปกติอย่างเดียว ไม่ต้องเลือกชนิดสินค้า / virtual / downloadable
		echo '<style>#woocommerce-product-data .type_box,.woocommerce-layout__header,.woocommerce-embed-page #wpbody-content > .notice{display:none!important}</style>';
	}
} );
