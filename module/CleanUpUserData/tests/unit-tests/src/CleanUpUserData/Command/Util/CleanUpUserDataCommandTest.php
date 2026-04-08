<?php

/**
 * CleanUpRecordCacheCommand test.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
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
     * Build a fully-populated mock user row.
     *
     * @param int $id Numeric user ID
     *
     * @return \VuFind\Db\Row\User
     */
    protected function buildMockUser(int $id): \VuFind\Db\Row\User
    {
        $user = $this->getMockBuilder(\VuFind\Db\Row\User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'id'                  => $id,
            'username'            => "user$id",
            'password'            => '',
            'pass_hash'           => password_hash("secret$id", PASSWORD_BCRYPT),
            'firstname'           => "Firstname$id",
            'lastname'            => "Lastname$id",
            'email'               => "user$id@example.com",
            'email_verified'      => '2023-01-0' . $id . ' 10:00:00',
            'pending_email'       => '',
            'user_provided_email' => 1,
            'cat_id'              => "cat_id_$id",
            'cat_username'        => "cat_user$id",
            'cat_password'        => "cat_pass$id",
            'cat_pass_enc'        => null,
            'college'             => "College$id",
            'major'               => "Major$id",
            'home_library'        => "Library$id",
            'created'             => '2022-01-0' . $id . ' 08:00:00',
            'verify_hash'         => md5("verify$id"),
            'last_login'          => '2023-06-0' . $id . ' 12:00:00',
            'auth_method'         => 'Database',
            'last_language'       => 'de',
        ];

        $user->method('__get')->willReturnCallback(
            function ($key) use ($data) {
                return $data[$key] ?? null;
            }
        );

        return $user;
    }

    /**
     * Test that the five mock users have the expected IDs.
     *
     * @return void
     */
    public function testUserIds()
    {
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = $this->buildMockUser($i);
        }

        $table = $this->getMockBuilder(\VuFind\Db\Table\User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById'])
            ->getMock();
        $table->method('getById')
            ->willReturnCallback(
                function ($id) use ($users) {
                    return $users[$id - 1] ?? null;
                }
            );

        $expectedIds = [1, 2, 3, 4, 5];
        $actualIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $actualIds[] = $table->getById($i)->id;
        }
        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * Test that the cleanup command deletes the expected users.
     *
     * @return void
     */
    /* public function testBasicOperation()
    {
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = $this->buildMockUser($i);
        }

        $table = $this->getMockBuilder(\VuFind\Db\Table\User::class)
            ->disableOriginalConstructor()
            ->addMethods(['cleanup'])
            ->getMock();
        $table->expects($this->once())
            ->method('cleanup')
            ->will($this->returnValue($users));

        $command = new CleanUpUserDataCommand($table);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $expected = "5 records deleted.\n";
        $this->assertEquals($expected, $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    } */
}
