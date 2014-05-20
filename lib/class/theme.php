<?php

/**
 *
 * Custom template tags
 *
 */
class Theme
{

    private static $path;

    // {{{ Images

    /**
     *
     * Retrieves the cover image of the current post or parameter
     * If not is there cover image, a default image is displayed with the appropriate dimensions
     *
     * @global object $post
     * @param string $size Name of image format
     * @param integer $post_id ID post to recover the highlighted image
     * @param string $alt Alternative text for image. By default this is the post title.
     * @return string HTML tag to insert the image on the site
     *
     */
    public static function get_thumb( $post_id=null, $size='thumbnail' )
    {
        $post_id = (int) $post_id;
        if ( !$post_id ) {
            global $post;
            $post_id = $post->ID;
        }

        $thumb = false;
        $thumb_id = (int) get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            $image = get_post( $thumb_id );
            list( $src, $alt ) = self::get_image_data( $image, $size );
            // if ( $src && $alt )
            // unnecessary condition it was already verified the existence of image
            // so, get_posts always returns valid
            $thumb = '<img src="' . $src . '" alt="' . esc_attr( $alt ) . '" />';
        } else {
            global $_wp_additional_image_sizes;
            if ( isset( $_wp_additional_image_sizes[ $size ] ) )
                extract( $_wp_additional_image_sizes[ $size ] );

            // @fix Set behavior if the format is thumbnail, medium or large
            if ( !isset( $width ) || !isset( $height ) ) {
                $width = 150;
                $height = 150;
            }

            $alt = __r( 'Image not available' );
            $img = sprintf( 'http://dummyimage.com/%sx%s/444/fff&text=%s', $width, $height, urlencode( $alt ) );
            $thumb = sprintf( '<img src="%s" alt="%s" />', $img, $alt );
            $thumb = apply_filters( 'default_image', $thumb, $img, $alt );
        }

        return $thumb;
    }


    /**
     *
     * Returns the alternate text and url of the image according to the requested format
     *
     * @param object $image Post image type
     * @param string|array $size Size of the image being recovered
     * @return array URL and alternative text to numeric array
     *
     */
    private static function get_image_data( $image, $size )
    {
        if ( isset( $image->ID ) && isset( $image->post_title ) ) {
            $alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
            if ( !$alt ) $alt = $image->post_title;
            list( $src ) = wp_get_attachment_image_src( $image->ID, $size );
            return array( $src, $alt );
        }
        return false;
    }

    /**
     *
     * Retrieves images of a given post
     *
     * @param array $args Arguments to set the posts to be retrieved
     * @return array $posts Post images
     *
     */
    public static function get_images( $args=array() )
    {
        $defaults = array(
            'post_type'         => 'attachment',
            'post_mime_type'    => 'image',
            'post_status'       => 'any',
            'numberposts'       => -1
        );
        $args = wp_parse_args( $args, $defaults );
        return get_posts( $args );
    }

    /**
     *
     * Displays images retrieved get_posts
     * he format of return of images is within tags of items and HTML links
     * Estructure: <li><a href="" title=""><img src="" alt="" /></a></li>
     *
     * @param array $images Images Posts
     * $param string|array Name or dimensions of the image size to be recovered
     * @return string HTML markup for display of on screen images
     *
     */
    public static function list_images( $images, $size='gallery', $rel=null )
    {
        if ( !is_array( $images ) || !count( $images ) )
            return false;

        $html = '';
        foreach ( $images as $image ) {
            if ( isset( $image->ID ) && isset( $image->post_title ) ) {
                list( $src, $alt ) = self::get_image_data( $image, $size );
                $html .= sprintf(
                    '<li><a%s href="%s" title="%s"><img alt="%s" src="%s" /></a></li>',
                    ( is_string( $rel ) ) ? ' rel="' . $rel . '"' : '',
                    wp_get_attachment_url( $image->ID ),
                    $image->post_title,
                    $alt,
                    $src
                );
            }
        }
        return $html;
    }

    // }}}


    // {{{ Page Template

    /**
     *
     * Retrieves clean name of template file
     * Use template pages with name pg-{nome}.php
     *
     * @return string Name of the page template
     *
     */
    public static function get_template()
    {
        if ( !is_page() )
            return false;

        return str_replace(
            array( '/' . 'pg-', '.php', TEMPLATEPATH ),
            '',
            get_page_template()
        );
    }

    /**
     *
     * Gets the page link with the assigned template
     *
     * @global object $wpdb
     * @param string $template Name of the template file without prefix or termination
     * @return string Address anchor of that page or reference to the top of the current page
     *
     */

    public static function get_template_permalink( $template='' )
    {
        $url = '#';
        if ( $template ) {
            global $wpdb;
            $page_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} " .
                    "WHERE meta_key='_wp_page_template' AND meta_value=%s " .
                    "ORDER BY meta_id DESC LIMIT 1",
                    $template
                )
            );
            if ( $page_id )
                $url = get_permalink( $page_id );
        }
        return $url;
    }

    /**
     *
     * Retrieves the object from Post based on past model as parameter
     *
     * @param string $template Name of the template file without prefix or termination
     * @return object|boolean $post Info page or false if no page found
     *
     */
    public static function get_template_page( $template, $slug=null )
    {
        $pages = get_posts(
            array(
                'post_type'		=> 'page',
                'posts_per_page'=> -1,
                'meta_key'		=> '_wp_page_template',
                'meta_value'	=> $template
            )
        );

        $results = count( $pages );
        if ( is_array( $pages ) && $results ) {
            if ( ( $results > 1 ) && $slug )
                return get_page_by_path( $slug );
            else
                return array_shift( $pages );
        }
        return false;
    }

    /**
     *
     * Retrieves name of the template file with the ID of the page
     *
     * @return int page ID
     *
     */
    public static function get_template_by_id( $id )
    {
        $id = (int) $id;
        return get_post_meta( $id, '_wp_page_template', true );
    }

    // }}}

    // {{{ Browsing

    /**
     *
     * Paging results in a custom mode.
     * If there are additional pages, nothing is displayed.
     *
     * $args = array(
     *  'wp_query'             => $wp_query
     *  'first_and_last'       => boolean
     *  'first_and_last_class' => string
     *  'first_label'          => string
     *  'last_label'           => string
     *  'container'            => 'nav'
     *  'stats'                => 'Page 1 of 3'
     * );
     *
     * @param array $args Settings paging of results
     *
     */
    public static function paginate( $args=array() )
    {
        global $wp_query;
        $defaults = array(
            'wp_query'              => $wp_query,
            'first_and_last'        => false,
            'first_and_last_class'  => '',
            'first_label'           => __r( 'First' ),
            'last_label'            => __r( 'Last' ),
            'container'             => 'nav',
            'container_class'       => 'pagination',
            'stats'                 => __r( 'Page %d of %d:' )
        );
        $args = wp_parse_args( $args, $defaults );

        $page_tot = $args[ 'wp_query' ]->max_num_pages;
        $page_cur = get_query_var( 'paged' );
        $posts = (int) $args[ 'wp_query' ]->found_posts;

        if ( ( $page_tot == 1 ) || !$posts )
            return false;

        if ( !$page_cur )
            $page_cur = 1;

        $html  = sprintf( '<%s class="%s">', $args[ 'container' ], $args[ 'container_class' ] );
        $html .= sprintf( $args[ 'stats' ], $page_cur, $page_tot ) . ' ';

        if ( $args[ 'first_and_last' ] && ( $page_cur > 1 ) ) {
            $html .= sprintf(
                '<a href="%1$s" title="%2$s"%3$s>%2$s</a>',
                get_pagenum_link(),
                $args[ 'first_label' ],
                ( $args[ 'first_and_last_class' ] ) ? ' class="' . $args[ 'first_and_last_class' ] . '"' : ''
            );
        }

        $links = paginate_links(
            array(
                'base'      => str_replace( $page_tot, '%#%', get_pagenum_link( $page_tot ) ),
                'format'    => '?paged=%#%',
                'current'   => max( 1, $page_cur ),
                'total'     => $page_tot,
                'prev_next' => false,
                'end_size'  => 1,
                'mid_size'  => 2
            )
        );

        // If you want to replace the link first and last on the list
        // $links = str_replace( '>1</a>', ' title="Primeira">Primeira</a>', $links );
        // $links = str_replace( ">{$page_tot}</a>", ' title="Última">Última</a>', $links );

        $html .= $links;

        if ( $args[ 'first_and_last' ] && ( $page_cur < $page_tot ) ) {
            $html .= sprintf(
                '<a href="%1$s" title="%2$s"%3$s>%2$s</a>',
                get_pagenum_link( $page_tot ),
                $args[ 'last_label' ],
                ( $args[ 'first_and_last_class' ] ) ? ' class="' . $args[ 'first_and_last_class' ] . '"' : ''
            );
        }

        $html .= sprintf( '</%s>', $args[ 'container' ] );
        echo $html;
    }

    /**
     *
     * Returns the breadcrumbs, site browsing paths
     *
     * @global integer $page Some of the content of the post
     * @global integer $paged Current page of results
     * @global object $post Post object WordPress
     *
     */
    public static function breadcrumbs( $args=array() )
    {
        self::$path = array();

        $defaults = array(
            'here'      => __r( 'You are here:' ),
            'home'      => __r( 'Home Page' ),
            'sep'       => ' > ',
            'part'      => __r( 'Part %d' ),
            'page'      => __r( 'Page %d' ),
            'search'    => __r( 'Search results' ),
            'pg404'     => __r( 'Invalid page' ),
            'archive'   => __r( 'Archive' ),
            'container' => 'nav',
            'class'     => 'breadcrumbs'
        );
        $l = wp_parse_args( $args, $defaults ); // labels

        if ( is_home() || is_front_page() ) {
            array_push( self::$path, $l[ 'home' ] );
        } else {
            array_push(
                self::$path,
                array(
                    'url'   => ROOT_URL,
                    'title' => SITE_NAME,
                    'label' => $l[ 'home' ]
                )
            );

            global $page, $paged;
            if ( is_singular() ) {
                global $post;
                $type = get_post_type_object( $post->post_type );
                if ( $post->post_type == 'post' ) {
                    self::breadcrumb_parents( 'post', $post->ID );
                } else if ( in_array( $post->post_type, array( 'page', 'attachment' ) ) ) {
                    $parent_id = $post->post_parent;
                    if ( $parent_id > 0 )
                        self::breadcrumb_parents( get_post_type( $parent_id ), $post->post_parent );
                } else if ( $type->has_archive ) {
                    array_push(
                        self::$path,
                        array(
                            'url'   => get_post_type_archive_link( $post->post_type ),
                            'label' => $type->label,
                            'type'  => 'post_type_single',
                            'object'=> $type
                        )
                    );
                } else {
                    $path = apply_filters( 'breadcrumbs_singular', null, $post, $type );
                    if ( is_array( $path ) ) {
                        foreach ( $path as $p )
                            array_push( self::$path, $p );
                    }
                }

                if ( $page > 1 ) {
                    array_push(
                        self::$path,
                        array(
                            'url'   => get_permalink( $post->ID ),
                            'label' => $post->post_title,
                            'type'  => 'single',
                            'object'=> $post
                        ),
                        sprintf( $l[ 'part' ], $page )
                    );
                } else {
                    array_push( self::$path, $post->post_title );
                }
            } else if ( is_search() ) {
                array_push( self::$path, $l[ 'search' ] );
            } else if ( is_404() ) {
                array_push( self::$path, $l[ 'pg404' ] );
            } else {
                $q = get_queried_object();
                if ( $q ) {
                    if ( is_post_type_archive() ) {
                        if ( $paged > 1 ) {
                            array_push(
                                self::$path,
                                array(
                                    'url'	=> get_post_type_archive_link( $q->name ),
                                    'label' => $q->labels->name,
                                    'type'  => 'post_type_archive',
                                    'object'=> $q
                                ),
                                sprintf( $l[ 'page' ], $paged )
                            );
                        } else {
                            array_push(
                                self::$path,
                                $q->labels->name
                            );
                        }
                    } else {
                        if ( isset( $q->parent ) ) {
                            $term_parent = (int) $q->parent;
                            if ( $term_parent ) {
                                do {
                                    $item = get_term( $term_parent, $q->taxonomy );
                                    array_push(
                                        self::$path,
                                        array(
                                            'url'   => get_term_link( $item->slug, $item->taxonomy ),
                                            'label' => $item->name,
                                            'type'  => 'term',
                                            'object'=> $item
                                        )
                                    );
                                    $term_parent = (int) $item->parent;
                                } while ( $term_parent > 0 );
                            }
                        }

                        $tax = get_taxonomy( $q->taxonomy );
                        if ( $paged > 1 ) {
                            array_push(
                                self::$path,
                                array(
                                    'url'	=> get_term_link( $q->slug, $q->taxonomy ),
                                    'label' => $tax->labels->singular_name . ': ' . $q->name,
                                    'type'  => 'term',
                                    'object'=> $q
                                ),
                                sprintf( $l[ 'page' ], $paged )
                            );
                        } else {
                            array_push(
                                self::$path,
                                $tax->labels->singular_name . ': ' . $q->name
                            );
                        }
                    }
                } else {
                    array_push(
                        self::$path,
                        'Arquivo'
                    );
                }
            }
        }

        $path = apply_filters( 'breadcrumbs_path', self::$path );
        if ( is_array( $path ) ) {
            printf(
                '<%s%s>%s ',
                $l[ 'container' ],
                ( $l[ 'class' ] ) ? ' class="' . $l[ 'class' ] . '"' : '',
                $l[ 'here' ]
            );
            foreach ( $path as $p ) {
                if ( is_array( $p ) ) {
                    if ( !isset( $p[ 'title' ] ) )
                        $p[ 'title' ] = $p[ 'label' ];

                    printf(
                        '<a href="%s" title="%s">%s</a>%s',
                        $p[ 'url' ],
                        $p[ 'title' ],
                        $p[ 'label' ],
                        $l[ 'sep' ]
                    );
                } else {
                    echo $p;
                }
            }
            printf( '</%s>', $l[ 'container' ] );
        }
    }

    /**
     *
     * Helper function that retrieves all of the above links in the browsing hierarchy
     *
     * @param string $type Post type
     * @param int $post_id Post ID of WordPress
     *
     */
    private static function breadcrumb_parents( $type, $post_id )
    {
        $path = array();
        switch ( $type )
        {
            case 'post':
                $cats = wp_get_post_categories( $post_id );
                if ( is_array( $cats ) ) {
                    // only one category
                    $cat_id = array_shift( $cats );
                    while ( $cat_id > 0 ) {
                        $cat = get_term( $cat_id, 'category' );
                        array_push(
                            $path,
                            array(
                                'url'   => get_category_link( $cat_id ),
                                'label' => $cat->name,
                                'type'  => 'category',
                                'object'=> $cat
                            )
                        );
                        $cat_id = (int) $cat->parent;
                    }
                }
                break;
            case 'page':
                $parent_id = $post_id;
                while ( $parent_id > 0 ) {
                    $p = get_post( $parent_id );
                    array_push(
                        $path,
                        array(
                            'url'   => get_permalink( $parent_id ),
                            'label' => $p->post_title,
                            'type'  => 'page',
                            'object'=> $p
                        )
                    );
                    $parent_id = $p->post_parent;
                }
                break;
        }
        self::$path = array_merge( self::$path, array_reverse( $path ) );
    }


    // }}}

    /**
     *
     * Retrieves the summary of the post or else a content summary
     *
     * @param object $post
     * @param integer $excerpt_size Size abstract in number of words
     * @param string $more_symbol Symbol to represent the continuity of the text
     * @return string $excerpt Overview formatted
     *
     */
    public static function get_the_excerpt( $post=null, $excerpt_size=30, $more_symbol='...' )
    {
        if ( !is_object( $post ) )
            global $post;

        /*
        if ( post_password_required() )
            return __( 'There is no excerpt because this is a protected post.' ); */

        $excerpt = $post->post_excerpt;
        if ( !$excerpt ) {
            $excerpt = wp_trim_words(
                strip_shortcodes( strip_tags( $post->post_content ) ),
                $excerpt_size,
                $more_symbol
            );
        }

        return apply_filters( 'get_the_excerpt', $excerpt );
    }

}

?>