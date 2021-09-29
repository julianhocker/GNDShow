# GNDShow
This extension for mediawiki allows you to integrate data from the German national libary into your wiki. German national library has information about all books published in Germany, so this makes it easy for you to add library information to your wiki.
 
The extension adds the following magic words to your mediawiki:
* gndshowlite: takes in the p-value you want to show and only shows this information (fits great if you want to display local data from semantic mediawiki together with data from wikidata)

## Installation
1. Add "freearhey/wikidata": "3.2" to your composer.json of the wiki in the section "require"
2. Run composer update --no-dev
3. Clone this repo via git clone https://github.com/julianhocker/GNDShow.git into extensions  
4. Add wfLoadExtension('GNDShow'); to your LocalSettings.php

## Known issues 
* Please open issues if you encounter problems 
