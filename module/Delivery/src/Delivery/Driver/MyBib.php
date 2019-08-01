<?php
/**
 * Factory for MultiBackend ILS driver.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Delivery\Driver;

class MyBib implements DriverInterface {

    protected $session;

    protected $config;

/*
$res = xmlrpc_server_create();
$xmlRpcData = xmlrpc_encode($data);


$resopnse = xmlrpc_encode($xmlRpcResponse);
 */


    /**
     * Set configuration.
     *
     * Set the configuration for the driver.
     *
     * @param array $config Configuration array (usually loaded from a VuFind .ini
     * file whose name corresponds with the driver class name).
     *
     * @return void
     */
    public function setConfig($config) {
        $this->config = $config;
    }

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @return void
     */
    public function init() {
/*
        $res = xmlrpc_server_create();
        
        Server mit  Pfad des Skriptes : http://esx-48.gbv.de/edoc_test/xmlrpc_server.php
        Kennung und Passwort: Katalogplus 

        register mit user, pw, client-Kennung
        login 
*/
    }

    public function prepareOrder($user) {
        
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @throws \VuFind\Exception\ILS
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function sendOrder($order) {
        $url = 'http://esx-48.gbv.de/edoc_test/xmlrpc_server.php';

        $rpcClient = hash('md5', 'beluga-core');
        $rpcUser = 'Katalogplus';
        $rpcPass = 'Katalogplus';
        
        $method = 'service.register';
        $parameters = ['register_struct' => ['register_user' => $rpcUser, 'register_pwd' => $rpcPass, 'register_mac' => $rpcClient]];
//        $parameters = [$rpcUser, $rpcPass, $rpcClient];

        $request = xmlrpc_encode_request($method, $parameters);

        echo $request;

        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-type: text/xml', 'content' => $request]]);
        $file = file_get_contents($url, false, $context);
        $response = xmlrpc_decode($file);
        if ($response && xmlrpc_is_fault($response)) {
            echo "xmlrpc: $response[faultString] ($response[faultCode])";
        } else {
            print_r($response);
        }
        die;
    }
}