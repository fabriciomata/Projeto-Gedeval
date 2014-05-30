<?php

require_once TEMPLATEPATH . '/lib/root.php';

add_action( 'root_setup', 'custom_setup' );

function custom_setup()
{


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