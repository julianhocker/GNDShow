<?php

use Wikidata\Wikidata; #https://github.com/freearhey/wikidata
class GNDShowHooks
{
    // Register any render callbacks with the parser
    public static function onParserFirstCallInit(Parser $parser)
    {

        // Create a function hook associating the magic word with renderExample()
        $parser->setFunctionHook('gndshow', [self::class, 'gndshowlite']);
        $parser->setFunctionHook('bbfshow', [self::class, 'bbfshowlite']);
    }


    public static function gndshowlite(Parser $parser, $param1 = '', $param2 = '')
    {
        // for debugging: console-log
        function console_log($data)
        {
            echo '<script>';
            echo 'console.log(' . json_encode($data) . ')';
            echo '</script>';
        }

        
        // function for repeating publication fetch and data processing
        function getDNBref($refKey, $idn, $refIdns)
        {

            $urlRef = "https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&query=$refKey%3D$idn&recordSchema=oai_dc&maximumRecords=100";

            console_log("Get 'Author of'-Data on: " . $urlRef);

            $xmlRef = simplexml_load_file($urlRef) or die("Can't connect to URL");

            $ns = $xmlRef->getNamespaces(true);

            $refResult = "";

            foreach ($xmlRef->records->record as $record) {

                $ns_dc = $record->recordData->dc->children($ns['dc']);

                $record_title = strval($ns_dc->title);
                $record_creator = strval($ns_dc->creator);
                $record_date = strval($ns_dc->date);

                foreach ($ns_dc->identifier as $identifier) {
                    if ($identifier->attributes("xsi", TRUE)->type == "dnb:IDN") {
                        $record_idn = strval(strval($ns_dc->identifier));
                    }
                }

                $record_url = "http://d-nb.info/" . $record_idn;


                if (in_array($record_idn, $refIdns)) {
                    console_log($record_idn . " is already in list!");
                } else {
                    array_push($refIdns, $record_idn);

                    $refResult = $refResult . "
                            |$record_title
                            |$record_creator
                            |$record_date
                            |$record_url
                            |-
                        ";
                }
            }

            $GLOBALS["output"] = $GLOBALS["output"] . $refResult;

            // console_log("refIdns-Array: " . print_r($refIdns));

            return $refIdns;
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

        // #1 Getting the DNB-IDN by GND-ID

        $url_idn = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid%3D$gnd&recordSchema=oai_dc";

        $xml_idn = simplexml_load_file($url_idn) or die("Can't connect to URL");

        // // get DNB-IDN in MARC21-xml: separate controlfield with tag="001"

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

        global $output;        

        // Prepared Output base
        $output = "
            {| class='wikitable'
            !Titel
            !Verfasser:in
            !Datum
            !Quelle
            |-
            ";

        // Collection of fetched publication IDNs to prevent doublettes, used as return value to pass it with every ref-data-fetch
        $refIdns = array();

        // #2 Getting the auRef-Data by DNB-IDN
        $refIdns = getDNBref("auRef", $idn, $refIdns);

        // #3 Getting the betRef-Data by DNB-IDN
        $refIdns = getDNBref("betRef", $idn, $refIdns);

        // #4 Getting the swiRef-Data by DNB-IDN
        $refIdns = getDNBref("swiRef", $idn, $refIdns);

        return $output;
    }

    public static function bbfshowlite(Parser $parser, $param1 = '', $param2 = '')
    {
        return "BBFShow Test!";
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
