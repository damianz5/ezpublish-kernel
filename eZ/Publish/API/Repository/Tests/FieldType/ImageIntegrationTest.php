<?php
/**
 * File contains: eZ\Publish\API\Repository\Tests\FieldType\ImageIntegrationTest class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\API\Repository\Tests\FieldType;

use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;

/**
 * Integration test for use field type
 *
 * @group integration
 * @group field-type
 */
class ImageIntegrationTest extends FileSearchBaseIntegrationTest
{
    /**
     * Stores the loaded image path for copy test.
     */
    protected static $loadedImagePath;

    /**
     * IOService storage prefix for the tested Type's files
     * @var string
     */
    protected static $storagePrefixConfigKey = 'image_storage_prefix';

    protected function getStoragePrefix()
    {
        return $this->getConfigValue( self::$storagePrefixConfigKey );
    }

    /**
     * Sets up fixture data.
     *
     * @return array
     */
    protected function getFixtureData()
    {
        return array(
            'create' => array(
                'fileName' => 'Icy-Night-Flower.jpg',
                'inputUri' => ( $path = __DIR__ . '/_fixtures/image.jpg' ),
                'alternativeText' => 'My icy flower at night',
                'fileSize' => filesize( $path )
            ),
            'update' => array(
                'fileName' => 'Blue-Blue-Blue.png',
                'inputUri' => ( $path = __DIR__ . '/_fixtures/image.png' ),
                'alternativeText' => 'Such a blue …',
                'fileSize' => filesize( $path ),
            ),
        );
    }

    /**
     * Get name of tested field type
     *
     * @return string
     */
    public function getTypeName()
    {
        return 'ezimage';
    }

    /**
     * Get expected settings schema
     *
     * @return array
     */
    public function getSettingsSchema()
    {
        return array();
    }

    /**
     * Get a valid $fieldSettings value
     *
     * @return mixed
     */
    public function getValidFieldSettings()
    {
        return array();
    }

    /**
     * Get $fieldSettings value not accepted by the field type
     *
     * @return mixed
     */
    public function getInvalidFieldSettings()
    {
        return array(
            'somethingUnknown' => 0,
        );
    }

    /**
     * Get expected validator schema
     *
     * @return array
     */
    public function getValidatorSchema()
    {
        return array(
            'FileSizeValidator' => array(
                'maxFileSize' => array(
                    'type'    => 'int',
                    'default' => false,
                ),
            )
        );
    }

    /**
     * Get a valid $validatorConfiguration
     *
     * @return mixed
     */
    public function getValidValidatorConfiguration()
    {
        return array(
            'FileSizeValidator' => array(
                'maxFileSize' => 2 * 1024 * 1024, // 2 MB
            ),
        );
    }

    /**
     * Get $validatorConfiguration not accepted by the field type
     *
     * @return mixed
     */
    public function getInvalidValidatorConfiguration()
    {
        return array(
            'StringLengthValidator' => array(
                'minStringLength' => new \stdClass(),
            )
        );
    }

    /**
     * Get initial field data for valid object creation
     *
     * @return mixed
     */
    public function getValidCreationFieldData()
    {
        $fixtureData = $this->getFixtureData();
        return new ImageValue( $fixtureData['create'] );
    }

    /**
     * Asserts that the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was stored and loaded correctly.
     *
     * @param Field $field
     *
     * @return void
     */
    public function assertFieldDataLoadedCorrect( Field $field )
    {
        $this->assertInstanceOf(
            'eZ\\Publish\\Core\\FieldType\\Image\\Value',
            $field->value
        );

        $fixtureData = $this->getFixtureData();
        $expectedData = $fixtureData['create'];

        // Will be nullified by external storage
        $expectedData['inputUri'] = null;

        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );

        $this->assertTrue(
            $this->uriExistsOnIO( $field->value->uri ),
            "Asserting that {$field->value->uri} exists."
        );

        self::$loadedImagePath = $field->value->id;
    }

    /**
     * Get field data which will result in errors during creation
     *
     * This is a PHPUnit data provider.
     *
     * The returned records must contain of an error producing data value and
     * the expected exception class (from the API or SPI, not implementation
     * specific!) as the second element. For example:
     *
     * <code>
     * array(
     *      array(
     *          new DoomedValue( true ),
     *          'eZ\\Publish\\API\\Repository\\Exceptions\\ContentValidationException'
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array[]
     */
    public function provideInvalidCreationFieldData()
    {
        return array(
            // will fail because the provided file doesn't exist, and fileSize/fileName won't be set
            array(
                new ImageValue(
                    array(
                        'inputUri' => __DIR__ . '/_fixtures/nofile.png',
                    )
                ),
                'eZ\\Publish\\Core\\Base\\Exceptions\\InvalidArgumentException',
            )
        );
    }

    /**
     * Get update field externals data
     *
     * @return array
     */
    public function getValidUpdateFieldData()
    {
        $fixtureData = $this->getFixtureData();
        return new ImageValue( $fixtureData['update'] );
    }

    /**
     * Get externals updated field data values
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function assertUpdatedFieldDataLoadedCorrect( Field $field )
    {
        $this->assertInstanceOf(
            'eZ\\Publish\\Core\\FieldType\\Image\\Value',
            $field->value
        );

        $fixtureData = $this->getFixtureData();
        $expectedData = $fixtureData['update'];

        // Will change during storage
        $expectedData['inputUri'] = null;

        $expectedData['uri'] = $field->value->uri;

        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );

        $this->assertTrue(
            $this->uriExistsOnIO( $field->value->uri ),
            "Asserting that file {$field->value->uri} exists"
        );
    }

    /**
     * Get field data which will result in errors during update
     *
     * This is a PHPUnit data provider.
     *
     * The returned records must contain of an error producing data value and
     * the expected exception class (from the API or SPI, not implementation
     * specific!) as the second element. For example:
     *
     * <code>
     * array(
     *      array(
     *          new DoomedValue( true ),
     *          'eZ\\Publish\\API\\Repository\\Exceptions\\ContentValidationException'
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array[]
     */
    public function provideInvalidUpdateFieldData()
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     *
     * @param Field $field
     */
    public function assertCopiedFieldDataLoadedCorrectly( Field $field )
    {
        $this->assertFieldDataLoadedCorrect( $field );

        $this->assertEquals(
            self::$loadedImagePath,
            $field->value->id
        );
    }

    /**
     * Get data to test to hash method
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the original value assigned to the
     * first index and the expected hash result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          new MyValue( true ),
     *          array( 'myValue' => true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array
     */
    public function provideToHashData()
    {
        return array(
            array(
                new ImageValue(
                    array(
                        'inputUri' => ( $path = __DIR__ . '/_fixtures/image.jpg' ),
                        'fileName' => 'Icy-Night-Flower.jpg',
                        'alternativeText' => 'My icy flower at night',
                    )
                ),
                array(
                    'inputUri' => $path,
                    'path' => $path,
                    'fileName' => 'Icy-Night-Flower.jpg',
                    'alternativeText' => 'My icy flower at night',
                    'fileSize' => null,
                    'id' => null,
                    'imageId' => null,
                    'uri' => null,
                    'width' => null,
                    'height' => null
                ),
            ),
            array(
                new ImageValue(
                    array(
                        'id' => $path = 'var/test/storage/images/file.png',
                        'fileName' => 'Icy-Night-Flower.jpg',
                        'alternativeText' => 'My icy flower at night',
                        'fileSize' => 23,
                        'imageId' => '1-2',
                        'uri' => "/$path",
                        'width' => 123,
                        'height' => 456
                    )
                ),
                array(
                    'id' => $path,
                    'path' => $path,
                    'fileName' => 'Icy-Night-Flower.jpg',
                    'alternativeText' => 'My icy flower at night',
                    'fileSize' => 23,
                    'inputUri' => null,
                    'imageId' => '1-2',
                    'uri' => "/$path",
                    'width' => 123,
                    'height' => 456
                ),
            ),
        );
    }

    /**
     * Get expectations for the fromHash call on our field value
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function provideFromHashData()
    {
        $fixture = $this->getFixtureData();
        return array(
            array(
                $fixture['create'],
                $this->getValidCreationFieldData()
            ),
        );
    }

    public function testInherentCopyForNewLanguage()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $type = $this->createContentType(
            $this->getValidFieldSettings(),
            $this->getValidValidatorConfiguration(),
            array(),
            // Causes a copy of the image value for each language in legacy
            // storage
            array( 'isTranslatable' => false )
        );

        $draft = $this->createContent( $this->getValidCreationFieldData(), $type );

        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->initialLanguageCode = 'ger-DE';
        $updateStruct->setField( 'name', 'Sindelfingen' );

        // Automatically creates a copy of the image field in the back ground
        $updatedDraft = $contentService->updateContent( $draft->versionInfo, $updateStruct );

        $paths = array();
        foreach ( $updatedDraft->getFields() as $field )
        {
            if ( $field->fieldDefIdentifier === 'data' )
            {
                $paths[$field->languageCode] = $field->value->uri;
            }
        }

        $this->assertTrue(
            isset( $paths['eng-US'] ) && isset( $paths['ger-DE'] ),
            "Failed asserting that file path for all languages were found in draft"
        );

        $this->assertEquals(
            $paths['eng-US'],
            $paths['ger-DE']
        );

        $contentService->deleteContent( $updatedDraft->contentInfo );

        foreach ( $paths as $uri )
        {
            self::assertFalse(
                $this->uriExistsOnIO( $uri ),
                "$uri has not been removed"
            );
        }
    }

    public function providerForTestIsEmptyValue()
    {
        return array(
            array( new ImageValue ),
        );
    }

    public function providerForTestIsNotEmptyValue()
    {
        return array(
            array(
                $this->getValidCreationFieldData()
            ),
        );
    }

    /**
     * Covers EZP-23080
     */
    public function testUpdatingImageMetadataOnlyWorks()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $type = $this->createContentType(
            $this->getValidFieldSettings(),
            $this->getValidValidatorConfiguration(),
            array()
        );

        $draft = $this->createContent( $this->getValidCreationFieldData(), $type );

        /** @var ImageValue $imageFieldValue */
        $imageFieldValue = $draft->getFieldValue( 'data' );
        $initialValueImageUri = $imageFieldValue->uri;

        // update alternative text
        $imageFieldValue->alternativeText = __METHOD__;
        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->setField( 'data', $imageFieldValue );
        $updatedDraft = $contentService->updateContent( $draft->versionInfo, $updateStruct );

        /** @var ImageValue $updatedImageValue */
        $updatedImageValue = $updatedDraft->getFieldValue( 'data' );

        self::assertEquals( $initialValueImageUri, $updatedImageValue->uri );
        self::assertEquals( __METHOD__, $updatedImageValue->alternativeText );
    }

    /**
     * covers EZP-23152
     */
    public function testThatRemovingDraftDoesntRemovePublishedImages()
    {
        $repository = $this->getRepository();

        // Load services
        $contentService  = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        // create content and publish image
        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier( 'image' ),
            'eng-GB'
        );
        $contentCreateStruct->setField( 'name', 'EZP23152_1' );
        $contentCreateStruct->setField( 'image', $this->getValidCreationFieldData() );

        $locationCreateStruct = $locationService->newLocationCreateStruct( 2 );
        $content = $contentService->createContent( $contentCreateStruct, array( $locationCreateStruct ) );
        $content = $contentService->publishVersion( $content->getVersionInfo() );

        $originalFileUri = $content->fields['image']['eng-GB']->uri;

        $this->assertTrue(
            $this->uriExistsOnIO( $originalFileUri ),
            "Asserting image file $originalFileUri exists."
        );

        // Create a new draft and update it
        $updatedDraft = $contentService->createContentDraft( $content->contentInfo );
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = 'eng-GB';
        $contentUpdateStruct->setField( 'name', 'EZP23152_2' );
        $updatedDraft = $contentService->updateContent( $updatedDraft->versionInfo, $contentUpdateStruct );

        // remove the newly published content version, verify that the original file exists
        $contentService->deleteVersion( $updatedDraft->versionInfo, 2 );
        $this->assertTrue(
            $this->uriExistsOnIO( $originalFileUri ),
            "Asserting original image file $originalFileUri exists."
        );

        // delete content
        $contentService->deleteContent( $content->contentInfo );
        $this->assertFalse(
            $this->uriExistsOnIO( $originalFileUri ),
            "Asserting image file $originalFileUri has been removed."
        );
    }

    public function testUpdateImageAltTextOnly()
    {
        $repository = $this->getRepository();

        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier( 'image' );
        $createStruct = $contentService->newContentCreateStruct( $contentType, 'eng-GB' );

        $createStruct->setField( 'name', __METHOD__ );
        $createStruct->setField(
            'image',
            new ImageValue(
                [
                    'inputUri' => __DIR__ . '/_fixtures/image.jpg',
                    'fileName' => 'image.jpg',
                    'fileSize' => filesize( __DIR__ . '/_fixtures/image.jpg' ),
                    'alternativeText' => 'Initial alternative text'
                ]
            )
        );

        $content = $contentService->createContent(
            $createStruct,
            [$locationService->newLocationCreateStruct( 2 )]
        );

        $imageField = $content->getFieldValue( 'image' );
        $imageField->alternativeText = 'Updated alternative text';

        $contentService->publishVersion( $content->getVersionInfo() );

        $updateStruct = $contentService->newContentUpdateStruct();
        $updateStruct->setField( 'image', $imageField );

        $newVersion = $contentService->createContentDraft( $content->contentInfo );
        $contentService->updateContent( $newVersion->versionInfo, $updateStruct );
    }

    protected function getValidSearchValueOne()
    {
        return new ImageValue(
            array(
                'fileName' => 'cafe-terrace-at-night.png',
                'inputUri' => ( $path = __DIR__ . '/_fixtures/image.png' ),
                'alternativeText' => 'café terrace at night, also known as the cafe terrace on the place du forum',
                'fileSize' => filesize( $path ),
            )
        );
    }

    protected function getValidSearchValueTwo()
    {
        return new ImageValue(
            array(
                'fileName' => 'thatched-cottages-at-cordeville.jpg',
                'inputUri' => ( $path = __DIR__ . '/_fixtures/image.jpg' ),
                'alternativeText' => 'chaumes de cordeville à auvers-sur-oise',
                'fileSize' => filesize( $path ),
            )
        );
    }

    protected function getSearchTargetValueOne()
    {
        $value = $this->getValidSearchValueOne();
        return $value->fileName;
    }

    protected function getSearchTargetValueTwo()
    {
        $value = $this->getValidSearchValueTwo();
        return $value->fileName;
    }

    /**
     * Redefined here in order to execute before tests with modified fields below,
     * which depend on it for the returned value.
     */
    public function testCreateTestContent()
    {
        return parent::testCreateTestContent();
    }

    public function criteriaProviderModifiedFieldAlternativeText()
    {
        $valueOne = $this->getValidSearchValueOne();
        $valueTwo = $this->getValidSearchValueTwo();

        return $this->provideCriteria( $valueOne->alternativeText, $valueTwo->alternativeText );
    }

    /**
     * Tests Content Search filtering with Field criterion on the alternative text modified field
     *
     * @dataProvider criteriaProviderModifiedFieldAlternativeText
     * @depends testCreateTestContent
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     * @param boolean $includesOne
     * @param boolean $includesTwo
     * @param array $context
     */
    public function testFilterContentModifiedFieldAlternativeText(
        Criterion $criterion,
        $includesOne,
        $includesTwo,
        array $context
    )
    {
        $this->assertFilterContentModifiedField(
            $criterion,
            $includesOne,
            $includesTwo,
            $context,
            true,
            "alternative_text"
        );
    }

    /**
     * Tests Content Search querying with Field criterion on the alternative text modified field
     *
     * @dataProvider criteriaProviderModifiedFieldAlternativeText
     * @depends testCreateTestContent
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     * @param boolean $includesOne
     * @param boolean $includesTwo
     * @param array $context
     */
    public function testQueryContentModifiedFieldAlternativeText(
        Criterion $criterion,
        $includesOne,
        $includesTwo,
        array $context
    )
    {
        $this->assertFilterContentModifiedField(
            $criterion,
            $includesOne,
            $includesTwo,
            $context,
            false,
            "alternative_text"
        );
    }

    public function criteriaProviderModifiedFieldFileSize()
    {
        $valueOne = $this->getValidSearchValueOne();
        $valueTwo = $this->getValidSearchValueTwo();

        return $this->provideCriteria( $valueOne->fileSize, $valueTwo->fileSize );
    }

    /**
     * Tests Content Search filtering with Field criterion on the file size modified field
     *
     * @dataProvider criteriaProviderModifiedFieldFileSize
     * @depends testCreateTestContent
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     * @param boolean $includesOne
     * @param boolean $includesTwo
     * @param array $context
     */
    public function testFilterContentModifiedFieldFileSize(
        Criterion $criterion,
        $includesOne,
        $includesTwo,
        array $context
    )
    {
        $this->assertFilterContentModifiedField(
            $criterion,
            $includesOne,
            $includesTwo,
            $context,
            true,
            "file_size"
        );
    }

    /**
     * Tests Content Search querying with Field criterion on the file size modified field
     *
     * @dataProvider criteriaProviderModifiedFieldFileSize
     * @depends testCreateTestContent
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     * @param boolean $includesOne
     * @param boolean $includesTwo
     * @param array $context
     */
    public function testQueryContentModifiedFieldFileSize(
        Criterion $criterion,
        $includesOne,
        $includesTwo,
        array $context
    )
    {
        $this->assertFilterContentModifiedField(
            $criterion,
            $includesOne,
            $includesTwo,
            $context,
            false,
            "file_size"
        );
    }

    /**
     * Tests Content Search sort with Field sort clause on the alternative text modified field
     *
     * @dataProvider sortClauseProvider
     * @depends testCreateTestContent
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause
     * @param boolean $ascending
     * @param array $context
     */
    public function testSortContentModifiedFieldAlternativeText(
        SortClause $sortClause,
        $ascending,
        array $context
    )
    {
        $this->assertSortContentModifiedField(
            $sortClause,
            $ascending,
            $context,
            "alternative_text"
        );
    }

    /**
     * Tests Content Search sort with Field sort clause on the file size modified field
     *
     * @dataProvider sortClauseProvider
     * @depends testCreateTestContent
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause
     * @param boolean $ascending
     * @param array $context
     */
    public function testSortContentModifiedFieldFieldSize(
        SortClause $sortClause,
        $ascending,
        array $context
    )
    {
        $this->assertSortContentModifiedField(
            $sortClause,
            $ascending,
            $context,
            "file_size"
        );
    }
}
