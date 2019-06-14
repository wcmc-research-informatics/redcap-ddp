README
======

This directory contains the distribution files of SUPER REDCap, which is the implementation name for 
Dynamic Data Pull at Weill Cornell Medicine. This includes the PHP files required to connect the REDCap
forms and database together. There is an additional constants.php file that is required but not included.
SUPER REDCap also requires a JSON configuration file for each project. I will explain these later.

SUPER REDCap also requires PHP7 to function. PHP5 is potentially supported if one is willing to write
the database connection methods that uses PHP5-exclusive functions.

Directories
-----------

Within the ddp folder there are directories. Scripts are broken up into these directories based on functionality:

config: holds all the JSON configuration files
dao: holds the data access objects required to transform database data into PHP objects
fields: handles the mapping between database columns and REDCap instruments
rest: web services
unit: unit tests devised during testing. Not required for running DDP.
utils: Miscellaneous files (this is where constants.php goes)

Do not set the sticky bit (rws) as part of the folder permissions or REDCap will be unauthorized to pull data!

Installation and Configuration
------------------------------

Drag and drop into the web root of your web server. Create a new class in utils/ called Constants.php with the following parameters:


	class Constants {
	
	  	private $CONFIG_FILES = "/var/www/html/ddp/redcap-ddp/config/";
        public  $FIELD_DICTIONARY = $CONFIG_FILES . "field_dictionary.json";
        private $JSON_STR = "_config.json";
        // The constructor creates a key-value array of redcap pid values to the absolute
        // path of the project's configuration file on disk.
        private $pidfiles = array();
    
        public function __construct(){
            $trim = function($string){ return str_replace($this->JSON_STR, "", $string); };
            $append = function($string){ return str_replace($string, $this->CONFIG_FILES . $string . $this->JSON_STR, $string); };
            $conf = array_map($trim, array_diff(scandir($this->CONFIG_FILES), [".", "..", "field_dictionary.json", "field_dictionary.json~"]));
            
            $this->pidfiles = array_map($append, array_combine( $conf , $conf ));
        }

        public function __get($property) {
            if (property_exists($this, $property)) {
                return $this->$property;
            }
        } 
	
		// Key is a string to identify target database. Value is another associative
		// array where the keys are attributes for connecting to the server and the
		// values are the values.
		public static $host = array("SERVER1" => array("Server" => "",
                                                  	"Username" => "",
                                                  	"Password" => "",
                                                  	"Database" => "DATABASE1",
                                                  	"Type" => "ORACLE")
                                             
                                   );
}

The value for "Type" is currently unused but will be supported in a later update. It should dictate what type
of database $host is connecting to.

You are required to create your own database connection class. This step is done in the db connect.php class in the utils/ directory. I have provided an example abstract class called "mssqldbconnect" and a implementation of this class in the form of "archdbconnect". Sites which utilize MSSQL can extend the "mssqldbconnect" class directly and create their own connection/disconnection methods. Other sites which leverage systems such as PostgresSQL or Oracle will have to write their own abstract class and concrete implementation in "dbconnect.php". 

The value chosen for the $source variable in the concrete implementation class (ex: "oradbconnect") should match the corresponding value in the getField() method in the REDCapFieldFormatter.php class, found underneath the fields/ directory. This value should also match the value given in "Database" field in the Constants.php class.

Now configure REDCap's DDP control center to look for the data and metadata web services in index.php. Index.php is smart enough to figure out what web service needs to be called. Index.php should not be called directly from the web browser; if DDP is set up properly the communication should "just happen". Please note that if you are not going to build out the authentication web service, do not insert index.php into the corresponding field in the control center. Doing this will cause authentication for all users to fail and the middleware will not work.

Observe the structure of the configuration files in the config/ dictionary. For your institution, you will need to adjust/create the already present dictionary terms to fit your requirements. The only requirement here is that once again the Source attribute in the field_dictionary should match up to the "SERVER1" value in $host, and that all configuration JSON files are appended with the suffix specified by the $JSONSTR variable. The Source attribute identifies what database connection should be used to find the data needed.

Observe the structure of the configuration files in the config/ directory. Each configuration file is specified with the pid, and the pid is also specified again within the JSON. Likewise, the name of the term is also repeated in the "field" element of the JSON. This repetition is intentional as DDP uses loops to process the JSON. Having these values available at multiple levels simplifies the amount of work required to link the configuration file and the field dictionary to the proper project.

All project configuration files should have the following attributes:

            "field": "mrn",					// Name of the field. Should match the name of the outer element
            "label": "MRN",					// Label shown in REDCap web client
            "description": null,			// An optional description
            "temporal": 0,					// 0 for one-time data field, 1 for temporal
            "category": "Demographics",	// An optional header to categorize fields in the REDCap web client
            "subcategory": null,			// An optional subheader for additional categorization
            "identifier": "1",				// Only MRN (or similar medical record number) should be 1. Otherwise, 0.
            "time_format": "",				// Specify how the date should be formatted with "Y-M-D" or "D-M-Y".
            "dictionary": "mrn",			// Name of the term in the dictionary with the corresponding SQL
            "map": ""							// If the data returned can have multiple values, use this field
            										// to map the database value to the redcap value (so db value is "key",
            										// REDCap value is "value")


Version History
---------------
Version 3.2
Released 6/14/2019

* An update to the field dictionary has been done to prevent SQL injection attacks against specially crafted REDCap case report forms.

Version 3.11
Released 4/23/2018

* README.md documentation updated with new documentation on how Constants should be set up. Changing references to variables within Constants class so that nothing breaks.

Version 3.10
Released 4/19/2018

* Generalized the db_connect.php class so that one class can be used to facilitate connections for each database platform. It is no longer required to implement a new subclass to connect to any new instances. Updated README.md with new documentation to reflect all changes. Implemented authentication web service.

Version 3.00
Released 2/27/2018

* Updated Microsoft SQL Server connection methods for PHP-7 compliance. Added security checks.

Version 2.30
Released 4/26/2017

* README.md documentation updated with answers to more common questions. The db_connect.php class was refactored so that projects which request terms from more than one source system renegotiates the connection to use the proper connector instead of the last system it was connected to, which is the default behavior for mssql-select-db function.

Version 2.20
Released 6/23/2016

* README.md documentation updated with answers to common questions. The db_connect.php classes have been renamed and refactored to make clearer how the parent and child classes interact, and what is required of a developer wishing to implement DDP at their institution.

Version 2.10
Released  4/18/2016

* Various documentation updates and bug fixes applied.

Version 2.01
Released  12/16/2015

* Added field 'biospecimen_id' to the field dictionary. This new field requires the project id from REDCap, so DDP has been adjusted to pass this information along properly and substitute it when necessary.

Version 2.00
Released  12/16/2015

* README.md file created
* DDP was extended to be able to handle SQL statements in the field dictionary that do a direct comparison with = and a fuzzy comparison with wildcards.
* The field dictionary was changed to use the raw Clarity tables instead of an aggregated table.

Version 1.0x
Released  7/27/2015

* First release on GitHub