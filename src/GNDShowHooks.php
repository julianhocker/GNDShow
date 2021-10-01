<?php

use Wikidata\Wikidata; // https://github.com/freearhey/wikidata
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
        // function for repeating publication fetch and data processing
        function getDNBref($refKey, $idn, $refIdns)
        {

            $urlRef = "https://services.dnb.de/sru/dnb?version=1.1&operation=searchRetrieve&query=$refKey%3D$idn&recordSchema=oai_dc&maximumRecords=100";

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

                // check on doublettes - if not, catch this object
                if (in_array($record_idn, $refIdns) !== true) {

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

            return $refIdns;
                      
        }

        //Param1 represents the wikidata-id

        global $wgScriptPath;
        global $wgDockerServer;

        $gnd = "";

        // if param1 is given, use that as gnd-id eg. "280275-2"
        if (empty($param1) !== true) {
            $gnd = $param1;
        }
        else // if param1 is NOT given, try to fetch gnd-id within article by Wikidata-ID & function "getData" via parameter P227
        {
            $language = wfMessage('language')->plain();
            $wikilanguage = $language . "wiki";
            $title = $parser->getTitle()->getText();
            $titleunderscores = $parser->getTitle()->getDBKey();
            // get wikidatalink from actual page
            if (empty($param2)) { // if param2 is not set, take the wikidatalink from the actual page

                $endpoint = "$wgDockerServer$wgScriptPath/api.php";
                $url = "$endpoint?action=ask&query=[[$titleunderscores]]|?Wikidata_ID|limit=5&format=json";
                $json_data = file_get_contents($url);
                $apiresponse = json_decode($json_data, true);
                try {
                    if (empty($apiresponse['query']['results'][$title]['printouts']['Wikidata ID'][0])) {
                        throw new Exception("not defined");
                    } else {
                        $wikidataentry = $apiresponse['query']['results'][$title]['printouts']['Wikidata ID'][0]; // get wikidatalink from api
                    }
                }
                //catch exception
                catch (Exception $e) {
                    return "No wikidata entry found";
                }
            } else {
                $wikidataentry = $param2;
            }

            $wikidata = new Wikidata(); //init object to get info from wikidata

            //check if we get valid information from wikidata
            try {
                if (empty($wikidata->get($wikidataentry, $language))) {
                    throw new Exception('not defined');
                } else {
                    $entity = $wikidata->get($wikidataentry, $language); // get data for entitiy (with Q-number)
                    $properties = $entity->properties->toArray(); // convert data to array to make handling easier
                }
            } catch (Exception $e) {
                return "wrong Wikidata ID";
            }

            $gnd = self::getData($properties, "P227");
            if ($gnd == "not defined") {
                return wfMessage('unknown')->plain();
            }
        }

        // #1 Getting the DNB-IDN by GND-ID

        $url_idn = "https://services.dnb.de/sru/authorities?version=1.1&operation=searchRetrieve&query=nid%3D$gnd&recordSchema=oai_dc";

        $xml_idn = simplexml_load_file($url_idn) or die("Can't connect to URL");

        // get DNB-IDN in MARC21-xml: separate controlfield with tag="001"

        $ns = $xml_idn->getNamespaces(true);

        // get DNB-IDN in oai_dc: separate dc:identifier with xsi:type="dnb:IDN"

        try {
            if (empty($xml_idn->records->record->recordData->dc)) {
                throw new Exception('not defined');
            } else {
                foreach ($xml_idn->records->record->recordData->dc as $record) {

                    $ns_dc = $record->children($ns['dc']);
        
                    // $ns_xsi = $ns_dc->children['http://www.w3.org/2001/XMLSchema-instance'];
        
                    // if (trim($ns_xsi->identifier['type'] == "dnb:IDN")) {
        
                    foreach ($ns_dc->identifier as $identifier) {
                        if ($identifier->attributes("xsi", TRUE)->type == "dnb:IDN") {
                            $idn = strval(strval($ns_dc->identifier));
                        }
                    }
        
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
        } catch (Exception $e) {
            return "wrong GND ID";
        }

    }

    // get bbf data by manually given on edit Wiki page
    public static function bbfshowlite(Parser $parser, $param1 = '', $param2 = '')
    {

        // function to get bbf object data

        function getBbfRef($urn, $bbfRefIdns)
        {
            $bbfURL = "https://scripta.bbf.dipf.de/viewer/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier=$urn";

            $xmlRef = @simplexml_load_file($bbfURL) or die("Can't connect to URL");

            // Namespaces: $ns for own use, register mets to XPath
            $ns = $xmlRef->getNamespaces(true);
   
            $refResult = "";
            try {
                if (empty($xmlRef->GetRecord->record->metadata)) {
                    throw new Exception('not defined');
                } else {

                    $metadata = $xmlRef->GetRecord->record->metadata;
                    $ns_oaidc = $metadata->children($ns['oai_dc']);
                    $oaidc = $ns_oaidc->dc;
                    $ns_dc = $oaidc->children($ns['dc']);

                    $record_title = strval($ns_dc->title);            
                    $record_date = strval($ns_dc->date);
                    $record_desc = strval($ns_dc->source);
                    $record_url = strval($ns_dc->identifier);

                    $record_desc = "'". $record_desc . "'";

                    $refResult = $refResult . "
                            |$record_title
                            |$record_date
                            |$record_url
                            |$record_desc
                            |-
                        ";

                    $GLOBALS["bbfOutput"] = $GLOBALS["bbfOutput"] . $refResult;

                    return $bbfRefIdns;
                }
            } catch (Exception $e) {
                $GLOBALS["bbfOutput"] = "wrong BBF URN";
                return $e->getMessage();
            }
            
        }

        global $bbfOutput;

        // Prepared Output base
        $bbfOutput = "
            {| class='wikitable'
            !Titel
            !Datum
            !Quelle
            !Beschreibung
            |-
            ";

        // Collection of fetched publication BBF-Object-URNs to prevent doublettes, used as return value to pass it with every ref-data-fetch
        $bbfRefIdns = array();

        // check if multiple URNs been given
        if (strpos($param1, ",")) {

            // split up the URNs by comma
            $bbfarray = explode(",", $param1);   

            // get data by multiple given URNs
            foreach ($bbfarray as $urn) {
                
                // check on doublettes
                if (in_array($urn, $bbfRefIdns) !== true ) {

                    array_push($bbfRefIdns, $urn);

                    getBbfRef($urn, $bbfRefIdns);
                }
            }
        }
        // get data by single given URN
        else {
            getBbfRef($param1, $bbfRefIdns);
        }

        return $bbfOutput;
    }

    public static function getData($properties = '', $pvalue = '')
    { // get data if p-value only has one value
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
