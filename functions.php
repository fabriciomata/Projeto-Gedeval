<?php

require_once TEMPLATEPATH . '/lib/root.php';

add_action( 'root_setup', 'custom_setup' );

function custom_setup()
{
	CPT::add(
    'produtos',
    array(
        'singular'  => 'Produto',
        'plural'    => 'Produtos'
    )
);

	Metabox::add( 'itens-produto', 'produtos' );
	Metabox::add_fields(
    'itens-produto',
    array(
        array(
            'name'  => 'nome_produto',
            'label' => 'Nome do Produto',
            'meta'  => 'text'
        ),
        array(
            'name'  => 'descricao_produto',
            'label' => 'Descrição',
            'type'  => 'textarea',
            'meta'  => 'custom_key'
        )
    )
);

}

//Funções do Tema

add_theme_support('post-thumbnails');
add_theme_support('gravatar');

//Tamanhos para thumbs do destaque

add_image_size('thumbdestaque',404,206);

//Habilitando Menus

if (function_exists(register_nav_menu)){
	register_nav_menu('menu_topo','Este é o menu do topo');
	register_nav_menu('menu_sidebar','Este é o menu da sidebar');
	}

?>