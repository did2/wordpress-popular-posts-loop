<?php
/*
Plugin Name: Loop Wordpress Popular Posts
Plugin URI: http://did2memo.net/
Description: You can place popular posts ranking "loop" anywhere on your template using  lwpp_popular_posts_ids().
Version: 1.0.0
Author: did2
Author URI: http://did2memo.net/
License: GPL2
*/

/**
 * Get post id list of popular posts.
 * 
 * @param array $atts see sample template files.
 *
 */
function lwpp_get_popular_posts_ids($atts) {	
	global $wpdb;

	/* begin from wpp_shortcode */
	extract( shortcode_atts( array(
		'header' => '',
		'limit' => 10,
		'range' => 'daily',
		'order_by' => 'comments',
		'pages' => true,
		'title_length' => 0,
		'cat' => '',
		'category__in' => '',
		'cats_to_exclude' => '',
		'rating' => false
	), $atts ) );
	
	// possible values for "Time Range" and "Order by"
	$range_values = array("yesterday", "daily", "weekly", "monthly", "all");
	$order_by_values = array("comments", "views", "avg");
	$thumbnail_selector = array("wppgenerated", "usergenerated");
	
	$shortcode_ops = array(
		'title' => strip_tags($header),
		'limit' => empty($limit) ? 10 : (is_numeric($limit)) ? (($limit > 0) ? $limit : 10) : 10,
		'range' => (in_array($range, $range_values)) ? $range : 'daily',
		'order_by' => (in_array($order_by, $order_by_values)) ? $order_by : 'comments',
		'pages' => empty($pages) || $pages == "false" ? false : true,
		'shorten_title' => array(
			'active' => empty($title_length) ? false : (is_numeric($title_length)) ? (($title_length > 0) ? true : false) : false,
			'length' => empty($title_length) ? 0 : (is_numeric($title_length)) ? $title_length : 0 
		),
		'cat' => array(
			'active' => empty($cat) ? false : true,
			'cats' => empty($cat) ? '' : $cat
		),
		'category__in' => array(
			'active' => empty($category__in) ? false : (ctype_digit(str_replace(",", "", $category__in))) ? true : false,
			'cats' => empty($category__in) ? '' : (ctype_digit(str_replace(",", "", $category__in))) ? $category__in : ''
		),
		'exclude-cats' => array(
			'active' => empty($cats_to_exclude) ? false : (ctype_digit(str_replace(",", "", $cats_to_exclude))) ? true : false,
			'cats' => empty($cats_to_exclude) ? '' : (ctype_digit(str_replace(",", "", $cats_to_exclude))) ? $cats_to_exclude : ''
		),
		'rating' => empty($rating) || $rating = "false" ? false : true,
		'stats_tag' => array(
			'comment_count' => empty($stats_comments) ? false : $stats_comments,
			'views' => empty($stats_views) ? false : $stats_views,
			'author' => empty($stats_author) ? false : $stats_author,
			'date' => array(
				'active' => empty($stats_date) ? false : $stats_date,
				'format' => empty($stats_date_format) ? 'F j, Y' : $stats_date_format
			)
		)
	);

	$instance = $shortcode_ops;

	$table = $wpdb->prefix . "popularpostsdata";
				
	if ( $instance['pages'] ) {
		$nopages = '';
	} else {
		$nopages = "AND $wpdb->posts.post_type = 'post'";
	}

	switch( $instance['range'] ) {
		case 'all':
			$range = "post_date_gmt < '".gmdate("Y-m-d H:i:s")."'";
			break;
		case 'yesterday':
			$range = $table."cache.day >= '".gmdate("Y-m-d")."' - INTERVAL 1 DAY";
			break;
		case 'daily':
			//$range = $table."cache.day = ".$this->curdate();
			//$range = $table."cache.day >= '".$this->now()."' - INTERVAL 1 DAY";
			$range = $table."cache.day >= '".gmdate("Y-m-d")."'";
			break;
		case 'weekly':
			$range = $table."cache.day >= '".gmdate("Y-m-d")."' - INTERVAL 7 DAY";
			break;
		case 'monthly':
			$range = $table."cache.day >= '".gmdate("Y-m-d")."' - INTERVAL 30 DAY";
			break;
		default:
			$range = "post_date_gmt < '".gmdate("Y-m-d H:i:s")."'";
			break;
	}

	// sorting options
	switch( $instance['order_by'] ) {
		case 'comments':
			$sortby = 'comment_count';
			break;
		case 'views':
			$sortby = 'pageviews';
			break;
		case 'avg':
			$sortby = 'avg_views';
			break;
		default:
			$sortby = 'comment_count';
			break;
	}
	
	$force_pv = "";

	if ( $instance['range'] == 'all') {
		$join = "LEFT JOIN $table ON $wpdb->posts.ID = $table.postid";
		$force_pv = "AND ".$table.".pageviews > 0 ";
	} else {
		$join = "RIGHT JOIN ".$table."cache ON $wpdb->posts.ID = ".$table."cache.id";				
	}

	// Merge cat from query.php (WordPress 3.5, l. 1734 //Category stuff)
	if ( $instance['cat']['active']) {
					// $instance['cat']['cats'] = ''.urldecode($instance['cat']['cats']).'';
					// $instance['cat']['cats'] = addslashes_gpc($instance['cat']['cats']);
		$cat_array = preg_split('/[,\s]+/', $instance['cat']['cats']);
					$instance['cat']['cats'] = '';
					$req_cats = array();
					foreach ( (array) $cat_array as $cat ) {
							$cat = intval($cat);
							$req_cats[] = $cat;
							$in = ($cat > 0);
							$cat = abs($cat);
							if ( $in ) {
				if ( ! $instance['category__in']['active'] ) {
					$instance['category__in']['active'] = true;
					$instance['category__in']['cats'] = $cat;
				} else {
										$instance['category__in']['cats'] .= ", " . $cat;
				}

				$children = get_term_children($cat, 'category');
				if ( ! empty( $children ) ) {
										$instance['category__in']['cats'] .= ", " . join( ", " , $children );
				}
							} else {
				if ( ! $instance['exclude-cats']['active'] ) {
					$instance['exclude-cats']['active'] = true;
					$instance['exclude-cats']['cats'] = $cat;
				} else {
										$instance['exclude-cats']['cats'] .= ", " . $cat;
				}
				$children = get_term_children($cat, 'category');
				if ( ! empty( $children ) ) {
										$instance['exclude-cats']['cats'] .= ", " . join( ", " , $children );
				}
							}
					}
					$instance['cat']['cats'] = implode(',', $req_cats);
			}

	// Category excluding snippet suggested by user almergabor at http://wordpress.org/support/topic/plugin-wordpress-popular-posts-exclude-and-include-categories?replies=2#post-2464701
	// Thanks, almergabor!
	if ( ( $instance['category__in']['active'] && !empty($instance['category__in']['cats']) )
		|| ( $instance['exclude-cats']['active'] && !empty($instance['exclude-cats']['cats']) ) ) {				
		$category = " AND $wpdb->posts.ID IN (
					SELECT object_id
					FROM $wpdb->term_relationships AS r
						JOIN $wpdb->term_taxonomy AS x ON x.term_taxonomy_id = r.term_taxonomy_id
						JOIN $wpdb->terms AS t ON t.term_id = x.term_id
					WHERE x.taxonomy = 'category'
						AND t.term_id IN(".$instance['category__in']['cats'].")
						AND t.term_id NOT IN(".$instance['exclude-cats']['cats'].")
					) ";

	} else {
		$category = "";
	}

	$mostpopular = $wpdb->get_results("SELECT $wpdb->posts.ID, $wpdb->posts.post_title FROM $wpdb->posts $join WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_password = '' AND $range $force_pv $nopages $exclude $category GROUP BY $wpdb->posts.ID ORDER BY $sortby DESC LIMIT " . $instance['limit'] . "");
	$mostpopular_ids = array();
	foreach( $mostpopular as $wppost ) { $mostpopular_ids[] = $wppost->ID; }
	
	return $mostpopular_ids;
}