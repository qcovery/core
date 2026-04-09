<?php

/**
 * Console command: clean up record cache.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2020.
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
 * @package  Console
 * @author   Johannes Schultze <schultze@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace CleanUpUserData\Command\Util;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Db\Table\User;

/**
 * Console command: clean up record cache.
 *
 * @category VuFind
 * @package  Console
 * @author   Johannes Schultze <schultze@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class CleanUpUserDataCommand extends Command
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/cleanup_user_data';

    /**
     * Record table object
     *
     * @var User
     */
    protected $userTable;

    /**
     * Constructor
     *
     * @param User        $table Record table object
     * @param string|null $name  The name of the command; passing null means it
     * must be set in configure()
     */
    public function __construct(User $table, $name = null)
    {
        $this->userTable = $table;
        parent::__construct($name);
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('User data cleaner')
            ->setHelp('Removes unneeded user data records from the database.')
            ->setAliases(['util/cleanupuserdata'])
            ->addOption(
                'hours',
                null,
                InputOption::VALUE_REQUIRED,
                'hours to delete user data (default: 24)'
            );
    }

    /**
     * Clean up user data.
     *
     * @return void
     */
    public function cleanup()
    {
        $this->userTable->update(['firstname' => '']);
        $this->userTable->update(['lastname' => '']);
        $this->userTable->update(['cat_pass_enc' => '']);
        $this->userTable->update(['created' => '2000-01-01 00:00:00']);
        $this->userTable->update(['last_language' => '']);
        $this->userTable->update(['email' => '']);
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hours = $input->getOption('hours') ?? 24;

        $deleted = $this->userTable->cleanup($hours);
        $count = count($deleted);
        $output->writeln("$count records deleted.");
        return 0;
    }
}
