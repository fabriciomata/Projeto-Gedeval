<?php

// {{{ i18n

/**
 *
 * String manipulation ally to translation
 *
 * @return string Translated Text
 *
 */
function __r()
{
    return _rr( func_get_args() );
}

/**
 *
 * Show string manipulated and translated
 *
 */
function _er()
{
    echo _rr( func_get_args() );
}

/**
 *
 * Translates the arguments of the functions
 *
 * @param array $args Parameters string manipulation
 * @return string Translated Text
 *
 */
function _rr( $args )
{
    $args[ 0 ] = __( $args[ 0 ], TEXT_DOMAIN );
    return ( count( $args ) > 1 ) ? call_user_func_array( 'sprintf', $args ) : $args[ 0 ];
}

/**
 *
 * Maintains the translation of AJAX requests
 *
 * @global string $locale Current location defined in wp-config.php
 * @global string $sitepress Reference to plugin WPML
 * @return string Correct location
 *
 */
function custom_locale()
{
    global $locale, $sitepress;
    if ( !session_id() )
        session_start();

    if ( !defined( 'DOING_AJAX' ) ) {
        $locale = get_locale();
        $_SESSION[ 'locale' ] = $locale;
    } else {
        $locale = $_SESSION[ 'locale' ];
        remove_filter( 'locale', array( $sitepress, 'locale' ) );
        add_filter( 'locale', function(){
            return $_SESSION[ 'locale' ];
        }, 1 );
    }
    return $locale;
}

// }}}

?>