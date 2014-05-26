<?php 

/*Template Name: Contato*/

get_header(); ?>

<div id="box_principal">
	<div class="topo-box">
		<div class="letra">C</div>
		<div class="titulo-topo">Contato</div>
	</div>
	<div class="conteudo-box">
		<div class="formulario">
			<div class="titulo-empresa">Fale Conosco</div>
			<div style="margin-top:20px"><?php echo do_shortcode('[contact-form-7 id="20" title="Contato"]'); ?></div>
		</div>
		<div class="maps">
			<div class="titulo-empresa">Localização</div>
			<div class="google-mapa">
				<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3490.52014970968!2d-51.06984543525067!3d-28.97195383569613!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x951e93b12dc64b43%3A0xb35d4ddce837b84a!2sRua+Padre+Feij%C3%B3%2C+780!5e0!3m2!1spt-BR!2sbr!4v1401046204122" width="518" height="398" frameborder="0" style="border:0"></iframe>
			</div>
			<div class="exibir-mapa"><a target="_blank" href="https://www.google.com.br/maps/place/Rua+Padre+Feij%C3%B3,+780/@-28.9719538,-51.0698454,17z/data=!4m2!3m1!1s0x951e93b12dc64b43:0xb35d4ddce837b84a">EXIBIR MAPA AMPLIADO<a/></div>
			<div class="endereco">
				<span>Endereço:</span>
				<p>Rua Padre Feijó, 780A</p>
				<p>CEP: 95190-000</p> 
				<p>São Marcos - RS</p>
				<span>Fones:</span> 
				<p>(54) 3291.1911</p> 
				<p>(54) 3291.1671</p>
				<p>(54) 3291 2510</p>
				<span>Email:</span>
				<p>gedeval@brturbo.com.br</p> 
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>