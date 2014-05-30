<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo('name'); ?></title>
	<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" />
	<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/reset.css" />
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
   <!-- inclusão do javascript-->
   <script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/jquery-1.6.2.min.js"></script>
   <script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/jcarousellite_1.0.1.min.js"></script>
   <script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/carrossel.js"></script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<!--- - - - - - - Cabecalho - - - - - - - - - -->
	<div id="topo">
		<div id="logo"><img src="<?php echo get_template_directory_uri(); ?>/img/logo.png" /></div>
		<div id="menu">
			<nav>
			
			<?php 
  				wp_nav_menu(
 				array(
  				'menu'              =>    'menu_topo',
  				'theme_location'    =>    'menu_topo',    
  				'menu_id'           =>    'menu',    
  				'echo'              =>    true,    
  				'depht'             =>    0, 
  				'walker'            =>    '', 
  					));
  			?>

				<!--<ul>
					<li><a href="#">Home</a></li>
					<li><a href="#">Produtos</a></li>
					<li><a href="#">Empresa</a></li>
					<li><a href="#">Representantes</a></li>
					<li><a href="#">Contato</a></li>
				</ul>-->
			</nav>
		</div>
	</div>

	<script type="text/javascript">

function EmptyField(id)
{
	var Fid = document.getElementById(id);
	var TheDefaultValue = Fid.defaultValue;
	var TheValue        = Fid.value;
	if(TheDefaultValue == TheValue)
	{
			Fid.value = '';
	}
}
</script>


	<!--- - - - - - - Conteudo - - - - - - - - - -->
	<div id="conteudo">

