<?php

/**
 *
 * Creating and managing meta values ​​for terms
 *
 */
class Term extends Dashboard
{

    private static $terms;

    /**
     *
     * Custom fields to taxonomies
     *
     * @var array
     *
     */
    private static $fields;

    /**
     *
     * Initializer function of the class
     * Responsible for creating the table in the database if there is no
     *
     * @global object $wpdb Class database of WordPress http://codex.wordpress.org/Class_Reference/wpdb
     *
     */
    public static function init()
    {
        if ( !isset( self::$fields ) ) {
            self::$fields = array();
            self::$terms = array();

            global $wpdb;
            $wpdb->termmeta = $wpdb->prefix . 'termmeta';
            $table_exists = get_option( 'root_termmeta' );

            if ( !$table_exists ) {
                add_option( 'root_termmeta', true );
                $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->termmeta}` (
                    `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `term_id` bigint(20) unsigned NOT NULL,
                    `meta_key` varchar(255) NOT NULL,
                    `meta_value` longtext NOT NULL,
                    PRIMARY KEY (`meta_id`)
                ) ENGINE=InnoDB";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
            }

            add_action( 'init', array( 'Term', 'register' ) );
        }
    }

    public static function add( $tax, $post_type, $label=null, $args=array() )
    {
        self::init();

        if ( is_null( $label ) )
            $label = ucfirst( $tax );

        if ( isset( $args[ 'cols' ] ) ) {
            self::add_cols( $tax, $args[ 'cols' ] );
            unset( $args[ 'cols' ] );
        }

        self::$terms[ $tax ] = wp_parse_args(
            $args,
            array(
                'post_type'         => $post_type,
                'labels'            => self::set_labels( $label, $tax ),
                'public'            => true,
                'show_in_nav_menus' => false,
                'show_ui'           => true,
                'show_tagcloud'     => false,
                'hierarchical'      => true
            )
        );
    }

    /**
     *
     * Applies labels throughout the Dashboard
     *
     * @param string|array $label Label taxonomy
     * @param string $tax Name taxonomy
     * @return array Formatted labels
     *
     */
    private static function set_labels( $label, $tax )
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
            'term_set_labels',
            array(
                'name'              => $plural,
                'singular_name'     => $singular,
                'search_items'      => __r( 'Search %s', $plural ),
                'all_items'         => __r( 'All' ),
                'parent_item'       => __r( 'Parent %s', $singular ),
                'parent_item_colon' => __r( 'Parent %s:', $singular ),
                'edit_item'         => __r( 'Edit %s', $plural ),
                'update_item'       => __r( 'Update %s', $singular ),
                'add_new_item'      => __r( 'Add %s', $singular ),
                'new_item_name'     => __r( 'Add %s', $singular ),
                'menu_name'         => $plural
            ),
            $tax
        );
    }

    /**
     *
     * Allows deletion of a custom taxonomy
     *
     * @param string $type Taxonomy to be removed
     *
     */
    public static function delete( $term )
    {
        $terms = (array) $term;
        foreach ( $terms as $t ) {
            if ( isset( self::$terms[ $t ] ) )
                unset( self::$terms[ $t ] );
        }
    }

    /**
     *
     * Registers taxonomies and triggers the update of the rules of permalinks
     *
     */
    public static function register()
    {
        foreach( self::$terms as $term=>$attr )
            register_taxonomy( $term, $attr[ 'post_type' ], $attr );

        self::flush();
    }

    /**
     *
     * Insert form fields to the term
     *
     * @param string $tax Taxonomy that will receive the custom fields
     * @param array $fields Fields list
     *
     */
    public static function add_fields( $tax, $fields )
    {
        self::init();
        if ( !isset( self::$fields[ $tax ] ) ) {
            self::$fields[ $tax ] = new Form();

            // Insert
            add_action( $tax . '_add_form_fields',  array( 'Term', 'edit' ) );
            add_action( 'created_' . $tax,          array( 'Term', 'save_meta' ) );

            // Update
            add_action( $tax . '_edit_form_fields', array( 'Term', 'edit' ) );
            add_action( 'edit_' . $tax,             array( 'Term', 'save_meta' ) );

            add_action( 'delete_' . $tax,           array( 'Term', 'delete_meta' ) );
        }

        self::$fields[ $tax ]->add_fields( $fields, apply_filters( 'term_fields_prefix', '' ) );
    }

    /**
     *
     * Inserts fields on the screens of insertion and modification of the terms
     *
     * @param string|object $tax Name of the object itself or taxonomy of taxonomy
     *
     */
    public static function edit( $tax )
    {
        if ( is_object( $tax ) ) {
            $t = $tax->taxonomy; // tax name
            add_filter( 'form_field', array( 'Term', 'fields_format_edit' ) );

            $args = array(
                'object_type'   => 'term', // post, comment, user
                'object_id'     => $tax->term_id,
                'fields'        => self::$fields[ $t ]->get_fields()
            );
            $values = self::meta_values( $args );
            foreach( $values as $f => $v )
                self::$fields[ $t ]->set_field_value( $f, $v );
        } else {
            $t = $tax;
            self::get_fields( $t );
            add_filter( 'form_field', array( 'Term', 'fields_format_add' ) );
        }
        self::$fields[ $t ]->render();
    }

    /**
     *
     * Retrieves only the fields for the entry screen
     *
     * @param string $t Taxonomy name
     *
     */
    private static function get_fields( $t )
    {
        $fields = self::$fields[ $t ]->get_fields();
        foreach ( $fields as $field ) {
            $place = ( isset( $field[ 'place' ] ) ) ? $field[ 'place' ] : 'update';
            if ( $place == 'update' )
                self::$fields[ $t ]->delete_field( $field[ 'name' ] );
        }
    }

    /**
     *
     * Formats the HTML to the screen for entering terms
     *
     * @return string formatted HTML
     *
     */
    public static function fields_format_add()
    {
        return apply_filters( 'term_fields_format_add', '<div class="form-field"><label for="%s">%s</label>%s</div>' );
    }

    /**
     *
     * Formats the HTML for the screen editing terms
     *
     * @return string formatted HTML
     *
     */
    public static function fields_format_edit()
    {
        return apply_filters( 'term_fields_format_edit', '<tr class="form-field"><th scope="row" valign="top"><label for="%s">%s</label></th><td>%s</td></tr>' );
    }

    /**
     *
     * Saved in the database the values ​​of custom fields
     *
     * @param integer $term_id term ID
     * @return boolean Success or fail
     *
     */
    public static function save_meta( $term_id )
    {
        $tax = get_term( $term_id, $_POST[ 'taxonomy' ] );
        if ( is_wp_error( $tax ) )
            return false;

        do_action( 'term_save', $term_id, $tax );

        $args = array(
            'object_type'   => 'term',
            'object_id'     => $term_id,
            'fields'        => self::$fields[ $tax->taxonomy ]->get_fields()
        );
        self::meta_save( $args );
        return true;
    }

    /**
     *
     * Deletes the meta values ​​of the term exclusion
     *
     * @param integer $term_id Term identifier
     *
     */
    public static function delete_meta( $term_id )
    {
        do_action( 'term_delete', $term_id );

        global $wpdb;
        $term_meta_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT meta_id FROM $wpdb->termmeta WHERE term_id=%d",
                $term_id
            )
        );

        foreach ( $term_meta_ids as $mid )
            delete_metadata_by_mid( 'term', $mid );
    }

    /**
     *
     * Adds new columns to list of results
     *
     * @param string $tax Taxonomy to be customized
     * @param array $custom List with a new columns
     *
     */
    public static function add_cols( $tax, $cols )
    {
        if ( !isset( self::$cols ) )
            self::$cols = array();

        self::$cols[ $tax ] = $cols;

        add_filter( 'manage_edit-' . $tax . '_columns',     array( 'Term', 'cols' ) );
        add_filter( 'manage_' . $tax . '_custom_column',    array( 'Term', 'cols_content' ), 10, 3 );

        if ( self::has_cols_order( $cols ) ) {
            add_filter( 'manage_edit-' . $tax . '_sortable_columns',    array( 'Term', 'cols_order' ) );
            add_filter( 'get_terms_args',                               array( 'Term', 'cols_request' ), 10, 2 );
        }
    }

    /**
     *
     * Inserts new columns to list of results
     *
     * @param array $cols Current columns on display
     * @return array Custom columns
     *
     */
    public static function cols( $cols )
    {
        $obj = self::get_screen_object();
        if ( isset( self::$cols[ $obj ] ) ) {
            $custom = array();
            foreach( self::$cols[ $obj ] as $c )
                $custom[ $c[ 'id' ] ] = $c[ 'label' ];

            $cols = array_merge( $cols, $custom );
        }

        return $cols;
    }

    /**
     *
     * Defines the content for custom columns
     *
     * @param string deprecated $value ''
     * @param string $col Column that the content will be inserted
     * @param integer $user_id term ID in WordPress
     * @return string Information to be displayed in the results
     *
     */
    public static function cols_content( $value, $col, $term_id )
    {
        global $taxonomy;
        unset( $value ); // deprecated
        $term = get_term( $term_id, $taxonomy );
        switch ( $col )
        {
            default:
                $meta = get_term_meta( $term_id, $col, true );
                return apply_filters( 'term_cols_content', $meta, $col, $term );
                break;
        }
    }

    /**
     *
     * Sets the susceptible sort columns
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
	 * to the custom columns and chosen fields
     *
     * @param array $vars Variables of the query WordPress
     * @return array Updated variables
     *
     */
    public static function cols_request( $args ) //, $taxonomies )
    {
        if ( is_admin() ) {
            $s = get_current_screen();
            if ( $s->base == 'edit-tags' ) {
                $cols = array_values( self::get_cols_order() );
                if (
                    isset( $args[ 'orderby' ] ) &&
                    in_array( $args[ 'orderby' ], $cols )
                ) {
                    add_filter( 'terms_clauses', array( 'Term', 'define_filters' ), 1, 3 );
                }
            }
        }
        return $args;
    }

    public static function define_filters( $p, $tax_name, $vars )
    {
        /**
         *
         * $p = array(
         *  'fields'    => '',
         *  'join'      => 'INNER JOIN wp_term_taxonomy AS tt ON t.term_id = tt.term_id',
         *  'where'     => "tt.taxonomy IN ('category') AND tt.parent = '0'",
         *  'orderby'   => 'ORDER BY t.term_id',
         *  'order'     => '',
         *  'limits'    => ''
         * );
         *
         */

        unset( $tax_name );
        // Not is dropdown of terms
        if ( $vars[ 'number' ] > 0 ) {
            $meta_key = $_GET[ 'orderby' ];
            $type = self::get_order_type( $meta_key );
            $cast = ( $type == 'numeric' ) ? '+0' : '';

            global $wpdb;
            $p[ 'join' ]    .= " LEFT JOIN {$wpdb->termmeta} tm ON t.term_id=tm.term_id";
            $p[ 'where' ]   .= sprintf( " AND tm.meta_key='%s'", $meta_key );
            $p[ 'orderby' ]  = 'ORDER BY tm.meta_value' . $cast;
            $p[ 'order' ]    = $_GET[ 'order' ];
        }
        return $p;
    }

}
?>