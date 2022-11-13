#!/bin/zsh

# copy the hello-login plugin files into plugin folder
cp -v ../CHANGELOG.md ../hello-login.php ../readme.txt plugin/
cp -r -v ../css ../includes ../languages plugin/

# allow WordPress to delete the plugin
chmod a+w -R plugin/

