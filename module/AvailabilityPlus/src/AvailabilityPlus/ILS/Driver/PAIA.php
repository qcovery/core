<?php
/**
 * PAIA ILS Driver for VuFind to get patron information
 *
 * PHP version 7
 *
 * Copyright (C) Oliver Goldschmidt, Magda Roos, Till Kinstler, André Lahmann 2013,
 * 2014, 2015.
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
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace DAIAplus\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * PAIA ILS Driver for VuFind to get patron information
 *
 * Holding information is obtained by DAIA, so it's not necessary to implement those
 * functions here; we just need to extend the DAIA driver.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class PAIA extends DAIA
{

}
