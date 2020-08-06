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

class Dod implements DriverInterface {

    protected $config;

    protected $viewRenderer;

    protected $mailer;

    protected $mailErrors = [];

    public function __construct($viewRenderer, \VuFind\Mailer\Mailer $mailer) {
        $this->viewRenderer = $viewRenderer;
        $this->mailer = $mailer;
    }

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
        
    }

    public function prepareOrder($user)
    {
        $orderData = [];
        $orderData['clientName'] = $user->firstname . ' ' . $user->lastname;
        $orderData['contactPersonName'] = $user->firstname . ' ' . $user->lastname;
        $orderData['clientIdentifier'] = $user->cat_id;
        $orderData['delEmailAddress'] = $user->delivery_email;
        return $orderData;
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
    public function sendOrder($orderData) {
        $orderTemplate = $this->config['orderTemplate'];
        $orderData = $this->viewRenderer->render('Order/' . $orderTemplate, $orderData);
        $config = $this->config;
        if ($this->mailer->send($config['orderMailTo'], $config['orderMailFrom'], $config['orderSubject'], $orderData)) {
            return 'per Email';
        } else {
            return null;
        }
    }

    public function getErrors() {
        $errors = $this->mailErrors;
        $this->mailErrors = [];
        return $errors;
    }
}
