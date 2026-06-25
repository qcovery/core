<?php

    /**
     * Clean up user data.
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
     * @package  Tests
     * @author   Johannes Schultze <schultze@effective-webwork.de>
     * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
     * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
     */

namespace CleanUpUserDataTest\Command\Util;

use Symfony\Component\Console\Tester\CommandTester;
use CleanUpUserData\Command\Util\CleanUpUserDataCommand;

/**
 * CleanUpUserDataCommand test.
 *
 * @category VuFind
 * @package  Tests
 * @author   Johannes Schultze <schultze@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class CleanUpUserDataCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a \VuFind\Db\Row\User object.
     *
     * @param int $id Numeric user ID
     *
     * @return \VuFind\Db\Row\User
     */
    protected function buildPopulatedUserRow(int $id): \VuFind\Db\Row\User
    {
        $data = [
            'id'                  => $id,
            'username'            => "user$id",
            'password'            => '',
            'pass_hash'           => password_hash("secret$id", PASSWORD_BCRYPT),
            'firstname'           => "Firstname$id",
            'lastname'            => "Lastname$id",
            'email'               => "user$id@example.com",
            'email_verified'      => "2023-01-0$id 10:00:00",
            'pending_email'       => '',
            'user_provided_email' => 1,
            'cat_id'              => "cat_id_$id",
            'cat_username'        => "cat_user$id",
            'cat_password'        => "cat_pass$id",
            'cat_pass_enc'        => "cat_pass$id",
            'college'             => "College$id",
            'major'               => "Major$id",
            'home_library'        => "Library$id",
            'created'             => "2022-01-0$id 08:00:00",
            'verify_hash'         => md5("verify$id"),
            'last_login'          => "2023-06-0 $id 12:00:00",
            'auth_method'         => 'Database',
            'last_language'       => 'de',
        ];

        $adapter = $this->getMockBuilder(\Laminas\Db\Adapter\Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder(\VuFind\Db\Row\User::class)
            ->setConstructorArgs([$adapter])
            ->onlyMethods(['save'])
            ->getMock();

        $user->populate($data, true);

        return $user;
    }

    /**
     * Test that the cleanup() method calls update on the user table with selected values.
     *
     * @return void
     */
    public function testCleanup()
    {
        // Build user rows with non-empty values beforehand (mock database).
        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $users[] = $this->buildPopulatedUserRow($i);
        }

        // Verify that fields have values before cleanup.
        foreach ($users as $index => $user) {
            $id = $index + 1;
            $this->assertEquals("Firstname$id", $user['firstname']);
            $this->assertEquals("Lastname$id", $user['lastname']);
            $this->assertEquals("cat_pass$id", $user['cat_pass_enc']);
            $this->assertEquals("user$id@example.com", $user['email']);
            $this->assertEquals("2022-01-0$id 08:00:00", $user['created']);
            $this->assertEquals("de", $user['last_language']);
        }

        $table = $this->getMockBuilder(\VuFind\Db\Table\User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['update', 'select'])
            ->getMock();

        // update() applies the given field changes to all rows in the mock database.
        $table->method('update')
            ->willReturnCallback(function ($data) use (&$users) {
                foreach ($users as $user) {
                    foreach ($data as $field => $value) {
                        $user[$field] = $value;
                    }
                }
                return count($users);
            });

        // select() returns the current state of the mock database.
        $table->method('select')
            ->willReturnCallback(function () use (&$users) {
                return $users;
            });

        $config = new \Laminas\Config\Config([
            'CleanUp' => [
                'firstname' => '',
                'lastname' => '',
                'cat_pass_enc' => '',
                'created' => '2000-01-01 00:00:00',
                'last_language' => '',
                'email' => '',
            ]
        ]);
        $command = new CleanUpUserDataCommand($table, $config);
        $command->cleanup();

        // Fetch users via select and verify fields are cleared afterwards.
        $result = $table->select([]);
        foreach ($result as $user) {
            $this->assertEquals('', $user['firstname']);
            $this->assertEquals('', $user['lastname']);
            $this->assertEquals('', $user['cat_pass_enc']);
            $this->assertEquals('', $user['email']);
            $this->assertEquals('2000-01-01 00:00:00', $user['created']);
            $this->assertEquals('', $user['last_language']);
        }
    }

    /**
     * Test that the cleanup command cleans up the expected users.
     *
     * @return void
     */
    public function testBasicOperation()
    {
        $table = $this->getMockBuilder(\VuFind\Db\Table\User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['update'])
            ->getMock();
        $table->expects($this->once())
            ->method('update')
            ->will($this->returnValue(5));

        $config = new \Laminas\Config\Config(['Global' => ['default_hours' => 24]]);
        $command = new CleanUpUserDataCommand($table, $config);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $expected = "5 row(s) in user table cleaned up successfully. (24 hours)\n";
        $this->assertEquals($expected, $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
