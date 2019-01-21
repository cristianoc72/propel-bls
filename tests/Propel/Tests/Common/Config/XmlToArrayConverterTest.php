<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

use org\bovigo\vfs\vfsStream;
use Propel\Common\Config\XmlToArrayConverter;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class XmlToArrayConverterTest extends TestCase
{
    use VfsTrait;
    use DataProviderTrait;

    /**
     * @dataProvider providerForXmlToArrayConverter
     */
    public function testConvertFromString($xml, $expected)
    {
        $actual = XmlToArrayConverter::convert($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverter
     */
    public function testConvertFromFile($xml, $expected)
    {
        $file = $this->newFile('testconvert.xml', $xml);
        $actual = XmlToArrayConverter::convert($file->url());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverterXmlInclusions
     */
    public function testConvertFromFileWithXmlInclusion($xmlLoad, $xmlInclude, $expected)
    {
        $this->newFile('testconvert.xml', $xmlLoad);
        $this->newFile('testconvert_include.xml', $xmlInclude);
        $actual = XmlToArrayConverter::convert(vfsStream::url('root/testconvert.xml'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testInvalidFileNameThrowsException()
    {
        XmlToArrayConverter::convert(1);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testInexistentFileThrowsException()
    {
        XmlToArrayConverter::convert('nonexistent.xml');
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testInvalidXmlThrowsException()
    {
        $invalidXml = <<< XML
No xml
only plain text
---------
XML;
        XmlToArrayConverter::convert($invalidXml);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\XmlParseException
     * @expectedExceptionMessage An error occurred while parsing XML configuration file:
     */
    public function testErrorInXmlThrowsException()
    {
        $xmlWithError = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML;
        XmlToArrayConverter::convert($xmlWithError);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\XmlParseException
     * @expectedExceptionMessage Some errors occurred while parsing XML configuration file:
    - Fatal Error 76: Opening and ending tag mismatch: titles line 4 and title
    - Fatal Error 73: expected '>'
    - Fatal Error 5: Extra content at the end of the document
     */
    public function testMultipleErrorsInXmlThrowsException()
    {
        $xmlWithErrors = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</moviess>
XML;
        XmlToArrayConverter::convert($xmlWithErrors);
    }

    public function testEmptyFileReturnsEmptyArray()
    {
        $file = $this->newFile('empty.xml', '');
        $actual = XmlToArrayConverter::convert($file->url());

        $this->assertEquals([], $actual);
    }
}
