<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\Type\BinaryInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Type\DecimalInterface;
use Drupal\Core\TypedData\Type\DurationInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\UriInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;

// cspell:ignore eins

/**
 * Tests the functionality of all core data types.
 *
 * @group TypedData
 */
class TypedDataTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'field', 'file', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Creates a typed data object and ensures it implements TypedDataInterface.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  protected function createTypedData($definition, $value = NULL, $name = NULL) {
    if (is_array($definition)) {
      $definition = DataDefinition::create($definition['type']);
    }
    $data = $this->typedDataManager->create($definition, $value, $name);
    $this->assertInstanceOf(TypedDataInterface::class, $data);
    return $data;
  }

  /**
   * Tests the basics around constructing and working with typed data objects.
   */
  public function testGetAndSet(): void {
    // Boolean type.
    $typed_data = $this->createTypedData(['type' => 'boolean'], TRUE);
    $this->assertInstanceOf(BooleanInterface::class, $typed_data);
    $this->assertTrue($typed_data->getValue(), 'Boolean value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(FALSE);
    $this->assertFalse($typed_data->getValue(), 'Boolean value was changed.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $this->assertIsString($typed_data->getString());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Boolean wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // String type.
    $value = $this->randomString();
    $typed_data = $this->createTypedData(['type' => 'string'], $value);
    $this->assertInstanceOf(StringInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'String value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = $this->randomString();
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getValue(), 'String value was changed.');
    $this->assertEquals(0, $typed_data->validate()->count());
    // Funky test.
    $this->assertIsString($typed_data->getString());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'String wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(['no string']);
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // Integer type.
    $value = rand();
    $typed_data = $this->createTypedData(['type' => 'integer'], $value);
    $this->assertInstanceOf(IntegerInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'Integer value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = rand();
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getValue(), 'Integer value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Integer wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // Decimal type.
    $value = (string) (mt_rand(1, 10000) / 100);
    $typed_data = $this->createTypedData(['type' => 'decimal'], $value);
    $this->assertInstanceOf(DecimalInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'Decimal value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = (string) (mt_rand(1, 10000) / 100);
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getValue(), 'Decimal value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Decimal wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(0);
    $this->assertSame('0.0', $typed_data->getCastedValue(), '0.0 casted value was fetched.');
    $typed_data->setValue('1337e0');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Scientific notation is not allowed in numeric type.');
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // Float type.
    $value = 123.45;
    $typed_data = $this->createTypedData(['type' => 'float'], $value);
    $this->assertInstanceOf(FloatInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'Float value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = 678.90;
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getValue(), 'Float value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Float wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // Date Time type; values with timezone offset.
    $value = '2014-01-01T20:00:00+00:00';
    $typed_data = $this->createTypedData(['type' => 'datetime_iso8601'], $value);
    $this->assertInstanceOf(DateTimeInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue());
    $this->assertEquals($typed_data->getDateTime()->format('c'), $typed_data->getValue(), 'Value representation of a date is ISO 8601');
    $this->assertSame('+00:00', $typed_data->getDateTime()->getTimezone()->getName());
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = '2014-01-02T20:00:00+00:00';
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getDateTime()->format('c'), 'Date value was changed and set by an ISO8601 date.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $this->assertSame('2014-01-02', $typed_data->getDateTime()->format('Y-m-d'), 'Date value was changed and set by date string.');
    $this->assertSame('+00:00', $typed_data->getDateTime()->getTimezone()->getName());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime(), 'Date wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');
    // Check implementation of DateTimeInterface.
    $typed_data = $this->createTypedData(['type' => 'datetime_iso8601'], '2014-01-01T20:00:00+00:00');
    $this->assertInstanceOf(DrupalDateTime::class, $typed_data->getDateTime());
    $this->assertSame('+00:00', $typed_data->getDateTime()->getTimezone()->getName());
    $typed_data->setDateTime(new DrupalDateTime('2014-01-02T20:00:00+00:00'));
    $this->assertSame('+00:00', $typed_data->getDateTime()->getTimezone()->getName());
    $this->assertEquals('2014-01-02T20:00:00+00:00', $typed_data->getValue());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime());

    // Date Time type; values without timezone offset.
    $value = '2014-01-01T20:00';
    $typed_data = $this->createTypedData(['type' => 'datetime_iso8601'], $value);
    $this->assertInstanceOf(DateTimeInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'Date value was fetched.');
    // @todo Uncomment this assertion in https://www.drupal.org/project/drupal/issues/2716891.
    // $this->assertEquals($typed_data->getDateTime()->format('c'), $typed_data->getValue(), 'Value representation of a date is ISO 8601');
    $this->assertSame('UTC', $typed_data->getDateTime()->getTimezone()->getName());
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = '2014-01-02T20:00';
    $typed_data->setValue($new_value);
    // @todo Uncomment this assertion in https://www.drupal.org/project/drupal/issues/2716891.
    // $this->assertTrue($typed_data->getDateTime()->format('c') === $new_value, 'Date value was changed and set by an ISO8601 date.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $this->assertSame('2014-01-02', $typed_data->getDateTime()->format('Y-m-d'), 'Date value was changed and set by date string.');
    $this->assertSame('UTC', $typed_data->getDateTime()->getTimezone()->getName());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime(), 'Date wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');
    // Check implementation of DateTimeInterface.
    $typed_data = $this->createTypedData(['type' => 'datetime_iso8601'], '2014-01-01T20:00:00');
    $this->assertInstanceOf(DrupalDateTime::class, $typed_data->getDateTime());
    $this->assertSame('UTC', $typed_data->getDateTime()->getTimezone()->getName());
    // When setting datetime without a timezone offset, the default timezone is
    // used (Australia/Sydney). DateTimeIso8601::setDateTime() converts this
    // DrupalDateTime object to a string using ::format('c'), it gets converted
    // to an offset. The offset for Australia/Sydney is +11:00.
    $typed_data->setDateTime(new DrupalDateTime('2014-01-02T20:00:00'));
    $this->assertSame('+11:00', $typed_data->getDateTime()->getTimezone()->getName());
    $this->assertEquals('2014-01-02T20:00:00+11:00', $typed_data->getValue());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime());

    // Timestamp type.
    $requestTime = \Drupal::time()->getRequestTime();
    $value = $requestTime;
    $typed_data = $this->createTypedData(['type' => 'timestamp'], $value);
    $this->assertInstanceOf(DateTimeInterface::class, $typed_data);
    $this->assertSame($typed_data->getValue(), $value, 'Timestamp value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $new_value = $requestTime + 1;
    $typed_data->setValue($new_value);
    $this->assertSame($typed_data->getValue(), $new_value, 'Timestamp value was changed and set.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime(), 'Timestamp wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');
    // Check implementation of DateTimeInterface.
    $typed_data = $this->createTypedData(['type' => 'timestamp'], $requestTime);
    $this->assertInstanceOf(DrupalDateTime::class, $typed_data->getDateTime());
    $typed_data->setDateTime(DrupalDateTime::createFromTimestamp($requestTime + 1));
    $this->assertEquals($requestTime + 1, $typed_data->getValue());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDateTime());

    // DurationIso8601 type.
    $value = 'PT20S';
    $typed_data = $this->createTypedData(['type' => 'duration_iso8601'], $value);
    $this->assertInstanceOf(DurationInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'DurationIso8601 value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('P40D');
    $this->assertEquals(40, $typed_data->getDuration()->d, 'DurationIso8601 value was changed and set by duration string.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'DurationIso8601 wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');
    // Check implementation of DurationInterface.
    $typed_data = $this->createTypedData(['type' => 'duration_iso8601'], 'PT20S');
    $this->assertInstanceOf(\DateInterval::class, $typed_data->getDuration());
    $typed_data->setDuration(new \DateInterval('P40D'));
    // @todo Should we make this "nicer"?
    $this->assertEquals('P0Y0M40DT0H0M0S', $typed_data->getValue());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDuration());

    // Time span type.
    $value = 20;
    $typed_data = $this->createTypedData(['type' => 'timespan'], $value);
    $this->assertInstanceOf(DurationInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'Time span value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(60 * 60 * 4);
    $this->assertEquals(14400, $typed_data->getDuration()->s, 'Time span was changed');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Time span wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');
    // Check implementation of DurationInterface.
    $typed_data = $this->createTypedData(['type' => 'timespan'], 20);
    $this->assertInstanceOf(\DateInterval::class, $typed_data->getDuration());
    $typed_data->setDuration(new \DateInterval('PT4H'));
    $this->assertEquals(60 * 60 * 4, $typed_data->getValue());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getDuration());

    // URI type.
    $uri = 'http://example.com/foo/';
    $typed_data = $this->createTypedData(['type' => 'uri'], $uri);
    $this->assertInstanceOf(UriInterface::class, $typed_data);
    $this->assertSame($uri, $typed_data->getValue(), 'URI value was fetched.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue($uri . 'bar.txt');
    $this->assertSame($uri . 'bar.txt', $typed_data->getValue(), 'URI value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'URI wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');
    $typed_data->setValue('public://field/image/Photo on 4-28-14 at 12.01 PM.jpg');
    $this->assertEquals(0, $typed_data->validate()->count(), 'Filename with spaces is valid.');

    // Generate some files that will be used to test the binary data type.
    $files = [];
    for ($i = 0; $i < 3; $i++) {
      $path = "public://example_$i.png";
      \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', $path);
      $image = File::create(['uri' => $path]);
      $image->save();
      $files[] = $image;
    }

    // Email type.
    $value = $this->randomString();
    $typed_data = $this->createTypedData(['type' => 'email'], $value);
    $this->assertInstanceOf(StringInterface::class, $typed_data);
    $this->assertSame($value, $typed_data->getValue(), 'Email value was fetched.');
    $new_value = 'test@example.com';
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getValue(), 'Email value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Email wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalidAtExample.com');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // Binary type.
    $typed_data = $this->createTypedData(['type' => 'binary'], $files[0]->getFileUri());
    $this->assertInstanceOf(BinaryInterface::class, $typed_data);
    $this->assertIsResource($typed_data->getValue());
    $this->assertEquals(0, $typed_data->validate()->count());
    // Try setting by URI.
    $typed_data->setValue($files[1]->getFileUri());
    $this->assertEquals(fgets(fopen($files[1]->getFileUri(), 'r')), fgets($typed_data->getValue()), 'Binary value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    // Try setting by resource.
    $typed_data->setValue(fopen($files[2]->getFileUri(), 'r'));
    $this->assertEquals(fgets($typed_data->getValue()), fgets(fopen($files[2]->getFileUri(), 'r')), 'Binary value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Binary wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue('invalid');
    $this->assertEquals(1, $typed_data->validate()->count(), 'Validation detected invalid value.');

    // Any type.
    $value = ['foo'];
    $typed_data = $this->createTypedData(['type' => 'any'], $value);
    $this->assertSame($value, $typed_data->getValue(), 'Any value was fetched.');
    $new_value = 'test@example.com';
    $typed_data->setValue($new_value);
    $this->assertSame($new_value, $typed_data->getValue(), 'Any value was changed.');
    $this->assertIsString($typed_data->getString());
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue(), 'Any wrapper is null-able.');
    $this->assertEquals(0, $typed_data->validate()->count());
    // We cannot test invalid values as everything is valid for the any type,
    // but make sure an array or object value passes validation also.
    $typed_data->setValue(['entry']);
    $this->assertEquals(0, $typed_data->validate()->count());
    $typed_data->setValue((object) ['entry']);
    $this->assertEquals(0, $typed_data->validate()->count());
  }

  /**
   * Tests using typed data lists.
   */
  public function testTypedDataLists(): void {
    // Test working with an existing list of strings.
    $value = ['one', 'two', 'three'];
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);
    $this->assertEquals($value, $typed_data->getValue(), 'List value has been set.');
    // Test iterating.
    $count = 0;
    foreach ($typed_data as $item) {
      $this->assertInstanceOf(TypedDataInterface::class, $item);
      $count++;
    }
    $this->assertEquals(3, $count);

    // Test getting the string representation.
    $this->assertEquals('one, two, three', $typed_data->getString());
    $typed_data[1] = '';
    $this->assertEquals('one, three', $typed_data->getString());

    // Test using array access.
    $this->assertEquals('one', $typed_data[0]->getValue());
    $typed_data[] = 'four';
    $this->assertEquals('four', $typed_data[3]->getValue());
    $this->assertEquals(4, $typed_data->count());
    $this->assertTrue(isset($typed_data[0]));
    $this->assertTrue(!isset($typed_data[6]));

    // Test isEmpty and cloning.
    $this->assertFalse($typed_data->isEmpty());
    $clone = clone $typed_data;
    $this->assertSame($typed_data->getValue(), $clone->getValue());
    $this->assertNotSame($typed_data[0], $clone[0]);
    $clone->setValue([]);
    $this->assertTrue($clone->isEmpty());

    // Make sure that resetting the value using NULL results in an empty array.
    $clone->setValue([]);
    $typed_data->setValue(NULL);
    $this->assertSame([], $typed_data->getValue());
    $this->assertSame([], $clone->getValue());

    // Test dealing with NULL items.
    $typed_data[] = NULL;
    $this->assertTrue($typed_data->isEmpty());
    $this->assertCount(1, $typed_data);
    $typed_data[] = '';
    $this->assertFalse($typed_data->isEmpty());
    $this->assertCount(2, $typed_data);
    $typed_data[] = 'three';
    $this->assertFalse($typed_data->isEmpty());
    $this->assertCount(3, $typed_data);

    $this->assertEquals([NULL, '', 'three'], $typed_data->getValue());
    // Test unsetting.
    unset($typed_data[1]);
    $this->assertCount(2, $typed_data);
    // Check that items were shifted.
    $this->assertEquals('three', $typed_data[1]->getValue());

    // Getting a not set list item returns NULL, and does not create a new item.
    $this->assertNull($typed_data[2]);
    $this->assertCount(2, $typed_data);

    // Test setting the list with less values.
    $typed_data->setValue(['one']);
    $this->assertEquals(1, $typed_data->count());

    // Test setting invalid values.
    try {
      $typed_data->setValue('string');
      $this->fail('No exception has been thrown when setting an invalid value.');
    }
    catch (\Exception) {
      // Expected exception; just continue testing.
    }
  }

  /**
   * Tests the filter() method on typed data lists.
   */
  public function testTypedDataListsFilter(): void {
    // Check that an all-pass filter leaves the list untouched.
    $value = ['zero', 'one'];
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);
    $typed_data->filter(function (TypedDataInterface $item) {
      return TRUE;
    });
    $this->assertEquals(2, $typed_data->count());
    $this->assertEquals('zero', $typed_data[0]->getValue());
    $this->assertEquals(0, $typed_data[0]->getName());
    $this->assertEquals('one', $typed_data[1]->getValue());
    $this->assertEquals(1, $typed_data[1]->getName());

    // Check that a none-pass filter empties the list.
    $value = ['zero', 'one'];
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);
    $typed_data->filter(function (TypedDataInterface $item) {
      return FALSE;
    });
    $this->assertEquals(0, $typed_data->count());

    // Check that filtering correctly renumbers elements.
    $value = ['zero', 'one', 'two'];
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);
    $typed_data->filter(function (TypedDataInterface $item) {
      return $item->getValue() !== 'one';
    });
    $this->assertEquals(2, $typed_data->count());
    $this->assertEquals('zero', $typed_data[0]->getValue());
    $this->assertEquals(0, $typed_data[0]->getName());
    $this->assertEquals('two', $typed_data[1]->getValue());
    $this->assertEquals(1, $typed_data[1]->getName());
  }

  /**
   * Tests using a typed data map.
   */
  public function testTypedDataMaps(): void {
    // Test working with a simple map.
    $value = [
      'one' => 'eins',
      'two' => 'beta',
      'three' => 'gamma',
    ];
    $definition = MapDataDefinition::create()
      ->setPropertyDefinition('one', DataDefinition::create('string'))
      ->setPropertyDefinition('two', DataDefinition::create('string'))
      ->setPropertyDefinition('three', DataDefinition::create('string'));

    $typed_data = $this->createTypedData($definition, $value);

    // Test iterating.
    $count = 0;
    foreach ($typed_data as $item) {
      $this->assertInstanceOf(TypedDataInterface::class, $item);
      $count++;
    }
    $this->assertEquals(3, $count);

    // Test retrieving metadata.
    $this->assertEquals(array_keys($value), array_keys($typed_data->getDataDefinition()->getPropertyDefinitions()));
    $definition = $typed_data->getDataDefinition()->getPropertyDefinition('one');
    $this->assertEquals('string', $definition->getDataType());
    $this->assertNull($typed_data->getDataDefinition()->getPropertyDefinition('invalid'));

    // Test getting and setting properties.
    $this->assertEquals('eins', $typed_data->get('one')->getValue());
    $this->assertEquals($value, $typed_data->toArray());
    $typed_data->set('one', 'alpha');
    $this->assertEquals('alpha', $typed_data->get('one')->getValue());
    // Make sure the update is reflected in the value of the map also.
    $value = $typed_data->getValue();
    $this->assertEquals(['one' => 'alpha', 'two' => 'beta', 'three' => 'gamma'], $value);

    $properties = $typed_data->getProperties();
    $this->assertEquals(array_keys($value), array_keys($properties));
    $this->assertSame($typed_data->get('one'), $properties['one'], 'Properties are identical.');

    // Test setting a not defined property. It shouldn't show up in the
    // properties, but be kept in the values.
    $typed_data->setValue(['foo' => 'bar']);
    $this->assertEquals(['one', 'two', 'three'], array_keys($typed_data->getProperties()));
    $this->assertEquals(['foo', 'one', 'two', 'three'], array_keys($typed_data->getValue()));

    // Test getting the string representation.
    $typed_data->setValue(['one' => 'eins', 'two' => '', 'three' => 'gamma']);
    $this->assertEquals('eins, gamma', $typed_data->getString());

    // Test isEmpty and cloning.
    $this->assertFalse($typed_data->isEmpty());
    $clone = clone $typed_data;
    $this->assertSame($typed_data->getValue(), $clone->getValue());
    $this->assertNotSame($typed_data->get('one'), $clone->get('one'));
    $clone->setValue([]);
    $this->assertTrue($clone->isEmpty());

    // Make sure the difference between NULL (not set) and an empty array is
    // kept.
    $typed_data->setValue(NULL);
    $this->assertNull($typed_data->getValue());
    $typed_data->setValue([]);
    $value = $typed_data->getValue();
    $this->assertIsArray($value);

    // Test accessing invalid properties.
    $typed_data->setValue($value);
    try {
      $typed_data->get('invalid');
      $this->fail('No exception has been thrown when getting an invalid value.');
    }
    catch (\Exception) {
      // Expected exception; just continue testing.
    }

    // Test setting invalid values.
    try {
      $typed_data->setValue('invalid');
      $this->fail('No exception has been thrown when setting an invalid value.');
    }
    catch (\Exception) {
      // Expected exception; just continue testing.
    }

    // Test adding a new property to the map.
    $typed_data->getDataDefinition()->setPropertyDefinition('zero', DataDefinition::create('any'));
    $typed_data->set('zero', 'null');
    $this->assertEquals('null', $typed_data->get('zero')->getValue());
    $definition = $typed_data->get('zero')->getDataDefinition();
    $this->assertEquals('any', $definition->getDataType(), 'Definition for a new map entry returned.');
  }

  /**
   * Tests typed data validation.
   */
  public function testTypedDataValidation(): void {
    $definition = DataDefinition::create('integer')
      ->setConstraints([
        'Range' => ['min' => 5],
      ]);
    $violations = $this->typedDataManager->create($definition, 10)->validate();
    $this->assertEquals(0, $violations->count());

    $integer = $this->typedDataManager->create($definition, 1);
    $violations = $integer->validate();
    $this->assertEquals(1, $violations->count());

    // Test translating violation messages.
    $message = t('This value should be %limit or more.', ['%limit' => 5]);
    $this->assertEquals($message, $violations[0]->getMessage(), 'Translated violation message retrieved.');
    $this->assertEquals('', $violations[0]->getPropertyPath());
    $this->assertSame($integer, $violations[0]->getRoot(), 'Root object returned.');

    // Test translating violation messages when pluralization is used.
    $definition = DataDefinition::create('string')
      ->setConstraints([
        'Length' => ['min' => 10],
      ]);
    $violations = $this->typedDataManager->create($definition, "short")->validate();
    $this->assertEquals(1, $violations->count());
    $message = t('This value is too short. It should have %limit characters or more.', ['%limit' => 10]);
    $this->assertEquals($message, $violations[0]->getMessage(), 'Translated violation message retrieved.');

    // Test having multiple violations.
    $definition = DataDefinition::create('integer')
      ->setConstraints([
        'Range' => ['min' => 5],
        'Null' => [],
      ]);
    $violations = $this->typedDataManager->create($definition, 10)->validate();
    $this->assertEquals(1, $violations->count());
    $violations = $this->typedDataManager->create($definition, 1)->validate();
    $this->assertEquals(2, $violations->count());

    // Test validating property containers and make sure the NotNull and Null
    // constraints work with typed data containers.
    $definition = BaseFieldDefinition::create('integer')
      ->setConstraints(['NotNull' => []]);
    $field_item = $this->typedDataManager->create($definition, ['value' => 10]);
    $violations = $field_item->validate();
    $this->assertEquals(0, $violations->count());

    $field_item = $this->typedDataManager->create($definition, ['value' => 'no integer']);
    $violations = $field_item->validate();
    $this->assertEquals(1, $violations->count());
    $this->assertEquals('0.value', $violations[0]->getPropertyPath());

    // Test that the field item may not be empty.
    $field_item = $this->typedDataManager->create($definition);
    $violations = $field_item->validate();
    $this->assertEquals(1, $violations->count());

    // Test the Null constraint with typed data containers.
    $definition = BaseFieldDefinition::create('float')
      ->setConstraints(['Null' => []]);
    $field_item = $this->typedDataManager->create($definition, ['value' => 11.5]);
    $violations = $field_item->validate();
    $this->assertEquals(1, $violations->count());
    $field_item = $this->typedDataManager->create($definition);
    $violations = $field_item->validate();
    $this->assertEquals(0, $violations->count());

    // Test getting constraint definitions by type.
    $definitions = $this->typedDataManager->getValidationConstraintManager()->getDefinitionsByType('entity');
    $this->assertTrue(isset($definitions['EntityType']), 'Constraint plugin found for type entity.');
    $this->assertTrue(isset($definitions['Null']), 'Constraint plugin found for type entity.');
    $this->assertTrue(isset($definitions['NotNull']), 'Constraint plugin found for type entity.');

    $definitions = $this->typedDataManager->getValidationConstraintManager()->getDefinitionsByType('string');
    $this->assertFalse(isset($definitions['EntityType']), 'Constraint plugin not found for type string.');
    $this->assertTrue(isset($definitions['Null']), 'Constraint plugin found for type string.');
    $this->assertTrue(isset($definitions['NotNull']), 'Constraint plugin found for type string.');

    // Test automatic 'required' validation.
    $definition = DataDefinition::create('integer')
      ->setRequired(TRUE);
    $violations = $this->typedDataManager->create($definition)->validate();
    $this->assertEquals(1, $violations->count());
    $violations = $this->typedDataManager->create($definition, 0)->validate();
    $this->assertEquals(0, $violations->count());

    // Test validating a list of a values and make sure property paths starting
    // with "0" are created.
    $definition = BaseFieldDefinition::create('integer');
    $violations = $this->typedDataManager->create($definition, [['value' => 10]])->validate();
    $this->assertEquals(0, $violations->count());
    $violations = $this->typedDataManager->create($definition, [['value' => 'string']])->validate();
    $this->assertEquals(1, $violations->count());

    $this->assertEquals('string', $violations[0]->getInvalidValue());
    $this->assertSame('0.value', $violations[0]->getPropertyPath());
  }

  /**
   * Tests the last() method on typed data lists.
   */
  public function testTypedDataListsLast(): void {
    // Create an ItemList with two string items.
    $value = ['zero', 'one'];
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);

    // Assert that the last item is the second one ('one').
    $this->assertEquals('one', $typed_data->last()->getValue());

    // Add another item to the list and check the last item.
    $value[] = 'two';
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), $value);
    $this->assertEquals('two', $typed_data->last()->getValue());

    // Check behavior with an empty list.
    $typed_data = $this->createTypedData(ListDataDefinition::create('string'), []);
    $this->assertNull($typed_data->last(), 'Empty list should return NULL.');
  }

}
