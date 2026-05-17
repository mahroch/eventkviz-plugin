<?php
/**
 * Opaque link token — obfuscation + tamper-protection pre quiz URL params.
 *
 * Namiesto čitateľného `?akcia=X&team=Y&user=Z&mq=W` plugin generuje
 * `?t=<token>` kde token = `base64(JSON({a,t,u,m})).<HMAC-SHA256[:10]>`.
 *
 * Pri prijatí decode + verify HMAC (init hook) → set $_GET[akcia/team/user/mq]
 * z dekódovaného payload. Zvyšok plugin kódu nemení správanie.
 *
 * Bezpečnosť: HMAC podpis (secret v wp_options) zabezpečí že útočník nedokáže
 * fabricate vlastný team/user bez prelomenia secretu. Bez expiry — link je
 * deterministický (hráč ho môže bookmark-núť).
 */

if ( ! defined( 'WPINC' ) ) die;

class Eventkviz_Link_Token {

	const QUERY_KEY = 't';
	const OPTION_KEY = 'eventkviz_link_secret';

	/** @var string|null memoized secret */
	private static $secret = null;

	private static function secret() {
		if ( self::$secret !== null ) return self::$secret;
		$key = get_option( self::OPTION_KEY, '' );
		if ( $key === '' && class_exists( 'Eventkviz_Activator' ) ) {
			// Lazy init — pre installs ktoré bežali pred secret feature.
			Eventkviz_Activator::ensure_link_secret();
			$key = get_option( self::OPTION_KEY, '' );
		}
		self::$secret = (string) $key;
		return self::$secret;
	}

	/**
	 * Zakóduje quiz URL params do opaque tokenu.
	 *
	 * @param array $params { akcia, team, user, mq } — len neprázdne sa zahŕňajú.
	 * @return string token, alebo prázdny string ak nie sú žiadne params.
	 */
	public static function encode( $params ) {
		// Aliasované krátke kľúče (a/t/u/m) — kratší token.
		$aliased = array(
			'a' => isset( $params['akcia'] ) ? (string) $params['akcia'] : '',
			't' => isset( $params['team'] )  ? (string) $params['team']  : '',
			'u' => isset( $params['user'] )  ? (string) $params['user']  : '',
			'm' => isset( $params['mq'] )    ? (string) $params['mq']    : '',
		);
		$aliased = array_filter( $aliased, function ( $v ) { return $v !== ''; } );
		if ( empty( $aliased ) ) return '';

		$json = wp_json_encode( $aliased, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		// URL-safe base64 bez padding
		$b64  = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
		$sig  = substr( hash_hmac( 'sha256', $b64, self::secret() ), 0, 10 );
		return $b64 . '.' . $sig;
	}

	/**
	 * Dekóduje + overí podpis tokenu. Vráti normalizované params alebo null.
	 *
	 * @return array|null { akcia, team, user, mq } — chýbajúce keys = ''
	 */
	public static function decode( $token ) {
		if ( ! is_string( $token ) || strpos( $token, '.' ) === false ) return null;
		list( $b64, $sig ) = array_pad( explode( '.', $token, 2 ), 2, '' );
		if ( $b64 === '' || $sig === '' ) return null;

		$expected = substr( hash_hmac( 'sha256', $b64, self::secret() ), 0, 10 );
		if ( ! hash_equals( $expected, $sig ) ) return null;

		// Re-padd na decode
		$padding = ( 4 - strlen( $b64 ) % 4 ) % 4;
		$padded  = $b64 . str_repeat( '=', $padding );
		$json    = base64_decode( strtr( $padded, '-_', '+/' ), true );
		if ( $json === false ) return null;

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) return null;

		return array(
			'akcia' => isset( $data['a'] ) ? (string) $data['a'] : '',
			'team'  => isset( $data['t'] ) ? (string) $data['t'] : '',
			'user'  => isset( $data['u'] ) ? (string) $data['u'] : '',
			'mq'    => isset( $data['m'] ) ? (string) $data['m'] : '',
		);
	}

	/**
	 * Init hook — ak request má `?t=<token>` a podpis je platný, namapuje params
	 * na pôvodné $_GET keys (akcia/team/user/mq). Zvyšok plugin code číta z $_GET
	 * ako predtým — žiadny iný refactor netreba.
	 *
	 * Volá sa skoro v init (priorita 4), pred shortcode handler-mi.
	 */
	public static function apply_from_request() {
		if ( empty( $_GET[ self::QUERY_KEY ] ) ) return;
		$decoded = self::decode( (string) $_GET[ self::QUERY_KEY ] );
		if ( $decoded === null ) return; // invalid signature — silently ignore (legacy QS fallback)

		foreach ( array( 'akcia', 'team', 'user', 'mq' ) as $k ) {
			if ( $decoded[ $k ] !== '' && empty( $_GET[ $k ] ) ) {
				$_GET[ $k ] = $decoded[ $k ];
			}
		}
	}

	/**
	 * Pridá `?t=<token>` na base URL. Existujúce non-quiz query args (napr. type,
	 * gc_*) sa zachovajú; akcia/team/user/mq sa zhrnú do tokenu.
	 *
	 * @param string $base_url base URL bez query string
	 * @param array  $params   { akcia, team, user, mq }
	 * @param array  $extra    extra query args ktoré ostanú plain (napr. type)
	 * @return string finálne URL
	 */
	public static function build_url( $base_url, $params, $extra = array() ) {
		$token = self::encode( $params );
		$qs    = array();
		if ( $token !== '' ) $qs[ self::QUERY_KEY ] = $token;
		foreach ( $extra as $k => $v ) {
			if ( $v !== null && $v !== '' ) $qs[ $k ] = $v;
		}
		if ( empty( $qs ) ) return $base_url;
		return $base_url . ( strpos( $base_url, '?' ) === false ? '?' : '&' ) . http_build_query( $qs );
	}
}
