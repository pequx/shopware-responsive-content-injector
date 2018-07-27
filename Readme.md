# `PHAG_RESPONSIVE_CONTENT_INJECTOR`
build: 1.0.7

## Synopsis
The plugin provides a easy method to add responsive marketing content into a existing blog entry during edition in the visual backend interface.
It facilitates event subscribers, handles the blog content, transfer it into a node-like entity and returns enrhiched by auto-cached html snippets in accordance with the orginal parametric sequence.

## Installation
1. Please install and configure the plugin using backend interface.
2. Execute as follows: `cd var/cache`, `./clear_cache.sh`, `./bin/console sw:theme:dump:configuration`, `cd themes`, `npm install` (via https://developers.shopware.com/designers-guide/best-practice-theme-development/ for gulp) or use Shopware compiler: https://developers.shopware.com/designers-guide/less/#creating-css-source-maps

## DevTools Installation
This plugin helps to execute unit and coverage tests automatically. I hope.
1. In the main plugin folder execute `composer install` in order to install dev plugin tools.
2. In the same folder create a symlink `ln -s vendor/shopware/plugin-dev-tools/psh.phar psh`
3. In `vendor/shopware/plugin-dev-tools` run `./install.sh`
4. Back in main folder execute `./psh`
5. Create a file `.sw-zip-blacklist` by typing `echo psh > .sw-zip-blacklist`.
