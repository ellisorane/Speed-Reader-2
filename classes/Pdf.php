<?php


class Pdf {
    private $conn;
    private $table = 'pdfs';
    public $id;
    public $pdf;

    // Get DB conn
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // convert pdf to string for speed reader
    public function stringifyPdf () {
        

    }

    // delete stringified pdf content
    public function deleteStringifiedPdf () {

    }

    // keep progress of current pdf (May need to add this in a different class)
    public function progress () {

    }
}