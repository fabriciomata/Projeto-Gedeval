<?php

/**
 *
 * Handles the insertion and retrieval of values ​​in Dashboard
 *
 */
abstract class Dashboard
{

    /**
     *
     * @var array Sortable columns in the list of results
     *
     */
    protected static $cols;

    /**
     *
     * Performs the update of the rewriting rules of URLs according to the version of the theme
     * The rules are rewritten only if the version of the theme is different from that stored in the database
     *
     */
    protected function flush()
    {
        run_once( 'root_flush_rules', 'flush_rewrite_rules', THEME_VERSION );
    }

    /**
     *
     * Data before being saved
     *
     * @param array $fields Fields to be processed
     * @return array Formatted list of values
     *
     */
    protected static function prepare_save( $fields )
    {
        $meta = array();
        if ( is_array( $fields ) && !empty( $fields ) ) {
            $not_save = self::get_empty_types();
            foreach ( $fields as $key => $field ) {
                if ( in_array( $field[ 'type' ], $not_save ) )
                    continue;

                $v = false;
                $field_name = $field[ 'name' ];
                if ( isset( $_POST[ $field_name ] ) )
                    $v = ( isset( $field[ 'html' ] ) ) ? wp_kses( $_POST[ $field_name ] ) : sanitize_text_field( $_POST[ $field_name ] );

                $fields[ $key ][ 'value' ] = $v;

                $m = ( isset( $field[ 'meta' ] ) ) ? $field[ 'meta' ] : $field[ 'name' ];
                if ( !isset( $meta[ $m ] ) )
                    $meta[ $m ] = array();

                $meta[ $m ][ $field_name ] = $v;
            }
        }
        return $meta;
    }

    /**
     *
     * Checks if the value (serialized or not) is empty
     *
     * @param array $value Value in inspection
     * @return boolean Empty or filled
     *
     */
    private static function is_empty_value( $value )
    {
        $blank = true;
        if ( count( $value ) > 1 ) {
            foreach( $value as $v ) {
                if ( $v ) {
                    $blank = false;
                    break;
                }
            }
        } else {
            $value = array_shift( $value );
            if ( $value )
                $blank = false;
        }
        return $blank;
    }

    /**
     *
     * Saves the meta data in single or serialized form
     *
     * $args = array(
     *  'object_type'   => 'post', 'comment', 'user', 'term'
     *  'object_id'     => $object_id,
     *  'fields'        => $fields
     * );
     *
     * @param array $args Receives the object type, and ID fields to be processed
     * @return int|boolean ID of the object on success, otherwise false
     *
     */
    protected static function meta_save( $args )
    {
        $meta = self::prepare_save( $args[ 'fields' ] );
        // meta = array( meta_key => array( name => value ) )
        foreach ( $meta as $key => $value ) {
            if ( !self::is_empty_value( $value ) )
                update_metadata( $args[ 'object_type' ], $args[ 'object_id' ], $key, self::the_value( $value, $key ) );
            else // if ( $blank  && $meta_old )
                delete_metadata( $args[ 'object_type' ], $args[ 'object_id' ], $key );
        }

        return $args[ 'object_id' ];
    }

    /**
     *
     * Saves single or serialized mode options
     *
     * @param array $fields Fields to be processed
     *
     */
    protected static function option_save( $fields )
    {
        $meta = self::prepare_save( $fields );
        foreach ( $meta as $key => $value ) {
            if ( !self::is_empty_value( $value ) )
                update_option( $key, self::the_value( $value, $key ) );
            else
                delete_option( $key );
        }
    }

    private static function the_value( $value, $key )
    {
        return ( isset( $value[ $key ] ) ) ? array_shift( $value ) : $value;
    }

    /**
     *
     * Retrieves the meta values ​​of a object
     *
     * $args = array(
     *  'object_type'   => 'post', 'comment', 'user', 'term'
     *  'object_id'     => $object_id,
     *  'fields'        => $fields
     * );
     *
     *
     * @param array $args Object type, ID and fields to be retrieved
     * @return array|boolean The values ​​of the object, or false
     *
     */
    protected static function meta_values( $args )
    {
        if ( is_array( $args[ 'fields' ] ) && !empty( $args[ 'fields' ] ) ) {
            $values = array();
            $fields = $args[ 'fields' ];
            $meta = get_metadata( $args[ 'object_type' ], $args[ 'object_id' ] );

            foreach( $fields as $field ) {
                $f = $field[ 'name' ];
                $key = ( isset( $field[ 'meta' ] ) ) ? $field[ 'meta' ] : $field[ 'name' ];
                if ( isset( $meta[ $key ] ) ) {
                    $v = maybe_unserialize( $meta[ $key ][0] );
                    if ( is_array( $v ) && isset( $v[ $f ] ) )
                        $v = $v[ $f ];

                    $values[ $f ] = $v;
                }
            }
            return $values;
        }
        return false;
    }

    /**
     *
     * Returns the object for editing
     *
     * @return string Name of taxonomy, type of post or type of user data
     *
     */
    protected static function get_screen_object()
    {
        $object = false;
        $s = get_current_screen();
        switch ( $s->base )
        {
            case 'edit-tags':
                $object = $s->taxonomy;
                break;
            case 'edit':
                $object = $s->post_type;
                break;
            case 'users':
                $object = 'user';
                break;
        }
        return $object;
    }

    /**
     *
     * Retrieves which likely sort columns according to the current screen
     *
     * @return array Columns that can be sorted
     *
     */
    protected static function get_cols_order()
    {
        $c = array();
        $obj = self::get_screen_object();
        if ( isset( self::$cols[ $obj ] ) ) {
            $custom_cols = self::$cols[ $obj ];
            foreach( $custom_cols as $col ) {
                if ( isset( $col[ 'order' ] ) )
                    $c[ $col[ 'id' ] ] = $col[ 'id' ];
            }
        }
        return $c;
    }

    /**
     *
     * Identify if there are columns to be ordered in custom mode
     *
     * @param array $cols Columns to be checked
     * @return boolean Success or fail
     *
     */
    protected static function has_cols_order( $cols )
    {
        $has_order = false;
        foreach ( $cols as $c ) {
            if ( isset( $c[ 'order' ] ) ) {
                $has_order = true;
                break;
            }
        }
        return $has_order;
    }

    /**
     *
     * Sets the order type (text, numeric) according to the selected column
     *
     * @param string $key Column to be ordered
     * @return string Field type
     *
     */
    protected static function get_order_type( $key )
    {
        $type = 'string';
        $obj = self::get_screen_object();
        $custom_cols = self::$cols[ $obj ];
        foreach( $custom_cols as $col ) {
            if ( ( $col[ 'id' ] == $key ) && isset( $col[ 'type' ] ) ) {
                $type = $col[ 'type' ];
                break;
            }
        }
        return $type;
    }

    /**
     *
     * Returns the fields that do not need to name
     *
     * @return array Fields ID
     *
     */
    protected static function get_empty_types()
    {
        return array( 'sep', 'submit', 'reset' );
    }

}

// @todo filters on the wp_list_table of the all lists

?>