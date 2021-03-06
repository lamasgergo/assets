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
namespace Assets\Test\TestCase\Utility;

use Assets\Utility\AssetsCreator;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Reflection\ReflectionTrait;

/**
 * AssetsCreatorTest class
 */
class AssetsCreatorTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Plugin::load('TestPlugin');

        Configure::write('Assets.target', TMP . 'assets');
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Plugin::unload('TestPlugin');

        //Deletes all assets
        foreach (glob(Configure::read('Assets.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `__construct()` method
     * @test
     */
    public function testConstruct()
    {
        $asset = new AssetsCreator('test', 'css');

        $this->assertInstanceOf('Assets\Utility\AssetsCreator', $asset);

        $this->assertEquals('css', $this->getProperty($asset, 'type'));

        $paths = $this->getProperty($asset, 'paths');
        $this->assertEquals(1, count($paths));
        $this->assertEquals(WWW_ROOT . 'css', dirname($paths[0]));
        $this->assertEquals('test.css', basename($paths[0]));

        $asset = $this->getProperty($asset, 'asset');
        $this->assertEquals(Configure::read('Assets.target'), dirname($asset));
        $this->assertRegExp('/^[0-9a-z]+\.css$/', basename($asset));
    }

    /**
     * Test for `__construct()` method, passing a no existing file
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `webroot/css/noExistingFile.css` doesn't exist
     * @test
     */
    public function testConstructNoExistingFile()
    {
        new AssetsCreator('noExistingFile', 'css');
    }

    /**
     * Test for `__construct()` method, passing a no existing file from plugin
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `Plugin/TestPlugin/webroot/css/noExistingFile.css` doesn't exist
     * @test
     */
    public function testConstructNoExistingFileFromPlugin()
    {
        new AssetsCreator('TestPlugin.noExistingFile', 'css');
    }

    /**
     * Test for `__construct()` method, passing unsupported type
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Asset type `html` not supported
     * @test
     */
    public function testConstructUnsupportedType()
    {
        new AssetsCreator(null, 'html');
    }

    /**
     * Test for `_resolvePath()` method
     * @ŧest
     */
    public function testResolvePath()
    {
        $expected = [WWW_ROOT . 'css' . DS . 'test.css'];

        $asset = new AssetsCreator('test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('test.css', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('/css/test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('/css/test.css', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('subdir/test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $expected = [WWW_ROOT . 'css' . DS . 'subdir' . DS . 'test.css'];
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('/othercssdir/test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $expected = [WWW_ROOT . 'othercssdir' . DS . 'test.css'];
        $this->assertEquals($expected, $result);

        //Tests array
        $asset = new AssetsCreator([
            'test',
            'subdir/test',
            '/othercssdir/test',
        ], 'css');
        $result = $this->getProperty($asset, 'paths');
        $expected = [
            WWW_ROOT . 'css' . DS . 'test.css',
            WWW_ROOT . 'css' . DS . 'subdir' . DS . 'test.css',
            WWW_ROOT . 'othercssdir' . DS . 'test.css',
        ];
        $this->assertEquals($expected, $result);

        //Tests plugins
        $expected = [Plugin::path('TestPlugin') . 'webroot' . DS . 'css' . DS . 'test.css'];

        $asset = new AssetsCreator('TestPlugin.test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('TestPlugin.test.css', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('TestPlugin./css/test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $asset = new AssetsCreator('TestPlugin./css/test.css', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $expected = [Plugin::path('TestPlugin') . 'webroot' . DS . 'css' . DS . 'subdir' . DS . 'test.css'];
        $asset = new AssetsCreator('TestPlugin.subdir/test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        $expected = [Plugin::path('TestPlugin') . 'webroot' . DS . 'othercssdir' . DS . 'test.css'];
        $asset = new AssetsCreator('TestPlugin./othercssdir/test', 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);

        //Tests array
        $expected = [
            Plugin::path('TestPlugin') . 'webroot' . DS . 'css' . DS . 'test.css',
            Plugin::path('TestPlugin') . 'webroot' . DS . 'css' . DS . 'subdir' . DS . 'test.css',
            Plugin::path('TestPlugin') . 'webroot' . DS . 'othercssdir' . DS . 'test.css',
        ];
        $asset = new AssetsCreator([
            'TestPlugin.test',
            'TestPlugin.subdir/test',
            'TestPlugin./othercssdir/test'
        ], 'css');
        $result = $this->getProperty($asset, 'paths');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `_getAssetPath()` method
     * @test
     */
    public function testGetAssetPath()
    {
        $asset = (new AssetsCreator('test', 'css'));
        $result = $this->invokeMethod($asset, '_getAssetPath');
        $expected = Configure::read('Assets.target') . DS . sprintf('%s.%s', md5(serialize([
            [
                $file = WWW_ROOT . 'css' . DS . 'test.css',
                filemtime($file),
            ],
        ])), 'css');
        $this->assertEquals($expected, $result);

        //From plugin
        $asset = (new AssetsCreator('TestPlugin.test', 'css'));
        $result = $this->invokeMethod($asset, '_getAssetPath');
        $expected = Configure::read('Assets.target') . DS . sprintf('%s.%s', md5(serialize([
            [
                $file = Plugin::path('TestPlugin') . 'webroot' . DS . 'css' . DS . 'test.css',
                filemtime($file),
            ],
        ])), 'css');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `create()` method, using a css file
     * @test
     */
    public function testCreateWithCss()
    {
        $result = (new AssetsCreator('test', 'css'))->create();
        $this->assertRegExp('/^[a-z0-9]+$/', $result);

        $file = Configure::read('Assets.target') . DS . sprintf('%s.%s', $result, 'css');
        $this->assertFileExists($file);

        $expected = '#my-id{font-size:12px}.my-class{font-size:14px}';
        $this->assertStringEqualsFile($file, $expected);

        //Tests array
        $result = (new AssetsCreator(['test', 'test2'], 'css'))->create();
        $this->assertRegExp('/^[a-z0-9]+$/', $result);

        $file = Configure::read('Assets.target') . DS . sprintf('%s.%s', $result, 'css');
        $this->assertFileExists($file);

        $expected = '#my-id{font-size:12px}.my-class{font-size:14px}' .
            '#my-id2{font-size:16px}.my-class2{font-size:18px}';
        $this->assertStringEqualsFile($file, $expected);
    }

    /**
     * Test for `create()` method, using a js file
     * @test
     */
    public function testCreateWithJs()
    {
        $result = (new AssetsCreator('test', 'js'))->create();
        $this->assertRegExp('/^[a-z0-9]+$/', $result);

        $file = Configure::read('Assets.target') . DS . sprintf('%s.%s', $result, 'js');
        $this->assertFileExists($file);

        $expected = 'function other_alert()' . PHP_EOL .
            '{alert(\'Another alert\')}' . PHP_EOL .
            '$(function(){var msg=\'Ehi!\';alert(msg)})';
        $this->assertStringEqualsFile($file, $expected);

        //Tests array
        $result = (new AssetsCreator(['test', 'test2'], 'js'))->create();
        $this->assertRegExp('/^[a-z0-9]+$/', $result);

        $file = Configure::read('Assets.target') . DS . sprintf('%s.%s', $result, 'js');
        $this->assertFileExists($file);

        $expected = 'function other_alert()' . PHP_EOL .
            '{alert(\'Another alert\')}' . PHP_EOL .
            '$(function(){var msg=\'Ehi!\';alert(msg)});' .
            'var first=\'This is first\';' .
            'var second=\'This is second\';' .
            'alert(first+\' and \'+second)';
        $this->assertStringEqualsFile($file, $expected);
    }

    /**
     * Test for `create()` method. It tests the asset is created only if it
     *  does not exist
     * @test
     */
    public function testCreateReturnsExistingAsset()
    {
        //Creates the asset
        $result = (new AssetsCreator('test', 'css'))->create();

        //Sets the file path and the creation time
        $file = Configure::read('Assets.target') . DS . sprintf('%s.%s', $result, 'css');
        $time = filemtime($file);

        //Tries to create again the same asset. Now the creation time is the same
        $result = (new AssetsCreator('test', 'css'))->create();
        $this->assertEquals($time, filemtime($file));

        //Deletes asset and wait 1 second
        unlink($file);
        sleep(1);

        //Tries to create again the same asset. Now the creation time is different
        $result = (new AssetsCreator('test', 'css'))->create();
        $this->assertNotEquals($time, filemtime($file));
    }

    /**
     * Test for `create()` method with no existing target directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessageRegExp /^Failed to create file noExistingDir\/[a-z0-9]+\.css$/
     * @test
     */
    public function testCreateNoExistingTarget()
    {
        Configure::write('Assets.target', 'noExistingDir');

        (new AssetsCreator('test', 'css'))->create();
    }
}
