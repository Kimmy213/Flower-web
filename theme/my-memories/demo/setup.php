<?php
/**
 * สร้างข้อมูลตัวอย่างของเว็บโชว์เคส: ตั้งค่าร้าน, หมวด, สินค้า, หน้า และเมนู
 * เรียกครั้งเดียวจาก blueprint:  require get_theme_file_path('demo/setup.php'); mm_demo_setup();
 */

function mm_demo_setup() {
	if ( get_option( 'mm_demo_done' ) ) return;
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	wp_set_current_user( 1 );
	$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
	if ( wc_get_page_id( 'shop' ) < 1 ) WC_Install::create_pages();

	mm_setup_options();
	$types = mm_setup_types();
	$cats  = mm_setup_categories();
	$imgs  = mm_setup_images();
	mm_setup_products( $cats, $types, $imgs );
	$pages = mm_setup_pages( $imgs );
	mm_setup_cards( $cats, $imgs );
	mm_setup_menu( $cats, $pages );

	update_option( 'mm_demo_done', 1 );
	flush_rewrite_rules();
}

/* ---------------------------------------------------------------- ตั้งค่า */

function mm_setup_options() {
	update_option( 'blogname', 'My Memories Flower Shop' );
	update_option( 'blogdescription', 'Romantic · Classy · Modern' );
	update_option( 'timezone_string', 'Asia/Bangkok' );

	foreach ( [
		'woocommerce_currency'               => 'THB',
		'woocommerce_currency_pos'           => 'left',
		'woocommerce_price_num_decimals'     => 0,
		'woocommerce_price_thousand_sep'     => ',',
		'woocommerce_default_country'        => 'TH:TH-10',
		'woocommerce_coming_soon'            => 'no',
		'woocommerce_store_pages_only'       => 'no',
		'woocommerce_enable_reviews'         => 'no',
		'woocommerce_attribute_lookup_enabled' => 'no',
		'woocommerce_catalog_columns'        => 4,
		'woocommerce_task_list_hidden'       => 'yes',
		'woocommerce_show_marketplace_suggestions' => 'no',
		'woocommerce_allow_tracking'         => 'no',
		'woocommerce_onboarding_profile'     => [ 'skipped' => true ],
	] as $k => $v ) {
		update_option( $k, $v );
	}

	foreach ( [ 'hello-world' => 'post', 'sample-page' => 'page' ] as $slug => $type ) {
		$p = get_page_by_path( $slug, OBJECT, $type );
		if ( $p ) wp_delete_post( $p->ID, true );
	}
}

/* ---------------------------------------------------------------- ประเภทสินค้า (product attribute: Arrangement — ใช้ slug 'type' ไม่ได้เพราะ WooCommerce จองไว้) */

function mm_setup_types() {
	$id = wc_attribute_taxonomy_id_by_name( 'arrangement' );
	if ( ! $id ) {
		$id = wc_create_attribute( [ 'name' => 'Arrangement', 'slug' => 'arrangement', 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ] );
		if ( is_wp_error( $id ) ) throw new Exception( $id->get_error_message() );
	}
	if ( ! taxonomy_exists( 'pa_arrangement' ) ) {
		register_taxonomy( 'pa_arrangement', [ 'product' ], [ 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ] );
	}

	$terms = [];
	$i     = 0;
	foreach ( [
		'bouquet'  => 'ช่อ',
		'box'      => 'กล่อง',
		'basket'   => 'กระเช้า',
		'vase'     => 'แจกัน',
		'wreath'   => 'พวงหรีด',
		'garland'  => 'พวงมาลัย',
		'gift-set' => 'ชุดของขวัญ',
	] as $slug => $name ) {
		$t = term_exists( $slug, 'pa_arrangement' ) ?: wp_insert_term( $name, 'pa_arrangement', [ 'slug' => $slug ] );
		update_term_meta( $t['term_id'], 'order', $i++ );
		$terms[ $slug ] = (int) $t['term_id'];
	}
	return [ 'attr_id' => $id, 'terms' => $terms ];
}

/* ---------------------------------------------------------------- หมวดหมู่ */

function mm_setup_categories() {
	$tree = [
		'fresh-flower'      => [ 'Fresh Flower', 'ดอกไม้สดคัดใหม่ทุกวัน จัดเป็นกล่อง ช่อ ตะกร้า แจกัน พวงหรีด และพวงมาลัย', [
			'fresh-box' => 'Flower Box', 'fresh-bouquet' => 'Flower Bouquet', 'fresh-basket' => 'Flower Basket',
			'fresh-vase' => 'Flower Vase', 'fresh-wreath' => 'Wreath', 'fresh-garland' => 'Garland',
		] ],
		'artificial-flower' => [ 'Artificial Flower', 'ดอกไม้ประดิษฐ์เกรดพรีเมียม สวยได้นาน ไม่ต้องดูแล', [
			'artificial-box' => 'Box', 'artificial-vase' => 'Vase', 'artificial-bouquet' => 'Bouquet', 'artificial-wreath' => 'Wreath',
		] ],
		'fruit'             => [ 'Fruit', 'กระเช้าและช่อผลไม้ รวมถึงชุดของขวัญเพื่อสุขภาพ', [
			'fruit-basket' => 'Basket', 'fruit-bouquet' => 'Bouquet', 'wellness-gift' => 'Wellness Gift',
		] ],
		'occasion'          => [ 'Occasion', 'ดอกไม้และของขวัญตามเทศกาลและโอกาสพิเศษ', [
			'new-year' => 'New Year', 'valentine' => 'Valentine', 'chinese-new-year' => 'Chinese New Year', 'songkran' => 'Songkran',
			'mothers-day' => "Mother's Day", 'fathers-day' => "Father's Day", 'christmas' => 'Christmas', 'graduation' => 'Graduation', 'new-born' => 'New Born',
		] ],
	];

	$ids   = [];
	$order = 0;
	foreach ( $tree as $slug => [ $name, $desc, $children ] ) {
		$ids[ $slug ] = mm_term( $slug, $name, $desc, 0, $order++ );
		$c            = 0;
		foreach ( $children as $cslug => $cname ) {
			$ids[ $cslug ] = mm_term( $cslug, $cname, '', $ids[ $slug ], $c++ );
		}
	}
	return $ids;
}

function mm_term( $slug, $name, $desc, $parent, $order ) {
	$t = term_exists( $slug, 'product_cat' ) ?: wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'description' => $desc, 'parent' => $parent ] );
	update_term_meta( $t['term_id'], 'order', $order );
	return (int) $t['term_id'];
}

/* ---------------------------------------------------------------- รูป */

function mm_setup_images() {
	$ids = [];
	foreach ( glob( get_theme_file_path( 'demo/images/*.jpg' ) ) as $file ) {
		$name = basename( $file, '.jpg' );
		$up   = wp_upload_bits( $name . '.jpg', null, file_get_contents( $file ) );
		if ( $up['error'] ) continue;
		$id = wp_insert_attachment( [ 'post_mime_type' => 'image/jpeg', 'post_title' => $name, 'post_status' => 'inherit' ], $up['file'] );
		// ไม่สร้าง sub-size เพื่อให้ Playground โหลดเร็ว รูปตัวอย่างเล็กอยู่แล้ว
		[ $w, $h ] = getimagesize( $up['file'] );
		wp_update_attachment_metadata( $id, [ 'width' => $w, 'height' => $h, 'file' => _wp_relative_upload_path( $up['file'] ), 'sizes' => [] ] );
		$ids[ $name ] = $id;
	}
	return $ids;
}

/* ---------------------------------------------------------------- สินค้า */

function mm_setup_products( $cats, $types, $imgs ) {
	$list = json_decode( file_get_contents( get_theme_file_path( 'demo/products.json' ) ), true );
	foreach ( $list as $i => $p ) {
		$product = new WC_Product_Simple();
		$product->set_name( $p['name'] );
		$product->set_slug( $p['slug'] );
		$product->set_status( 'publish' );
		$product->set_regular_price( (string) $p['price'] );
		$product->set_featured( ! empty( $p['featured'] ) );
		$product->set_menu_order( $i );
		$product->set_short_description( '<p>' . esc_html( $p['th'] ) . '</p>' );
		$product->set_description( '<p>' . esc_html( $p['th'] ) . ' จัดด้วยมือในสไตล์ Romantic · Classy · Modern ปรับโทนสีและขนาดได้ตามงบ แนบการ์ดข้อความได้ฟรี</p><p><em>รายละเอียดและราคาเป็นข้อมูลตัวอย่าง</em></p>' );

		$cat_ids = [];
		foreach ( $p['cats'] as $slug ) {
			$cat_ids[] = $cats[ $slug ];
			$parent    = get_term( $cats[ $slug ], 'product_cat' )->parent;
			if ( $parent ) $cat_ids[] = $parent;
		}
		$product->set_category_ids( array_values( array_unique( $cat_ids ) ) );

		$attr = new WC_Product_Attribute();
		$attr->set_id( $types['attr_id'] );
		$attr->set_name( 'pa_arrangement' );
		$attr->set_options( [ $types['terms'][ $p['type'] ] ] );
		$attr->set_visible( true );
		$attr->set_variation( false );
		$product->set_attributes( [ $attr ] );

		if ( isset( $imgs[ $p['slug'] ] ) ) $product->set_image_id( $imgs[ $p['slug'] ] );
		$id = $product->save();
		wp_set_object_terms( $id, [ $types['terms'][ $p['type'] ] ], 'pa_arrangement' );
	}
}

/* ---------------------------------------------------------------- ตัวช่วยเขียน block */

function mm_b_p( $text, $class = '' ) {
	$attrs = $class ? ' {"className":"' . $class . '"}' : '';
	$cls   = $class ? ' class="' . $class . '"' : '';
	return "<!-- wp:paragraph$attrs -->\n<p$cls>$text</p>\n<!-- /wp:paragraph -->\n";
}

function mm_b_h( $text, $level = 2 ) {
	$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
	return "<!-- wp:heading$attrs -->\n<h$level class=\"wp-block-heading\">$text</h$level>\n<!-- /wp:heading -->\n";
}

function mm_b_buttons( $buttons ) {
	$out = '';
	foreach ( $buttons as [ $label, $url, $style ] ) {
		$cls  = 'outline' === $style ? ' is-style-outline' : '';
		$attr = 'outline' === $style ? ' {"className":"is-style-outline"}' : '';
		$out .= "<!-- wp:button$attr -->\n<div class=\"wp-block-button$cls\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_url( $url ) . "\">$label</a></div>\n<!-- /wp:button -->\n";
	}
	return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">$out</div>\n<!-- /wp:buttons -->\n";
}

function mm_b_sc( $sc ) {
	return "<!-- wp:shortcode -->\n$sc\n<!-- /wp:shortcode -->\n";
}

function mm_b_section( $inner, $class = '' ) {
	$c = trim( 'mm-section ' . $class );
	return "<!-- wp:group {\"align\":\"full\",\"className\":\"$c\",\"layout\":{\"type\":\"constrained\",\"contentSize\":\"1240px\"}} -->\n<div class=\"wp-block-group alignfull $c\">$inner</div>\n<!-- /wp:group -->\n";
}

function mm_b_intro( $eyebrow, $title, $text = '' ) {
	return mm_b_p( $eyebrow, 'mm-eyebrow' ) . mm_b_h( $title ) . ( $text ? mm_b_p( $text, 'mm-lead' ) : '' );
}

function mm_b_cover( $img, $eyebrow, $title, $text, $buttons = [], $min = 520 ) {
	$url   = wp_get_attachment_url( $img );
	$inner = mm_b_p( $eyebrow, 'mm-eyebrow' ) . mm_b_h( $title, 1 ) . mm_b_p( $text, 'mm-lead' ) . ( $buttons ? mm_b_buttons( $buttons ) : '' );
	return "<!-- wp:cover {\"url\":\"$url\",\"id\":$img,\"dimRatio\":0,\"minHeight\":$min,\"contentPosition\":\"center left\",\"isDark\":false,\"align\":\"full\",\"className\":\"mm-hero\",\"layout\":{\"type\":\"constrained\",\"contentSize\":\"1240px\"}} -->\n"
		. "<div class=\"wp-block-cover alignfull is-light has-custom-content-position is-position-center-left mm-hero\" style=\"min-height:{$min}px\"><img class=\"wp-block-cover__image-background wp-image-$img\" alt=\"\" src=\"$url\" data-object-fit=\"cover\"/><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim-0 has-background-dim\"></span><div class=\"wp-block-cover__inner-container\">\n$inner</div></div>\n<!-- /wp:cover -->\n";
}

function mm_b_gallery( $ids ) {
	return mm_b_sc( '[gallery ids="' . implode( ',', $ids ) . '" columns="3" size="large" link="none"]' ) . mm_b_p( 'ภาพตัวอย่าง จะเปลี่ยนเป็นผลงานจริงของร้าน', 'mm-note' );
}

/* ---------------------------------------------------------------- หน้า */

function mm_page( $slug, $title, $content, $parent = 0, $order = 0 ) {
	$id = wp_insert_post( [
		'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title,
		'post_content' => $content, 'post_parent' => $parent, 'menu_order' => $order,
	] );
	update_post_meta( $id, '_wp_page_template', 'page-landing' );
	return $id;
}

function mm_setup_pages( $img ) {
	$shop = get_permalink( wc_get_page_id( 'shop' ) );
	$line = [ 'สั่งทาง LINE', mm_line_url(), 'outline' ];
	$cta  = fn( $title, $text, $extra = [] ) => mm_b_section( mm_b_h( $title ) . mm_b_p( $text, 'mm-lead' ) . mm_b_buttons( array_merge( [ [ 'ติดต่อร้านทาง LINE', mm_line_url(), 'fill' ] ], $extra ) ), 'mm-cta' );
	$pages = [];

	// Shop page = All Collections
	wp_update_post( [ 'ID' => wc_get_page_id( 'shop' ), 'post_title' => 'All Collections' ] );

	$event_and_party = home_url( '/event-and-party/' );
	$customers_url   = home_url( '/our-customers/' );

	$pages['home'] = mm_page( 'home', 'Home',
		mm_b_cover( $img['hero'], 'My Memories Flower Shop', 'ดอกไม้สำหรับทุกความทรงจำ', 'ช่อดอกไม้ กล่องดอกไม้ กระเช้า และงานดอกไม้สำหรับอีเวนต์ จัดด้วยมือในสไตล์ Romantic · Classy · Modern', [ [ 'ดูคอลเลกชัน', $shop, 'fill' ], $line ], 620 )
		. mm_b_section( mm_b_intro( 'Our Collections', 'เลือกตามหมวด' ) . mm_b_sc( '[mm_cards set="collections"]' ) )
		. mm_b_section( mm_b_intro( 'Best Sellers', 'ช่อยอดนิยมของร้าน' ) . mm_b_sc( '[products visibility="featured" limit="8" columns="4" orderby="menu_order" order="ASC"]' ) . mm_b_buttons( [ [ 'See more collections', $shop, 'fill' ] ] ), 'mm-tint' )
		. mm_b_section( mm_b_intro( 'Our Customers', 'ลูกค้าที่ไว้ใจเรา', 'ดูแลดอกไม้ให้ทั้งลูกค้าทั่วไป เซเลบริตี้ องค์กร และแบรนด์' ) . mm_b_sc( '[mm_cards set="customers"]' ) )
		. $cta( 'จัดดอกไม้สำหรับงานของคุณ', 'งานเปิดตัว งานแต่ง ปาร์ตี้ หรืองานองค์กร ทักมาเล่ารายละเอียดได้เลย', [ [ 'ดู Event & Party', $event_and_party, 'outline' ] ] )
	);

	$pages['event-and-party'] = mm_page( 'event-and-party', 'Event & Party',
		mm_b_cover( $img['event'], 'Event & Party', 'ดอกไม้สำหรับงานพิเศษ', 'ออกแบบและจัดดอกไม้ให้เข้ากับธีมงาน ตั้งแต่โต๊ะต้อนรับจนถึงฉากถ่ายรูป' )
		. mm_b_section( mm_b_intro( 'Services', 'เลือกประเภทงาน' ) . mm_b_sc( '[mm_cards set="event"]' ) )
		. $cta( 'เล่างานของคุณให้เราฟัง', 'บอกวันที่ สถานที่ ธีมสี และงบประมาณ ร้านจะส่งไอเดียและใบเสนอราคากลับไป' ), 0, 1 );

	$pages['event'] = mm_page( 'event', 'Event',
		mm_b_cover( $img['event'], 'Event', 'Event Flowers', 'งานเปิดตัวสินค้า งานสัมมนา งานแต่งงาน และพิธีการ จัดดอกไม้ทั้งงานตามคอนเซปต์' )
		. mm_b_section( mm_b_intro( 'What we do', 'งานที่เรารับจัด', 'ซุ้มดอกไม้และฉากถ่ายรูป · ดอกไม้โต๊ะต้อนรับและโต๊ะจัดเลี้ยง · ดอกไม้ประดับเวที · ช่อมอบบนเวที · ดอกไม้ติดหน้าอก' ) . mm_b_gallery( [ $img['event'], $img['elegant-orchid-vase'], $img['pure-tribute-wreath'], $img['pearl-white-box'], $img['everlasting-vase'], $img['blush-peony-bouquet'] ] ) )
		. $cta( 'วางแผนงานอีเวนต์', 'แนะนำให้ติดต่อล่วงหน้าอย่างน้อย 2 สัปดาห์สำหรับงานใหญ่' ), $pages['event-and-party'], 0 );

	$pages['party'] = mm_page( 'party', 'Party',
		mm_b_cover( $img['party'], 'Party', 'Party Flowers', 'ปาร์ตี้วันเกิด เบบี้ชาวเวอร์ ดินเนอร์ส่วนตัว และงานฉลองเล็ก ๆ ที่อยากให้พิเศษ' )
		. mm_b_section( mm_b_intro( 'Ideas', 'ไอเดียสำหรับปาร์ตี้', 'ดอกไม้กลางโต๊ะ · ช่อเล็กสำหรับแขก · กล่องดอกไม้เซอร์ไพรส์ · ตกแต่งมุมถ่ายรูป' ) . mm_b_gallery( [ $img['party'], $img['secret-garden-basket'], $img['rose-heart-box'], $img['silk-pastel-bouquet'], $img['sunny-days'], $img['fresh-fruit-basket'] ] ) )
		. $cta( 'เตรียมปาร์ตี้ของคุณ', 'บอกจำนวนแขก ธีมสี และวันที่ แล้วร้านจะช่วยออกแบบให้' ), $pages['event-and-party'], 1 );

	$pages['our-customers'] = mm_page( 'our-customers', 'Our Customers',
		mm_b_cover( $img['customer-client'], 'Our Customers', 'ลูกค้าของเรา', 'ขอบคุณทุกความไว้วางใจ ทั้งลูกค้าทั่วไป เซเลบริตี้ องค์กร และแบรนด์' )
		. mm_b_section( mm_b_sc( '[mm_cards set="customers"]' ) ), 0, 2 );

	$groups = [
		'client'       => [ 'Client', 'ลูกค้าคนสำคัญ', 'ช่อดอกไม้และของขวัญที่ลูกค้าส่งต่อความรู้สึกให้คนที่รัก', 'customer-client' ],
		'celebrity'    => [ 'Celebrity', 'เซเลบริตี้', 'ผลงานดอกไม้ที่เซเลบริตี้และอินฟลูเอนเซอร์เลือกใช้', 'customer-celebrity' ],
		'organization' => [ 'Organization', 'องค์กร', 'ดอกไม้สำหรับงานองค์กร งานแสดงความยินดี และของขวัญลูกค้าองค์กร', 'customer-organization' ],
		'brand'        => [ 'Brand', 'แบรนด์', 'งานดอกไม้ร่วมกับแบรนด์ ทั้งงานเปิดตัว ป๊อปอัป และคอนเทนต์', 'customer-brand' ],
	];
	$order = 0;
	foreach ( $groups as $slug => [ $title, $th, $text, $cover ] ) {
		$pages[ $slug ] = mm_page( $slug, $title,
			mm_b_cover( $img[ $cover ], 'Our Customers', $title, $text )
			. mm_b_section( mm_b_intro( 'Gallery', 'ผลงาน · ' . $th ) . mm_b_gallery( array_slice( array_values( array_filter( $img, fn( $k ) => ! str_starts_with( $k, 'customer' ) && ! in_array( $k, [ 'hero', 'event', 'party' ], true ), ARRAY_FILTER_USE_KEY ) ), $order * 3, 6 ) ) )
			. $cta( 'อยากให้เราดูแลดอกไม้ของคุณ', 'ทักมาคุยรายละเอียดได้ทุกวัน' ), $pages['our-customers'], $order++ );
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pages['home'] );
	return $pages;
}

/* ---------------------------------------------------------------- การ์ด, เมนู และ quick links */

function mm_setup_cards( $cats, $img ) {
	update_option( 'mm_cards_collections', [
		[ 'term' => $cats['fresh-flower'], 'img' => $img['blush-peony-bouquet'], 'title' => 'Fresh Flower', 'sub' => 'ดอกไม้สด' ],
		[ 'term' => $cats['artificial-flower'], 'img' => $img['forever-rose-box'], 'title' => 'Artificial Flower', 'sub' => 'ดอกไม้ประดิษฐ์' ],
		[ 'term' => $cats['fruit'], 'img' => $img['fresh-fruit-basket'], 'title' => 'Fruit', 'sub' => 'กระเช้าและช่อผลไม้' ],
		[ 'term' => $cats['occasion'], 'img' => $img['rose-heart-box'], 'title' => 'Occasion', 'sub' => 'ตามเทศกาล' ],
		[ 'page' => 'event-and-party', 'img' => $img['event'], 'title' => 'Event & Party', 'sub' => 'งานอีเวนต์และปาร์ตี้' ],
	] );
	update_option( 'mm_cards_customers', [
		[ 'page' => 'our-customers/client', 'img' => $img['customer-client'], 'title' => 'Client', 'sub' => 'ลูกค้าคนสำคัญ' ],
		[ 'page' => 'our-customers/celebrity', 'img' => $img['customer-celebrity'], 'title' => 'Celebrity', 'sub' => 'เซเลบริตี้' ],
		[ 'page' => 'our-customers/organization', 'img' => $img['customer-organization'], 'title' => 'Organization', 'sub' => 'องค์กร' ],
		[ 'page' => 'our-customers/brand', 'img' => $img['customer-brand'], 'title' => 'Brand', 'sub' => 'แบรนด์' ],
	] );
	update_option( 'mm_cards_event', [
		[ 'page' => 'event-and-party/event', 'img' => $img['event'], 'title' => 'Event', 'sub' => 'งานเปิดตัว งานแต่ง พิธีการ' ],
		[ 'page' => 'event-and-party/party', 'img' => $img['party'], 'title' => 'Party', 'sub' => 'วันเกิด ดินเนอร์ งานฉลอง' ],
	] );
}

function mm_nav_link( $label, $url ) {
	return '<!-- wp:navigation-link {"label":"' . esc_attr( $label ) . '","url":"' . esc_url( $url ) . '"} /-->';
}

function mm_nav_sub( $label, $url, $children ) {
	return '<!-- wp:navigation-submenu {"label":"' . esc_attr( $label ) . '","url":"' . esc_url( $url ) . '"} -->' . implode( '', $children ) . '<!-- /wp:navigation-submenu -->';
}

function mm_setup_menu( $cats, $pages ) {
	$menu = [];
	foreach ( [ 'fresh-flower', 'artificial-flower', 'fruit', 'occasion' ] as $top ) {
		$children = [];
		foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $cats[ $top ], 'hide_empty' => false ] ) as $t ) {
			$children[] = mm_nav_link( $t->name, get_term_link( $t ) );
		}
		$t      = get_term( $cats[ $top ], 'product_cat' );
		$menu[] = mm_nav_sub( $t->name, get_term_link( $t ), $children );
	}
	$menu[] = mm_nav_sub( 'Event & Party', get_permalink( $pages['event-and-party'] ), [
		mm_nav_link( 'Event', get_permalink( $pages['event'] ) ), mm_nav_link( 'Party', get_permalink( $pages['party'] ) ),
	] );
	$menu[] = mm_nav_sub( 'Our Customers', get_permalink( $pages['our-customers'] ), array_map(
		fn( $s ) => mm_nav_link( ucfirst( $s ), get_permalink( $pages[ $s ] ) ), [ 'client', 'celebrity', 'organization', 'brand' ]
	) );
	$menu[] = mm_nav_link( 'Contact', '#contact' );

	wp_insert_post( [ 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'post_title' => 'Main menu', 'post_content' => implode( "\n", $menu ) ] );

	update_option( 'mm_quick_links', [
		'Home'            => home_url( '/' ),
		'All Collections' => get_permalink( wc_get_page_id( 'shop' ) ),
		'Fresh Flower'    => get_term_link( $cats['fresh-flower'], 'product_cat' ),
		'Occasion'        => get_term_link( $cats['occasion'], 'product_cat' ),
		'Event & Party'   => get_permalink( $pages['event-and-party'] ),
		'Our Customers'   => get_permalink( $pages['our-customers'] ),
	] );
}
