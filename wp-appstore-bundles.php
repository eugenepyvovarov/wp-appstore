<?php

/**
 * @author Arashi
 * @copyright 2012
 */

function wp_appstore_wizard_bundles() {
    $bundles = get_site_transient('wp_appstore_bundles');
    $list = '<ul>';
    if(is_array($bundles))
        $bundles_names = array_keys($bundles);
        
        foreach ($bundles_names as $bundle) {
            $list .= '<li>';
            $list .= '<a href="'.esc_attr(WP_AppStore::admin_url(array('screen'=>'prepare-bundle', 'bundle' => $bundle))).'">'.$bundles[$bundle]['name'].'</a>';
            $list .= '</li>';
        }
    $list .= '</ul>';
    ?>
     <div class="wrap">
        <?php screen_icon( 'plugins' );?>
        <h2>WP AppStore
			<span style="position:absolute;padding-left:15px;">
            <a href="http://www.facebook.com/pages/WP-AppStore/147376395324061" target="_blank"><img src="<?php echo plugins_url( 'images/facebook.png', __FILE__ ); ?>" alt="" /></a>
            <a href="http://twitter.com/wpappstore" target="_blank"><img src="<?php echo plugins_url( 'images/twitter.png', __FILE__ ); ?>" alt="" /></a>
            <a href="http://wp-appstore.com/" target="_blank"><img src="<?php echo plugins_url( 'images/rss.png', __FILE__ ); ?>" alt="" /></a>
            </span>
		</h2>
        <?php echo $list; ?>
    </div>
    <?php
}

function wp_appstore_wizard_prepare_bundle($bundle_slug) {
    $bundles = get_site_transient('wp_appstore_bundles');
    $list = '<ul>';
    if(is_array($bundles) && array_key_exists($bundle_slug, $bundles)){
        $bundle = $bundles[$bundle_slug];
    }else{
        $bundle['name'] = 'Error!';
        $bundle['description'] = 'Error occured while reading bundle content. Please try again.';
    }
    $list .= '</ul>';
    ?>
     <div class="wrap">
        <?php screen_icon( 'plugins' );?>
        <h2>WP AppStore
			<span style="position:absolute;padding-left:15px;">
            <a href="http://www.facebook.com/pages/WP-AppStore/147376395324061" target="_blank"><img src="<?php echo plugins_url( 'images/facebook.png', __FILE__ ); ?>" alt="" /></a>
            <a href="http://twitter.com/wpappstore" target="_blank"><img src="<?php echo plugins_url( 'images/twitter.png', __FILE__ ); ?>" alt="" /></a>
            <a href="http://wp-appstore.com/" target="_blank"><img src="<?php echo plugins_url( 'images/rss.png', __FILE__ ); ?>" alt="" /></a>
            </span>
		</h2>
        <h3><?php echo $bundle['name']; ?></h3>
        <p><?php echo $bundle['description']; ?></p>
        <?php if(is_array($bundle['plugins']) || is_array($bundle['themes'])): ?>
        <form action="admin.php?page=wp-appstore.php" method="post">
        <input type="hidden" name="prepared-bundle" value="<?php echo $bundle_slug; ?>" />
            <?php if(is_array($bundle['plugins'])): ?>
            <h4>Plugins to install:</h4>
            <ul>
                <?php foreach($bundle['plugins'] as $plugin): ?>
                    <?php if($plugin['installed']): ?>
                    <li><span class="plugin-data"><?php echo $plugin['title'] ?></span> - <span class="plugin-installed">Installed</span></li>
                    <?php else: ?>
                    <li><input type="checkbox" value="<?php echo $plugin['slug']; ?>" checked="checked" name="plugin[]" /><span class="plugin-data"><?php echo $plugin['title'] ?></span></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if(is_array($bundle['themes'])): ?>
            <h4>Themes to install:</h4>
            <ul>
                <?php foreach($bundle['themes'] as $theme): ?>
                    <?php if($theme['installed']): ?>
                    <li><span class="plugin-data"><?php echo $theme['title'] ?></span> - <span class="plugin-installed">Installed</span></li>
                    <?php else: ?>
                    <li><input type="checkbox" value="<?php echo $theme['slug']; ?>" checked="checked" name="theme[]" /><span class="plugin-data"><?php echo $theme['title'] ?></span></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <input type="submit" class="button rbutton" value="Install Selected" />
        </form>
        <?php endif; ?>
        <span class="buyoptions"><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'wizard')));?>" class="button rbutton" title="Bundles Wizard">Go back to the bundles</a></span>
    </div>
    <?php
}

?>