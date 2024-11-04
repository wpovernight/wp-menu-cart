#!/usr/bin/env bash

# Get the plugin slug from this git repository.
PLUGIN_SLUG="${PWD##*/}"

# Get the current release version
TAG=$(sed -e "s/refs\/tags\///g" <<< $GITHUB_REF)
VERSION="${TAG#v}"

# Get the SVN data from wp.org in a folder named `svn`
SVN_URL="https://plugins.svn.wordpress.org/wp-menu-cart/"
SVN_DIR="$HOME/svn-wp"
svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"

# Switch to SVN directory
cd "$SVN_DIR"

svn update --set-depth infinity trunk
svn update --set-depth infinity tags/$VERSION

# Copy files from release to `svn/trunk`
rsync -rcm --exclude-from="$GITHUB_WORKSPACE/.distignore" "$GITHUB_WORKSPACE/" trunk/ --delete --delete-excluded 

# Detect and schedule additions and deletions in SVN
svn status | grep '^[!?]' | while IFS= read -r line; do
    status="${line:0:1}"
    file="${line:8}"
    if [ "$status" = "!" ]; then
        echo "Deleting: $file"
        svn delete "$file"
    elif [ "$status" = "?" ]; then
        echo "Adding: $file"
        svn add "$file"
    fi
done

# Prepare the files for commit in SVN
svn add --force trunk

# Create the version tag in svn
svn cp "trunk" "tags/$VERSION"

# Prepare the tag for commit
svn add --force tags

# Commit files to wordpress.org.
svn ci  --message "Release $TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive

#### REPEAT FOR woocommerce-menu-bar-cart ####

# Overwrite plugin name
sed -i -e "s/=== WP Menu Cart ===/=== Menu Cart for WooCommerce ===/g" "$GITHUB_WORKSPACE/readme.txt"
sed -i -e "s/Plugin Name: WP Menu Cart/Plugin Name: Menu Cart for WooCommerce/g" "$GITHUB_WORKSPACE/wp-menu-cart.php"

# Get the SVN data from wp.org in a folder named `svn`
SVN_URL="https://plugins.svn.wordpress.org/woocommerce-menu-bar-cart/"
SVN_DIR="$HOME/svn-wc"
svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"

# Switch to SVN directory
cd "$SVN_DIR"

svn update --set-depth infinity trunk
svn update --set-depth infinity tags/$VERSION

# Copy files from release to `svn/trunk`
rsync -rcm --exclude-from="$GITHUB_WORKSPACE/.distignore" "$GITHUB_WORKSPACE/" trunk/ --delete --delete-excluded 

# Prepare the files for commit in SVN
svn add --force trunk

# Create the version tag in svn
svn cp "trunk" "tags/$VERSION"

# Prepare the tag for commit
svn add --force tags

# Commit files to wordpress.org.
svn ci  --message "Release $TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive
