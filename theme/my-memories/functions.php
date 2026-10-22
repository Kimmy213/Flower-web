<?php
/**
 * My Memories — showcase theme
 *
 * ทุกอย่างที่ร้านต้องแก้ อยู่ในหลังบ้าน ไม่ต้องแตะไฟล์นี้:
 *   ข้อมูลร้าน → เมนู "ข้อมูลร้าน" (inc/admin.php)
 *   เมนูด้านบน, การ์ดหน้าแรก, quick links → สร้างจากหมวดสินค้าและหน้าเว็บอัตโนมัติ
 */

require_once __DIR__ . '/inc/admin.php';

// ค่าเริ่มต้น ร้านแก้ทับได้ที่หลังบ้าน (option: mm_shop) ช่องว่าง = ไม่แสดงบนเว็บ
function mm_shop() {
	$defaults = [
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
		'show_bar'  => 1,
	];
	return array_merge( $defaults, (array) get_option( 'mm_shop', [] ) );
}

function mm_line_url( $text = '' ) {
	$id = mm_shop()['line'];
	if ( ! $id ) return '#contact';
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
	add_post_type_support( 'page', 'excerpt' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'mm-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Noto+Serif+Thai:wght@500;600&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap', [], null );
	wp_enqueue_style( 'mm-theme', get_theme_file_uri( 'assets/theme.css' ), [], wp_get_theme()->get( 'Version' ) );
} );

add_action( 'wp_body_open', function () {
	if ( mm_shop()['show_bar'] ) {
		echo '<div class="mm-showcase-bar">Showcase · เว็บตัวอย่างสำหรับร้าน ' . esc_html( mm_shop()['name'] ) . ' — รูปและราคาสินค้ายังเป็นข้อมูลตัวอย่าง</div>';
	}
} );

/* ---------------------------------------------------------------- ประเภทสินค้า (ช่อ กล่อง กระเช้า …) */

// เป็น taxonomy แบบติ๊กเลือกข้างขวาของหน้าแก้สินค้า เหมือนหมวด
// (ไม่ใช้ product attribute ของ WooCommerce เพราะต้องกดหลายขั้นในแท็บ Attributes)
add_action( 'init', function () {
	register_taxonomy( 'mm_arrangement', [ 'product' ], [
		'labels'            => [
			'name' => 'ประเภทสินค้า', 'singular_name' => 'ประเภทสินค้า', 'menu_name' => 'ประเภทสินค้า',
			'add_new_item' => 'เพิ่มประเภทใหม่', 'edit_item' => 'แก้ไขประเภท', 'search_items' => 'ค้นหาประเภท', 'all_items' => 'ประเภททั้งหมด',
		],
		'hierarchical'      => true,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => false,
		'rewrite'           => false,
		'capabilities'      => [ 'manage_terms' => 'manage_product_terms', 'edit_terms' => 'edit_product_terms', 'delete_terms' => 'delete_product_terms', 'assign_terms' => 'assign_product_terms' ],
	] );
} );

// ลากเรียงลำดับประเภทในหลังบ้านได้ เหมือนหมวดสินค้า
add_filter( 'woocommerce_sortable_taxonomies', fn( $t ) => array_merge( $t, [ 'mm_arrangement' ] ) );

// ?arrangement=box ในหน้ารวมสินค้า/หมวด
add_action( 'woocommerce_product_query', function ( $q ) {
	$type = sanitize_title( $_GET['arrangement'] ?? '' );
	if ( ! $type ) return;
	$tax   = (array) $q->get( 'tax_query' );
	$tax[] = [ 'taxonomy' => 'mm_arrangement', 'field' => 'slug', 'terms' => [ $type ] ];
	$q->set( 'tax_query', $tax );
} );

/* ---------------------------------------------------------------- WooCommerce: โชว์สินค้า สั่งทาง LINE */

add_filter( 'woocommerce_is_purchasable', '__return_false' );
// ไม่มีตะกร้า จึงไม่ให้ WooCommerce แทรกไอคอนตะกร้า/บัญชีลูกค้าลง header
add_filter( 'hooked_block_types', fn( $blocks ) => array_values( array_diff( $blocks, [ 'woocommerce/mini-cart', 'woocommerce/customer-account' ] ) ), 99 );
// ซ่อนแถบ admin หน้าเว็บ ให้ลูกค้าเห็นเหมือนเว็บจริง (เข้าหลังบ้านที่ /wp-admin)
add_filter( 'show_admin_bar', '__return_false' );
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
	$s    = mm_shop();
	$text = sprintf( 'สนใจสั่ง: %s (%s บาท)', $product->get_name(), number_format( (float) $product->get_price() ) );
	echo '<div class="mm-order">';
	if ( $s['line'] ) printf( '<a class="mm-btn" href="%s" target="_blank" rel="noopener">สั่งทาง LINE</a>', esc_url( mm_line_url( $text ) ) );
	if ( $s['line_shop'] ) printf( '<a class="mm-btn ghost" href="%s" target="_blank" rel="noopener">ดูใน LINE Shopping</a>', esc_url( $s['line_shop'] ) );
	echo '<p>ทักแชทเพื่อเช็กดอกไม้ตามฤดูกาล วันจัดส่ง และการ์ดข้อความ</p></div>';
}, 31 );

add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['reviews'], $tabs['additional_information'] );
	return $tabs;
} );

/* ---------------------------------------------------------------- เมนูด้านบน: สร้างจากหมวดและหน้าอัตโนมัติ */

function mm_menu_markup() {
	$link = fn( $label, $url ) => '<!-- wp:navigation-link {"label":' . wp_json_encode( $label ) . ',"url":' . wp_json_encode( $url ) . '} /-->';
	$sub  = fn( $label, $url, $kids ) => $kids
		? '<!-- wp:navigation-submenu {"label":' . wp_json_encode( $label ) . ',"url":' . wp_json_encode( $url ) . '} -->' . implode( '', $kids ) . '<!-- /wp:navigation-submenu -->'
		: $link( $label, $url );

	$out = '';
	foreach ( mm_top_categories() as $cat ) {
		$kids = array_map( fn( $c ) => $link( $c->name, get_term_link( $c ) ), get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $cat->term_id, 'hide_empty' => false ] ) );
		$out .= $sub( $cat->name, get_term_link( $cat ), $kids );
	}
	foreach ( mm_menu_pages() as $page ) {
		$kids = array_map( fn( $p ) => $link( $p->post_title, get_permalink( $p ) ), mm_child_pages( $page->ID ) );
		$out .= $sub( $page->post_title, get_permalink( $page ), $kids );
	}
	return $out . $link( 'Contact', '#contact' );
}

function mm_top_categories() {
	return get_terms( [ 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => [ (int) get_option( 'default_product_cat' ) ] ] );
}

// หน้าระดับบนที่ขึ้นเมนู: ทุกหน้าที่ไม่ใช่หน้าแรกและหน้าของ WooCommerce (เรียงตาม "ลำดับ" ของหน้า)
function mm_menu_pages() {
	$skip = array_filter( [ (int) get_option( 'page_on_front' ), wc_get_page_id( 'shop' ), wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ), wc_get_page_id( 'myaccount' ), (int) get_option( 'wp_page_for_privacy_policy' ), wc_terms_and_conditions_page_id(), wc_get_page_id( 'refund_returns' ) ] );
	return get_pages( [ 'parent' => 0, 'sort_column' => 'menu_order,post_title', 'exclude' => $skip ] );
}

function mm_child_pages( $parent ) {
	return get_pages( [ 'parent' => $parent, 'sort_column' => 'menu_order,post_title' ] );
}

add_filter( 'render_block_data', function ( $block ) {
	if ( 'core/navigation' === $block['blockName'] && str_contains( $block['attrs']['className'] ?? '', 'mm-nav' ) ) {
		$block['innerBlocks'] = parse_blocks( mm_menu_markup() );
		$block['innerBlocks'] = array_values( array_filter( $block['innerBlocks'], fn( $b ) => $b['blockName'] ) );
	}
	return $block;
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

// [mm_collections] การ์ดหมวดหลัก (รูป = ภาพย่อของหมวด, คำใต้ชื่อ = วลีแรกของคำอธิบายหมวด) + หน้า Event & Party
add_shortcode( 'mm_collections', function () {
	$html = '';
	foreach ( mm_top_categories() as $cat ) {
		// คำใต้การ์ด = วลีแรกของคำอธิบายหมวด (ถึงเว้นวรรคแรก)
		$sub   = explode( ' ', trim( wp_strip_all_tags( $cat->description ) ) )[0];
		$html .= mm_card( get_term_link( $cat ), (int) get_term_meta( $cat->term_id, 'thumbnail_id', true ), $cat->name, $sub );
	}
	foreach ( mm_menu_pages() as $page ) {
		if ( 'event-and-party' === $page->post_name ) $html .= mm_card( get_permalink( $page ), get_post_thumbnail_id( $page ), $page->post_title, $page->post_excerpt );
	}
	return '<div class="mm-cards mm-cards-collections">' . $html . '</div>';
} );

// [mm_subpages parent="our-customers"] การ์ดของหน้าย่อย (รูป = รูปเด่นของหน้า, คำอธิบาย = ข้อความสรุป/excerpt)
add_shortcode( 'mm_subpages', function ( $atts ) {
	$slug   = shortcode_atts( [ 'parent' => '' ], $atts )['parent'];
	$parent = get_page_by_path( $slug );
	if ( ! $parent ) return '';
	$kids = mm_child_pages( $parent->ID );
	$html = implode( '', array_map( fn( $p ) => mm_card( get_permalink( $p ), get_post_thumbnail_id( $p ), $p->post_title, $p->post_excerpt ), $kids ) );
	return '<div class="mm-cards mm-cards-' . ( count( $kids ) <= 2 ? 'event' : 'customers' ) . '">' . $html . '</div>';
} );

// แถบกรองสินค้า: หมวด, หมวดย่อย, ประเภท (การเรียงราคาใช้ dropdown ของ WooCommerce)
add_shortcode( 'mm_shop_filters', function () {
	$current = is_product_category() ? get_queried_object() : null;
	$top     = $current ? ( $current->parent ? get_term( $current->parent, 'product_cat' ) : $current ) : null;
	$chip    = fn( $url, $label, $on ) => sprintf( '<a class="mm-chip%s" href="%s">%s</a>', $on ? ' is-on' : '', esc_url( $url ), esc_html( $label ) );
	$keep    = fn( $url ) => add_query_arg( array_filter( [ 'orderby' => $_GET['orderby'] ?? null, 'arrangement' => $_GET['arrangement'] ?? null ] ), $url );

	$title = $current ? $current->name : 'All Collections';
	$intro = $current && $current->description ? $current->description : 'ดอกไม้และของขวัญทั้งหมดของร้าน เลือกหมวด ประเภท หรือเรียงตามราคาได้';

	$row1 = $chip( $keep( get_permalink( wc_get_page_id( 'shop' ) ) ), 'ทั้งหมด', ! $current );
	foreach ( mm_top_categories() as $t ) {
		$row1 .= $chip( $keep( get_term_link( $t ) ), $t->name, $top && $top->term_id === $t->term_id );
	}

	$row2 = '';
	if ( $top ) {
		foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $top->term_id, 'hide_empty' => false ] ) as $t ) {
			$row2 .= $chip( $keep( get_term_link( $t ) ), $t->name, $current->term_id === $t->term_id );
		}
	}

	$active = sanitize_title( $_GET['arrangement'] ?? '' );
	$base   = remove_query_arg( [ 'arrangement', 'paged' ] );
	$row3   = $chip( $base, 'ทุกประเภท', ! $active );
	foreach ( get_terms( [ 'taxonomy' => 'mm_arrangement', 'hide_empty' => false ] ) as $t ) {
		$row3 .= $chip( add_query_arg( 'arrangement', $t->slug, $base ), $t->name, $active === $t->slug );
	}

	return '<div class="mm-shop-head"><p class="mm-eyebrow">Collections</p><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $intro ) . '</p></div>'
		. '<div class="mm-filters"><div class="mm-chips">' . $row1 . '</div>'
		. ( $row2 ? '<div class="mm-chips sub">' . $row2 . '</div>' : '' )
		. '<div class="mm-chips type"><span class="mm-chips-label">ประเภท</span>' . $row3 . '</div></div>';
} );

add_shortcode( 'mm_footer', function () {
	$s = mm_shop();

	$contact = '';
	if ( $s['address'] ) $contact .= '<li>' . esc_html( $s['address'] ) . '</li>';
	if ( $s['hours'] ) $contact .= '<li>' . esc_html( $s['hours'] ) . '</li>';
	if ( $s['line'] ) $contact .= '<li>LINE: <a href="' . esc_url( mm_line_url() ) . '" target="_blank" rel="noopener">' . esc_html( $s['line'] ) . '</a></li>';
	if ( $s['phone'] ) $contact .= '<li>โทร: <a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $s['phone'] ) ) . '">' . esc_html( $s['phone'] ) . '</a></li>';
	if ( $s['email'] ) $contact .= '<li>Email: <a href="mailto:' . esc_attr( $s['email'] ) . '">' . esc_html( $s['email'] ) . '</a></li>';

	$social = '';
	foreach ( [ 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'line_shop' => 'LINE Shopping' ] as $k => $label ) {
		if ( $s[ $k ] ) $social .= '<a href="' . esc_url( $s[ $k ] ) . '" target="_blank" rel="noopener">' . $label . '</a>';
	}
	if ( $s['line'] ) $social .= '<a href="' . esc_url( mm_line_url() ) . '" target="_blank" rel="noopener">LINE</a>';

	$links = '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li><li><a href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">All Collections</a></li>';
	foreach ( mm_top_categories() as $cat ) $links .= '<li><a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a></li>';
	foreach ( mm_menu_pages() as $page ) $links .= '<li><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $page->post_title ) . '</a></li>';

	$map = $s['map_query'] ? '<div class="mm-map"><iframe title="แผนที่ร้าน" src="' . esc_url( 'https://maps.google.com/maps?q=' . rawurlencode( $s['map_query'] ) . '&z=15&output=embed' ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>' : '';

	return '<footer id="contact" class="mm-footer"><div class="mm-footer-grid">'
		. '<div><a class="mm-logo light" href="' . esc_url( home_url( '/' ) ) . '"><span class="mm-logo-name">' . esc_html( $s['name'] ) . '</span><span class="mm-logo-sub">' . esc_html( $s['sub'] ) . '</span></a><p>' . esc_html( $s['tagline'] ) . '</p><div class="mm-social">' . $social . '</div></div>'
		. '<div><h4>Contact</h4><ul>' . $contact . '</ul></div>'
		. '<div><h4>Quick links</h4><ul>' . $links . '</ul></div>'
		. $map
		. '</div><p class="mm-copy">© ' . gmdate( 'Y' ) . ' ' . esc_html( $s['name'] . ' ' . $s['sub'] ) . '</p></footer>';
} );
