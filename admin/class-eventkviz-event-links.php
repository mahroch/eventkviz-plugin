<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Eventkviz_Event_Links_Admin {

    const QUIZ_SLUGS = array(
        'music'     => array( 'label' => 'Hudobný kvíz',     'slug' => 'aqljk' ),
        'movies'    => array( 'label' => 'Filmový kvíz',     'slug' => 'merdfghh' ),
        'knowledge' => array( 'label' => 'Vedomostný kvíz',  'slug' => 'kwersdfzx' ),
        'sudoku'    => array( 'label' => 'Sudoku kvíz',      'slug' => 'sweertydfd' ),
        // mapa: multi-quiz, linky sa generujú dynamicky per sub-kvíz (viď nižšie v render_metabox)
    );

    public static function init() {
        add_action( 'add_meta_boxes_eventkviz_event', array( __CLASS__, 'register_metabox' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_clipboard_helper' ) );
    }

    public static function register_metabox() {
        add_meta_box(
            'eventkviz-event-links',
            '🔗 Linky pre hráčov',
            array( __CLASS__, 'render_metabox' ),
            'eventkviz_event',
            'normal',
            'high'
        );
    }

    public static function enqueue_clipboard_helper( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'eventkviz_event' ) return;

        $css = '
            .ek-links-section { margin: 18px 0; }
            .ek-links-section h3 { margin: 0 0 6px; font-size: 14px; }
            .ek-links-section .description { margin: 0 0 10px; color: #666; }
            .ek-link-row { display: flex; align-items: center; gap: 8px; margin: 6px 0; }
            .ek-link-row .ek-link-url { flex: 1; text-decoration: none; }
            .ek-link-row .ek-link-url code { display: block; padding: 6px 10px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; font-size: 12px; word-break: break-all; color: #2271b1; }
            .ek-link-row .ek-link-url:hover code { background: #eef4fa; border-color: #2271b1; }
            .ek-link-row .ek-open-btn { padding: 0 10px; line-height: 28px; font-size: 14px; text-decoration: none; }
            .ek-link-row .ek-copy-btn { white-space: nowrap; }
            .ek-link-row .ek-link-label { min-width: 110px; color: #50575e; font-size: 13px; }
            .ek-link-row.ek-link-row--inline { background: rgba(0,124,186,0.05); padding: 4px 6px; border-radius: 4px; }
            .ek-copy-feedback { color: #00a32a; font-weight: 600; margin-left: 6px; opacity: 0; transition: opacity 0.2s; }
            .ek-copy-feedback.show { opacity: 1; }
        ';
        wp_add_inline_style( 'wp-admin', $css );

        $js = '
            (function(){
                document.addEventListener("click", function(e){
                    var btn = e.target.closest(".ek-copy-btn");
                    if (!btn) return;
                    var url = btn.getAttribute("data-copy");
                    if (!url) return;
                    navigator.clipboard.writeText(url).then(function(){
                        var fb = btn.parentElement.querySelector(".ek-copy-feedback");
                        if (fb) { fb.classList.add("show"); setTimeout(function(){ fb.classList.remove("show"); }, 1500); }
                    });
                });
            })();
        ';
        wp_add_inline_script( 'common', $js );
    }

    public static function render_metabox( $post ) {
        if ( ! $post instanceof WP_Post ) return;
        $akcia = $post->post_name;

        if ( empty( $akcia ) ) {
            echo '<p><em>Najprv ulož event aby sa mu vygeneroval slug. Potom sa tu zobrazia distribuovateľné URL.</em></p>';
            return;
        }

        $vstup_url = home_url( '/eventkviz-vstup/' );
        $stats_url = home_url( '/eventkviz-statistika/' );

        // 1. Multi-quiz hub
        echo '<div class="ek-links-section">';
        echo '<h3>1. Hlavný vstup (multi-quiz, hráč si vyberie tím a vidí všetky kvízy)</h3>';
        echo '<p class="description">Najjednoduchší scenár — pošli tento link všetkým hráčom.</p>';
        self::render_link( add_query_arg( 'akcia', $akcia, $vstup_url ), '' );
        echo '</div>';

        // 2. Per-quiz hub
        echo '<div class="ek-links-section">';
        echo '<h3>2. Vstup do konkrétneho kvízu (hráč si vyberie tím, ide rovno do daného kvízu)</h3>';
        echo '<p class="description">Použi keď chceš poslať link na <em>jeden konkrétny</em> kvíz (napr. „dnes hráme len film kvíz").</p>';
        foreach ( self::QUIZ_SLUGS as $type => $info ) {
            self::render_link(
                add_query_arg( array( 'akcia' => $akcia, 'type' => $type ), $vstup_url ),
                $info['label']
            );
        }
        echo '</div>';

        // 3. Direct URLs (skip selector)
        echo '<div class="ek-links-section">';
        echo '<h3>3. Priame URL (bez výberu, hráč ide rovno do kvízu)</h3>';
        echo '<p class="description">Personalizovaný link pre konkrétny tím / hráča. Nahraď <code>TEAM</code> a <code>USER</code> reálnymi kódmi (alebo nech ostane prázdne ak ich nepoužívaš).</p>';
        foreach ( self::QUIZ_SLUGS as $type => $info ) {
            $url = add_query_arg(
                array( 'akcia' => $akcia, 'team' => 'TEAM', 'user' => 'USER' ),
                home_url( '/' . $info['slug'] . '/' )
            );
            self::render_link( $url, $info['label'] );
        }
        // Multi-mapa: pre každý sub-kvíz vlastný link s mq slugom
        $mapa_quizzes_json = get_post_meta( $post->ID, 'event_mapa_quizzes', true );
        $mapa_quizzes      = is_string( $mapa_quizzes_json ) && $mapa_quizzes_json !== '' ? json_decode( $mapa_quizzes_json, true ) : array();
        if ( is_array( $mapa_quizzes ) && ! empty( $mapa_quizzes ) ) {
            foreach ( $mapa_quizzes as $sq ) {
                $slug  = isset( $sq['slug'] ) ? $sq['slug'] : '';
                $label = isset( $sq['label'] ) ? $sq['label'] : 'Mapový kvíz';
                if ( $slug === '' ) continue;
                $url = add_query_arg(
                    array( 'akcia' => $akcia, 'mq' => $slug, 'team' => 'TEAM', 'user' => 'USER' ),
                    home_url( '/mapa-quiz/' )
                );
                self::render_link( $url, 'Mapa: ' . $label );
            }
        }
        echo '</div>';

        // 3b. Tokenizované linky pre konkrétny tím — JS form generuje cez REST endpoint.
        // Plain šablóny vyššie (sekcia 3) ostávajú pre batch ručnú replacement; tieto
        // sú opaque (?t=...) bez čitateľného team/user/akcia v URL.
        echo '<div class="ek-links-section">';
        echo '<h3>3b. 🔒 Tokenizované linky pre konkrétny tím (skryje team/user/akcia v URL)</h3>';
        echo '<p class="description">Zadaj reálny <strong>kód tímu</strong> a <strong>kód hráča</strong>, klikni „Generuj". Vygenerujú sa <em>opaque</em> linky (<code>?t=...</code>) pre všetky kvízy. Hráč v URL nevidí, čo tam je — útočník nedokáže manipulovať identitu. <em>Plain linky vyššie stále fungujú</em> pre prípady kde robíš ručný batch replacement.</p>';
        echo '<div class="ek-token-form" style="margin:10px 0; padding:12px; background:#f6f7f7; border-radius:4px; display:flex; gap:12px; align-items:center; flex-wrap:wrap">';
        echo '<label>Kód tímu: <input type="text" id="ek-tok-team" placeholder="napr. ABC" style="margin-left:6px"></label>';
        echo '<label>Kód hráča: <input type="text" id="ek-tok-user" placeholder="napr. XYZ (nepov.)" style="margin-left:6px"></label>';
        echo '<button type="button" class="button button-primary" id="ek-tok-gen">Generuj tokenizované linky</button>';
        echo '</div>';
        echo '<div id="ek-tok-results"></div>';

        // Build dataset pre JS — zoznam (slug, label) pre štandardné kvízy + mapquiz sub-quizzes
        $token_links_dataset = array();
        foreach ( self::QUIZ_SLUGS as $type => $info ) {
            $token_links_dataset[] = array(
                'slug'  => $info['slug'],
                'label' => $info['label'],
                'mq'    => '',
            );
        }
        if ( is_array( $mapa_quizzes ) && ! empty( $mapa_quizzes ) ) {
            foreach ( $mapa_quizzes as $sq ) {
                $slug  = isset( $sq['slug'] ) ? $sq['slug'] : '';
                $label = isset( $sq['label'] ) ? $sq['label'] : 'Mapový kvíz';
                if ( $slug === '' ) continue;
                $token_links_dataset[] = array(
                    'slug'  => 'mapa-quiz',
                    'label' => 'Mapa: ' . $label,
                    'mq'    => $slug,
                );
            }
        }
        ?>
        <script>
        (function(){
            var btn = document.getElementById('ek-tok-gen');
            if (!btn) return;
            var quizzes = <?php echo wp_json_encode( $token_links_dataset ); ?>;
            var akcia = <?php echo wp_json_encode( $akcia ); ?>;
            var restUrl = <?php echo wp_json_encode( esc_url_raw( rest_url( 'eventkviz/v1/link-token' ) ) ); ?>;
            var $results = document.getElementById('ek-tok-results');

            btn.addEventListener('click', async function(){
                var team = document.getElementById('ek-tok-team').value.trim();
                var user = document.getElementById('ek-tok-user').value.trim();
                if (!team && !user) { alert('Zadaj aspoň kód tímu alebo hráča.'); return; }
                btn.disabled = true; btn.textContent = 'Generujem…';
                $results.innerHTML = '';

                for (var i = 0; i < quizzes.length; i++) {
                    var q = quizzes[i];
                    var u = new URL(restUrl);
                    u.searchParams.set('quiz_slug', q.slug);
                    u.searchParams.set('akcia', akcia);
                    u.searchParams.set('team', team);
                    u.searchParams.set('user', user);
                    if (q.mq) u.searchParams.set('mq', q.mq);
                    try {
                        var r = await fetch(u.toString());
                        var d = await r.json();
                        var url = (d && d.url) ? d.url : '(chyba)';
                        appendRow(q.label, url);
                    } catch (e) {
                        appendRow(q.label, '(REST error: ' + e.message + ')');
                    }
                }
                btn.disabled = false; btn.textContent = 'Generuj tokenizované linky';
            });

            function appendRow(label, url) {
                var row = document.createElement('div');
                row.className = 'ek-link-row';
                row.innerHTML =
                    '<span class="ek-link-label">' + escapeHtml(label) + '</span>' +
                    '<a href="' + escapeAttr(url) + '" target="_blank" rel="noopener" class="ek-link-url"><code>' + escapeHtml(url) + '</code></a>' +
                    '<a href="' + escapeAttr(url) + '" target="_blank" rel="noopener" class="button ek-open-btn" title="Otvoriť">↗</a>' +
                    '<button type="button" class="button ek-copy-btn" data-copy="' + escapeAttr(url) + '">Kopírovať</button>' +
                    '<span class="ek-copy-feedback">✓ Skopírované</span>';
                $results.appendChild(row);
                // Wire copy button (musí byť dynamic)
                row.querySelector('.ek-copy-btn').addEventListener('click', function(){
                    navigator.clipboard.writeText(this.dataset.copy);
                    var fb = row.querySelector('.ek-copy-feedback');
                    fb.style.opacity = '1';
                    setTimeout(function(){ fb.style.opacity = ''; }, 1500);
                });
            }

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, function(c){
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
                });
            }
            function escapeAttr(s) { return escapeHtml(s); }
        })();
        </script>
        <?php
        echo '</div>';

        // 4. Stats
        echo '<div class="ek-links-section">';
        echo '<h3>4. Štatistika eventu (verejný leaderboard)</h3>';
        echo '<p class="description">Link s celkovým rebríčkom tímov tohto eventu — verejný, hráči si môžu pozerať počas eventu (napr. na projektor). Pre raw záznamy pozri JetEngine → Results.</p>';
        self::render_link( add_query_arg( 'akcia', $akcia, $stats_url ), '' );
        echo '</div>';

        echo '<p style="margin-top:16px;padding:10px;background:#f6f7f7;border-left:3px solid #2271b1;font-size:13px">';
        echo '<strong>💡 Tip:</strong> Globálne stránky <code>/eventkviz-vstup/</code> a <code>/eventkviz-statistika/</code> existujú raz pre všetky eventy — Eventkviz si ich vytvoril automaticky pri aktivácii. Slúžia ako univerzálny router cez <code>?akcia=</code> parameter.';
        echo '</p>';
    }

    private static function render_link( $url, $label ) {
        echo '<div class="ek-link-row">';
        if ( $label !== '' ) {
            echo '<span class="ek-link-label">' . esc_html( $label ) . '</span>';
        }
        echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="ek-link-url" title="Otvoriť v novom tabe"><code>' . esc_html( $url ) . '</code></a>';
        echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="button ek-open-btn" title="Otvoriť v novom tabe">↗</a>';
        echo '<button type="button" class="button ek-copy-btn" data-copy="' . esc_attr( $url ) . '">Kopírovať</button>';
        echo '<span class="ek-copy-feedback">✓ Skopírované</span>';
        echo '</div>';
    }
}
