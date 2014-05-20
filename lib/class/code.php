<?php

/**
 *
 * Manipulation of shortcodes safely
 *
 */
class Code
{

    /**
     *
     * @var array $codes Stores the tags of shortcodes created
     *
     */
    private static $codes;

    /**
     *
     * Filters the attributes passed to the shortcodes
     *
     * @param array $attr The attributes
     * @param string $content Delimited content by tags
     * @param string $tag Shortcode
     * @return string Result shortcode according to the callback function
     *
     *
     */
    private static function controller( $attr, $content, $tag )
    {
        $code = ( isset( self::$codes[ $tag ] ) ) ? self::$codes[ $tag ] : false;
        if ( $code ) {
            $response = true;
            if ( isset( $code[ 'attr' ] ) ) {
                $attr_default = array();
                foreach ( $code[ 'attr' ] as $a )
                    $attr_default[ $a[ 'name' ] ] = ( isset( $a[ 'std' ] ) ) ? $a[ 'std' ] : null;

                $attr = shortcode_atts( $attr_default, $attr );

                foreach ( $code[ 'attr' ] as $a ) {
                    if ( isset( $a[ 'req' ] ) && !$attr[ $a[ 'name' ] ] ) {
                        $response = false;
                        break;
                    }
                }
            }

            if ( $response )
                return call_user_func( $code[ 'cb' ], $attr, $content );
        }
    }

    /**
     *
     * Adds a new shortcode
     *
     * @param string $tag Shortcode
     * @param function $callback Callback function
     * @param array $attr Accepted attributes and characteristics
     *
     */
    public static function add( $tag, $callback, $attr=null )
    {
        self::$codes[ $tag ] = array(
            'cb'    => $callback,
            'attr'  => $attr
        );
        add_shortcode( $tag, array( 'Code', 'controller' ) );
    }

    /**
     *
     * [gallery]
     *
     */
    public static function gallery( $attr )
    {
        $attr[ 'include' ] = $attr[ 'ids' ];
        unset( $attr[ 'ids' ] );

        $html = '';
        $images = Theme::get_images( $attr );
        if ( is_array( $images ) && count( $images ) > 0 )
            $html = sprintf(
                apply_filters( 'gallery_container', '<ul class="gallery">%s</ul>' ),
                Theme::list_images( $images )
            );

        return $html;
    }

}

?>