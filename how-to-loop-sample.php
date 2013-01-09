<?php
	// wppl_get_popular_posts returns a list of post ids.
	$mostpopular_ids = wppl_get_popular_posts_list( array(
		'range' => 'daily',
		'order_by' => 'views',
		'limit' => 5,
		'cat' => '317, -100',
		// 'category__in' => '317'
		/*
			You can use following parameters and values:
				- range : yesterday, daily, weekly, monthly, all
				- order_by : views, comments, views, avg
				- limit : (int)
					- max length of result.
				- cat : (comma separated int) 
					- set categories (include their children).
				- category__in : (comma separated int)
					- set categories.
				- cat_to_exclude : (comma separated int)
					- set categories to be excluded.
				
			'cat' and 'category__in' are loop-wpp original parameters.
		*/
		) );

	// install filter to posts_orderby in order not to change $mostpopular_ids's order.
	function mostpopular_ids_orderby ( $orderby ) {
		global $mostpopular_ids;
		return 'FIELD(ID, ' . join( ", " , $mostpopular_ids ) . ')';
	}
	add_filter( 'posts_orderby' , 'mostpopular_ids_orderby' );

	// construct new WP_Query
	$args = array( 'post__in' => $mostpopular_ids );
	$mp_query = new WP_Query($args);
	
	// start loop
	while ($mp_query->have_posts () ) : $mp_query->the_post();
?>

<!--
(The Loop)
You can use
	- the_category()
	- the_title()
	- the_tags()
	- the_time()
	- edit_post_link()
	- get_the_ID()
	and
	- wpp_get_views ( get_the_ID() )
		- This php code echo the number of page view of the post, which is counted by WordPress Popular Posts plugin.
-->

<?php	
	endwhile;
	
	// reset global variables.
	wp_reset_query();
?>