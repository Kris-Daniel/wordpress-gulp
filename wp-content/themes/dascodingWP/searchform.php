<form role="search" method="get" class="search_form <?php if($isNotFound) {echo "nf__form";} ?>" action="<?php echo home_url( '/' ); ?>">
	<input type="text" class="search_input" value="<?php echo get_search_query() ?>" name="s" title="<?php echo esc_attr_x( 'Search for:', 'label' ) ?>" placeholder="Поиск по товарам...">
	<input type="submit" class="search_submit" value="<?php if($isNotFound) {echo "Search again";}?>">
</form>