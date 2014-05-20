<?php

/**
 *
 * Handling forms
 *
 */
class Form
{

    /**
     *
     * @var string HTML markup
     *
     */
    private $html;

    /**
     *
     * @var array Field list
     *
     */
    private $fields;

    /**
     *
     * @var array Sets whether the form has fields linked to it
     *
     */
    public $has_fields;

    /**
     *
     * Form construct
     *
     * @param string $action HTML attribute of the form
     * @param string $method Send method GET|POST
     * @param array $attr Aditional attributes
     *
     */
    public function __construct( $action=null, $method='post', $attr=array() )
    {
        $attr[ 'type' ] = 'form';

        if ( is_null( $action ) ) {
            $html = '';
        } else {
            $html = sprintf(
                '<form action="%s" method="%s"%s>',
                $action,
                $method,
                $this->print_attr( $attr )
            );
        }

        $this->html = $html;
        $this->fields = array();
        $this->has_fields = false;

        add_filter( 'form_field', array( $this, 'fields_format' ), 1, 3 );
    }

    /**
     *
     * Checks if the attribute has sent an allowed value
     *
     * @param string $type Field type
     * @param string $attr Attribute to be inserted
     * @return boolean Allowed or not using the attribute to the specific field type
     *
     */
    private function is_attr( $type, $attr )
    {
        $attrs = array(
            'type',
            'name',
            'value',
            'class',
            'id',
            'required',
            'tabindex'
        );
        switch ( $type ) {
            case 'form':
                $attr_del = array( 'type', 'name', 'value', 'required' );
                array_push( $attrs , 'enctype' );
                break;
            case 'text':
                array_push( $attrs , 'placeholder' );
                break;
            case 'textarea':
                $attr_del = array( 'type', 'value' );
                array_push( $attrs , 'placeholder', 'cols', 'rows' );
                break;
            case 'select':
                $attr_del = array( 'type', 'value' );
                array_push( $attrs , 'size', 'multiple' );
                break;
            case 'radio':
                array_push( $attrs , 'checked' );
                break;
            case 'checkbox':
                $attr_del = array( 'value' );
                array_push( $attrs , 'checked' );
                break;
            case 'reset':
            case 'submit':
                $attr_del = array( 'required' );
                break;
        }

        if ( isset( $attr_del ) ) {
            foreach ( $attr_del as $a ) {
                $k = array_search( $a, $attrs );
                unset( $attrs[ $k ] );
            }
        }

        return ( in_array( $attr, $attrs ) );
    }

    /**
     *
     * Inserts attributes in the tag field
     *
     * @param array $f List of attributes and their values
     * @return string HTML markup attribute
     *
     */
    private function print_attr( $f )
    {
        $html = '';
        foreach ( $f as $a=>$v ) {
            if ( $v && $this->is_attr( $f[ 'type' ], $a ) ) {
                $html .= sprintf( ' %s="%s"', $a, $v );
            }
        }
        return $html;
    }

    /**
     *
     * Input field type
     *
     * @param array $f Field data
     * @return string HTML markup
     *
     */
    private function input( $f )
    {
        $html = sprintf(
            '<input%s />',
            $this->print_attr( $f )
        );
        return $html;
    }

    /**
     *
     * Textarea field type
     *
     * @param array $f Field data
     * @return string HTML markup
     *
     */
    private function textarea( $f )
    {
        $size = array(
            'cols'  => 50,
            'rows'  => 5
        );
        $keys = array_keys( $size );
        foreach ( $keys as $s ) {
            if ( !isset( $f[ $s ] ) )
                $f[ $s ] = $size[ $s ];
        }
        $html = sprintf(
            '<textarea%s>%s</textarea>',
            $this->print_attr( $f ),
            $f[ 'value' ]
        );
        return $html;
    }

    /**
     *
     * Select field type
     *
     * @param array $f Field data
     * @return string HTML markup
     *
     */
    private function select( $f )
    {
        if ( is_array( $f[ 'opt' ] ) ) {
            $value = array( $f[ 'value' ] );

            $mult = false;
            if ( isset( $f[ 'mult' ] ) ) {
                unset( $f[ 'mult' ] );
                $mult = true;
            } else if ( isset( $f[ 'multiple' ] ) ) {
                $mult = true;
            }
            if ( $mult ) {
                $f[ 'multiple' ] = 'multiple';
                $f[ 'name' ] .= '[]';
            }

            $html = sprintf(
                '<select%s>%s</select>',
                $this->print_attr( $f ),
                $this->select_options( $f[ 'opt' ], $value )
            );
        }
        else {
            $html = __r( 'No options to the select field.' );
        }
        return $html;
    }

    /**
     *
     * Sets options for the select field
     *
     * @param array $opions Ratio values ​​and labels
     * @param array $values Values ​​to select
     * @return string HTML markup
     *
     */
    private function select_options( $options, $values )
    {
        $html = '';
        foreach ( $options as $v => $l ) { // value, label
            $html .= '<option value="' . $v . '"';
            foreach ( $values as $value ) {
                if ( $v == $value ) {
                    $html .= ' selected="selected"';
                    break;
                }
            }
            $html .= '>' . $l . '</option>';
        }

        return $html;
    }

    /**
     *
     * Radio field type
     *
     * @param array $f Field data
     * @return string HTML markup
     *
     */
    private function radio( $f )
    {
        $html = '';
        if ( count( $f[ 'opt' ] ) > 1 ) {
            $value = $f[ 'value' ];
            foreach ( $f[ 'opt' ] as $v => $l ) { // value, label
                $f[ 'value' ] = $v;
                if ( $value == $v ) {
                    $f[ 'checked' ] = 'checked';
                } else if ( isset( $f[ 'checked' ] ) ) {
                    unset( $f[ 'checked' ] );
                }

                $input = sprintf(
                    '<input%s />%s',
                    self::print_attr( $f ),
                    $l
                );
                $opt = sprintf(
                    '<label>%s</label>',
                    $input
                );
                $html .= $opt;
            }
        }
        return $html;
    }

    /**
     *
     * Checkbox field type
     *
     * @param array $f Field data
     * @return string HTML markup
     *
     */
    private function checkbox( $f )
    {
        $html = '';
        if ( isset( $f[ 'label' ] ) ) {
            if ( isset( $f[ 'checked' ] ) || ( $f[ 'value' ] == 'on' ) )
                $f[ 'checked' ] = 'checked';

            $input = sprintf(
                '<input%s />%s',
                self::print_attr( $f ),
                ( isset( $f[ 'desc' ] ) ) ? $f[ 'desc' ] : ''
            );
            $opt = sprintf( '<label>%s</label>', $input );
            $html .= $opt;
        }
        return $html;
    }

    /**
     *
     * Inserts a field to the form
     * Allowed fields like:
     * text, textarea, select, radio, checkbox, reset, submit, password, number, date, time, email, hidden
     *
     * $field = array(
     *  'label' => '',
     *  'name'  => '',
     *  'value' => '',
     *  'type'  => '', // set the field type
     *  'id'    => ''
     * );
     *
     * In addition to these basic attributes above the HTML tag attributes are allowed;
     * for example class, required, placeholder (ph)...
     *
     * Attributes are accepted in accordance with the type also:
     * checkbox accepts the parameter checked
     * select accepts the parameter multiple (mult)
     * textarea; cols, rows
     *
     * Types radio and select must have the opt parameter with the desired options
     * opt is an associative array with label => value
     * in a select color the opt array would be something like
     * 'opt' => array( 'red' => 'Red', 'lime-green' => 'Lime Green' );
     *
     * @param array $f Field attributes
     * @return string|boolean Error messages or true if the field has been added
     *
     */
    public function add_field( $f )
    {
        foreach ( $this->fields as $field ) {
            if ( isset( $f[ 'name' ] ) && ( $f[ 'name' ] == $field[ 'name' ] ) )
                return __r( 'There is already a field with the same name!' );
        }

        $field = array(
            'label' => '',
            'name'  => '',
            'value' => '',
            'type'  => '',
            'id'    => ''
        );
        $f = wp_parse_args( $f, $field );
        if ( !in_array( $f[ 'type' ], $this->get_fields_anonymous() ) && !$f[ 'name' ] ) {
            return __r( 'You need to define a name to the field!' );
        } else {
            if ( isset( $f[ 'req' ] ) ) {
                $f[ 'required' ] = 'required';
                unset( $f[ 'req' ] );
            }

            if ( !$f[ 'name' ] )
                $f[ 'name' ] = 'field-' . count( $this->fields );

            if ( !$f[ 'type' ] )
                $f[ 'type' ] = 'text';

            if ( !$f[ 'id' ] )
                $f[ 'id' ] = $this->get_field_id( $f );

            if ( isset( $f[ 'ph' ] ) ) {
                $f[ 'placeholder' ] = $f[ 'ph' ];
                unset( $f[ 'ph' ] );
            }
            array_push( $this->fields, $f );
            $this->has_fields = true;
        }
        return true;
    }

    /**
     *
     * Filter for formatting specific fields
     *
     * @param string $html Default HTML markup
     * @param string $html_field Field HTML markup
     * @param array $f Field attributes
     * @return string Updated HTML markup
     *
     */
    public function fields_format( $html, $html_field, $f )
    {
        switch ( $f[ 'type' ] )
        {
            case 'radio':
            case 'checkbox':
                unset( $html );
                return sprintf(
                    "<div><span>%s</span> %s</div>",
                    ( isset( $f[ 'label' ] ) ) ? $f[ 'label' ] : 'Escolha: ',
                    $html_field
                );
                break;
            case 'sep':
                unset( $html, $html_field );
                return sprintf(
                    "<div><strong>%s</strong></div>",
                    ( isset( $f[ 'label' ] ) ) ? $f[ 'label' ] : ''
                );
                break;
            case 'hidden':
                unset( $html );
                return $html_field;
                break;
            default:
                return $html;
                break;
        }
    }

    /**
     *
     * Inserts multiple fields at once
     *
     * @param array $fields List of all fields
     * @param string $prefix Sets a prefix to the field name
     *
     */
    public function add_fields( $fields, $prefix=null )
    {
        if ( is_array( $fields ) && count( $fields ) ) {
            foreach ( $fields as $f ) {
                if ( $prefix )
                    $f[ 'name' ] = $prefix . $f[ 'name' ];

                $this->add_field( $f );
            }
        }
    }

    /**
     *
     * Sets an id for the field
     *
     * @param array $field Field attributes
     * @return string Identifier as [field type] - [field name]
     *
     */
    private function get_field_id( $field )
    {
        // @fix regular expression instead of str_replace
        $id = $field[ 'type' ] . '-' . str_replace( '_', '-', $field[ 'name' ] );
        return str_replace( '--', '-', $id );
    }

    /**
     *
     * List of fields that do not need to have a defined name
     *
     * @return array Field list
     *
     */
    private function get_fields_anonymous()
    {
        return array(
            'sep',
            'reset',
            'submit'
        );
    }

    /**
     *
     * Displays on the screen formatted form with fields inserted
     *
     */
    public function render()
    {
        $html = apply_filters( 'form_header', $this->html );
        echo $html;

        do_action( 'before_form_render' );

        echo apply_filters( 'form_render', $this->render_html(), $this->fields );

        do_action( 'after_form_render' );

        if ( $html )
            echo apply_filters( 'form_footer', '</form>' );
    }

    /**
     *
     * Transforms each field in their proper HTML markup
     *
     */
    private function render_html()
    {
        if ( is_array( $this->fields ) ) {
            $html = '';
            foreach ( $this->fields as $f ) {
                switch ( $f[ 'type' ] ) {
                    case 'text':
                    case 'password':
                    case 'number':
                    case 'date':
                    case 'time':
                    case 'email':
                    case 'reset':
                    case 'submit':
                    case 'hidden':
                        $html_field = $this->input( $f );
                        break;
                    case 'textarea':
                        $html_field = $this->textarea( $f );
                        break;
                    case 'select':
                        $html_field = $this->select( $f );
                        break;
                    case 'radio':
                        $html_field = $this->radio( $f );
                        break;
                    case 'checkbox':
                        $html_field = $this->checkbox( $f );
                        break;
                    default:
                        $html_field = __r( 'Invalid field...' );
                        break;
                }

                $html .= sprintf(
                    apply_filters( 'form_field', '<div><label for="%s">%s</label>%s</div>', $html_field, $f, ( $html ) ),
                    $f[ 'id' ],
                    $f[ 'label' ],
                    $html_field
                );
            }
            return $html;
        }
    }

    /**
     *
     * Returns all fields added to the form
     *
     * @return array List of fields
     *
     */
    public function get_fields()
    {
        return $this->fields;
    }

    /**
     *
     * Returns all the field names added to the form
     *
     * @return array List of fields
     *
     */
    public function get_fields_names()
    {
        $names = array();
        foreach ( $this->fields as $f )
            array_push( $names, $f[ 'name' ] );

        return $names;
    }

    /**
     *
     * Allows you to enter a value to the field after being added to the form
     *
     * @param string $field Field name
     * @param mixed $value Field value
     *
     */
    public function set_field_value( $field, $value )
    {
        foreach ( $this->fields as $k => $f ) {
            if ( $f[ 'name' ] == $field ) {
                $this->fields[ $k ][ 'value' ] = $value;
                break;
            }
        }
    }

    /**
     *
     * Removes a field from the field list
     *
     * @param string $field_name Field name
     *
     */
    public function delete_field( $field_name )
    {
        foreach ( $this->fields as $k => $f ) {
            if ( $f[ 'name' ] == $field_name ) {
                unset( $this->fields[ $k ] );
                break;
            }
        }
    }

}

// @todo fieldset, legend, button, upload, mult-ulpload, select optgroup

?>