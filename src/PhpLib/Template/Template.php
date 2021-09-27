<?php
// from www.massassi.com/php/articles/template_engines/
//   License: public domain
//   modified to php5 - wal3
//     - remove deprecated "var" keyword
//     - to use  __construct

namespace PhpLib\Template;

class Template {
    private $vars;  // holds all the template variables
    private $file;  // template file

    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */
    function __construct( $file=NULL ) {
        $this->file = $file;
    }

    /**
     * Set a template variable.
     */
    function set($name, $value) {
        //$this->vars[$name] = is_object($value) ? $value->fetch() : $value;
        $this->vars[$name] = $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     */
    function fetch($file=NULL) {
        if(!$file) $file = $this->file;

        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        include($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }
}
?>
