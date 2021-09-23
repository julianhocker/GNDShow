# GNDShow
This extension for mediawiki allows you to integrate data from the German national libary (GND) and the Library for Research on the History of Education (BBF) into your wiki. German national library has information about all books published in Germany, so this makes it easy for you to add library information to your wiki. In the case of the Library for Research on the History of Education, specific publications can be issued collectively.
 
The extension adds the following magic words to your mediawiki:
* gndshowlite: takes in the p-value you want to show and only shows this information (fits great if you want to display local data from semantic mediawiki together with data from wikidata) - instead, a specific GND ID can also be passed
* bbfshowlite: takes in given BBF URNs (single or comma separated list) for specific publications

## Installation
1. Install WikidataShow (required to fetch automatic p-value): https://github.com/julianhocker/WikidataShow
2. Clone this repo via git clone https://github.com/julianhocker/GNDShow.git into extensions 
3. Add wfLoadExtension('GNDShow'); to your LocalSettings.php

## Known issues 
* Please open issues if you encounter problems

## Links
* https://www.dnb.de/DE/Professionell/Standardisierung/GND/gnd_node.html
* https://bbf.dipf.de/
* https://github.com/julianhocker/WikidataShow