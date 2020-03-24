#!/usr/bin/env bash

# Get the plugin slug from this git repository.
PLUGIN_SLUG="${PWD##*/}"

# Get the current release version
TAG=$(sed -e "s/refs\/tags\///g" <<< $GITHUB_REF)
VERSION="${TAG//v}"

# Replace the version in these 2 files.
# sed -i -e "s/__STABLE_TAG__/$TAG/g" ./src/readme.txt
# sed -i -e "s/__STABLE_TAG__/$TAG/g" "./src/$PLUGIN_SLUG.php"

# Get the SVN data from wp.org in a folder named `svn`
# svn co --depth immediates "https://plugins.svn.wordpress.org/$PLUGIN_SLUG" ./svn
svn co --depth immediates "https://plugins.svn.wordpress.org/wp-menu-cart" ./wp-svn

svn update --set-depth infinity ./wp-svn/trunk
svn update --set-depth infinity ./wp-svn/tags/$VERSION

# Copy files from `src` to `svn/trunk`
rsync -avr --exclude-from=".distignore" ./* ./wp-svn/trunk

# Switch to SVN directory
cd ./wp-svn

# Prepare the files for commit in SVN
svn add --force trunk

# Create the version tag in svn
svn cp trunk tags/$VERSION

# Prepare the tag for commit
svn add --force tags

# Commit files to wordpress.org.
svn ci  --message "Release $TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive

#### REPEAT FOR woocommerce-menu-bar-cart ####

# Overwrite plugin name
sed -i -e "s/=== WP Menu Cart ===/=== WooCommerce Menu Cart ===/g" ./readme.txt
sed -i -e "s/Plugin Name: WP Menu Cart/Plugin Name: WooCommerce Menu Cart/g" ./wp-menu-cart.txt

# Get the SVN data from wp.org in a folder named `svn`
svn co --depth immediates "https://plugins.svn.wordpress.org/woocommerce-menu-bar-cart" ./wc-svn

svn update --set-depth infinity ./wc-svn/trunk
svn update --set-depth infinity ./wc-svn/tags/$VERSION

# Copy files from `src` to `svn/trunk`
rsync -avr --exclude-from=".distignore" ./* ./wc-svn/trunk

# Switch to SVN directory
cd ./wc-svn

# Prepare the files for commit in SVN
svn add --force trunk

# Create the version tag in svn
svn cp trunk tags/$VERSION

# Prepare the tag for commit
svn add --force tags

# Commit files to wordpress.org.
svn ci  --message "Release $TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive