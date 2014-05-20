<?php

/**
 *
 * Manages the entire treatment of existing posts types and custom posts
 *
 */
class CPT extends Dashboard
{

    /**
     *
     * @var static array $types Types of posts added
     *
     */
    private static $types;

    /**
     *
     * Initializes the private variables and triggers the types record
     *
     */
    private static function init()
    {
        if ( !isset( self::$types ) ) {
            self::$cols = array();
            self::$types = array();

            add_action( 'init', array( 'CPT', 'register' ) );
        }
    }

    /**
     *
     * Inserts a new type of post
     *
     * @param string $id Unique identifier for CPT
     * @param string|array $label Label(s) to view the type in Dashboard
     * @param array $args Own options WordPress http://codex.wordpress.org/Function_Reference/register_post_type
     *
     */
    public static function add( $id, $label=null, $args=array() )
    {
        self::init();

        if ( is_null( $label ) )
            $label = ucfirst( $id );

        if ( isset( $args[ 'cols' ] ) ) {
            self::add_cols( $id, $args[ 'cols' ] );
            unset( $args[ 'cols' ] );
        }

        self::$types[ $id ] = wp_parse_args(
            $args,
            array(
                'labels'    => self::set_labels( $label, $id ),
                'public'    => true
            )
        );
    }

    /**
     *
     * Applies labels throughout the Dashboard
     *
     * @param string|array $label Label the type of post
     * @param string $post_type Post type
     * @return array Formatted label
     *
     */
    private static function set_labels( $label, $post_type )
    {
        $plural = false;
        if ( is_string( $label ) ) {
            $singular = $label;
        } else if ( is_array( $label ) && isset( $label[ 'singular' ] ) ) {
            $singular = $label[ 'singular' ];
            $plural = ( isset( $label[ 'plural' ] ) ) ? $label[ 'plural' ] : $singular . 's';
        }

        if ( !( $plural ) )
            $plural = $singular . 's';

        return apply_filters(
            'cpt_set_labels',
            array(
                'name'                  => $plural,
                'add_new'               => __r( 'Add new' ),
                'add_new_item'          => __r( 'Add new %s', $singular ),
                'edit_item'             => __r( 'Edit %s', $singular ),
                'new_item'              => __r( 'New %s', $singular ),
                'view_item'             => __r( 'View %s', $singular ),
                'search_items'          => __r( 'Search %s', $plural ),
                'not_found'             => __r( 'Nothing found' ),
                'not_found_in_trash'    => __r( 'Nothing found in trash' ),
                'all_items'             => __r( 'All' )
            ),
            $post_type
        );
    }

    /**
     *
     * Allows deleting custom post type
     *
     * @param string $type Type of post to be removed
     *
     */
    public static function delete( $type )
    {
        $types = (array) $type;
        foreach ( $types as $t ) {
            if ( isset( self::$types[ $t ] ) )
                unset( self::$types[ $t ] );
        }
    }

    /**
     *
     * Registers the types of posts and references to update the rules of permalinks
     *
     */
    public static function register()
    {
        foreach( self::$types as $type=>$attr )
            register_post_type( $type, $attr );

        self::flush();
    }


    /**
     *
     * Insert new columns to a specific type of post
     *
     * @param string $type Custom post types
     * @param array $cols Columns to be inserted
     *
     */
    public static function add_cols( $type, $cols )
    {
        if ( !isset( self::$cols ) )
            self::$cols = array();

        self::$cols[ $type ] = $cols;

        if ( !has_filter( 'manage_posts_columns', array( 'CPT', 'cols' ) ) )
            add_filter( 'manage_posts_columns', array( 'CPT', 'cols' ), 10, 2 );

        add_filter( 'manage_pages_columns', array( 'CPT', 'cols' ), 10, 2 );
        add_action( 'manage_' . $type . '_posts_custom_column', array( 'CPT', 'cols_content' ) );

        if ( self::has_cols_order( $cols ) ) {
            if ( !has_filter( 'request', array( 'CPT', 'cols__request' ) ) )
                add_filter( 'request', array( 'CPT', 'cols__request' ) );

            add_filter( 'manage_edit-' . $type . '_sortable_columns', array( 'CPT', 'cols_order' ) );
        }
    }

    /**
     *
     * Inserts new columns to list of results
     *
     * @param array $cols Current columns on display
     * @param string $post_type Post type
     * @return array Custom columns
     *
     *
     */
    public static function cols( $cols, $post_type='page' )
    {
        if ( isset( self::$cols[ $post_type ] ) ) {
            $custom = array();
            foreach( self::$cols[ $post_type ] as $c )
                $custom[ $c[ 'id' ] ] = $c[ 'label' ];

            $cols = array_merge( $cols, $custom );
        }

        return $cols;
    }

    /**
     *
     * Insert content in custom columns
     *
     * @global object $post Object post of WordPress
     * @param string $col Column that will receive content
     *
     */
    public static function cols_content( $col )
    {
        global $post;
        switch ( $col )
        {
            case 'thumbnail':
                the_post_thumbnail( 'thumbnail' );
                break;
            case 'comment_count':
                echo $post->comment_count;
                break;
            case 'order':
                echo $post->menu_order;
                break;
            default:
                $meta = get_post_meta( $post->ID, $col, true );
                echo apply_filters( 'cpt_cols_content', $meta, $col, $post );
                break;
        }
    }

    /**
     *
     * Define columns capable of ordening
     *
     * @param array $cols List with current columns
     * @return array List with new columns added
     *
     */
    public static function cols_order( $cols )
    {
        return array_merge( $cols, self::get_cols_order() );
    }

    /**
     *
     * Performs necessary filters to enable sorting according 
     * to the custom columns and  chosen fields
     *
     * @param array $vars Variables of the query of WordPress
     * @return array Updated variables
     *
     */
    public static function cols__request( $vars )
    {
        if ( is_admin() ) {
            $s = get_current_screen();
            if ( $s->base == 'edit' ) {
                $cols = array_values( self::get_cols_order() );
                if (
                    isset( $vars[ 'orderby' ] ) &&
                    in_array( $vars[ 'orderby' ], $cols )
                ) {
                    $meta_key = $vars[ 'orderby' ];
                    switch ( $meta_key ) {
                        case 'order':
                            $vars[ 'orderby' ] = 'menu_order';
                            break;
                        case 'comment_count':
                            $vars[ 'orderby' ] = $meta_key;
                            break;
                        default:
                            $type = self::get_order_type( $meta_key );
                            $cols_order = ( $type == 'numeric' ) ? 'meta_value_num' : 'meta_value';
                            $vars = array_merge(
                                $vars,
                                array(
                                    'meta_key'  => $meta_key,
                                    'orderby'   => $cols_order
                                )
                            );
                            break;
                    }
                    return apply_filters( 'cpt_custom_order', $vars );
                }
            }
        }
        return $vars;
    }

}

?>