<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo('name'); ?></title>
	<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" />
	<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/reset.css" />
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<!--- - - - - - - Cabecalho - - - - - - - - - -->
	<div id="topo">
		<div id="logo"></div>
		<div id="menu">
			<nav>

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

