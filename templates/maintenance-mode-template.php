<?php
/**
 * Template Name: Maintenance Mode
 * Description: Template for maintenance mode page.
 *
 * @author  Ondřej Doněk, <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @link https://github.com/ondrejd/odwp-maintenance_mode for the canonical source repository
 * @package odwp-maintenance_mode
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

get_header();

?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <header class="header">
            <h1><?php echo $this->title ?></h1>
        </header>
        <div class="content">
            <div class="content-wrap">
                <p><?php echo $this->body ?></p>
            </div>
        </div>
        <footer class="footer">
            <p><?php echo $this->footer ?></p>
            <?php wp_footer() ?>
        </footer>
    </main>
    <?php get_sidebar( 'content-bottom' ); ?>
</div>
<?php

get_sidebar();
get_footer();
