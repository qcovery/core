<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of dodOrder
 *
 * @author liebenow
 */
namespace CaLief\Order;

class DodOrder {

    private $errMsg = "";
    private $transdb;
    private $arrVars;
    private $cfgxml;
    private $mailxml;
    private $mailbody = "";
    private $isXML = true;
    private $user;

    public function __construct($ConfigFile, $OrdermailFile, $user = null) {
        // var_dump('neu erstelltes Objekt!'.  date_timestamp_get(date_create()) .' ok');
        if (file_exists(dirname(__FILE__).'/'.$ConfigFile)) {
            $this->cfgxml = simplexml_load_file(dirname(__FILE__).'/'.$ConfigFile);
        } else {
            exit('Konnte ' . $ConfigFile . ' nicht öffnen.');
        }
        if (file_exists(dirname(__FILE__).'/'.$OrdermailFile)) {
            if (stristr($OrdermailFile, '.xml')) {
                $this->mailxml = simplexml_load_file(dirname(__FILE__).'/'.$OrdermailFile);
            } else {
                $this->mailbody = file_get_contents(dirname(__FILE__).'/'.$OrdermailFile);
                $this->isXML = false;
            }
        } else {
            exit('Konnte ' . $OrdermailFile . ' nicht öffnen.');
        }
        $this->user = $user;
    }

    public function getError() {
        return $this->errMsg;
    }

    public function setVars($varArr) {
        if (count($varArr) > 2) {
            $this->arrVars = $varArr;
            if ($this->isXML) {
                foreach ($this->mailxml->children() as $child) {
                    $elemName = $child->getName();
                    if ($this->arrVars[$elemName]) {
                        $this->mailbody .= $elemName . ": " . $this->replaceChars($this->arrVars[$elemName]) . "\r\n";
                    } else {
                        $this->mailbody .= $elemName . ": " . $this->replaceChars($this->mailxml->$elemName) . "\r\n";
                    }
                }
            } else {
                // ... replace values in mailbody
                /* error_log(print_r($varArr, true));
                if ($this->user) {
                    $varArr
                } */
                
                foreach ($varArr as $key => $var) {
                    $this->mailbody = str_ireplace('['.$key.']', $var, $this->mailbody);
                }
            }
        } else {
            $this->errMsg = "Fehler beim setzen der Variablen!";
            return FALSE;
        }
        
        return TRUE;
    }

    function checkEmailAddress($email_address) {
        $s = '/^[A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6}$/i';
        if (preg_match($s, $email_address)) {
            return TRUE;
        }
        $this->errMsg = "Bitte &uuml;berpr&uuml;fen Sie die angegebene E-Mailadresse!";
        return FALSE;
    }

    function sendOrderMail() {
        if ($this->mailbody != "") {
            $empfaenger = $this->cfgxml->MAIL->EMPFAENGER;
            $betreff = $this->cfgxml->MAIL->SUBJECT;
            $header = 'From: ' . $this->cfgxml->MAIL->ABSENDER . "\r\n" .
                      'Reply-To: ' . $this->cfgxml->MAIL->ABSENDER . "\r\n" .
                      'X-Mailer: PHP/' . phpversion() . "\r\n" .
                      'Content-Type: text/plain; charset=utf-8' . "\r\n" .
                      'Content-Transfer-Encoding: 7bit';

            mail($empfaenger, $betreff, $this->mailbody, $header);
            $this->errMsg = "Bestellung erfolgreich versendet!";
            return TRUE;
        } else {
            $this->errMsg .= "E-Mail leer !?";
            return FALSE;
        }
    }
    
    function getValue($tag) {
        $value = FALSE;
        $value = $this->mailxml->$tag;
        return $value;
    }

    private function prepareData($data) {
        $searchArray = array('/A\xcc\x88/', '/A\xcc\x8a/', '/O\xcc\x88/', '/U\xcc\x88/', '/a\xcc\x88/', '/a\xcc\x8a/', '/c\xcc\xa6/', '/e\xcc\x82/', '/o\xcc\x88/', '/u\xcc\x88/', '/\xc2\x98/', '/\xc2\x9c/');
        $translateArray = array('Ä', 'Å', 'Ö' ,'Ü' ,'ä' ,'å', 'ç', 'ê', 'ö' ,'ü' ,'' ,'' );
        return preg_replace($searchArray, $translateArray, $data);
    }

    function replaceChars($string) {
        $string = utf8_decode($this->prepareData($string));
        $string = str_ireplace('ß', 'ss', $string);
        $string = str_ireplace('ä', 'ae', $string);
        $string = str_ireplace('ö', 'oe', $string);
        $string = str_ireplace('ü', 'ue', $string);
        $string = str_ireplace('Ä', 'Ae', $string);
        $string = str_ireplace('Ö', 'Oe', $string);
        $string = str_ireplace('Ü', 'Ue', $string);
        return $string;
    }
}
