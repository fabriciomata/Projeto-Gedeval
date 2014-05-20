<?php

/**
 *
 * Treatment of Metaboxes
 *
 */
class Metabox extends Dashboard
{

    /**
     *
     * @var array All custom metaboxes
     *
     */
    private static $boxes;

    /**
     *
     * Adds a new metabox
     *
     * @param string $id Unique identifier
     * @param string|array $post_type Types of posts to which metabox will be added
     * @param array $args Additional properties of metabox http://codex.wordpress.org/Function_Reference/add_meta_box
     *
     */
    public static function add( $id, $post_type, $args=array() )
    {
        self::init();

        $defaults = array(
            'title'     => __r( 'Additional information' ),
            'context'   => 'normal',
            'priority'  => 'high',
            'args'      => null,
            'callback'  => null
        );
        $attr = array(
            'id'        => $id,
            'post_type' => $post_type,
            'form'      => new Form()
        );
        $box = array_merge( $defaults, $args, $attr );

        self::$boxes[ $id ] = apply_filters( 'metabox_register', $box );
    }

    /**
     *
     * Class Initializer, start the appropriate triggers
     *
     */
    public static function init()
    {
        if ( !is_array( self::$boxes ) ) {
            self::$boxes = array();

            add_filter( 'form_field',   array( 'Metabox', 'fields_format' ), 2, 3 );

            add_action( 'admin_init',   array( 'Metabox', 'admin_init' ) );
            add_action( 'save_post',    array( 'Metabox', 'save' ) );
        }
    }

    /**
     *
     * Inserts the form fields metabox
     *
     * @param string $metabox Unique identifier of metabox
     * @param array $fields Field list
     *
     */
    public static function add_fields( $metabox, $fields )
    {
        self::$boxes[ $metabox ][ 'form' ]->add_fields( $fields, apply_filters( 'metabox_fields_prefix', '' ) );
    }

    /**
     *
     * Launcher Dashboard, inserts metaboxes to system
     *
     */
    public static function admin_init()
    {
        foreach ( self::$boxes as $b )
            add_meta_box( $b[ 'id' ], $b[ 'title' ], array( 'Metabox', 'edit' ), $b[ 'post_type' ], $b[ 'context' ], $b[ 'priority' ], $b[ 'args' ] );
    }

    /**
     *
     * Responsible for the content of metabox
     *
     * $metabox = array(
     *  'id'        => '',
     *  'title      => '',
     *  'callback'  => '',
     *  'args'      => ''
     * );
     *
     * @param object $post Post object of WordPress
     * @param array $metabox Attributes of metabox
     *
     */
    public static function edit( $post, $metabox )
    {
        $b = self::$boxes[ $metabox[ 'id' ] ];

        do_action( 'custom_metabox', $b );

        if ( $b[ 'callback' ] )
            call_user_func( $b[ 'callback' ], $post );

        if ( $b[ 'form' ]->has_fields ) {
            echo apply_filters( 'metabox_open_form', '<table class="form-table">' );

            self::set_values( $post );

            $b[ 'form' ]->render();

            echo apply_filters( 'metabox_close_form', '</table>' );
        }
    }

    /**
     *
     * Filter for formatting fields metabox
     *
     * @return string HTML formatted according to the standards of Dashboard
     *
     */
    public static function fields_format( $html, $html_field, $f )
    {
        // %s = id, label, html_field
        unset( $html );
        if ( $f[ 'type' ] == 'sep' )
            return apply_filters( 'metabox_fields_sep_format', '<tr><th id="%s" colspan="2"><strong>%s</strong></th></tr>', $html_field, $f );
        else
            return apply_filters( 'metabox_fields_format', '<tr><th><label for="%s">%s</label></th><td>%s</td></tr>', $html_field, $f );
    }

    /**
     *
     * Checks the conditions for deployment of information and saves the fields as meta values
     *
     * @param integer $post_id Identifier Post
     *
     */
    public static function save( $post_id )
    {
        $post_type = ( isset( $_POST[ 'post_type' ] ) ) ? $_POST[ 'post_type' ] : '';

        if (
            $post_type &&
            !defined( 'DOING_AUTOSAVE' ) &&
            current_user_can( 'edit_post', $post_id ) &&
            ( get_post_status( $post_id ) == 'publish' )
        ) {
            $fields = array();
            foreach ( self::$boxes as $b ) {
                if ( $b[ 'post_type' ] == $post_type )
                    $fields = array_merge( $fields, $b[ 'form' ]->get_fields() );
            }
            $args = array(
                'object_type'   => 'post',
                'object_id'     => $post_id,
                'fields'        => $fields
            );
            do_action( 'metabox_save', $args[ 'object_type' ], $args[ 'object_id' ] );
            self::meta_save( $args );
        }
    }

    /**
     *
     * Sets the field values ​​where they exist in the database
     *
     * @param object $post Post object of WordPress
     *
     */
    private static function set_values( $post )
    {
        if ( !in_array( $post->post_status, array( 'auto-draft', 'trash', 'inherit' ) ) ) {
            $fields = array();
            foreach ( self::$boxes as $b ) {
                if ( $b[ 'post_type' ] == $post->post_type )
                    $fields = array_merge( $fields, $b[ 'form' ]->get_fields() );
            }

            $args = array(
                'object_type'   => 'post',
                'object_id'     => $post->ID,
                'fields'        => $fields
            );
            $values = self::meta_values( $args );
            foreach ( self::$boxes as $b ) {
                if ( $b[ 'post_type' ] == $post->post_type ) {
                    foreach( $values as $f => $v )
                        $b[ 'form' ]->set_field_value( $f, $v );
                }
            }
        }
    }

}

?>