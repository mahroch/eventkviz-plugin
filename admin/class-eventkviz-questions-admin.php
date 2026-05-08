<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Eventkviz_Questions_Admin {

    public static function init() {
        add_action( 'add_meta_boxes_questions-knowledge', array( __CLASS__, 'register_synonyms_metabox' ) );
        add_action( 'edit_form_after_title', array( __CLASS__, 'render_inline_help' ) );
    }

    public static function register_synonyms_metabox() {
        add_meta_box(
            'eventkviz-knowledge-synonyms-help',
            '💡 Synonymá pre odpoveď',
            array( __CLASS__, 'render_synonyms_metabox' ),
            'questions-knowledge',
            'side',
            'high'
        );
    }

    public static function render_synonyms_metabox() {
        ?>
        <p>
            Ak chceš akceptovať viac variantov tej istej odpovede (synonymá, skratky),
            oddeľ ich znakom <code>|</code> v poli
            <code>correct-answer-1</code> alebo <code>correct-answer-2</code>.
        </p>
        <p>
            <strong>Príklad:</strong><br>
            <code>Bratislava|BA|hlavné mesto SR</code>
        </p>
        <p>
            Hra akceptuje ktorúkoľvek z odpovedí.
            Porovnanie <strong>ignoruje veľkosť písmen a diakritiku</strong> — takže „bratislava",
            „BRATISLAVA" aj „Hugo" / „hugo" prejdú.
        </p>
        <p style="opacity:.75;font-size:12px;margin-top:14px">
            Tip: ak nepotrebuješ synonymá, nechaj pole tak ako predtým — bez <code>|</code>.
        </p>
        <?php
    }

    public static function render_inline_help( $post ) {
        if ( ! ( $post instanceof WP_Post ) ) return;
        if ( $post->post_type !== 'questions-knowledge' ) return;
        ?>
        <div class="notice notice-info inline" style="margin:10px 0">
            <p style="margin:0">
                <strong>💡 Pre admina:</strong>
                V poli <code>correct-answer-1</code> môžeš oddeliť synonymá znakom <code>|</code>
                (napr. <code>Bratislava|BA|hlavné mesto</code>).
                Porovnanie odpovede ignoruje veľkosť písmen a diakritiku.
                Detaily v boxíku „Synonymá pre odpoveď" napravo.
            </p>
        </div>
        <?php
    }
}
