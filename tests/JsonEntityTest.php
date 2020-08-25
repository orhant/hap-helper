<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 25.08.20 21:42:55
 */

/** @noinspection PhpMethodMayBeStaticInspection */
declare(strict_types = 1);
namespace dicr\tests;

use PHPUnit\Framework\TestCase;

/**
 * Class TelegramEntityTest
 */
class JsonEntityTest extends TestCase
{
    /**
     *
     */
    public function testAttributeFields()
    {
        $sampleFields = TestJsonEntity::sampleAttributeFields();
        $actualFields = (new TestJsonEntity())->attributeFields();

        self::assertEquals($sampleFields, $actualFields);
    }

    /**
     *
     */
    public function testGetJson()
    {
        $sampleJson = TestJsonEntity::sampleJson();
        unset($sampleJson['ids']);

        $actualJson = TestJsonEntity::sampleEntity()->json;

        self::assertEquals($sampleJson, $actualJson);
    }

    /**
     *
     */
    public function testSetJson()
    {
        $sampleEntity = TestJsonEntity::sampleEntity();
        $sampleEntity->ids = null;

        $actualEntity = new TestJsonEntity([
            'json' => TestJsonEntity::sampleJson()
        ]);

        self::assertEquals($sampleEntity, $actualEntity);
    }
}
