<?php
/**
 * My Memories — showcase theme
 */

// ข้อมูลร้าน ค่าที่เป็น '' ยังไม่มีข้อมูลจริง (จะไม่แสดงบนเว็บ)
function mm_shop() {
	return [
		'name'      => 'My Memories',
		'sub'       => 'Flower Shop',
		'tagline'   => 'Romantic · Classy · Modern',
		'address'   => 'Sukhumvit 101/1, Bangkok',
		'hours'     => 'เปิดทุกวัน 08:00–18:00',
		'phone'     => '',
		'email'     => '',
		'line'      => '@prd.plus',
		'line_shop' => 'https://shop.line.me/@prd.plus',
		'instagram' => 'https://www.instagram.com/my.memories.flowershop/',
		'facebook'  => '',
		'tiktok'    => '',
		'map_query' => 'My Memories Flower Shop Sukhumvit 101/1',
	];
}

function mm_line_url( $text = '' ) {
	$id = mm_shop()['line'];
	return $text
		? 'https://line.me/R/oaMessage/' . rawurlencode( $id ) . '/?' . rawurlencode( $text )
		: 'https://line.me/R/ti/p/' . rawurlencode( $id );
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_editor_style( 'assets/theme.css' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'mm-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Noto+Serif+Thai:wght@500;600&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap', [], null );
	wp_enqueue_style( 'mm-theme', get_theme_file_uri( 'assets/theme.css' ), [], wp_get_theme()->get( 'Version' ) );
} );

// แถบบอกว่าเป็นเว็บตัวอย่าง
add_action( 'wp_body_open', function () {
	echo '<div class="mm-showcase-bar">Showcase · เว็บตัวอย่างสำหรับร้าน My Memories — รูปและราคาสินค้ายังเป็นข้อมูลตัวอย่าง</div>';
} );

/* ---------------------------------------------------------------- WooCommerce: โชว์สินค้า สั่งทาง LINE */

add_filter( 'woocommerce_is_purchasable', '__return_false' );
add_filter( 'woocommerce_show_page_title', '__return_false' );
add_filter( 'woocommerce_product_add_to_cart_text', fn() => 'ดูรายละเอียด' );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
add_filter( 'woocommerce_product_related_products_heading', fn() => 'คุณอาจชอบ' );
add_filter( 'woocommerce_product_description_tab_title', fn() => 'รายละเอียด' );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );

add_filter( 'woocommerce_catalog_orderby', fn() => [
	'menu_order' => 'แนะนำ',
	'date'       => 'ใหม่ล่าสุด',
	'price'      => 'ราคา: ต่ำ → สูง',
	'price-desc' => 'ราคา: สูง → ต่ำ',
] );

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$text = sprintf( 'สนใจสั่ง: %s (%s บาท)', $product->get_name(), number_format( (float) $product->get_price() ) );
	printf(
		'<div class="mm-order"><a class="mm-btn" href="%s" target="_blank" rel="noopener">สั่งทาง LINE</a><a class="mm-btn ghost" href="%s" target="_blank" rel="noopener">ดูใน LINE Shopping</a><p>ทักแชทเพื่อเช็กดอกไม้ตามฤดูกาล วันจัดส่ง และการ์ดข้อความ</p></div>',
		esc_url( mm_line_url( $text ) ),
		esc_url( mm_shop()['line_shop'] )
	);
}, 31 );

add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['reviews'], $tabs['additional_information'] );
	return $tabs;
} );

/* ---------------------------------------------------------------- Shortcodes */

add_shortcode( 'mm_logo', function () {
	$s = mm_shop();
	return sprintf(
		'<a class="mm-logo" href="%s"><span class="mm-logo-name">%s</span><span class="mm-logo-sub">%s</span></a>',
		esc_url( home_url( '/' ) ), esc_html( $s['name'] ), esc_html( $s['sub'] )
	);
} );

function mm_card( $url, $img, $title, $sub = '' ) {
	return sprintf(
		'<a class="mm-card" href="%s"><span class="mm-card-media">%s</span><span class="mm-card-title">%s</span>%s</a>',
		esc_url( $url ), $img ? wp_get_attachment_image( $img, 'large' ) : '', esc_html( $title ),
		$sub ? '<span class="mm-card-sub">' . esc_html( $sub ) . '</span>' : ''
	);
}

// [mm_cards set="collections|customers|event"] การ์ดลิงก์ไปหมวด/หน้าย่อย (setup.php เก็บรายการไว้ใน option)
add_shortcode( 'mm_cards', function ( $atts ) {
	$set   = shortcode_atts( [ 'set' => 'collections' ], $atts )['set'];
	$cards = get_option( 'mm_cards_' . $set, [] );
	$html  = '';
	foreach ( $cards as $c ) {
		$url   = isset( $c['term'] ) ? get_term_link( $c['term'], 'product_cat' ) : get_permalink( get_page_by_path( $c['page'] ) );
		$html .= mm_card( is_wp_error( $url ) ? '#' : $url, $c['img'] ?? 0, $c['title'], $c['sub'] ?? '' );
	}
	return '<div class="mm-cards mm-cards-' . esc_attr( $set ) . '">' . $html . '</div>';
} );

// แถบกรองสินค้า: หมวด, หมวดย่อย, ประเภท (การเรียงราคาใช้ dropdown ของ WooCommerce)
add_shortcode( 'mm_shop_filters', function () {
	$current = is_product_category() ? get_queried_object() : null;
	$top     = $current ? ( $current->parent ? get_term( $current->parent, 'product_cat' ) : $current ) : null;
	$chip    = fn( $url, $label, $on ) => sprintf( '<a class="mm-chip%s" href="%s">%s</a>', $on ? ' is-on' : '', esc_url( $url ), esc_html( $label ) );
	$keep    = fn( $url ) => add_query_arg( array_filter( [ 'orderby' => $_GET['orderby'] ?? null, 'filter_arrangement' => $_GET['filter_arrangement'] ?? null ] ), $url );

	$title = $current ? $current->name : 'All Collections';
	$intro = $current && $current->description ? $current->description : 'ดอกไม้และของขวัญทั้งหมดของร้าน เลือกหมวด ประเภท หรือเรียงตามราคาได้';

	$row1 = $chip( $keep( get_permalink( wc_get_page_id( 'shop' ) ) ), 'ทั้งหมด', ! $current );
	foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => [ get_option( 'default_product_cat' ) ] ] ) as $t ) {
		$row1 .= $chip( $keep( get_term_link( $t ) ), $t->name, $top && $top->term_id === $t->term_id );
	}

	$row2 = '';
	if ( $top ) {
		foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $top->term_id, 'hide_empty' => false ] ) as $t ) {
			$row2 .= $chip( $keep( get_term_link( $t ) ), $t->name, $current->term_id === $t->term_id );
		}
	}

	$active = sanitize_title( $_GET['filter_arrangement'] ?? '' );
	$base   = remove_query_arg( [ 'filter_arrangement', 'paged' ] );
	$row3   = $chip( $base, 'ทุกประเภท', ! $active );
	foreach ( get_terms( [ 'taxonomy' => 'pa_arrangement', 'hide_empty' => false ] ) as $t ) {
		$row3 .= $chip( add_query_arg( 'filter_arrangement', $t->slug, $base ), $t->name, $active === $t->slug );
	}

	return '<div class="mm-shop-head"><p class="mm-eyebrow">Collections</p><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $intro ) . '</p></div>'
		. '<div class="mm-filters"><div class="mm-chips">' . $row1 . '</div>'
		. ( $row2 ? '<div class="mm-chips sub">' . $row2 . '</div>' : '' )
		. '<div class="mm-chips type"><span class="mm-chips-label">ประเภท</span>' . $row3 . '</div></div>';
} );

add_shortcode( 'mm_footer', function () {
	$s = mm_shop();

	$contact = '<li>' . esc_html( $s['address'] ) . '</li><li>' . esc_html( $s['hours'] ) . '</li>';
	$contact .= '<li>LINE: <a href="' . esc_url( mm_line_url() ) . '" target="_blank" rel="noopener">' . esc_html( $s['line'] ) . '</a></li>';
	if ( $s['phone'] ) $contact .= '<li>โทร: <a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $s['phone'] ) ) . '">' . esc_html( $s['phone'] ) . '</a></li>';
	if ( $s['email'] ) $contact .= '<li>Email: <a href="mailto:' . esc_attr( $s['email'] ) . '">' . esc_html( $s['email'] ) . '</a></li>';

	$social = '';
	foreach ( [ 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'line_shop' => 'LINE Shopping' ] as $k => $label ) {
		if ( $s[ $k ] ) $social .= '<a href="' . esc_url( $s[ $k ] ) . '" target="_blank" rel="noopener">' . $label . '</a>';
	}
	$social .= '<a href="' . esc_url( mm_line_url() ) . '" target="_blank" rel="noopener">LINE</a>';

	$links = '';
	foreach ( (array) get_option( 'mm_quick_links', [] ) as $label => $url ) {
		$links .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}

	$map = 'https://maps.google.com/maps?q=' . rawurlencode( $s['map_query'] ) . '&z=15&output=embed';

	return '<footer id="contact" class="mm-footer"><div class="mm-footer-grid">'
		. '<div><a class="mm-logo light" href="' . esc_url( home_url( '/' ) ) . '"><span class="mm-logo-name">' . esc_html( $s['name'] ) . '</span><span class="mm-logo-sub">' . esc_html( $s['sub'] ) . '</span></a><p>' . esc_html( $s['tagline'] ) . '</p><div class="mm-social">' . $social . '</div></div>'
		. '<div><h4>Contact</h4><ul>' . $contact . '</ul></div>'
		. '<div><h4>Quick links</h4><ul>' . $links . '</ul></div>'
		. '<div class="mm-map"><iframe title="แผนที่ร้าน" src="' . esc_url( $map ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>'
		. '</div><p class="mm-copy">© ' . gmdate( 'Y' ) . ' ' . esc_html( $s['name'] . ' ' . $s['sub'] ) . '</p></footer>';
} );
