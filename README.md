# GNDShow
This extension for mediawiki allows you to integrate data from the German National Libary (DNB) - to be more precise: from the Integrated Authority File (GND) - and the Library for Research on the History of Education (BBF) into your wiki. The DNB has information about all books published in Germany, so this makes it easy for you to add library information to your wiki. In the case of the BBF, specific publications can be issued collectively.
 
The extension adds the following magic words to your mediawiki:
* gndshowlite: takes in the p-value you want to show and only shows this information (fits great if you want to display local data from semantic mediawiki together with data from wikidata) - instead, one specific GND ID can also be passed
* bbfshowlite: takes in given BBF URNs (single or comma separated list) for specific publications you want to show and only shows this information

## Installation
1. Add "freearhey/wikidata": "3.2" to your composer.json of the wiki in the section "require"
2. Run composer update --no-dev
3. Clone this repo via git clone https://github.com/julianhocker/GNDShow.git into extensions  
4. Add wfLoadExtension('GNDShow'); to your LocalSettings.php

## Known issues 
* Please open issues if you encounter problems 

## Links
* https://www.dnb.de/DE/Professionell/Standardisierung/GND/gnd_node.html
* https://bbf.dipf.de/
* https://github.com/julianhocker/WikidataShow
