<?php
/**
 * Template for displaying single documents
 * 
 * This template can be overridden by copying it to yourtheme/lift-docs/single-lift_document.php
 */

get_header(); ?>

<div class="lift-document-single">
    <?php while (have_posts()): the_post(); ?>
        <article <?php post_class(); ?>>
            <div class="container">
                <header class="entry-header">
                    <?php
                    // Breadcrumb
                    $categories = get_the_terms(get_the_ID(), 'lift_doc_category');
                    if ($categories && !is_wp_error($categories)):
                    ?>
                        <nav class="document-breadcrumb">
                            <a href="<?php echo get_post_type_archive_link('lift_document'); ?>">
                                <?php _e('Documents', 'lift-docs-system'); ?>
                            </a>
                            <span class="separator"> / </span>
                            <a href="<?php echo get_term_link($categories[0]); ?>">
                                <?php echo $categories[0]->name; ?>
                            </a>
                            <span class="separator"> / </span>
                            <span class="current"><?php the_title(); ?></span>
                        </nav>
                    <?php endif; ?>
                    
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    
                    <?php if (get_post_meta(get_the_ID(), '_lift_doc_featured', true)): ?>
                        <div class="featured-badge">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Featured Document', 'lift-docs-system'); ?>
                        </div>
                    <?php endif; ?>
                </header>
                
                <?php if (has_post_thumbnail()): ?>
                    <div class="document-thumbnail">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="entry-content">
                    <?php
                    // Check password protection
                    $is_password_protected = get_post_meta(get_the_ID(), '_lift_doc_password_protected', true);
                    $doc_password = get_post_meta(get_the_ID(), '_lift_doc_password', true);
                    $entered_password = $_POST['lift_doc_password'] ?? $_SESSION['lift_doc_' . get_the_ID()] ?? '';
                    
                    if ($is_password_protected && $doc_password && $doc_password !== $entered_password):
                    ?>
                        <div class="document-password-form">
                            <h3><?php _e('This document is password protected', 'lift-docs-system'); ?></h3>
                            <p><?php _e('Please enter the password to view this document.', 'lift-docs-system'); ?></p>
                            
                            <form method="post" action="">
                                <div class="password-field">
                                    <label for="lift_doc_password"><?php _e('Password:', 'lift-docs-system'); ?></label>
                                    <input type="password" id="lift_doc_password" name="lift_doc_password" required />
                                </div>
                                <button type="submit" class="button">
                                    <?php _e('Submit', 'lift-docs-system'); ?>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <?php the_content(); ?>
                        
                        <?php
                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . __('Pages:', 'lift-docs-system'),
                            'after' => '</div>',
                        ));
                        ?>
                    <?php endif; ?>
                </div>
                
                <?php
                // Show tags
                $tags = get_the_terms(get_the_ID(), 'lift_doc_tag');
                if ($tags && !is_wp_error($tags) && LIFT_Docs_Settings::get_setting('enable_tags', true)):
                ?>
                    <div class="document-tags">
                        <h4><?php _e('Tags:', 'lift-docs-system'); ?></h4>
                        <div class="tag-list">
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?php echo get_term_link($tag); ?>" class="tag-link">
                                    <?php echo $tag->name; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <footer class="entry-footer">
                    <div class="document-navigation">
                        <?php
                        $prev_post = get_previous_post(true, '', 'lift_doc_category');
                        $next_post = get_next_post(true, '', 'lift_doc_category');
                        ?>
                        
                        <?php if ($prev_post): ?>
                            <div class="nav-previous">
                                <a href="<?php echo get_permalink($prev_post->ID); ?>">
                                    <span class="nav-subtitle"><?php _e('Previous Document', 'lift-docs-system'); ?></span>
                                    <span class="nav-title"><?php echo get_the_title($prev_post->ID); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($next_post): ?>
                            <div class="nav-next">
                                <a href="<?php echo get_permalink($next_post->ID); ?>">
                                    <span class="nav-subtitle"><?php _e('Next Document', 'lift-docs-system'); ?></span>
                                    <span class="nav-title"><?php echo get_the_title($next_post->ID); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="document-share">
                        <h4><?php _e('Share this document:', 'lift-docs-system'); ?></h4>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                               target="_blank" class="share-facebook">
                                <?php _e('Facebook', 'lift-docs-system'); ?>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                               target="_blank" class="share-twitter">
                                <?php _e('Twitter', 'lift-docs-system'); ?>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" 
                               target="_blank" class="share-linkedin">
                                <?php _e('LinkedIn', 'lift-docs-system'); ?>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(get_permalink()); ?>" 
                               class="share-email">
                                <?php _e('Email', 'lift-docs-system'); ?>
                            </a>
                            <button type="button" class="share-copy" data-url="<?php echo get_permalink(); ?>">
                                <?php _e('Copy Link', 'lift-docs-system'); ?>
                            </button>
                        </div>
                    </div>
                </footer>
                
                <?php
                // Comments
                if (comments_open() || get_comments_number()) {
                    comments_template();
                }
                ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<style>
/* Single document specific styles */
.document-breadcrumb {
    margin-bottom: 20px;
    font-size: 0.9em;
    color: #666;
}

.document-breadcrumb a {
    color: #0073aa;
    text-decoration: none;
}

.document-breadcrumb a:hover {
    text-decoration: underline;
}

.document-breadcrumb .separator {
    margin: 0 8px;
}

.document-breadcrumb .current {
    color: #333;
    font-weight: 500;
}

.featured-badge {
    display: inline-flex;
    align-items: center;
    background: #ff6b35;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    margin: 10px 0;
}

.featured-badge .dashicons {
    margin-right: 5px;
    font-size: 16px;
}

.document-thumbnail {
    text-align: center;
    margin: 20px 0;
}

.document-thumbnail img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.document-password-form {
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    margin: 20px 0;
}

.document-password-form h3 {
    margin-top: 0;
    color: #333;
}

.password-field {
    margin: 20px 0;
}

.password-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.password-field input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 200px;
    max-width: 100%;
}

.document-tags {
    margin: 30px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.document-tags h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-link {
    background: #e1e1e1;
    color: #333;
    padding: 4px 12px;
    border-radius: 16px;
    text-decoration: none;
    font-size: 0.85em;
    transition: background-color 0.3s ease;
}

.tag-link:hover {
    background: #0073aa;
    color: white;
}

.document-navigation {
    display: flex;
    justify-content: space-between;
    margin: 40px 0;
    gap: 20px;
}

.nav-previous,
.nav-next {
    flex: 1;
    max-width: 48%;
}

.nav-previous a,
.nav-next a {
    display: block;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.nav-previous a:hover,
.nav-next a:hover {
    background: #0073aa;
    color: white;
    transform: translateY(-2px);
}

.nav-next {
    text-align: right;
}

.nav-subtitle {
    display: block;
    font-size: 0.8em;
    color: #666;
    margin-bottom: 5px;
}

.nav-title {
    display: block;
    font-weight: 600;
    font-size: 1.1em;
}

.document-share {
    margin: 30px 0;
    text-align: center;
}

.document-share h4 {
    margin-bottom: 15px;
    color: #333;
}

.share-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

.share-buttons a,
.share-buttons button {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
    border: 1px solid #ddd;
    background: #f9f9f9;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
}

.share-facebook:hover { background: #3b5998; color: white; border-color: #3b5998; }
.share-twitter:hover { background: #1da1f2; color: white; border-color: #1da1f2; }
.share-linkedin:hover { background: #0077b5; color: white; border-color: #0077b5; }
.share-email:hover { background: #333; color: white; border-color: #333; }
.share-copy:hover { background: #0073aa; color: white; border-color: #0073aa; }

@media (max-width: 768px) {
    .document-navigation {
        flex-direction: column;
    }
    
    .nav-previous,
    .nav-next {
        max-width: 100%;
    }
    
    .nav-next {
        text-align: left;
    }
    
    .share-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .share-buttons a,
    .share-buttons button {
        width: 200px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy link functionality
    $('.share-copy').on('click', function() {
        var url = $(this).data('url');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                alert('<?php _e("Link copied to clipboard!", "lift-docs-system"); ?>');
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                alert('<?php _e("Link copied to clipboard!", "lift-docs-system"); ?>');
            } catch (err) {
                console.error('Failed to copy: ', err);
            }
            
            document.body.removeChild(textArea);
        }
    });
});
</script>

<?php get_footer(); ?>
