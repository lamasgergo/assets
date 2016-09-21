<?php
/**
 * This file is part of Assets.
 *
 * Assets is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Assets is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Assets.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace Assets\Test\TestCase\View\Helper;

use Assets\Utility\AssetsCreator as BaseAssetsCreator;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Extends `Assets\Utility\AssetsCreator` and makes the `_parsePaths()` method
 *  as a public method
 */
class AssetsCreator extends BaseAssetsCreator
{
    /**
     * Makes the `_parsePaths()` method as a public method
     * @param string|array $paths String or array of css/js files
     * @param string $extension Extension (`css` or `js`)
     * @return array
     * @uses Assets\Utility\AssetsCreator::_parsePaths()
     */
    public static function parsePaths($paths, $extension)
    {
        return parent::_parsePaths($paths, $extension);
    }
}

/**
 * AssetsCreatorTest class
 */
class AssetsCreatorTest extends TestCase
{
    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        //Deletes all assets
        foreach (glob(ASSETS . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `_parsePaths()` method
     * @return void
     * @test
     */
    public function testParsePaths()
    {
        $result = AssetsCreator::parsePaths('test', 'css');
        $expected = [
            [
                'path' => $file = APP . 'webroot' . DS . 'css' . DS . 'test.css',
                'time' => filemtime($file),
            ],
        ];
        $this->assertEquals($expected, $result);

        $result = AssetsCreator::parsePaths('/css/test', 'css');
        $this->assertEquals($expected, $result);

        $result = AssetsCreator::parsePaths([
            'test',
            'subdir/test',
            '/othercssdir/test',
        ], 'css');
        $paths = [
            'css' . DS . 'test.css',
            'css' . DS . 'subdir' . DS . 'test.css',
            'othercssdir' . DS . 'test.css',
        ];
        $expected = array_map(function ($path) {
            $path = APP . 'webroot' . DS . $path;

            return ['path' => $path, 'time' => filemtime($path)];
        }, $paths);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `_parsePaths()` method with files from plugin
     * @return void
     * @test
     */
    public function testParsePathsPlugin()
    {
        Plugin::load('TestPlugin');

        $result = AssetsCreator::parsePaths([
            'TestPlugin.test',
            'TestPlugin.subdir/test',
            'TestPlugin./othercssdir/test',
        ], 'css');
        $paths = [
            'css' . DS . 'test.css',
            'css' . DS . 'subdir' . DS . 'test.css',
            'othercssdir' . DS . 'test.css',
        ];
        $expected = array_map(function ($path) {
            $path = APP . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot' . DS . $path;

            return ['path' => $path, 'time' => filemtime($path)];
        }, $paths);
        $this->assertEquals($expected, $result);

        Plugin::unload('TestPlugin');
    }

    /**
     * Test for `_parsePaths()` method, with no existing file
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @return void
     * @test
     */
    public function testParsePathsNoExistingFile()
    {
        AssetsCreator::parsePaths('noExistingFile', 'css');
    }

    /**
     * Test for `_parsePaths()` method, with no existing plugin
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @return void
     * @test
     */
    public function testParsePathsNoExistingPlugin()
    {
        AssetsCreator::parsePaths('noExistingPlugin.test', 'css');
    }

    /**
     * Test for `_parsePaths()` method, with no existing file from plugin
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @return void
     * @test
     */
    public function testParsePathsNoExistingPluginFile()
    {
        AssetsCreator::parsePaths('TestPlugin.noExistingFile', 'css');
    }

    /**
     * Test for `css()` method
     * @return void
     * @test
     */
    public function testCss()
    {
        $result = AssetsCreator::css('test');
        $expected = md5(serialize([
            [
                'path' => $file = APP . 'webroot' . DS . 'css' . DS . 'test.css',
                'time' => filemtime($file),
            ],
        ]));
        $this->assertEquals($expected, $result);

        $file = ASSETS . DS . sprintf('%s.%s', $result, 'css');
        $expected = '#my-id{font-size:12px}.my-class{font-size:14px}';
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $expected);

        $result = AssetsCreator::css(['test', 'test2']);
        $expected = md5(serialize([
            [
                'path' => $file = APP . 'webroot' . DS . 'css' . DS . 'test.css',
                'time' => filemtime($file),
            ],
            [
                'path' => $file = APP . 'webroot' . DS . 'css' . DS . 'test2.css',
                'time' => filemtime($file),
            ],
        ]));
        $this->assertEquals($expected, $result);

        $file = ASSETS . DS . sprintf('%s.%s', $result, 'css');
        $expected = '#my-id{font-size:12px}.my-class{font-size:14px}' .
            '#my-id2{font-size:16px}.my-class2{font-size:18px}';
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $expected);
    }

    /**
     * Test for `script()` method
     * @return void
     * @test
     */
    public function testScript()
    {
        $result = AssetsCreator::script('test');
        $expected = md5(serialize([
            [
                'path' => $file = APP . 'webroot' . DS . 'js' . DS . 'test.js',
                'time' => filemtime($file),
            ],
        ]));
        $this->assertEquals($expected, $result);

        $file = ASSETS . DS . sprintf('%s.%s', $result, 'js');
        $expected = 'function other_alert()' . PHP_EOL .
            '{alert(\'Another alert\')}' . PHP_EOL .
            '$(function(){var msg=\'Ehi!\';alert(msg)})';
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $expected);

        $result = AssetsCreator::script(['test', 'test2']);
        $expected = md5(serialize([
            [
                'path' => $file = APP . 'webroot' . DS . 'js' . DS . 'test.js',
                'time' => filemtime($file),
            ],
            [
                'path' => $file = APP . 'webroot' . DS . 'js' . DS . 'test2.js',
                'time' => filemtime($file),
            ],
        ]));
        $this->assertEquals($expected, $result);

        $file = ASSETS . DS . sprintf('%s.%s', $result, 'js');
        $expected = 'function other_alert()' . PHP_EOL .
            '{alert(\'Another alert\')}' . PHP_EOL .
            '$(function(){var msg=\'Ehi!\';alert(msg)});' .
            'var first=\'This is first\';' .
            'var second=\'This is second\';' .
            'alert(first+\' and \'+second)';
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $expected);
    }
}
