<?php 

/*Template Name: Inicial*/

get_header(); ?>
<div id="banner">
<?php if ( function_exists( 'meteor_slideshow' ) ) { meteor_slideshow(); } ?>
</div>
<div id="destaques">
	<div class="topo-destaques"></div>
	<div class="box_destaques">
      <div><a href="#" class="prev"><img src="<?php echo get_template_directory_uri(); ?>/img/prev_carrossel.png" border="0" /></a></div> 	
      <div id="carrossel" style="float:left">
            <ul>
               
            <?php
               wp_reset_query();
               query_posts('post_type=produtos&posts_per_page=9&tipo_carro=lancamentos');
               if(have_posts()):while(have_posts()):the_post();
            ?>
               <li><img src="<?php the_field('foto_do_produto'); ?>" width="210" height="133" alt="Foto 1" /><span><?php the_title(''); ?></span><p>Código : <b><?php the_field('codigo_do_produto'); ?></b></p></li>
            <?php
               endwhile;endif;
            ?>            

            </ul>
      </div>
      <div><a href="#" class="next"><img src="<?php echo get_template_directory_uri(); ?>/img/prox_carrossel.png" border="0" /></a></div>
	</div>
</div>
<?php get_footer(); ?>