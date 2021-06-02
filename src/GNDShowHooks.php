<?php
use Wikidata\Wikidata; #https://github.com/freearhey/wikidata
class GNDShowHooks {
   // Register any render callbacks with the parser
   public static function onParserFirstCallInit( Parser $parser ) {

      // Create a function hook associating the magic word with renderExample()
      $parser->setFunctionHook( 'gndshowlite', [ self::class, 'gndshowlite' ] );
   }

   public static function gndshowlite( Parser $parser, $param1 = '', $param2 = '') {
        //Param1 represents the id of the value e.g. 245 for the title
        //Param2 represents the id of the literature, e.g. 975877089
                global $wgScriptPath;
                global $wgServer;
                $language = wfMessage( 'language')->plain();
                $wikilanguage = $language ."wiki";
                $title = $parser->getTitle()->getText();
                $titleunderscores = $parser->getTitle()->getDBKey();
                ##get wikidatalink from actual page
                if(empty($param2)){#if param2 is not set, take the wikidatalink from the actual page
                    $endpoint = "$wgServer$wgScriptPath/api.php";
                    $url = "$endpoint?action=ask&query=[[$titleunderscores]]|?Wikidata_ID|limit=5&format=json";
                    $json_data = file_get_contents($url);
                    $apiresponse = json_decode($json_data, true);
                    try {
                         if (empty($apiresponse['query']['results'][$title]['printouts']['GND ID'][0])){
                             throw new Exception("not defined");
                         }else {
                             $wikidataentry = $apiresponse['query']['results'][$title]['printouts']['GND ID'][0];#get wikidatalink from api
                         }
                    }
                    //catch exception
                    catch(Exception $e) {
                        return "No GND entry found";
                    }
                } else {
                    $gndentry = $param2;
                }

        $result = "";
        $url = urlencode("http://d-nb.info/$gndentry/about/marcxml");
        $xml = simplexml_load_file($url);
        //$xml = str_replace('xmlns=', 'ns=', $xml); #hack to replace the namespace string with an empty string. Not nice!
        //$query = $xml ->xpath("//datafield[@tag=$param1]/subfield[@code='a']");
        $xml -> registerXPathNamespace('c', 'http://www.loc.gov/MARC21/slim');
        $query = $xml ->xpath("//c:datafield[@tag='$param1']/c:subfield[@code='a']");
        if(empty($query)){
            $result="error";
        } else {
            list($result) = $query;
            //while(list( , $node) = each($query)) {
            //            $result.= "$node  Test \n";
            //        }
        }
        //return $result;
        return "Hello World";
   }
}