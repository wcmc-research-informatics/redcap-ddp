<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

/**
 * This class is used to bring together the information in the field_dictionary
 * file and a project-specific configuration file. This creates a "configdict",
 * which has all of the information necessary to process a request.
 *
 * @author     Marcos Davila (mzd2016@med.cornell.edu)
 * @since      v0.1
 * @package    fields
 * @license    Open Source
 */
class FieldDictionary
{
    private $field_dictionary = array();
    private $config;

    /**
     * Tries to resolve missing class file dependencies at runtime
     *
     * @param $className
     * @return bool
     */
    function __autoload($className)
    {
        if (file_exists('utils/' . $className . '.php')) {
            require 'utils/' . $className . '.php';
            return true;
        } elseif (file_exists('fields/' . $className . '.php')) {
            require 'fields/' . $className . '.php';
            return true;
        } elseif (file_exists('dao/' . $className . '.php')) {
            require 'dao/' . $className . '.php';
            return true;
        } else {
            return false;
        }
    }

    /**
     * Calls the autoloader to import required PHP files, then
     * instantiates the field dictionary.
     *
     * @param $pid
     * @param $id
     * @param $fields
     * @param $config
     */
    public function __construct($pid, $id, $fields, $config)
    {
        $registered = spl_autoload_register(array($this, '__autoload'));

        if (!$registered) {
            exit('The autoloader was unable to resolve a missing dependency.');
        }

        $this->config = $config;
        $this->initFieldDictionary($pid, $id, $fields);
    }

    /**
     * Returns the field dictionary.
     *
     * @return array
     */
    public function getDictionary()
    {
        return $this->field_dictionary;
    }

    /**
     * Combines the elements from the configuration file
     * and the field dictionary.
     *
     * @return array
     */
    public function getDictionaryItemsFromSources()
    {
        $result = array();
        foreach ($this->config as $c) {
            // Map field of interest from configuration file
            // to the term it's known as in the dictionary
            // then combine them together
            $r = $this->field_dictionary [$c ["dictionary"]];
            $r = array_merge($c, $r);

            if (is_null($r)) {
                continue;
            }

            $result [$c ["dictionary"]] = $r;
        }
        return $result;
    }

    /*
     * Initializes the field directory by reading in the JSON file which
     * defines DDP supported data fields and how to retrieve their information
     * from the appropriate source system.
     */
    private function initFieldDictionary($pid, $id, $fields)
    {
        // Read in dictionary from JSON and do string replacement for the MRN value.
        // It is faster to do this while it is still in JSON.
        $string = file_get_contents("/var/www/html/ddp/redcap-ddp/config/field_dictionary.json");

        // replacement for NYP_MRN = id
        $string = str_replace("= id", "= " . $id, $string);

        // replacement for identity_id = id (hence iid)
        $string = str_replace("= iid", "= '" . $id . "'", $string);

        $string = str_replace("%id%", "'%" . $id . "%'", $string);
        $string = str_replace("=pid", "='" . $pid . "'", $string);

        // However, we need to decode the JSON to populate temporal timestamp values effectively
        $this->field_dictionary = json_decode($string, true);

        // Combine the elements from the configuration file to the dictionary
        $this->field_dictionary = $this->getDictionaryItemsFromSources();

        // Get the names of all fields requested from REDCap
        $fieldname = array();
        foreach ($fields as $f) {
            $fieldname [] = $f ['field'];
        }

        // Remove from the field dictionary fields not requested
        // Normally, foreach operates on a copy of your array so
        // any changes made don't affect the actual array.
        // Need to unset the values via $this->field_dictionary[$key];
        foreach ($this->field_dictionary as $key => $fd) {
            if (!in_array($fd ['field'], $fieldname)) {
                unset ($this->field_dictionary [$key]);
            }
        }

        // String replace on the elements requested to substitute
        // in MRN and timestamps.
        foreach ($fields as $f) {
            foreach ($this->field_dictionary as $fd) {
                // Compare against the field value provided in the configuration
                if ($f['field'] === $fd['field'] && $fd['temporal'] == 1) {
                    // Get the dictionary name from the configuration file
                    $dict_element_name = $fd['dictionary'];
                    // String replace the SQL for the matching dictionary element
                    $this->field_dictionary[$dict_element_name]["SQL"] = str_replace("timestamp_min", "'" . date('Y-m-d', strtotime($f ["timestamp_min"])) . "'", $this->field_dictionary[$dict_element_name]["SQL"]);
                    $this->field_dictionary[$dict_element_name]["SQL"] = str_replace("timestamp_max", "'" . date('Y-m-d', strtotime($f ["timestamp_max"])) . "'", $this->field_dictionary[$dict_element_name]["SQL"]);
                }
            }
        }
    }
}
