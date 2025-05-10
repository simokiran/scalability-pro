<?php
/* If the constructor hash changes from the parent or the grandparent then the code below needs to be updated 
	to include the code from the grandparent and the parent constructor which should still use our cache */
// caching of WP_Posts_List_Table with class replacement
class WP_SPRO_Posts_List_Table extends WP_Posts_List_Table{
    
    private $user_posts_count;
    private $sticky_posts_count = 0;
    
    public function __construct( $args = array() ) {
		global $post_type_object, $wpdb;
       
		/*
		parent::__construct(
			array(
				'plural' => 'posts',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
		*/
		// This is the original constructor call, but we need to call the GRANDparent constructor with the original WP_Posts_List_Table class.
		// so we just copy the code here and then use our hash check to confirm if the parent constructor or grandparent constructor changed
		$args = wp_parse_args(
			$args,
			array(
				'plural'   => '',
				'singular' => '',
				'ajax'     => false,
				'screen'   => null,
			)
		);

		$this->screen = convert_to_screen( $args['screen'] );

		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

		if ( ! $args['plural'] ) {
			$args['plural'] = $this->screen->base;
		}

		$args['plural']   = sanitize_key( $args['plural'] );
		$args['singular'] = sanitize_key( $args['singular'] );

		$this->_args = $args;

		if ( $args['ajax'] ) {
			// wp_enqueue_script( 'list-table' );
			add_action( 'admin_footer', array( $this, '_js_vars' ) );
		}

		if ( empty( $this->modes ) ) {
			$this->modes = array(
				'list'    => __( 'Compact view' ),
				'excerpt' => __( 'Extended view' ),
			);
		}

		$post_type        = $this->screen->post_type;
		$post_type_object = get_post_type_object( $post_type );

		$exclude_states = get_post_stati(
			array(
				'show_in_admin_all_list' => false,
			)
		);

        $exclude_states_serialized = serialize($exclude_states);
        // Concatenate the parameters
        $key_string = $post_type . '|' . get_current_user_id() . '|' . $exclude_states_serialized;
        // Generate an md5 hash of the concatenated string
        $cache_key = md5($key_string);

        $cached_post_count = spro_get_transient( 'user_posts_count', $cache_key );
       
        if( !$cached_post_count ){
            $cached_post_count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT( 1 )
                    FROM $wpdb->posts
                    WHERE post_type = %s
                    AND post_status NOT IN ( '" . implode( "','", $exclude_states ) . "' )
                    AND post_author = %d",
                    $post_type,
                    get_current_user_id()
                )
            );

            spro_set_transient( 'user_posts_count', $cache_key, $cached_post_count, 24*60*60 );
        }

		$this->user_posts_count = $cached_post_count;

		if ( $this->user_posts_count
			&& ! current_user_can( $post_type_object->cap->edit_others_posts )
			&& empty( $_REQUEST['post_status'] ) && empty( $_REQUEST['all_posts'] )
			&& empty( $_REQUEST['author'] ) && empty( $_REQUEST['show_sticky'] )
		) {
			$_GET['author'] = get_current_user_id();
		}

		$sticky_posts = get_option( 'sticky_posts' );
       
		if ( 'post' === $post_type && $sticky_posts ) {
			$sticky_posts = implode( ', ', array_map( 'absint', (array) $sticky_posts ) );


            $key_string = $post_type . '|' . $sticky_posts;
            $cache_key = md5($key_string);
    
            $cached_sticky_posts_count = spro_get_transient( 'user_posts_count', $cache_key );
        
            if( !$cached_sticky_posts_count ){
                $cached_sticky_posts_count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT( 1 )
                        FROM $wpdb->posts
                        WHERE post_type = %s
                        AND post_status NOT IN ('trash', 'auto-draft')
                        AND ID IN ($sticky_posts)",
                        $post_type
                    )
                );
                spro_set_transient( 'user_posts_count', $cache_key, $cached_sticky_posts_count, 24*60*60 );
            }

			$this->sticky_posts_count = $cached_sticky_posts_count;
		}
 
	}

    
    protected function display_tablenav( $which ) {     
		if ( 'top' === $which ) {            
			wp_nonce_field( 'bulk-posts' );
		}
		?> 
	<div class="tablenav custom_head <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
			<?php
		endif;
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

		<br class="clear" />
	</div>
		<?php
	}
}

function spro_posts_list_table_hash_check() {
    $stored_data = get_option('spro_posts_list_table_hash_version', []);

    // Ensure constants are defined or set defaults.
    $current_copy_version = defined('WP_LIST_TABLE_COPY_VERSION') ? WP_LIST_TABLE_COPY_VERSION : '1.0';

    // Conditionally include your custom class based on version difference or unset hash.
    if (!isset($stored_data['known_constructor_hash'], $stored_data['copy_version']) || 
        $stored_data['copy_version'] !== $current_copy_version) {
        
        include_once plugin_dir_path( __FILE__ ) . 'class-wp-posts-list-table-copy.php';
        include_once plugin_dir_path( __FILE__ ) . 'class-wp-list-table-copy.php';

        // Compute the hash of WP_Posts_List_Table_Copy constructor with adjustments.
        $postsListTableReflector = new ReflectionMethod('WP_Posts_List_Table_Copy', '__construct');
        $postsListTableConstructor = implode("", file($postsListTableReflector->getFileName(), FILE_IGNORE_NEW_LINES));
        $postsListTableConstructor = str_replace('WP_Posts_List_Table_Copy', 'WP_Posts_List_Table', $postsListTableConstructor);
        $postsListTableHash = md5($postsListTableConstructor);

        // Compute the hash for WP_List_Table_Copy constructor.
        $listTableReflector = new ReflectionMethod('WP_List_Table_Copy', '__construct');
        $listTableConstructor = implode("", file($listTableReflector->getFileName(), FILE_IGNORE_NEW_LINES));
        $listTableConstructor = str_replace('WP_List_Table_Copy', 'WP_List_Table', $listTableConstructor);
        $listTableHash = md5($listTableConstructor);

        // Update stored data.
        $stored_data['known_constructor_hash'] = $postsListTableHash;
        $stored_data['known_grandparent_hash'] = $listTableHash; // Store the new grandparent hash.
        $stored_data['copy_version'] = $current_copy_version;
    }

    $stored_data['wp_version'] = get_bloginfo('version'); // Always update to the current WP version.

    // Perform hash checks on actual constructors.
    $postsListTableCurrentHash = md5(implode("", file((new ReflectionMethod('WP_Posts_List_Table', '__construct'))->getFileName(), FILE_IGNORE_NEW_LINES)));
    $listTableCurrentHash = md5(implode("", file((new ReflectionMethod('WP_List_Table', '__construct'))->getFileName(), FILE_IGNORE_NEW_LINES)));

    // Compare and set hash check status for both parent and grandparent.
    $stored_data['hash_check_passed'] = ($postsListTableCurrentHash === $stored_data['known_constructor_hash']) && 
                                        ($listTableCurrentHash === $stored_data['known_grandparent_hash']);

    // Update the option with potentially updated data.
    update_option('spro_posts_list_table_hash_version', $stored_data);

    return $stored_data['hash_check_passed'];
}

function spro_check_wp_version_and_hash() {
    $stored_data = get_option('spro_posts_list_table_hash_version', []);
    $current_wp_version = get_bloginfo('version');

    // Force hash check if WP version changes or on initial setup.
    if (!isset($stored_data['wp_version']) || $stored_data['wp_version'] !== $current_wp_version) {
        $hash_check_passed = spro_posts_list_table_hash_check();
		return $hash_check_passed;
    }
	return $stored_data['hash_check_passed'];
}


add_action('admin_init', 'spro_check_wp_version_and_hash');
