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


        // #1 Getting the DNB-IDN by GND-ID

        // $url_idn = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid%3D$gnd&recordSchema=MARC21-xml";
        // $url = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid=$gnd&recordSchema=MARC21-xml";
        $url_idn = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid%3D$gnd&recordSchema=oai_dc";

        $xml_idn = simplexml_load_file($url_idn) or die("Can't connect to URL");

        // // get DNB-IDN in MARC21-xml: separate controlfield with tag="001"
        // foreach ($xml_idn->records->record->recordData->record->controlfield as $record) {
        //     if (trim($record['tag'] == "001")) {
        //         $idn = strval($record);
        //         console_log("DNB-IDN: " . $idn);
        //     }
        // }

        $ns = $xml_idn->getNamespaces(true);

        // get DNB-IDN in oai_dc: separate dc:identifier with xsi:type="dnb:IDN"

        foreach ($xml_idn->records->record->recordData->dc as $record) {

            $ns_dc = $record->children($ns['dc']);

            // $ns_xsi = $ns_dc->children['http://www.w3.org/2001/XMLSchema-instance'];

            // if (trim($ns_xsi->identifier['type'] == "dnb:IDN")) {
            
            foreach ($ns_dc->identifier as $identifier) {
                if ($identifier->attributes("xsi", TRUE)->type == "dnb:IDN") {
                    $idn = strval(strval($ns_dc->identifier));
                }
            }

            console_log("DNB-IDN: " . $idn);
            // }
        }



        // #2 Getting the auRef-Data by DNB-IDN

        // string to concat all upcoming entries
        $string = "";

        $url_auRef = "https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&query=auRef%3D$idn&recordSchema=oai_dc&maximumRecords=100";

        console_log("Get 'Author of'-Data on: " . $url_auRef);

        $xml_auRef = simplexml_load_file($url_auRef) or die("Can't connect to URL");

        $ns = $xml_auRef->getNamespaces(true);

        foreach ($xml_auRef->records->record as $record) {

            // if (trim($record['tag'] == "001")) {
            // $idn = strval($record);
            // console_log("DNB-IDN: " . $idn);
            // return $idn;
            // }

            // print_r($record);
            // console_log(strval($record));

            // $namespaces = $record->getNamespaces(true);
            // var_dump($namespaces);

            $ns_dc = $record->recordData->dc->children($ns['dc']);

            // console_log(strval($ns_dc->title));

            $record_title = strval($ns_dc->title);
            $record_creator = strval($ns_dc->creator);
            $record_date = strval($ns_dc->date);

            foreach ($ns_dc->identifier as $identifier) {
                if ($identifier->attributes("xsi", TRUE)->type == "dnb:IDN") {
                    // console_log("we have a winner!");
                    $record_idn = strval($identifier);
                }
            }

            $record_url = "http://d-nb.info/" . $record_idn;

            $string = $string . $record_date . ": " . $record_title . ", " . $record_creator . ", " . $record_url . "\n";
        }

        // #3 Getting the betRef-Data by DNB-IDN

        $url_betRef = "https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&query=betRef%3D$idn&recordSchema=oai_dc&maximumRecords=100";

        console_log("Get 'Involved with'-Data on: " . $url_betRef);

        $xml_betRef = simplexml_load_file($url_betRef) or die("Can't connect to URL");

        $ns = $xml_betRef->getNamespaces(true);

        foreach ($xml_betRef->records->record as $record) {

            $ns_dc = $record->recordData->dc->children($ns['dc']);

            // console_log(strval($ns_dc->title));

            $record_title = strval($ns_dc->title);
            $record_creator = strval($ns_dc->creator);
            $record_date = strval($ns_dc->date);

            foreach ($ns_dc->identifier as $identifier) {
                if ($identifier->attributes("xsi", TRUE)->type == "dnb:IDN") {
                    // console_log("we have a winner!");
                    $record_idn = strval($identifier);
                }
            }

            $record_url = "http://d-nb.info/" . $record_idn;

            $string = $string . $record_date . ": " . $record_title . ", " . $record_creator . ", " . $record_url . "\n";
        }

        // #4 Getting the swiRef-Data by DNB-IDN

        $url_swiRef = "https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&query=swiRef%3D$idn&recordSchema=oai_dc&maximumRecords=100";

        console_log("Get 'Topic in'-Data on: " . $url_swiRef);

        $xml_swiRef = simplexml_load_file($url_swiRef) or die("Can't connect to URL");

        $ns = $xml_swiRef->getNamespaces(true);

        $swiRef_result = array();

        foreach ($xml_swiRef->records->record as $record) {

            $ns_dc = $record->recordData->dc->children($ns['dc']);                       

            // console_log(strval($ns_dc->title));

            $record_title = strval($ns_dc->title);
            $record_creator = strval($ns_dc->creator);
            $record_date = strval($ns_dc->date);

            // $record_url = strval($ns_dc->identifier[$ns['xsi']->type] == "dnb:IDN"); // output: 1 --> yes there is a tag with this attribute...
            // if ($ns_dc->identifier[$ns['xsi']->type] == "dnb:IDN") {
            //     console_log("we have a winner!");
            //     $record_idn = strval($ns_dc->identifier);
            // }
            // $record_idn = $ns_dc->identifier[$ns['xsi']->type] == "dnb:IDN";

            // $record_tag = $ns_dc->identifier->attributes("xsi", TRUE)->type; // output: dnb:IDN
            foreach ($ns_dc->identifier as $identifier) {
                if ($identifier->attributes("xsi", TRUE)->type == "dnb:IDN") {
                    // console_log("we have a winner!");
                    $record_idn = strval($identifier);
                }
            }

            // console_log("record_tag: " . $record_tag);
            // console_log("record_idn: " . $record_idn);

            $record_url = "http://d-nb.info/" . $record_idn;

            $string = $string . $record_date . ": " . $record_title . ", " . $record_creator . ", " . $record_url . "\n";

          
        }

        // array_push($swiRef_result, "
        //     |strval($ns_dc->title)
        //     |$website
        //     |-
        // ");  

        console_log("swiRef-Results: " . $swiRef_result);
        
        return $string;

        // $output = "
        //     {| class='wikitable'
        //     !$websiteString
        //     |$website
        //     |-
        //     !$adressString
        //     |$adress
        //     |-
        //     !$mapString
        //     |$coordinates
        //     |-
        //     !$namesString
        //     |$nameresult
        //     |-
        //     !$foundedString
        //     |$earliestRecord[year], $inception
        //     |-
        //     !$imageString
        //     |$imagewiki
        //     |-
        //     !$instanceString
        //     |$instanceResult
        //     |-
        //     !$operatorSring
        //     |$operatorResult
        //     |-
        //     !$wikipediaString
        //     |$wikipedialink
        //     |-
        //     !$gndString
        //     |$gndlink
        //     |}";

        // return $output;


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
