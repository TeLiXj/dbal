<?php

namespace Doctrine\Tests\DBAL\Functional;

use Doctrine\DBAL\Types\Type;

require_once __DIR__ . '/../../TestInit.php';

class TypeConversionTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    static private $typeCounter = 0;

    public function setUp()
    {
        parent::setUp();

        /* @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */
        $sm = $this->_conn->getSchemaManager();

        if (!$sm->tablesExist(array('type_conversion'))) {

            $table = new \Doctrine\DBAL\Schema\Table("type_conversion");
            $table->addColumn('id', 'integer', array('notnull' => false));
            $table->addColumn('test_string', 'string', array('notnull' => false));
            $table->addColumn('test_boolean', 'boolean', array('notnull' => false));
            $table->addColumn('test_bigint', 'bigint', array('notnull' => false));
            $table->addColumn('test_smallint', 'bigint', array('notnull' => false));
            $table->addColumn('test_datetime', 'datetime', array('notnull' => false));
            $table->addColumn('test_date', 'date', array('notnull' => false));
            $table->addColumn('test_time', 'time', array('notnull' => false));
            $table->addColumn('test_text', 'text', array('notnull' => false));
            $table->addColumn('test_array', 'array', array('notnull' => false));
            $table->addColumn('test_object', 'object', array('notnull' => false));
            $table->setPrimaryKey(array('id'));

            $sm->createTable($table);
        }
    }

    static public function dataIdempotentDataConversion()
    {
        $obj = new \stdClass();
        $obj->foo = "bar";
        $obj->bar = "baz";

        return array(
            array('string',     'ABCDEFGaaaBBB', 'string'),
            array('boolean',    true, 'bool'),
            array('boolean',    false, 'bool'),
            array('bigint',     12345678, 'string'),
            array('smallint',   123, 'int'),
            array('datetime',   new \DateTime('2010-04-05 10:10:10'), 'DateTime'),
            array('date',       new \DateTime('2010-04-05'), 'DateTime'),
            array('time',       new \DateTime('10:10:10'), 'DateTime'),
            array('text',       str_repeat('foo ', 1000), 'string'),
            array('array',      array('foo' => 'bar'), 'array'),
            array('object',     $obj, 'object'),
        );
    }

    /**
     * @dataProvider dataIdempotentDataConversion
     * @param string $type
     * @param mixed $originalValue
     * @param string $expectedPhpType
     */
    public function testIdempotentDataConversion($type, $originalValue, $expectedPhpType)
    {
        $columnName = "test_" . $type;
        $typeInstance = Type::getType($type);
        $insertionValue = $typeInstance->convertToDatabaseValue($originalValue, $this->_conn->getDatabasePlatform());

        $this->_conn->insert('type_conversion', array('id' => ++self::$typeCounter, $columnName => $insertionValue));

        $sql = "SELECT " . $columnName . " FROM type_conversion WHERE id = " . self::$typeCounter;
        $actualDbValue = $typeInstance->convertToPHPValue($this->_conn->fetchColumn($sql), $this->_conn->getDatabasePlatform());

        $this->assertType($expectedPhpType, $actualDbValue, "The expected type from the conversion to and back from the database should be " . $expectedPhpType);
        $this->assertEquals($originalValue, $actualDbValue, "Conversion between values should produce the same out as in value, but doesnt!");
    }
}