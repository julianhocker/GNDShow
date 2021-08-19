<?php

use Wikidata\Wikidata; #https://github.com/freearhey/wikidata
class GNDShowHooks
{
    // Register any render callbacks with the parser
    public static function onParserFirstCallInit(Parser $parser)
    {

        // Create a function hook associating the magic word with renderExample()
        $parser->setFunctionHook('gndshow', [self::class, 'gndshowlite']);
    }

    public static function gndshowlite(Parser $parser, $param1 = '', $param2 = '')
    {

        function console_log($data)
        {
            echo '<script>';
            echo 'console.log(' . json_encode($data) . ')';
            echo '</script>';
        }

        //Param1 represents the id of the value e.g. 245 for the title
        //Param2 represents the id of the literature, e.g. 975877089

        global $wgScriptPath;
        global $wgServer;

        console_log("\$wgServer " . $wgServer);

        $language = wfMessage('language')->plain();
        $wikilanguage = $language . "wiki";
        $title = $parser->getTitle()->getText();
        $titleunderscores = $parser->getTitle()->getDBKey();
        ##get wikidatalink from actual page
        if (empty($param2)) { #if param2 is not set, take the wikidatalink from the actual page

            $endpoint = "$wgServer$wgScriptPath/api.php";
            $url = "$endpoint?action=ask&query=[[$titleunderscores]]|?Wikidata_ID|limit=5&format=json";
            $json_data = file_get_contents($url);
            $apiresponse = json_decode($json_data, true);
            try {
                if (empty($apiresponse['query']['results'][$title]['printouts']['Wikidata ID'][0])) {
                    throw new Exception("not defined");
                } else {
                    $wikidataentry = $apiresponse['query']['results'][$title]['printouts']['Wikidata ID'][0]; #get wikidatalink from api
                }
            }
            //catch exception
            catch (Exception $e) {
                return "No wikidata entry found";
            }
        } else {
            $wikidataentry = $param2;
        }

        $wikidata = new Wikidata(); #init object to get info from wikidata
        #check if we get valid information from wikidata
        try {
            if (empty($wikidata->get($wikidataentry, $language))) {
                throw new Exception('not defined');
            } else {
                $entity = $wikidata->get($wikidataentry, $language); # get data for entitiy (with Q-number)
                $properties = $entity->properties->toArray(); #convert data to array to make handling easier
            }
        } catch (Exception $e) {
            return "wrong Wikidata ID";
        }

        $gnd = self::getData($properties, "P227");
        if ($gnd == "not defined") {
            return wfMessage('unknown')->plain();
        } else {
            console_log("GND-ID: " . $gnd) . "\n";
        }


        // // $endpoint = "$wgServer$wgScriptPath/api.php";
        // $endpoint = "$wgServer$wgScriptPath/api.php";

        // $url = "$endpoint?action=ask&query=[[$titleunderscores]]|?Wikidata_ID|limit=5&format=json";

        // $url = "$endpoint?action=parse&format=json&page=$titleunderscores&prop=externallinks";

        // console_log("\$url: " . $url);

        // $json_data = file_get_contents($url);
        // console_log("json_data: " . $json_data);
        // $apiresponse = json_decode($json_data, true);
        // console_log("apiresponse: " . $apiresponse);

        // // console_log("externallinks[2]: " . $apiresponse['parse']['externallinks'][2]);

        // try {
        //         // if (empty($apiresponse['query']['results'][$title]['printouts']['GND-Seite'][0])){
        //         if (empty($apiresponse['parse']['externallinks'][2])){
        //             throw new Exception("not defined");
        //         }else {

        //         //  $wikidataentry = $apiresponse['query']['results'][$title]['printouts']['GND ID'][0];#get wikidatalink from api
        //             // $gndentry = $apiresponse['query']['results'][$title]['printouts']['GND ID'][0];#get wikidatalink from api
        //             $gndentry = $apiresponse['parse']['externallinks'][2];#get wikidatalink from api
        //         }
        // }
        // //catch exception
        // catch(Exception $e) {

        //     console_log("Exception \$e: " . $e);

        //     return "No GND entry found";
        // }
        // } else {
        //     $gndentry = $param2;
        // }

        // $result = "";
        // $url = urlencode("http://d-nb.info/$gndentry/about/marcxml");
        // $xml = simplexml_load_file($url);
        // //$xml = str_replace('xmlns=', 'ns=', $xml); #hack to replace the namespace string with an empty string. Not nice!
        // //$query = $xml ->xpath("//datafield[@tag=$param1]/subfield[@code='a']");
        // $xml -> registerXPathNamespace('c', 'http://www.loc.gov/MARC21/slim');
        // $query = $xml ->xpath("//c:datafield[@tag='$param1']/c:subfield[@code='a']");
        // if(empty($query)){
        //     $result="error";
        // } else {
        //     list($result) = $query;
        //     //while(list( , $node) = each($query)) {
        //     //            $result.= "$node  Test \n";
        //     //        }
        // }
        // //return $result;
        // return "Hello World";






        // Getting the DNB-IDN by GND-ID

        $url1 = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid%3D$gnd&recordSchema=MARC21-xml";
        // $url = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid=$gnd&recordSchema=MARC21-xml";
        // $url = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid=$gnd&recordSchema=oai_dc";

        $xml1 = simplexml_load_file($url1) or die("Can't connect to URL");

        // separate controlfield with tag 001 --> DNB-IDN
        foreach ($xml1->records->record->recordData->record->controlfield as $record) {
            if (trim($record['tag'] == "001")) {
                $idn = strval($record);
                console_log("DNB-IDN: " . $idn);
                // return $idn;
            }
        }







        // Getting the Author-Data by DNB-IDN

        $url2 = "https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&query=auRef%3D$idn&recordSchema=oai_dc";

        console_log("Get Author-Infos: " . $url2);

        $xml2 = simplexml_load_file($url2) or die("Can't connect to URL");

        // string to concat all entries
        $string = "";
        
        foreach ($xml2->records->record as $record) {                    
            

            // if (trim($record['tag'] == "001")) {
            // $idn = strval($record);
            // console_log("DNB-IDN: " . $idn);
            // return $idn;
            // }

            // print_r($record);
            // console_log(strval($record));

            // $namespaces = $record->getNamespaces(true);
            // var_dump($namespaces);

            $ns_dc = $record->recordData->dc->children('http://purl.org/dc/elements/1.1/');

            console_log(strval($ns_dc->title));

            $string = $string . strval($ns_dc->date) . ": " . strval($ns_dc->title) . "\n";
        }

        return $string;


        // $dom = new DOMDocument("1.0");
        // $dom->preserveWhiteSpace = false;
        // $dom->formatOutput = true;
        // $dom->loadXML($xml->asXML());
        // $domxml =  $dom->saveXML();

        // $result = simplexml_load_string($domxml) or die("Can't load XML string");

        // console_log("result: " . $result);
        // // return $result;



        // $test = $xml->searchRetrieveResponse->records->record[0]->recordData->dc->children('dc', true)->identifier;
        // console_log("test: " . $test);
        // print_r($test);
        // echo $test;
        // return $test;

        // // simplexml-testing
        // echo $xml->records->record[0]->recordData->record->leader;

        // console_log("hola again2");



        // console_log("\$url: " .  $url );


        //$xml = str_replace('xmlns=', 'ns=', $xml); #hack to replace the namespace string with an empty string. Not nice!
        //$query = $xml ->xpath("//datafield[@tag=$param1]/subfield[@code='a']");


        // $xml -> registerXPathNamespace('c', 'http://www.loc.gov/MARC21/slim');
        // $query = $xml ->xpath("//c:datafield[@tag='$param1']/c:subfield[@code='a']");
        // if(empty($query)){
        //     $result="error";
        // } else {
        //     list($result) = $query;
        //     //while(list( , $node) = each($query)) {
        //     //            $result.= "$node  Test \n";
        //     //        }
        // }



        //return $result;
        // return "Hello World";


    }


    public static function getData($properties = '', $pvalue = '')
    { #get data if p-value only has one value
        try {
            if (empty($properties[$pvalue]->values[0]->label)) {
                throw new Exception("not defined");
            } else {
                return $properties[$pvalue]->values[0]->label;
            }
        }
        //catch exception
        catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
