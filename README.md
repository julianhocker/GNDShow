# Wikidata Show
This extension for mediawiki allows you to integrate data from the German national libary into your wiki. German national library has information about all books published in Germany, so this makes it easy for you to add library information to your wiki.
 
The extension adds the following magic words to your mediawiki:
* gndshowlite: takes in the p-value you want to show and only shows this information (fits great if you want to display local data from semantic mediawiki together with data from wikidata)

Example:

![alt text](https://raw.githubusercontent.com/julianhocker/wikidatashow/master/example.png "Example of extension")

## Installation
1. Add "freearhey/wikidata": "3.2" to your composer.json of the wiki in the section "require"
2. Run composer update --no-dev
3. Clone this repo via git clone https://github.com/julianhocker/wikidatashow.git into extensions 
4. Add wfLoadExtension('WikidataShow'); to your LocalSettings.php

## Usage
### Wikidatashow
This way you get a box with all the data defined above directly from wikidata
* type the magic work {{#wikidatashow:}} into a page and it will get the corresponding information based on the smw-attribute 'Wikidata ID'
* you can also provide the wikidata-id directly, e.g. {{#wikidatashow:Q1533809}}

###Wikidatashotlite
This way you only get single items from wikidata. This function is provided for links to wikipedia, image, adress, website, link to GND
* type the magic word {{#wikidatashowlite:}} to a page, giving the p-value of the information you need or 'wikipedia', e.g. {{#wikidatashowlite:P18}} to get the corresponding image. 
* if you do not have Semantic Mediawiki running, just provide the wikidata ID as second paramenter to get the data: 
{{#wikidatashowlite:P227|Q1533809}}

## Dependencies
The extension was tested on Semantic MediaWiki 3.1.5. and MediaWiki 1.34.0. You do not need Semantic MediaWiki to make it running, but then you have to provide the wikidata-ID directly.  Translation is right now done in English and German, please feel free to add more translation ;). 

## Known issues 
* Please open issues if you encounter problems 
* connection to wikidata should be done more nicely in the code
* I also added the magic word wikidatashoweasy, that just returns the value from wikidata. It seemed handy at first, but I did not see great usage since many values are not proper formatted/usefull in wikidata