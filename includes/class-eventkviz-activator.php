<?php

/**
 * Fired during plugin activation.
 */
class Eventkviz_Activator {

	/**
	 * Slug → [title, content] for the global hub pages used to render
	 * event link selectors and statistics. The shortcodes inside read
	 * `?akcia=...` from the query string, so a single page serves all events.
	 */
	private static function hub_pages() {
		return array(
			'eventkviz-vstup'      => array(
				'title'   => 'Eventkviz – vstup pre hráčov',
				'content' => '[show_team_links]',
			),
			'eventkviz-statistika' => array(
				'title'   => 'Eventkviz – štatistika',
				'content' => '[statistika]',
			),
			'mapa-quiz'            => array(
				'title'   => 'Mapový kvíz',
				'content' => '[mapa_form_dynamic]',
			),
			'mapa-quiz-dynamic-evaluation' => array(
				'title'   => 'Mapový kvíz — vyhodnotenie',
				'content' => '[eval_mapa_quiz_dynamic]',
			),
		);
	}

	public static function activate() {
		self::ensure_hub_pages();
	}

	/**
	 * Idempotent — creates hub pages if missing. Safe to call on every
	 * admin_init, will no-op once pages exist.
	 */
	public static function ensure_hub_pages() {
		foreach ( self::hub_pages() as $slug => $cfg ) {
			$existing = get_page_by_path( $slug );
			if ( $existing instanceof WP_Post ) {
				continue;
			}
			wp_insert_post( array(
				'post_title'    => $cfg['title'],
				'post_name'     => $slug,
				'post_content'  => $cfg['content'],
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'comment_status'=> 'closed',
				'ping_status'   => 'closed',
			) );
		}
	}

	public static function hub_url( $slug, $args = array() ) {
		return add_query_arg( $args, home_url( '/' . $slug . '/' ) );
	}
}
