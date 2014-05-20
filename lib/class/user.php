<?php

/**
 *
 * Meta values ​​for users
 *
 */
class User extends Dashboard
{

    /**
     *
     * @var array List of form objects Framework
     *
     */
    private static $forms;

    /**
     *
     * @var array List of fields according to the user's permission
     *
     */
    private static $role_fields;

    /**
     *
     * Class Initializer, start the triggers needed
     *
     * @param string $place Sets the location to display the new fields
     *
     */
    private static function init()
    {
        if ( !is_array( self::$forms ) ) {
            self::$forms = array();
            self::$role_fields = array();

            add_filter( 'form_field', array( 'User', 'fields_format' ), 2, 4 );

            // New user
            add_action( 'user_new_form', array( 'User', 'edit' ) );
            add_action( 'user_register', array( 'User', 'save' ) );

            add_action( 'show_user_profile',        array( 'User', 'edit' ) );
            add_action( 'edit_user_profile',        array( 'User', 'edit' ) );

            add_action( 'personal_options_update',  array( 'User', 'save' ) );
            add_action( 'edit_user_profile_update', array( 'User', 'save' ) );
        }
    }

    /**
     *
     * Inserts the form fields according to the user access permission
     *
     * @param null|string|array $roles Access permission
     * @param array $fields Fields list
     *
     */
    public static function add_fields( $roles, $fields )
    {
        self::init();
        $roles = ( is_null( $roles ) ) ? array( 'all' ) : (array) $roles;
        foreach ( $roles as $role ) {
            self::$forms[ $role ] = new Form();
            self::$forms[ $role ]->add_fields( $fields, apply_filters( 'root_user_prefix', '' ) );
            self::$role_fields[ $role ] = self::$forms[ $role ]->get_fields_names();
        }
    }

    /**
     *
     * Renders the form for editing
     *
     * @param string|object $user Text of new user, or object User to WordPress
     *
     */
    public static function edit( $user )
    {
        if ( ( is_object( $user ) && self::check_roles( $user->roles ) ) || is_string( $user ) ) {
            do_action( 'user_edit', $user );

            echo apply_filters( 'user_open_form', '<table class="form-table">' );

            $roles = array( 'all' );
            if ( isset( $user->ID ) ) {
                self::set_values( $user->ID );
                $roles = array_merge( $roles, $user->roles );
            }

            foreach ( $roles as $r ) {
                if ( isset( self::$forms[ $r ] ) )
                    self::$forms[ $r ]->render();
            }

            echo apply_filters( 'user_close_form', '</table>' );
        }
    }


    /**
     *
     * Checks whether or not to display the form in accordance with the permission that this has
     *
     * @param array $roles Permissions for the form should be rendered
     * @return boolean Inserts or not the new fields
     *
     */
    private static function check_roles( $user_roles )
    {
        $show = false;
        $roles = array_keys( self::$role_fields );
        if ( in_array( 'all', $roles ) ) {
            $show = true;
        } else {
            foreach( $roles as $r ) {
                if ( in_array( $r, $user_roles ) ) {
                    $show = true;
                    break;
                }
            }
        }
        return $show;
    }

    /**
     *
     * Formats the form markup in accordance with the standard Dashboard
     *
     * @return string Formatted HTML
     *
     */
    public static function fields_format( $html, $html_field, $f, $has_html )
    {
        unset( $html );
        if ( $f[ 'type' ] == 'sep' ) {
            $format = ( $has_html ) ? '</table>' : '';
            $format .= '<h3 id="%s">%s</h3><table class="form-table">';
            return apply_filters( 'user_fields_sep_format', $format, $html_field, $f );
        } else {
            return apply_filters( 'user_fields_format', '<tr><th><label for="%s">%s</label></th><td>%s</td></tr>' );
        }
    }

    /**
     *
     * Permission checks and saves the meta data in the database
     *
     * @param integer $user_id User identifier of WordPress
     * @return integer User ID
     *
     */
    public static function save( $user_id )
    {
        if ( current_user_can( 'edit_user', $user_id ) ) {
            $args = array(
                'object_type'   => 'user', // post, comment, user
                'object_id'     => $user_id,
                'fields'        => self::get_fields( $user_id )
            );
            do_action( 'user_save', $args[ 'object_id' ] );
            self::meta_save( $args );
        }
        return $user_id;
    }

    /**
     *
     * Sets the field values ​​if they exist in the database
     *
     * @param integer $user_id User identifier of WordPress
     *
     */
    private static function set_values( $user_id )
    {
        if ( is_int( $user_id ) ) {
            $args = array(
                'object_type'   => 'user',
                'object_id'     => $user_id,
                'fields'        => self::get_fields( $user_id )
            );
            $values = self::meta_values( $args );
            foreach( $values as $f => $v ) {
                foreach ( self::$role_fields as $role => $fields ) {
                    if ( in_array( $f, $fields ) )
                        self::$forms[ $role ]->set_field_value( $f, $v );
                }
            }
        }
    }

    /**
     *
     * Returns the set of all custom fields
     *
     * @param int $user_id User ID of WordPress
     * @return array Fields list
     *
     */
    private static function get_fields( $user_id )
    {
        $fields = array();
        $u = get_userdata( $user_id );
        array_push( $u->roles, 'all' );
        foreach ( $u->roles as $r ) {
            if ( isset( self::$forms[ $r ] ) )
                $fields = array_merge( $fields, self::$forms[ $r ]->get_fields() );
        }
        return $fields;
    }

    /**
     *
     * Adds new columns to list of results
     *
     * @param array $cols List with the new columns
     *
     */
    public static function add_cols( $cols )
    {
        if ( !isset( self::$cols ) )
            self::$cols = array();

        self::$cols[ 'user' ] = $cols;

        add_filter( 'manage_users_columns',         array( 'User', 'cols' ) );
        add_filter( 'manage_users_custom_column',   array( 'User', 'cols_content' ), 10, 3 );

        if ( self::has_cols_order( $cols ) ) {
            add_filter( 'manage_users_sortable_columns',    array( 'User', 'cols_order' ) );
            add_filter( 'pre_user_query',                   array( 'User', 'cols_request' ) );
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
        if ( isset( self::$cols[ 'user' ] ) ) {
            $custom = array();
            foreach( self::$cols[ 'user' ] as $c )
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
     * @param string $col Column for which the content is inserted
     * @param integer $user_id User ID of WordPress
     * @return string Information to be displayed in the results
     *
     */
    public static function cols_content( $value, $col, $user_id )
    {
        unset( $value ); // deprecated
        $user = get_userdata( $user_id );
        switch ( $col )
        {
            case 'url':
                return $user->user_url;
                break;
            case 'description':
                return $user->user_description;
                break;
            default:
                $meta = get_user_meta( $user_id, $col, true );
                return apply_filters( 'user_cols_content', $meta, $col, $user );
                break;
        }
    }

    /**
     *
     * Define columns capable of ordering
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
     * Checking the request in Dashboard and customize to display the results of custom columns
     *
     * @global object $wpdb Class database of WordPress http://codex.wordpress.org/Class_Reference/wpdb
     * @param object $wp_user Class to query users of WordPress http://codex.wordpress.org/Class_Reference/WP_User_Query
     *
     */
    public static function cols_request( $wp_user )
    {
        if ( is_admin() ) {
            $s = get_current_screen();
            if ( $s->base == 'users' ) {
                $vars = $wp_user->query_vars;
                $cols = array_values( self::get_cols_order() );
                if (
                    isset( $vars[ 'orderby' ] ) &&
                    in_array( $vars[ 'orderby' ], $cols )
                ) {
                    $meta_key = $vars[ 'orderby' ];

                    global $wpdb;
                    $order = strtoupper( $_REQUEST[ 'order' ] );
                    if ( !in_array( $order, array( 'ASC', 'DESC' ) ) )
                        $order = 'ASC';

                    $wp_user->query_vars[ 'orderby' ] = 'meta_value';
                    $wp_user->query_fields = "SQL_CALC_FOUND_ROWS u.ID, replace( um.meta_value, ',', '.' ) AS mv";
                    $wp_user->query_from = "FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON u.ID=um.user_id";
                    $wp_user->query_where = sprintf(
                        "WHERE 1=1 AND um.meta_key='%s'",
                        $meta_key
                    );

                    $type = self::get_order_type( $meta_key );
                    $cast = ( $type == 'numeric' ) ? "mv+0" : 'mv';
                    $wp_user->query_orderby = sprintf(
                        "ORDER BY {$cast} {$order}",
                        $meta_key
                    );
                }
            }
        }
    }

}

?>
