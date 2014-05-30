<?php 

/*Template Name: Produtos*/

get_header(); ?>

<div id="box_principal">
	<div class="topo-box">
		<div class="letra">P</div>
		<div class="titulo-topo">Produtos</div>
		<div class="buscar"></div>
	</div>
	<div class="conteudo-box">
		<div class="menu-categorias">
			<div class="titulo-empresa">Categorias</div>
				<ul>
					<?php wp_list_categories('title_li=&taxonomy=tipo_carro'); ?>
				</ul>
		</div>
		<div class="lista-produtos">
			<div class="titulo-empresa">Produtos > Ford</div>
			<div class="lista-categorias">
				
				<?php
					wp_reset_query();
					query_posts('post_type=produtos&posts_per_page=9');
					if(have_posts()):while(have_posts()):the_post();
				?>

				<div class="produto">
					<div class="foto-produto"><img src="<?php the_field('foto_do_produto'); ?>" /></div>
					<div class="titulo-produto"><?php the_title(''); ?></div>
					<div class="descricao-produto"><p>Código : <?php the_field('codigo_do_produto'); ?></p>
												   <p><?php the_field('descricao_produto'); ?></p></div>
					<div class="manual-produto"><a target="_blank" href="<?php the_field('manual_do_produto'); ?>">Manual de Montagem</a><img src="<?php echo get_template_directory_uri(); ?>/img/seta_manual.png" /></div>
				</div>

								<?php
					endwhile;endif;
				?>  
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>