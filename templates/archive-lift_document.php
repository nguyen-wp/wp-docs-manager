<?php
/**
 * Template for displaying documents archive
 * 
 * This template can be overridden by copying it to yourtheme/lift-docs/archive-lift_document.php
 */

get_header(); ?>

<div class="lift-docs-archive">
    <div class="container">
        <header class="page-header">
            <h1 class="page-title">
                <?php
                if (is_tax('lift_doc_category')) {
                    printf(__('Documents in category: %s', 'lift-docs-system'), single_term_title('', false));
                } elseif (is_tax('lift_doc_tag')) {
                    printf(__('Documents tagged: %s', 'lift-docs-system'), single_term_title('', false));
                } else {
                    _e('All Documents', 'lift-docs-system');
                }
                ?>
            </h1>
            
            <?php if (is_tax() && term_description()): ?>
                <div class="taxonomy-description">
                    <?php echo term_description(); ?>
                </div>
            <?php endif; ?>
        </header>
        
        <?php if (LIFT_Docs_Settings::get_setting('enable_search', true)): ?>
            <div class="documents-search">
                <?php echo do_shortcode('[lift_document_search]'); ?>
            </div>
        <?php endif; ?>
        
        <div class="documents-filters">
            <?php if (LIFT_Docs_Settings::get_setting('enable_categories', true)): ?>
                <div class="filter-categories">
                    <label for="category-filter"><?php _e('Filter by Category:', 'lift-docs-system'); ?></label>
                    <select id="category-filter" class="lift-docs-filter" data-filter="category">
                        <option value=""><?php _e('All Categories', 'lift-docs-system'); ?></option>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'lift_doc_category',
                            'hide_empty' => true
                        ));
                        
                        foreach ($categories as $category) {
                            $selected = (is_tax('lift_doc_category', $category->slug)) ? 'selected' : '';
                            echo '<option value="' . $category->slug . '" ' . $selected . '>' . $category->name . ' (' . $category->count . ')</option>';
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="filter-sort">
                <label for="sort-filter"><?php _e('Sort by:', 'lift-docs-system'); ?></label>
                <select id="sort-filter" class="lift-docs-filter" data-filter="orderby">
                    <option value="date"><?php _e('Date', 'lift-docs-system'); ?></option>
                    <option value="title"><?php _e('Title', 'lift-docs-system'); ?></option>
                    <option value="menu_order"><?php _e('Order', 'lift-docs-system'); ?></option>
                    <option value="comment_count"><?php _e('Most Commented', 'lift-docs-system'); ?></option>
                </select>
            </div>
            
            <div class="view-toggle">
                <button class="view-grid active" data-view="grid" title="<?php _e('Grid View', 'lift-docs-system'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button class="view-list" data-view="list" title="<?php _e('List View', 'lift-docs-system'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
        </div>
        
        <?php if (have_posts()): ?>
            <div class="lift-docs-list grid-view">
                <?php while (have_posts()): the_post(); ?>
                    <article <?php post_class('lift-doc-card'); ?> data-id="<?php the_ID(); ?>">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="doc-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                                
                                <?php if (get_post_meta(get_the_ID(), '_lift_doc_featured', true)): ?>
                                    <span class="featured-badge"><?php _e('Featured', 'lift-docs-system'); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="doc-content">
                            <h3 class="doc-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <?php if (get_the_excerpt()): ?>
                                <p class="doc-excerpt"><?php echo get_the_excerpt(); ?></p>
                            <?php endif; ?>
                            
                            <div class="doc-meta">
                                <span class="doc-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo get_the_date(); ?>
                                </span>
                                
                                <span class="doc-author">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php the_author(); ?>
                                </span>
                                
                                <?php
                                $categories = get_the_terms(get_the_ID(), 'lift_doc_category');
                                if ($categories && !is_wp_error($categories)):
                                ?>
                                    <span class="doc-category">
                                        <span class="dashicons dashicons-category"></span>
                                        <a href="<?php echo get_term_link($categories[0]); ?>">
                                            <?php echo $categories[0]->name; ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (LIFT_Docs_Settings::get_setting('show_view_count', true)): ?>
                                    <?php $views = get_post_meta(get_the_ID(), '_lift_doc_views', true); ?>
                                    <?php if ($views): ?>
                                        <span class="doc-views">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php echo number_format($views); ?> <?php _e('views', 'lift-docs-system'); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php $file_size = get_post_meta(get_the_ID(), '_lift_doc_file_size', true); ?>
                                <?php if ($file_size): ?>
                                    <span class="doc-size">
                                        <span class="dashicons dashicons-media-default"></span>
                                        <?php echo size_format($file_size); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="doc-actions">
                                <a href="<?php the_permalink(); ?>" class="btn-view">
                                    <?php _e('View Document', 'lift-docs-system'); ?>
                                </a>
                                
                                <?php
                                $file_url = get_post_meta(get_the_ID(), '_lift_doc_file_url', true);
                                if ($file_url && LIFT_Docs_Settings::get_setting('show_download_button', true)):
                                    $download_url = add_query_arg(array(
                                        'lift_download' => get_the_ID(),
                                        'nonce' => wp_create_nonce('lift_download_' . get_the_ID())
                                    ), home_url());
                                ?>
                                    <a href="<?php echo esc_url($download_url); ?>" class="btn-download" data-document-id="<?php the_ID(); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php _e('Download', 'lift-docs-system'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('&laquo; Previous', 'lift-docs-system'),
                'next_text' => __('Next &raquo;', 'lift-docs-system'),
            ));
            ?>
            
            <?php if (get_query_var('paged') < $wp_query->max_num_pages): ?>
                <div class="lift-docs-load-more">
                    <button type="button" class="button" 
                            data-page="<?php echo get_query_var('paged', 1) + 1; ?>"
                            data-category="<?php echo is_tax('lift_doc_category') ? get_queried_object()->slug : ''; ?>"
                            data-tag="<?php echo is_tax('lift_doc_tag') ? get_queried_object()->slug : ''; ?>">
                        <?php _e('Load More Documents', 'lift-docs-system'); ?>
                    </button>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-documents">
                <h2><?php _e('No documents found', 'lift-docs-system'); ?></h2>
                <p><?php _e('Sorry, no documents were found matching your criteria.', 'lift-docs-system'); ?></p>
                
                <?php if (is_search()): ?>
                    <p>
                        <a href="<?php echo get_post_type_archive_link('lift_document'); ?>">
                            <?php _e('View all documents', 'lift-docs-system'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // View toggle functionality
    $('.view-toggle button').on('click', function() {
        var view = $(this).data('view');
        
        $('.view-toggle button').removeClass('active');
        $(this).addClass('active');
        
        $('.lift-docs-list')
            .removeClass('grid-view list-view')
            .addClass(view + '-view');
    });
});
</script>

<?php get_footer(); ?>
