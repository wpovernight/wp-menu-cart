#!/usr/bin/env bash
# https://zerowp.com/?p=55

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
echo "➤ Checking out .org repository..."
svn co --depth immediates "https://plugins.svn.wordpress.org/wp-menu-cart" ./svn

svn update --set-depth infinity ./svn/test
# svn update --set-depth infinity ./svn/tags/$VERSION

# Copy files from `src` to `svn/test`
rsync -avr --exclude-from=".distignore" ./* ./svn/test

# 3. Switch to SVN directory
cd ./svn

# Prepare the files for commit in SVN
svn add --force test

# Create the version tag in svn
# svn cp trunk tags/$VERSION

# Prepare the tag for commit
# svn add --force tags

# Commit files to wordpress.org.
svn ci  --message "Test release $TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive