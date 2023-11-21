<?php

function vb_filter_posts_mt() {

if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'bobz' ) )
    die('Permission denied');

/**
 * Default response
 */
$response = [
    'status'  => 500,
    'message' => '다시 시도해주세요.',
    'content' => false,
    'found'   => 0
];


$all     = false;
$terms   = $_POST['params']['terms'];
$page    = intval($_POST['params']['page']);
$qty     = intval($_POST['params']['qty']);
$pager   = isset($_POST['pager']) ? $_POST['pager'] : 'pager';
$tax_qry = [];
$msg     = '';

/**
 * Check if term exists
 */
if (!is_array($terms)) :
    $response = [
        'status'  => 501,
        'message' => '',
        'content' => 0
    ];

    die(json_encode($response));
else :

    foreach ($terms as $tax => $slugs) :

        if (in_array('all-terms', $slugs)) {
            $all = true;
        }

        $tax_qry[] = [
            'taxonomy' => $tax,
            'field'    => 'slug',
            'terms'    => $slugs,
            'operator' => 'AND'
        ];
    endforeach;
endif;

/**
 * Setup query
 */
$args = [
    'paged'          => $page,
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => $qty,
];

if ($tax_qry && !$all) :
    $args['tax_query'] = $tax_qry;
endif;

$qry = new WP_Query($args);

ob_start();
    if ($qry->have_posts()) :
        while ($qry->have_posts()) : $qry->the_post(); ?>

<article class="loop-item">

<div class="item-wrap">

    <a href="<?php the_permalink(); ?>">
        <div class="title-overlay">
            <h2 class="entry-title"><?php the_title(); ?></h2>
        </div>
        <?php
        if(has_post_thumbnail()) :
            the_post_thumbnail('custom');
        else : ?>
        <img class="not-found" src="https://nudgecomms7.cafe24.com/wp-content/uploads/2022/07/image-not-found.png"
            alt="">

        <?php endif;
        ?>
        <div class="tag-list">


            <?php 
        $tags = get_the_tags();

        foreach( $tags as $tag) { ?>

            <span class="tag-item"><?php echo $tag->name ?></span>

            <?php }

        ?>
        </div>

    </a>
</div>

<div class="entry-summary">

</div>
</article>

<?php endwhile;

        /**
         * Pagination
         */
        
        if ( $pager == 'pager' )
            vb_mt_ajax_pager($qry,$page);


        foreach ($tax_qry as $tax) :
            $msg .= '<div class="selected-tag-list">';

            foreach ($tax['terms'] as $trm) :
                $msg .= '<span class="selected-tag-item">'. $trm . '</span>';
            endforeach;
            $msg = trim($msg, ', ');
            $msg .= '</div>';
            //$msg .= ' from taxonomy: ' . $tax['taxonomy'];
            $msg .= '<span class="result-count">검색 결과: '.$qry->found_posts.'</span>';
        endforeach;

        $response = [
            'status'  => 200,
            'found'   => $qry->found_posts,
            'message' => $msg,
            'method'  => $pager,
            'next'    => $page + 1
        ];

        
    else :

        $response = [
            'status'  => 201,
            'message' => '검색결과가 없습니다.',
            'next'    => 0
        ];

    endif;

$response['content'] = ob_get_clean();

die(json_encode($response));

}
add_action('wp_ajax_do_filter_posts_mt', 'vb_filter_posts_mt');
add_action('wp_ajax_nopriv_do_filter_posts_mt', 'vb_filter_posts_mt');


/**
* Shortocde for displaying terms filter and results on page
*/

function vb_filter_posts_mt_sc($atts) {

$a = shortcode_atts( array(
    'tax'      => 'post_tag', // Taxonomy
    'terms'    => false, // Get specific taxonomy terms only
    'active'   => false, // Set active term by ID
    'per_page' => 12, // How many posts per page,
    'pager'    => 'pager' // 'pager' to use numbered pagination || 'infscr' to use infinite scroll
), $atts );

$result = NULL;
$terms  = get_terms($a['tax']);
$tags = get_tags_in_use(49);


if (count($terms)) :
    ob_start(); ?>
<div id="container-async" data-paged="<?= $a['per_page']; ?>" class="sc-ajax-filter sc-ajax-filter-multi">
<ul class="nav-filter">


    <?php foreach ($terms as $term) : ?>
    <?php if(in_array($term->name, $tags)) {?>

    <li <?php if ($term->term_id == $a['active']) :?> class="active" <?php endif; ?>>
        <a href="<?= get_term_link( $term, $term->taxonomy ); ?>" data-filter="<?= $term->taxonomy; ?>"
            data-term="<?= $term->name; ?>" data-page="1">
            <?= $term->name; ?>
        </a>
    </li>
    <?php } ?>
    <?php endforeach; ?>
</ul>

<div class="status"></div>
<div class="loading-spinner"></div>
<div class="content"></div>

<?php if ( $a['pager'] == 'infscr' ) : ?>
<nav class="pagination infscr-pager">
    <a href="#page-2" class="btn btn-primary">Load More</a>
</nav>
<?php endif; ?>


</div>

<?php $result = ob_get_clean();
endif;

return $result;
}
add_shortcode( 'ajax_filter_posts_mt', 'vb_filter_posts_mt_sc');


/**
* Pagination
*/
function vb_mt_ajax_pager( $query = null, $paged = 1 ) {

if (!$query)
    return;

$paginate = paginate_links([
    'base'      => '%_%',
    'type'      => 'array',
    'total'     => $query->max_num_pages,
    'format'    => '#page=%#%',
    'current'   => max( 1, $paged ),
    'prev_text' => '이전',
    'next_text' => '다음'
]);

if ($query->max_num_pages > 1) : ?>
<ul class="pagination">
<?php foreach ( $paginate as $page ) :?>
<li><?php echo $page; ?></li>
<?php endforeach; ?>
</ul>
<?php endif;
}


/*get tags in specific categry */

function get_tags_in_use($category_ID, $type = 'name'){
// Set up the query for our posts
$my_posts = new WP_Query(array(
  'cat' => $category_ID, // Your category id
  'posts_per_page' => -1 // All posts from that category
));

// Initialize our tag arrays
$tags_by_id = array();
$tags_by_name = array();
$tags_by_slug = array();

// If there are posts in this category, loop through them
if ($my_posts->have_posts()): while ($my_posts->have_posts()): $my_posts->the_post();

  // Get all tags of current post
  $post_tags = wp_get_post_tags($my_posts->post->ID);

  // Loop through each tag
  foreach ($post_tags as $tag):

    // Set up our tags by id, name, and/or slug
    $tag_id = $tag->term_id;
    $tag_name = $tag->name;
    $tag_slug = $tag->slug;

    // Push each tag into our main array if not already in it
    if (!in_array($tag_id, $tags_by_id))
      array_push($tags_by_id, $tag_id);

    if (!in_array($tag_name, $tags_by_name))
      array_push($tags_by_name, $tag_name);

    if (!in_array($tag_slug, $tags_by_slug))
      array_push($tags_by_slug, $tag_slug);

  endforeach;
endwhile; 
wp_reset_postdata();
endif;

// Return value specified
if ($type == 'id')
    return $tags_by_id;

if ($type == 'name')
    return $tags_by_name;

if ($type == 'slug')
    return $tags_by_slug;
}



function assets() {

wp_enqueue_script('post-ajax/js', get_stylesheet_directory_uri(). '/scripts/post-ajax.js', array('jquery'), null, true);

wp_localize_script( 'post-ajax/js', 'bobz', array(
    'nonce'    => wp_create_nonce( 'bobz' ),
    'ajax_url' => admin_url( 'admin-ajax.php' )
));
}
add_action('wp_enqueue_scripts', 'assets', 100);