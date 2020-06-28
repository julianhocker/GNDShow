# GNDShow
This extension for mediawiki allows you to integrate data from the German national libary into your wiki. German national library has information about all books published in Germany, so this makes it easy for you to add library information to your wiki.
 
The extension adds the following magic words to your mediawiki:
* gndshowlite: takes in the p-value you want to show and only shows this information (fits great if you want to display local data from semantic mediawiki together with data from wikidata)

## Installation
1. Clone this repo via git clone https://github.com/julianhocker/GNDShow.git into extensions 
2. Add wfLoadExtension('GNDShow'); to your LocalSettings.php

## Known issues 
* Please open issues if you encounter problems 
* I will work more on this, planning to add some more data you can get from the GND
* File names are wrong 